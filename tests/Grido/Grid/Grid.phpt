<?php

/**
 * Test: Grid.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

namespace Grido\Tests;

use Tester\Assert,
    Grido\Grid,
    Grido\Components\Columns\Column,
    Grido\Components\Filters\Filter;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Helper.inc.php';

class GridTest extends \Tester\TestCase
{
    function testOnRegisteredEvent()
    {
        $called = FALSE;
        Helper::grid(function(Grid $grid) use (&$called) {
            $grid->onRegistered[] = function(Grid $grid) use(&$called) {
                $called = TRUE;
                Assert::true($grid->hasColumns());
            };

            $grid->addColumnText('column', 'Column');

        })->run();

        Assert::true($called);
    }

    function testOnFetchDataEvent()
    {
        $grid = new Grid;
        $testData = array('id' => 1, 'column' => 'value');
        $grid->setModel($testData);
        $grid->onFetchData[] = function(Grid $grid) use ($testData) {
            Assert::same($testData, $grid->data);
        };
    }

    /**********************************************************************************************/

    function testSetModel()
    {
        $grid = new Grid;
        $grid->setModel(mock('Grido\DataSources\IDataSource'));
        Assert::type('Grido\DataSources\IDataSource', $grid->model);

        $grid->setModel(mock('Grido\DataSources\IDataSource'), TRUE);
        Assert::type('Grido\DataSources\Model', $grid->model);

        $grid->setModel(new \DibiFluent(mock('\DibiConnection')));
        Assert::type('Grido\DataSources\Model', $grid->model);

        $grid->setModel(mock('\Nette\Database\Table\Selection'));
        Assert::type('Grido\DataSources\Model', $grid->model);

        $grid->setModel(mock('\Doctrine\ORM\QueryBuilder'));
        Assert::type('Grido\DataSources\Model', $grid->model);

        $grid->setModel(array());
        Assert::type('Grido\DataSources\Model', $grid->model);

        $grid->setModel(new \DibiFluent(mock('\DibiConnection')));
        Assert::type('Grido\DataSources\Model', $grid->model);

        Assert::exception(function() use ($grid) {
            $grid->setModel(mock('BAD'));
        }, 'InvalidArgumentException', 'Model must implement \Grido\DataSources\IDataSource.');

        Assert::exception(function() use ($grid) {
            $grid->setModel(mock('BAD'), TRUE);
        }, 'InvalidArgumentException', 'Model must implement \Grido\DataSources\IDataSource.');
    }

    function testSetPropertyAccessor()
    {
        $grid = new Grid;

        $expected = 'Grido\PropertyAccessors\IPropertyAccessor';
        $grid->setPropertyAccessor(mock($expected));
        Assert::type($expected, $grid->propertyAccessor);

        Assert::error(function() use ($grid) {
            $grid->setPropertyAccessor('');
        }, E_RECOVERABLE_ERROR);
    }

    function testSetDefaultPerPage()
    {
        $grid = new Grid;
        $data = array(array(), array(), array(), array());
        $grid->setModel($data);
        $grid->addColumnText('column', 'Column');

        //test defaults
        Assert::same(array(10, 20, 30, 50, 100), $grid->perPageList);
        Assert::same(20, $grid->defaultPerPage);

        $defaultPerPage = 2;
        $perPageList = $grid->perPageList;
        $perPageList[] = $defaultPerPage;
        sort($perPageList);

        $grid->setDefaultPerPage((string) $defaultPerPage);
        Assert::same($defaultPerPage, $grid->defaultPerPage);
        Assert::same($perPageList, $grid->perPageList);
        Assert::same($defaultPerPage, count($grid->data));

        $grid = new Grid;
        $grid->setModel($data);
        $grid->addColumnText('column', 'Column');
        $grid->setDefaultPerPage(2);
        $grid->perPage = 10;
        Assert::same(count($data), count($grid->data));

        Assert::error(function() {
            $grid = new Grid;
            $grid->setModel(array());
            $grid->addColumnText('column', 'Column');
            $grid->perPage = 1;
            $grid->data;
        }, E_USER_NOTICE, "The number '1' of items per page is out of range.");
    }

    function testSetDefaultFilter()
    {
        $grid = new Grid;

        Assert::error(function() use ($grid) {
            $grid->setDefaultFilter('');
        }, E_RECOVERABLE_ERROR);

        $data = array(
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A2', 'B' => 'B2'),
            array('A' => 'A3', 'B' => 'B3'),
        );
        $grid->setModel($data);
        $grid->addColumnText('column', 'Column');
        $grid->addFilterText('A', 'Column');
        $defaultFilter = array('A' => 'A2');
        $grid->setDefaultFilter($defaultFilter);

        Assert::same($defaultFilter, $grid->defaultFilter);
        Assert::same(array(array('A' => 'A2', 'B' => 'B2')), $grid->data);
        Assert::same('A2', $grid['form'][Filter::ID]['A']->value);

        Assert::error(function() use ($defaultFilter) {
            $grid = new Grid;
            $grid->setModel(array());
            $grid->addColumnText('column', 'Column');
            $grid->setDefaultFilter($defaultFilter);
            $grid->getData();
        }, E_USER_NOTICE, "Filter with name 'A' does not exist.");
    }

    function testSetDefaultSort()
    {
        $grid = new Grid;
        $grid->setDefaultSort(array('a' => 'ASC', 'b' => 'desc', 'c' => 'Asc', 'd' => Column::ORDER_DESC));
        Assert::same(array('a' => Column::ORDER_ASC, 'b' => Column::ORDER_DESC, 'c' => Column::ORDER_ASC, 'd' => Column::ORDER_DESC), $grid->defaultSort);

        Assert::exception(function() use ($grid) {
            $grid->setDefaultSort(array('a' => 'up'));
        }, 'InvalidArgumentException', "Dir 'up' for column 'a' is not allowed.");

        $grid = new Grid;
        $data = array(
            array('A' => 'A1', 'B' => 'B3'),
            array('A' => 'A2', 'B' => 'B2'),
            array('A' => 'A3', 'B' => 'B1'),
        );
        $grid->setModel($data);
        $grid->addColumnText('B', 'B');
        $grid->setDefaultSort(array('B' => 'asc'));
        $grid2 = clone $grid;

        $expected = array(
            array('A' => 'A3', 'B' => 'B1'),
            array('A' => 'A2', 'B' => 'B2'),
            array('A' => 'A1', 'B' => 'B3'),
        );
        Assert::same($expected, $grid->data);

        $grid2->sort['B'] = Column::ORDER_DESC;
        Assert::same($data, $grid2->data);

        $grid = new Grid;
        $grid->setModel($data);
        $grid->setDefaultSort(array('A' => 'desc'));

        $A = array();
        foreach ($data as $key => $row) {
            $A[$key] = $row['A'];
        }
        array_multisort($A, SORT_DESC, $data);
        Assert::same($data, $grid->data);

        Assert::exception(function() use ($grid) {
            $grid->setDefaultSort(array('A' => 'up'));
        }, 'InvalidArgumentException', "Dir 'up' for column 'A' is not allowed.");
    }

    function testSetPerPageList()
    {
        $grid = new Grid;

        //test defaults
        Assert::same(array(10, 20, 30, 50, 100), $grid->perPageList);

        $grid->addFilterText('test', 'Test');

        $a = array(10, 20);
        $grid->setPerPageList($a);
        Assert::same($a, $grid->perPageList);
        Assert::same(array_combine($a, $a), $grid['form']['count']->items);
    }

    function testSetTranslator()
    {
        $grid = new Grid;

        $translator = '\Nette\Localization\ITranslator';
        $grid->setTranslator(mock($translator));
        Assert::type($translator, $grid->translator);

        Assert::error(function() use ($grid) {
            $grid->setTranslator('');
        }, E_RECOVERABLE_ERROR);
    }

    function testSetFilterRenderType()
    {
        $grid = new Grid;

        $type = Filter::RENDER_INNER;
        $grid->setFilterRenderType($type);
        Assert::same($type, $grid->filterRenderType);

        $type = Filter::RENDER_OUTER;
        $grid->setFilterRenderType($type);
        Assert::same($type, $grid->filterRenderType);

        $grid->setFilterRenderType('OUTER');
        Assert::same($type, $grid->filterRenderType);

        Assert::exception(function() use ($grid) {
            $grid->setFilterRenderType('INNERR');
        }, 'InvalidArgumentException', 'Type must be Filter::RENDER_INNER or Filter::RENDER_OUTER.');
    }

    function testSetPaginator()
    {
        $grid = new Grid;

        $paginator = '\Grido\Components\Paginator';
        $grid->setPaginator(mock($paginator));
        Assert::type($paginator, $grid->paginator);

        Assert::error(function() use ($grid) {
            $grid->setPaginator('');
        }, E_RECOVERABLE_ERROR);
    }

    function testSetPrimaryKey()
    {
        $grid = new Grid;
        $key = 'id';
        $grid->setPrimaryKey($key);
        Assert::same($key, $grid->primaryKey);
    }

    function testSetTemplateFile()
    {
        $grid = new Grid;
        $template = __FILE__;
        $grid->setTemplateFile($template);
        Assert::same($template, $grid->template->getFile());
    }

    function testSetRememberState()
    {
        Helper::grid(function($grid) {
            $grid->setRememberState(1);
            Assert::true($grid->rememberState);
        })->run();
    }

    function testSetRowCallback()
    {
        $grid = new Grid;

        $rowCallback = array();
        $grid->setRowCallback($rowCallback);
        Assert::same($rowCallback, $grid->rowCallback);

        $testRow = array('id' => 1, 'key' => 'value');
        $rowCallback = function($row, \Nette\Utils\Html $tr) use ($testRow) {
            Assert::same($testRow, $row);
        };
        $grid->setRowCallback($rowCallback);
        Assert::same($rowCallback, $grid->rowCallback);
        $grid->getRowPrototype($testRow);

        $rowCallback = mock('\Nette\Utils\Callback');
        $grid->setRowCallback($rowCallback);
        Assert::same($rowCallback, $grid->rowCallback);
    }

    function testSetClientSideOptions()
    {
        $grid = new Grid;
        $options = array('key' => 'value');
        $grid->setClientSideOptions($options);
        Assert::same($grid->tablePrototype->data['grido-options'], json_encode($options));
    }

    /**********************************************************************************************/

    function testGetDefaultPerPage()
    {
        $grid = new Grid;

        //test defaults
        Assert::same(array(10, 20, 30, 50, 100), $grid->perPageList);
        Assert::same(20, $grid->defaultPerPage);

        $grid->setPerPageList(array(2, 4, 6));
        Assert::same(2, $grid->defaultPerPage);
    }

    function testGetActualFilter()
    {
        $grid = new Grid;
        $filter = array('a' => 'A', 'b' => 'B');
        $defaultFilter = array('c' => 'C', 'd' => 'D');

        Assert::same(array(), $grid->getActualFilter());

        $grid->defaultFilter = $defaultFilter;
        Assert::same($defaultFilter, $grid->getActualFilter());
        Assert::same($defaultFilter, $grid->getActualFilter('undefined'));
        Assert::same('D', $grid->getActualFilter('d'));

        $grid->filter = $filter;
        Assert::same($filter, $grid->getActualFilter());
        Assert::same($filter, $grid->getActualFilter('undefined'));
        Assert::same('B', $grid->getActualFilter('b'));
    }

    function testGetFilterRenderType()
    {
        $grid = new Grid;
        Assert::same(Filter::RENDER_OUTER, $grid->filterRenderType);

        $grid = new Grid;
        $grid->addFilterText('xxx', 'Filter');
        Assert::same(Filter::RENDER_OUTER, $grid->filterRenderType);

        $grid = new Grid;
        $grid->addActionHref('action', 'Action');
        Assert::same(Filter::RENDER_OUTER, $grid->filterRenderType);

        $grid = new Grid;
        $grid->addFilterText('xxx', 'Filter');
        $grid->addActionHref('action', 'Action');
        Assert::same(Filter::RENDER_OUTER, $grid->filterRenderType);

        $grid = new Grid;
        $grid->addFilterText('xxx', 'Filter');
        $grid->addActionHref('action', 'Action');
        $grid->addColumnText('yyy', 'Column');
        Assert::same(Filter::RENDER_OUTER, $grid->filterRenderType);

        $grid = new Grid;
        $grid->addFilterText('xxx', 'Filter');
        $grid->addColumnText('xxx', 'Column');
        Assert::same(Filter::RENDER_OUTER, $grid->filterRenderType);

        $grid = new Grid;
        $grid->addFilterText('xxx', 'Filter');
        $grid->addActionHref('action', 'Action');
        $grid->addColumnText('xxx', 'Column');
        Assert::same(Filter::RENDER_INNER, $grid->filterRenderType);
    }

    function testGetTablePrototype()
    {
        $grid = new Grid;
        $table = $grid->tablePrototype;

        $table->class[] = 'test';
        Assert::same('<table class="table table-striped table-hover test"></table>', (string) $table);
    }

    /**********************************************************************************************/

    function testHandlePage()
    {
        Helper::grid(function(Grid $grid) {
            $grid->setDefaultPerPage(2);
            $grid->addColumnText('column', 'Column');
            $grid->setModel(array(
                array('A' => 'A1', 'B' => 'B3'),
                array('A' => 'A2', 'B' => 'B2'),
                array('A' => 'A3', 'B' => 'B1'),
            ));
            $grid->getData();
        });

        Helper::request(array('grid-page' => 2, 'do' => 'grid-page'));
        Assert::same(array(array('A' => 'A3', 'B' => 'B1')), Helper::$grid->data);
    }

    function testHandleSort()
    {
        Helper::grid(function(Grid $grid) {
            $grid->addColumnText('column', 'Column')->setSortable();
        });

        $sorting = array('column' => Column::ORDER_ASC);
        Helper::request(array('grid-page' => 2, 'grid-sort' => $sorting, 'do' => 'grid-sort'));
        Assert::same($sorting, Helper::$grid->sort);
        Assert::same(1, Helper::$grid->page);

        Helper::grid(function(Grid $grid) {
            $grid->setDefaultPerPage(2);
            $grid->setModel(array(
                array('A' => 'A1', 'B' => 'B3'),
                array('A' => 'A2', 'B' => 'B2'),
                array('A' => 'A3', 'B' => 'B1'),
            ));
            $grid->addColumnText('A', 'A');
            $grid->addColumnText('B', 'B')->setSortable();
        });

        Helper::request(array('grid-page' => 2, 'grid-sort' => array('B' => Column::ORDER_ASC), 'do' => 'grid-sort'));

        Assert::same(1, Helper::$grid->page); //test reset page after sorting
        Assert::same(array(
            array('A' => 'A3', 'B' => 'B1'),
            array('A' => 'A2', 'B' => 'B2'),
        ), Helper::$grid->data);

        //applySorting()
        Helper::request(array('grid-sort' => array('B' => 'UP'), 'do' => 'grid-sort'));
        Assert::error(function(){
            Helper::$grid->data;
        }, 'E_USER_NOTICE', "Dir 'UP' is not allowed.");

        Helper::request(array('grid-sort' => array('C' => Column::ORDER_ASC), 'do' => 'grid-sort'));
        Assert::error(function(){
            Helper::$grid->data;
        }, 'E_USER_NOTICE', "Column with name 'C' does not exist.");

        Helper::request(array('grid-sort' => array('A' => Column::ORDER_ASC), 'do' => 'grid-sort'));
        Assert::error(function(){
            Helper::$grid->data;
        }, 'E_USER_NOTICE', "Column with name 'A' is not sortable.");
    }

    function testHandleFilter()
    {
        $defaultFilter = array('filterB' => 'test');
        Helper::grid(function(Grid $grid) use ($defaultFilter) {
            $grid->setModel(array());
            $grid->setDefaultFilter($defaultFilter);
            $grid->addFilterText('filter', 'Filter');
            $grid->addFilterText('filterB', 'FilterB');
        });

        $params = array('grid-page' => 2, 'do' => 'grid-form-submit', Grid::BUTTONS => array('search' => 'Search'));

        $filter = array('filter' => 'test') + $defaultFilter;
        Helper::request($params + array(Filter::ID => $filter));
        Assert::same($filter, Helper::$grid->filter);
        Assert::same(1, Helper::$grid->page);

        $filter = array('filter' => '') + $defaultFilter;
        Helper::request($params + array(Filter::ID => $filter));
        Assert::same($defaultFilter, Helper::$grid->filter);
        Assert::same(1, Helper::$grid->page);

        $filter = array('filter' => '', 'filterB' => 'test');
        Helper::request($params + array(Filter::ID => $filter));
        unset($filter['filter']);
        Assert::same($filter, Helper::$grid->filter);
        Assert::same(1, Helper::$grid->page);

        $filter = array('filter' => 'test', 'filterB' => '');
        Helper::request($params + array(Filter::ID => $filter));
        Assert::same($filter, Helper::$grid->filter);
        Assert::same(1, Helper::$grid->page);

        $data = array(
            array('A' => 'A1', 'B' => 'B3'),
            array('A' => 'A2', 'B' => 'B2'),
            array('A' => 'A22','B' => 'B22'),
            array('A' => 'A3', 'B' => 'B1'),
        );

        Helper::grid(function(Grid $grid) use ($data) {
            $grid->setDefaultPerPage(1);
            $grid->setModel($data);
            $grid->addColumnText('column', 'Column');
            $grid->addFilterText('B', 'B');
        });

        Helper::request(array(
            'do' => 'grid-form-submit',
            'grid-page' => 2,
            Filter::ID => array('B' => 'B2'),
            Grid::BUTTONS => array('search' => 'Search'),
        ));

        Assert::same(1, Helper::$grid->page); //test reset page after filtering

        $expected = array(
            1 => array('A' => 'A2', 'B' => 'B2'),
            2 => array('A' => 'A22', 'B' => 'B22'),
        );
        Assert::same($expected, Helper::$grid->getData(FALSE));

        Helper::grid(function(Grid $grid) use ($data) {
            $grid->setModel($data);
            $grid->addColumnText('column', 'Column');
            $grid->addFilterText('A', 'A');
            $grid->addFilterText('B', 'B')
                ->setDefaultValue('B2');
        });

        Helper::request(array(
            'do' => 'grid-form-submit',
            'grid-page' => 1,
            Filter::ID => array('A' => '', 'B' => ''),
            Grid::BUTTONS => array('search' => 'Search'),
        ));

        Assert::same($data, Helper::$grid->getData(FALSE));
        Assert::same(array('B' => ''), Helper::$grid->filter);

        Assert::error(function() use ($data) {
            $grid = new Grid;
            $grid->addColumnText('column', 'Column');
            $grid->setModel($data);
            $grid->addFilterText('A', 'A');
            $grid->filter['B'] = 'B2';
            $grid->data;
        }, E_USER_NOTICE, "Filter with name 'B' does not exist.");

        //test session filter
        Helper::grid(function(Grid $grid) {
            $grid->setModel(array(array('A' => 'test')));
            $grid->setRememberState();
            $grid->addColumnText('A', 'A');
            $grid->addFilterText('A', 'A');
        });

        $params = array(
            'do' => 'grid-form-submit',
            Grid::BUTTONS => array('search' => 'Search'),
        );
        $filter = array('A' => 'test');
        Helper::request($params + array(Filter::ID => $filter));
        Helper::$grid->render(); //save2session
        Assert::same($filter, Helper::$grid->getRememberSession()->params['filter']);

        $filter = array('A' => '');
        Helper::request($params + array(Filter::ID => $filter));
        Helper::$grid->render(); //save2session
        Assert::same($filter, Helper::$grid->getRememberSession()->params['filter']);
    }

    function testHandleReset()
    {
        Helper::grid(function(Grid $grid) {
            $grid->setPerPageList(array(1, 2));
            $grid->setDefaultPerPage(1);
            $grid->setModel(array(
                array('A' => 'A1', 'B' => 'B4'),
                array('A' => 'A2', 'B' => 'B3'),
                array('A' => 'A3', 'B' => 'B2'),
                array('A' => 'A4', 'B' => 'B1'),
            ));

            $grid->addColumnText('A', 'A')->setSortable();
            $grid->addFilterText('B', 'B');

            $params = array(
                'sort' => array('A' => Column::ORDER_ASC),
                'filter' => array('B' => 'B2'),
                'perPage' => 2,
                'page' => 2

            );
            $grid->loadState($params);
        });

        Helper::request(array('do' => 'grid-form-submit', Grid::BUTTONS => array('reset' => 'Reset')));
        Assert::same(array(), Helper::$grid->sort);
        Assert::same(array(), Helper::$grid->filter);
        Assert::null(Helper::$grid->perPage);
        Assert::same(1, Helper::$grid->page);
    }

    function testHandlePerPage()
    {
        Helper::grid(function(Grid $grid) {
            $grid->setModel(array());
            $grid->addColumnText('column', 'Column');
        });

        $perPage = 10;
        Helper::request(array('count' => $perPage, 'grid-page' => 2, 'do' => 'grid-form-submit', Grid::BUTTONS => array('perPage' => 'Items per page')));
        Assert::same($perPage, Helper::$grid->perPage);
        Assert::same(1, Helper::$grid->page);
    }
}

run(__FILE__);
