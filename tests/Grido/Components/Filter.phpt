<?php

/**
 * Test: Filter.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

namespace Grido\Tests;

require_once __DIR__ . '/../bootstrap.php';

use Tester\Assert,
    Grido\Grid,
    Grido\Components\Filters\Filter;

class FilterTest extends \Tester\TestCase
{
    function testSetColumn() //+ getColumns()
    {
        $grid = new Grid;
        $filter = $grid->addFilterText('filter', 'Filter')
            ->setColumn('column1')->setColumn('column2', Filter::OPERATOR_OR);
        Assert::same(array('column1' => Filter::OPERATOR_AND, 'column2' => Filter::OPERATOR_OR), $filter->columns);
    }

    function testSetCondition()
    {
        $grid = new Grid;
        $filter = $grid->addFilterText('filter', 'Filter');

        Assert::error(function() use ($filter) {
            $filter->setCondition(Filter::CONDITION_CUSTOM);
        }, 'InvalidArgumentException', 'Second param cannot be empty.');

        Assert::error(function() use ($filter) {
            $filter->setCondition(Filter::CONDITION_CALLBACK);
        }, 'InvalidArgumentException', 'Second param cannot be empty.');

        $filter->setCondition('<> %s');
        Assert::same(array(' ([filter] <> %s )', '%value%'), $filter->__makeFilter('value'));

        $filter->setCondition(Filter::CONDITION_CUSTOM, array('deleted' => '[status] = deleted'));
        Assert::same(array(' ([status] = deleted )'), $filter->__makeFilter('deleted'));

        $testValue = 'TEST';
        $filter->setCondition(Filter::CONDITION_CALLBACK, function($value) use ($testValue) {
            Assert::same($testValue, $value);
            return array('[column] <> "TEST"');
        });
        Assert::same(array('[column] <> "TEST"'), $filter->__makeFilter($testValue));

        $filter->setCondition(Filter::CONDITION_NOT_APPLY);
        Assert::same(array(), $filter->__makeFilter('deleted'));
    }

    function testChangeValue()
    {
        $grid = new Grid;
        $filter = $grid->addFilterText('filter', 'Filter');
        Assert::same('TEST', $filter->changeValue('TEST'));
    }

    function testFormatValue()
    {
        $grid = new Grid;
        $filter = $grid->addFilterText('filter', 'Filter')
            ->setFormatValue('%%value%');

        Assert::same(array(' ([filter] LIKE %s )', '%TEST%'), $filter->__makeFilter('TEST'));
    }

    function testSetDefaufaulValue()
    {
        $grid = new Grid;
        $grid->addFilterText('filter', 'Filter')
            ->setDefaultValue('default');
        Assert::same(array('filter' => 'default'), $grid->defaultFilter);
    }

    function testGetWrapperPrototype()
    {
        $grid = new Grid;
        $filter = $grid->addFilterText('filter', 'Filter');
        Assert::type('Nette\Utils\Html', $filter->wrapperPrototype);
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
        Assert::type('\Grido\Components\Filters\Text', $component);
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
        Assert::type('\Grido\Components\Filters\Text', $component);
        Assert::same($label, $component->label);

        $name = 'custom';
        $control = new \Nette\Forms\Controls\TextArea($label);
        $grid->addFilterCustom($name, $control);
        $component = $grid->getFilter($name);
        Assert::type('\Grido\Components\Filters\Custom', $component);
        Assert::type('\Nette\Forms\Controls\TextArea', $component->formControl);

        Assert::error(function() use ($grid, $label) {
            $name = 'deprecated';
            $grid->addFilter($name, $label, Filter::TYPE_CHECK);
            $component = $grid->getFilter($name);
            Assert::type('\Grido\Components\Filters\Check', $component);
            Assert::same($label, $component->label);
        }, E_USER_DEPRECATED);

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
