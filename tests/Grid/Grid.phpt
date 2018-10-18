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
    Grido\Customization,
    Grido\Components\Columns\Column,
    Grido\Components\Filters\Filter;

require_once __DIR__ . '/../bootstrap.php';

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
        $testData = ['id' => 1, 'column' => 'value'];
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

        $grid->setModel(new \DibiFluent(mock('\\Dibi\Connection')));
        Assert::type('Grido\DataSources\Model', $grid->model);

        $grid->setModel(mock('\Nette\Database\Table\Selection'));
        Assert::type('Grido\DataSources\Model', $grid->model);

        $grid->setModel(mock('\Doctrine\ORM\QueryBuilder'));
        Assert::type('Grido\DataSources\Model', $grid->model);

        $grid->setModel([]);
        Assert::type('Grido\DataSources\Model', $grid->model);

        $grid->setModel(new \DibiFluent(mock('\\Dibi\Connection')));
        Assert::type('Grido\DataSources\Model', $grid->model);

        Assert::exception(function() use ($grid) {
            $grid->setModel(mock('BAD'));
        }, 'Grido\Exception', 'Model must implement \Grido\DataSources\IDataSource.');

        Assert::exception(function() use ($grid) {
            $grid->setModel(mock('BAD'), TRUE);
        }, 'Grido\Exception', 'Model must implement \Grido\DataSources\IDataSource.');

        Assert::exception(function() {
            $grid = new Grid;
            $grid->getData();
        }, 'Exception', 'Model cannot be empty, please use method $grid->setModel().');

    }

    function testSetDefaultPerPage()
    {
        $grid = new Grid;
        $data = [[], [], [], []];
        $grid->setModel($data);
        $grid->addColumnText('column', 'Column');

        //test defaults
        Assert::same([10, 20, 30, 50, 100], $grid->perPageList);
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

        $definition = function($strictMode = TRUE) {
            $grid = new Grid;
            $grid->setStrictMode($strictMode);
            $grid->setModel([]);
            $grid->addColumnText('column', 'Column');
            $grid->perPage = 1;
            $grid->data;
        };

        Assert::error(function() use ($definition) {
            $definition();
        }, E_USER_NOTICE, "The number '1' of items per page is out of range.");

        //STRICT MODE TEST
        $definition(FALSE);
    }

    function testSetDefaultFilter()
    {
        $grid = new Grid;

        $data = [
            ['A' => 'A1', 'B' => 'B1'],
            ['A' => 'A2', 'B' => 'B2'],
            ['A' => 'A3', 'B' => 'B3'],
        ];
        $grid->setModel($data);
        $grid->addColumnText('column', 'Column');
        $grid->addFilterText('A', 'Column');
        $defaultFilter = ['A' => 'A2'];
        $grid->setDefaultFilter($defaultFilter);

        Assert::same($defaultFilter, $grid->defaultFilter);
        Assert::same([['A' => 'A2', 'B' => 'B2']], $grid->data);
        Assert::same('A2', $grid['form'][Filter::ID]['A']->value);

        Assert::error(function() use ($defaultFilter) {
            $grid = new Grid;
            $grid->setModel([]);
            $grid->addColumnText('column', 'Column');
            $grid->setDefaultFilter($defaultFilter);
            $grid->getData();
        }, E_USER_NOTICE, "Filter with name 'A' does not exist.");
    }

    function testSetDefaultSort()
    {
        $grid = new Grid;
        $grid->setDefaultSort(['a' => 'ASC', 'b' => 'desc', 'c' => 'Asc', 'd' => Column::ORDER_DESC]);
        Assert::same(['a' => Column::ORDER_ASC, 'b' => Column::ORDER_DESC, 'c' => Column::ORDER_ASC, 'd' => Column::ORDER_DESC], $grid->defaultSort);

        Assert::exception(function() use ($grid) {
            $grid->setDefaultSort(['a' => 'up']);
        }, 'Grido\Exception', "Dir 'up' for column 'a' is not allowed.");

        $grid = new Grid;
        $data = [
            ['A' => 'A1', 'B' => 'B3'],
            ['A' => 'A2', 'B' => 'B2'],
            ['A' => 'A3', 'B' => 'B1'],
        ];
        $grid->setModel($data);
        $grid->addColumnText('B', 'B');
        $grid->setDefaultSort(['B' => 'asc']);
        $grid2 = clone $grid;

        $expected = [
            ['A' => 'A3', 'B' => 'B1'],
            ['A' => 'A2', 'B' => 'B2'],
            ['A' => 'A1', 'B' => 'B3'],
        ];
        Assert::same($expected, $grid->data);

        $grid2->sort['B'] = Column::ORDER_DESC;
        Assert::same($data, $grid2->data);

        $grid = new Grid;
        $grid->setModel($data);
        $grid->setDefaultSort(['A' => 'desc']);

        $A = [];
        foreach ($data as $key => $row) {
            $A[$key] = $row['A'];
        }
        array_multisort($A, SORT_DESC, $data);
        Assert::same($data, $grid->data);

        Assert::exception(function() use ($grid) {
            $grid->setDefaultSort(['A' => 'up']);
        }, 'Grido\Exception', "Dir 'up' for column 'A' is not allowed.");
    }

    function testSetPerPageList()
    {
        $grid = new Grid;

        //test defaults
        Assert::same([10, 20, 30, 50, 100], $grid->perPageList);

        $grid->addFilterText('test', 'Test');

        $a = [10, 20];
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

        Helper::assertTypeError(function() use ($grid) {
            $grid->setTranslator('');
        });
    }

    function testGetTranslator()
    {
        $grid = new Grid;
        $grid->translator->setLang('sk');
        Assert::type('\Grido\Translations\FileTranslator', $grid->translator);

        Assert::exception(function() use ($grid) {
            $grid->translator->setLang('aa');
        }, '\Grido\Exception');
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
        }, 'Grido\Exception', 'Type must be Filter::RENDER_INNER or Filter::RENDER_OUTER.');
    }

    function testSetPaginator()
    {
        $grid = new Grid;

        $paginator = '\Grido\Components\Paginator';
        $grid->setPaginator(mock($paginator));
        Assert::type($paginator, $grid->paginator);

        Helper::assertTypeError(function() use ($grid) {
            $grid->setPaginator('');
        });
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
        Helper::grid(function(Grid $grid) {
            $template = __FILE__;
            $grid->setTemplateFile($template);
            Assert::same($template, $grid->getTemplate()->getFile());
        });
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

        $rowCallback = [];
        $grid->setRowCallback($rowCallback);
        Assert::same($rowCallback, $grid->rowCallback);

        $testRow = ['id' => 1, 'key' => 'value'];
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
        Helper::grid(function($grid) {
            $grid->setModel([['test' => 'test']]);
            $grid->addColumnText('test', 'Test');
            $options = ['key' => 'value'];
            $grid->setClientSideOptions($options);
            $grid->render();
            Assert::same($grid->tablePrototype->getAttribute('data-' . Grid::CLIENT_SIDE_OPTIONS), json_encode($options));
        })->run();
    }

    /**********************************************************************************************/

    function testGetDefaultPerPage()
    {
        $grid = new Grid;

        //test defaults
        Assert::same([10, 20, 30, 50, 100], $grid->perPageList);
        Assert::same(20, $grid->defaultPerPage);

        $grid->setPerPageList([2, 4, 6]);
        Assert::same(2, $grid->defaultPerPage);
    }

    function testGetActualFilter()
    {
        $grid = new Grid;
        $filter = ['a' => 'A', 'b' => 'B'];
        $defaultFilter = ['c' => 'C', 'd' => 'D'];

        Assert::same([], $grid->getActualFilter());

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
        Helper::grid(function(Grid $grid) {
            $grid->model = [['test' => 'test']];
            $grid->addColumnText('test', 'Test');
            $grid->tablePrototype->class[] = 'test';
        })->run();

        ob_start();
            Helper::$grid->render();
        $output = ob_get_clean();

        Assert::contains('<table id="grid" class="test">', $output);
    }

    function testSetCustomization()
    {
        $grid = new Grid;
        $customization = new \Grido\Customization($grid);
        $grid->setCustomization($customization);

        Assert::same($grid->customization, $customization);

        $string = 'first second';
        $customization->buttonClass = $string;
        Assert::same($string, $customization->buttonClass);

        $array = ['first', 'second'];
        $customization->buttonClass = $array;
        Assert::same(implode(' ', $array), $customization->buttonClass);

        $customization->iconClass = $string;
        Assert::same($string, $customization->iconClass);

        $string = 'fa';
        $customization->iconClass = $string;
        Assert::same('fa fa-delete', $customization->getIconClass('delete'));

        $array = ['glyphicon', 'fa'];
        $customization->iconClass = $array;
        Assert::same('glyphicon glyphicon-delete fa fa-delete', $customization->getIconClass('delete'));

        $list = $customization->getTemplateFiles();
        foreach (new \DirectoryIterator(__DIR__ . '/../../src/templates') as $file) {
            if ($file->isFile()) {
                $list[$file->getBasename('.latte')] = realpath($file->getPathname());
            }
        }

        Assert::same($list, $customization->getTemplateFiles());

        Helper::grid(function(Grid $grid) {
            $availableTemplates = $grid->customization->getTemplateFiles();

            $grid->customization->useTemplateBootstrap();
            $grid->onRender($grid);
            Assert::same($availableTemplates[Customization::TEMPLATE_BOOTSTRAP], $grid->getTemplate()->getFile());
        })->run();
    }

    /**********************************************************************************************/

    function testHandlePage()
    {
        $definition = function(Grid $grid, $strictMode = TRUE) {
            $grid->setStrictMode($strictMode);
            $grid->setDefaultPerPage(2);
            $grid->addColumnText('column', 'Column');
            $grid->setModel([
                ['A' => 'A1', 'B' => 'B3'],
                ['A' => 'A2', 'B' => 'B2'],
                ['A' => 'A3', 'B' => 'B1'],
            ]);
            $grid->getData();
        };

        Helper::grid(function(Grid $grid) use ($definition) {
            $definition($grid);
        });

        Helper::request(['grid-page' => 2, 'do' => 'grid-page']);
        Assert::same([['A' => 'A3', 'B' => 'B1']], Helper::$grid->data);

        $requestPageIsOutOfRange = ['grid-page' => 10, 'do' => 'grid-page'];
        Assert::error(function() use ($requestPageIsOutOfRange) {
            Helper::request($requestPageIsOutOfRange);
        }, 'E_USER_NOTICE', "Page is out of range.");

        // STRICT MODE TESTS
        Helper::grid(function(Grid $grid) use ($definition) {
            $definition($grid, FALSE);
        })->run($requestPageIsOutOfRange);
    }

    function testHandleSort()
    {
        Helper::grid(function(Grid $grid) {
            $grid->addColumnText('column', 'Column')->setSortable();
        });

        $sorting = ['column' => Column::ORDER_ASC];
        Helper::request(['grid-page' => 2, 'grid-sort' => $sorting, 'do' => 'grid-sort']);
        Assert::same($sorting, Helper::$grid->sort);
        Assert::same(1, Helper::$grid->page);

        $definition = function(Grid $grid, $strictMode = TRUE) {
            $grid->setStrictMode($strictMode);
            $grid->setDefaultPerPage(2);
            $grid->setModel([
                ['A' => 'A1', 'B' => 'B3'],
                ['A' => 'A2', 'B' => 'B2'],
                ['A' => 'A3', 'B' => 'B1'],
            ]);
            $grid->addColumnText('A', 'A');
            $grid->addColumnText('B', 'B')->setSortable();
        };

        Helper::grid(function(Grid $grid) use ($definition) {
            $definition($grid);
        });

        Helper::request(['grid-page' => 2, 'grid-sort' => ['B' => Column::ORDER_ASC], 'do' => 'grid-sort']);

        Assert::same(1, Helper::$grid->page); //test reset page after sorting
        Assert::same([
            ['A' => 'A3', 'B' => 'B1'],
            ['A' => 'A2', 'B' => 'B2'],
        ], Helper::$grid->data);

        //applySorting()
        $requestDirIsNotAllowed = ['grid-sort' => ['B' => 'UP'], 'do' => 'grid-sort'];
        Helper::request($requestDirIsNotAllowed);
        Assert::error(function(){
            Helper::$grid->data;
        }, 'E_USER_NOTICE', "Dir 'UP' is not allowed.");

        $requestColumnDoesntExist = ['grid-sort' => ['C' => Column::ORDER_ASC], 'do' => 'grid-sort'];
        Helper::request($requestColumnDoesntExist);
        Assert::error(function(){
            Helper::$grid->data;
        }, 'E_USER_NOTICE', "Column with name 'C' does not exist.");

        $requestColumnIsntSortable = ['grid-sort' => ['A' => Column::ORDER_ASC], 'do' => 'grid-sort'];
        Helper::request($requestColumnIsntSortable);
        Assert::error(function(){
            Helper::$grid->data;
        }, 'E_USER_NOTICE', "Column with name 'A' is not sortable.");

        // STRICT MODE TESTS
        Helper::grid(function(Grid $grid) use ($definition) {
            $definition($grid, FALSE);
            $grid->getData();
        })->run($requestDirIsNotAllowed);

        Helper::grid(function(Grid $grid) use ($definition) {
            $definition($grid, FALSE);
            $grid->getData();
        })->run($requestColumnDoesntExist);

        Helper::grid(function(Grid $grid) use ($definition) {
            $definition($grid, FALSE);
            $grid->getData();
        })->run($requestColumnIsntSortable);
    }

    function testHandleFilter()
    {
        $defaultFilter = ['filterB' => 'default'];
        Helper::grid(function(Grid $grid) use ($defaultFilter) {
            $grid->setModel([]);
            $grid->setDefaultFilter($defaultFilter);
            $grid->addFilterText('filter', 'Filter');
            $grid->addFilterText('filterB', 'FilterB');
            $grid->addFilterCustom('filterC', new \Nette\Forms\Controls\MultiSelectBox(NULL, ['a' => 'a', 'b' => 'b', 'c' => 'c']));
        });

        $params = ['grid-page' => 2, 'do' => 'grid-form-submit', Grid::BUTTONS => ['search' => 'Search'], 'count' => 10];

        //new filter AND default value
        $filter = ['filter' => 'test'] + $defaultFilter;
        Helper::request($params + [Filter::ID => $filter]);
        Assert::same($filter, Helper::$grid->filter);
        Assert::same(1, Helper::$grid->page);

        //new filter AND reset default #1
        $filter = ['filter' => 'test'];
        Helper::request($params + [Filter::ID => $filter]);
        Assert::same($filter + ['filterB' => ''], Helper::$grid->filter);
        Assert::same(1, Helper::$grid->page);

        //new filter AND reset default #2
        $filter = ['filter' => 'test', 'filterB' => ''];
        Helper::request($params + [Filter::ID => $filter]);
        Assert::same($filter + ['filterB' => ''], Helper::$grid->filter);
        Assert::same(1, Helper::$grid->page);

        //new filter AND reset default #3
        $filter = ['filter' => 'test', 'filterB' => '0'];
        Helper::request($params + [Filter::ID => $filter]);
        Assert::same($filter + ['filterB' => ''], Helper::$grid->filter);
        Assert::same(1, Helper::$grid->page);

        //skip empty value
        $filter = ['filter' => ''] + $defaultFilter;
        Helper::request($params + [Filter::ID => $filter]);
        Assert::same($defaultFilter, Helper::$grid->filter);
        Assert::same(1, Helper::$grid->page);

        //new filter
        $filter = ['filter' => '0'] + $defaultFilter;
        Helper::request($params + [Filter::ID => $filter]);
        Assert::same($filter, Helper::$grid->filter);
        Assert::same(1, Helper::$grid->page);

        $filter = ['filter' => '0.0'] + $defaultFilter;
        Helper::request($params + [Filter::ID => $filter]);
        Assert::same($filter, Helper::$grid->filter);
        Assert::same(1, Helper::$grid->page);

        //change default
        $filter = ['filterB' => 'test'];
        Helper::request($params + [Filter::ID => $filter]);
        Assert::same($filter, Helper::$grid->filter);
        Assert::same(1, Helper::$grid->page);

        //support for multichoice
        $filter = ['filterC' => ['a', 'c']] + $defaultFilter;
        Helper::request($params + [Filter::ID => $filter]);
        $expected = sort($filter);
        $actual = sort(Helper::$grid->filter);
        Assert::same($expected, $actual);

        $data = [
            ['A' => 'A1', 'B' => 'B3'],
            ['A' => 'A2', 'B' => 'B2'],
            ['A' => 'A22','B' => 'B22'],
            ['A' => 'A3', 'B' => 'B1'],
        ];

        Helper::grid(function(Grid $grid) use ($data) {
            $grid->setDefaultPerPage(1);
            $grid->setModel($data);
            $grid->addColumnText('column', 'Column');
            $grid->addFilterText('B', 'B');
        });

        Helper::request([
            'do' => 'grid-form-submit',
            'grid-page' => 2,
            Filter::ID => ['B' => 'B2'],
            Grid::BUTTONS => ['search' => 'Search'],
            'count' => 10,
        ]);

        Assert::same(1, Helper::$grid->page); //test reset page after filtering

        $expected = [
            1 => ['A' => 'A2', 'B' => 'B2'],
            2 => ['A' => 'A22', 'B' => 'B22'],
        ];
        Assert::same($expected, Helper::$grid->getData(FALSE));

        Helper::grid(function(Grid $grid) use ($data) {
            $grid->setModel($data);
            $grid->addColumnText('column', 'Column');
            $grid->addFilterText('A', 'A');
            $grid->addFilterText('B', 'B')
                ->setDefaultValue('B2');
        });

        Helper::request([
            'do' => 'grid-form-submit',
            'grid-page' => 1,
            Filter::ID => ['A' => '', 'B' => ''],
            Grid::BUTTONS => ['search' => 'Search'],
            'count' => 10,
        ]);

        Assert::same($data, Helper::$grid->getData(FALSE));
        Assert::same(['B' => ''], Helper::$grid->filter);

        $definition = function($data, $strictMode = TRUE) {
            $grid = new Grid;
            $grid->setStrictMode($strictMode);
            $grid->addColumnText('column', 'Column');
            $grid->setModel($data);
            $grid->addFilterText('A', 'A');
            $grid->filter['B'] = 'B2';
            $grid->data;
        };
        Assert::error(function() use ($definition, $data) {
            $definition($data);
        }, E_USER_NOTICE, "Filter with name 'B' does not exist.");

        //STRICT MODE TEST
        $definition($data, FALSE);

        //test session filter
        Helper::grid(function(Grid $grid) {
            $grid->setModel([['A' => 'test']]);
            $grid->setRememberState();
            $grid->addColumnText('A', 'A');
            $grid->addFilterText('A', 'A');
        });

        $params = [
            'do' => 'grid-form-submit',
            Grid::BUTTONS => ['search' => 'Search'],
            'count' => 10,
        ];
        $filter = ['A' => 'test'];
        Helper::request($params + [Filter::ID => $filter]);
        Helper::$grid->render(); //save2session
        Assert::same($filter, Helper::$grid->getRememberSession()->params['filter']);

        $filter = ['A' => ''];
        Helper::request($params + [Filter::ID => $filter]);
        Helper::$grid->render(); //save2session
        Assert::same($filter, Helper::$grid->getRememberSession()->params['filter']);
    }

    function testHandleReset()
    {
        Helper::grid(function(Grid $grid) {
            $grid->setPerPageList([1, 2]);
            $grid->setDefaultPerPage(1);
            $grid->setModel([
                ['A' => 'A1', 'B' => 'B4'],
                ['A' => 'A2', 'B' => 'B3'],
                ['A' => 'A3', 'B' => 'B2'],
                ['A' => 'A4', 'B' => 'B1'],
            ]);

            $grid->addColumnText('A', 'A')->setSortable();
            $grid->addFilterText('B', 'B');

            $params = [
                'sort' => ['A' => Column::ORDER_ASC],
                'filter' => ['B' => 'B2'],
                'perPage' => 2,
                'page' => 2

            ];
            $grid->loadState($params);
        });

        Helper::request(['do' => 'grid-form-submit', Grid::BUTTONS => ['reset' => 'Reset'], 'count' => 2]);
        Assert::same([], Helper::$grid->sort);
        Assert::same([], Helper::$grid->filter);
        Assert::null(Helper::$grid->perPage);
        Assert::same(1, Helper::$grid->page);
    }

    function testHandlePerPage()
    {
        Helper::grid(function(Grid $grid) {
            $grid->setModel([]);
            $grid->addColumnText('column', 'Column');
        });

        $perPage = 10;
        Helper::request(['count' => $perPage, 'grid-page' => 2, 'do' => 'grid-form-submit', Grid::BUTTONS => ['perPage' => 'Items per page']]);
        Assert::same($perPage, Helper::$grid->perPage);
        Assert::same(1, Helper::$grid->page);
    }

    function testIsStrictMode()
    {
        $grid = new Grid;
        $grid->setStrictMode(FALSE);
        Assert::false($grid->isStrictMode());
    }
}

run(__FILE__);
