<?php

/**
 * Test: Grid - tests for a class's basic behaviour.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Helper.inc';

use Tester\Assert,
    Grido\Grid,
    Grido\Components\Columns\Column,
    Grido\Components\Filters\Filter;

class GridTest extends Tester\TestCase
{
    function testSetModel()
    {
        $grid = new Grid;
        $grid->setModel(mock('Grido\DataSources\IDataSource'));
        Assert::type('Grido\DataSources\IDataSource', $grid->model);

        $grid->setModel(mock('Grido\DataSources\IDataSource'), TRUE);
        Assert::type('Grido\DataSources\Model', $grid->model);

        $grid->setModel(mock('\DibiFluent'));
        Assert::type('Grido\DataSources\Model', $grid->model);

        $grid->setModel(mock('\Nette\Database\Table\Selection'));
        Assert::type('Grido\DataSources\Model', $grid->model);

        $grid->setModel(mock('\Doctrine\ORM\QueryBuilder'));
        Assert::type('Grido\DataSources\Model', $grid->model);

        $grid->setModel(array());
        Assert::type('Grido\DataSources\Model', $grid->model);

        $grid->setModel(mock('\DibiFluent'), TRUE);
        Assert::type('Grido\DataSources\Model', $grid->model);

        Assert::exception(function() use ($grid) {
            $grid->setModel(mock('BAD'));
        }, 'InvalidArgumentException', 'Model must be implemented \Grido\DataSources\IDataSource.');

        Assert::exception(function() use ($grid) {
            $grid->setModel(mock('BAD'), TRUE);
        }, 'InvalidArgumentException', 'Model must be implemented \Grido\DataSources\IDataSource.');
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
        $grid = new Grid;
        $grid->setRememberState(1);
        Assert::true($grid->rememberState);
    }

    function testSetRowCallback()
    {
        $grid = new Grid;

        $rowCallback = array();
        $grid->setRowCallback($rowCallback);
        Assert::same($rowCallback, $grid->rowCallback);

        $rowCallback = function() {};
        $grid->setRowCallback($rowCallback);
        Assert::same($rowCallback, $grid->rowCallback);

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

        $sorting = array('column' => Column::ASC);
        Helper::request(array('grid-page' => 2, 'grid-sort' => $sorting, 'do' => 'grid-sort'));
        Assert::same($sorting, Helper::$grid->sort);
        Assert::same(1, Helper::$grid->page);
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
                'sort' => array('A' => Column::ASC),
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
