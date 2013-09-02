<?php

/**
 * Test: Filter's component.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Helper.inc';

use Tester\Assert,
    Grido\Grid,
    Grido\Components\Filters\Filter;

class FilterTest extends Tester\TestCase
{
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
        $grid->addFilterText('A', 'Column');
        $defaultFilter = array('A' => 'A2');
        $grid->setDefaultFilter($defaultFilter);

        Assert::same($defaultFilter, $grid->defaultFilter);
        Assert::same(array(array('A' => 'A2', 'B' => 'B2')), $grid->data);
        Assert::same('A2', $grid['form'][Filter::ID]['A']->value);

        Assert::error(function() use ($defaultFilter) {
            $grid = new Grid;
            $grid->setModel(array());
            $grid->setDefaultFilter($defaultFilter);
            $grid->getData();
        }, E_USER_NOTICE, "Filter with name 'A' does not exist.");
    }

    /**********************************************************************************************/

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

    /**********************************************************************************************/

    function testHandleFilter()
    {
        $data = array(
            array('A' => 'A1', 'B' => 'B3'),
            array('A' => 'A2', 'B' => 'B2'),
            array('A' => 'A22', 'B' => 'B22'),
            array('A' => 'A3', 'B' => 'B1'),
        );

        Helper::grid(function(Grid $grid) use ($data) {
            $grid->setDefaultPerPage(1);
            $grid->setModel($data);
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
            $grid->addFilterText('B', 'B');
            $grid->addFilterText('A', 'A');
            $grid->setDefaultFilter(array('B' => 'B2'));
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
            $grid->setModel($data);
            $grid->addFilterText('A', 'A');
            $grid->filter['B'] = 'B2';
            $grid->data;
        }, E_USER_NOTICE, "Filter with name 'B' does not exist.");
    }

    /**********************************************************************************************/

    function testHasFilters()
    {
        $grid = new Grid;
        Assert::false($grid->hasFilters());

        $grid->addFilterText('filter', 'Filter');
        Assert::false($grid->hasFilters());
        Assert::true($grid->hasFilters(FALSE));
    }

    function testAddFilter() //addFilter*()
    {
        $grid = new Grid;
        $label = 'Filter';

        $name = 'text';
        $grid->addFilterText($name, $label);
        $component = $grid->getFilter($name);
        Assert::type('\Grido\Components\Filters\Text', $component);
        Assert::same($label, $component->label);

        $name = 'date';
        $grid->addFilterDate($name, $label);
        $component = $grid->getFilter($name);
        Assert::type('\Grido\Components\Filters\Date', $component);
        Assert::same($label, $component->label);

        $name = 'check';
        $grid->addFilterCheck($name, $label);
        $component = $grid->getFilter($name);
        Assert::type('\Grido\Components\Filters\Check', $component);
        Assert::same($label, $component->label);

        $name = 'select';
        $items = array('one' => 'raz', 'two' => 'dva');
        $grid->addFilterSelect($name, $label, $items);
        $component = $grid->getFilter($name);
        Assert::type('\Grido\Components\Filters\Select', $component);
        Assert::same($label, $component->label);
        Assert::same($items, $component->getControl()->items);

        $name = 'number';
        $grid->addFilterNumber($name, $label);
        $component = $grid->getFilter($name);
        Assert::type('\Grido\Components\Filters\Number', $component);
        Assert::same($label, $component->label);

        $name = 'custom';
        $control = new \Nette\Forms\Controls\TextArea($label);
        $grid->addFilterCustom($name, $control);
        $component = $grid->getFilter($name);
        Assert::type('\Grido\Components\Filters\Custom', $component);
        Assert::type('\Nette\Forms\Controls\TextArea', $component->formControl);

        // getter
        Assert::exception(function() use ($grid) {
            $grid->getFilter('filter');
        }, 'InvalidArgumentException');
        Assert::null($grid->getFilter('filter', FALSE));

        $grid = new Grid;
        Assert::null($grid->getFilter('filter'));
    }
}

run(__FILE__);
