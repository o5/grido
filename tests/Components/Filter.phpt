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
    Grido\Components\Filters\Condition;

class FilterTest extends \Tester\TestCase
{
    function testSetColumn() //+ getColumn()
    {
        $grid = new Grid;

        $filter = $grid->addFilterText('filter', 'Filter')
            ->setColumn('column1', 'xx');
        Assert::same(['column1'], $filter->column);

        $filter = $grid->addFilterText('filterX', 'Filter')
            ->setColumn('column1')
            ->setColumn('column2', Condition::OPERATOR_AND);
        Assert::same(['column1', Condition::OPERATOR_AND, 'column2'], $filter->column);

        $filter->setColumn('column3', 'and');
        Assert::error(function() use ($filter) {
            $filter->setColumn('column4', 'ORR');
        }, 'Grido\Exception', "Operator must be 'AND' or 'OR'.");

        $filter = $grid->addFilterText('filterY', 'Filter');
        Assert::same(['filterY'], $filter->column);

        $filter = $grid->addColumnText('columnX', 'ColumnX')
            ->setFilterText();
        Assert::same(['columnX'], $filter->column);
    }

    function testSetCondition() //+ __getCondition()
    {
        $grid = new Grid;
        $filter = $grid->addFilterText('filter', 'Filter');

        //test "where callback" is in method testSetWhere()

        //string condition
        $filter->setCondition('<> ?');
        Assert::type('Grido\Components\Filters\Condition', $filter->__getCondition('value'));
        Assert::same(['filter <> ?', '%value%'], $filter->__getCondition('value')->__toArray());

        //object Condition
        $filter->setCondition(new Condition('filter', '<> ?', 'value%'));
        Assert::type('Grido\Components\Filters\Condition', $filter->__getCondition('value'));
        Assert::same(['filter <> ?', 'value%'], $filter->__getCondition('value')->__toArray());

        //callback condition - return array
        $filter->setCondition(function($value) {
            Assert::same('value', $value);
            return ['status', '= ?', $value];
        });
        Assert::type('Grido\Components\Filters\Condition', $filter->__getCondition('value'));
        Assert::same(['status = ?', 'value'], $filter->__getCondition('value')->__toArray());

        //callback condition - return object Condition
        $filter->setCondition(function($value) {
            Assert::same('value', $value);
            return new Condition('status', '<> ?', $value);
        });
        Assert::type('Grido\Components\Filters\Condition', $filter->__getCondition('value'));
        Assert::same(['status <> ?', 'value'], $filter->__getCondition('value')->__toArray());

        //array of array condition
        $filter->setCondition(['deleted' => ['status', '= ?', 'deleted']]);
        Assert::type('Grido\Components\Filters\Condition', $filter->__getCondition('deleted'));
        Assert::same(['status = ?', 'deleted'], $filter->__getCondition('deleted')->__toArray());
        Assert::type('Grido\Components\Filters\Condition', $filter->__getCondition('value'));
        Assert::same(['0 = 1'], $filter->__getCondition('value')->__toArray());

        //array of object condition
        $filter->setCondition(['deleted' => new Condition('status', '= ?', 'deleted')]);
        Assert::type('Grido\Components\Filters\Condition', $filter->__getCondition('deleted'));
        Assert::same(['status = ?', 'deleted'], $filter->__getCondition('deleted')->__toArray());
        Assert::type('Grido\Components\Filters\Condition', $filter->__getCondition('value'));
        Assert::same(['0 = 1'], $filter->__getCondition('value')->__toArray());
    }

    function testSetWhere()
    {
        $grid = new Grid;
        $where = function() {};
        $filter = $grid->addFilterText('filter', 'Filter')
            ->setWhere($where);

        $condition = $filter->__getCondition('value');
        Assert::type('Grido\Components\Filters\Condition', $condition);
        Assert::same($where, $condition->callback);
        Assert::same('value', $condition->value);
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

        Assert::same(['filter LIKE ?', '%TEST%'], $filter->__getCondition('TEST')->__toArray());
    }

    function testSetDefaufaulValue()
    {
        $grid = new Grid;
        $grid->addFilterText('filter', 'Filter')
            ->setDefaultValue('default');
        Assert::same(['filter' => 'default'], $grid->defaultFilter);

        $grid->addFilterText('filter2', 'Filter2')
            ->setDefaultValue('default2');
        Assert::same(['filter' => 'default', 'filter2' => 'default2'], $grid->defaultFilter);
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

        $name = 'foo.bar';
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

        $name = 'daterange';
        $grid->addFilterDateRange($name, $label);
        $component = $grid->getFilter($name);
        Assert::type('\Grido\Components\Filters\DateRange', $component);
        Assert::type('\Grido\Components\Filters\Date', $component);
        Assert::same($label, $component->label);

        $name = 'check';
        $grid->addFilterCheck($name, $label);
        $component = $grid->getFilter($name);
        Assert::type('\Grido\Components\Filters\Check', $component);
        Assert::same($label, $component->label);

        $name = 'select';
        $items = ['one' => 'raz', 'two' => 'dva'];
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

        // getter
        Assert::exception(function() use ($grid) {
            $grid->getFilter('filter');
        }, 'Nette\InvalidArgumentException');
        Assert::null($grid->getFilter('filter', FALSE));

        $grid = new Grid;
        Assert::null($grid->getFilter('filter'));
    }
}

run(__FILE__);
