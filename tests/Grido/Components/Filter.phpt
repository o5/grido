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
    Grido\Components\Filters\Filter,
    Grido\Components\Filters\Condition;

class FilterTest extends \Tester\TestCase
{
    function testSetColumn() //+ getColumn()
    {
        $grid = new Grid;
        $filter = $grid->addFilterText('filter', 'Filter')
            ->setColumn('column1')->setColumn('column2', Filter::OPERATOR_AND);
        Assert::same(array('column1', Filter::OPERATOR_AND, 'column2'), $filter->column);

        $filter->setColumn('column3', 'and');
        Assert::error(function() use ($filter) {
            $filter->setColumn('column4', 'ORR');
        }, 'InvalidArgumentException', 'Operator must be Filter::OPERATOR_AND or Filter::OPERATOR_OR.');

        $filter = $grid->addFilterText('filterX', 'FilterX');
        Assert::same(array('filterX'), $filter->column);

        $filter = $grid->addColumnText('columnX', 'ColumnX')
            ->setFilterText();
        Assert::same(array('columnX'), $filter->column);
    }

    function testSetCondition()
    {
        $grid = new Grid;
        $filter = $grid->addFilterText('filter', 'Filter');

        //string condition
        $filter->setCondition('<> ?');
        Assert::same(array('filter <> ?', '%value%'), $filter->__getCondition('value')->__toArray());

        //object condition
        $filter->setCondition(new Condition('filter', '<> ?', 'value%'));
        Assert::same(array('filter <> ?', 'value%'), $filter->__getCondition('value')->__toArray());

        //callback condition - return array
        $filter->setCondition(function($value) {
            Assert::same('deleted', $value);
            return array('status', '= ?', 'deleted');
        });
        Assert::same(array('status = ?', 'deleted'), $filter->__getCondition('deleted')->__toArray());

        //callback condition - return object Condition
        $filter->setCondition(function($value) {
            Assert::same('deleted', $value);
            return new Condition('status', '= ?', 'deleted');
        });
        Assert::same(array('status = ?', 'deleted'), $filter->__getCondition('deleted')->__toArray());

        //callback condition - return empty string
        Assert::exception(function() use ($filter) {
            $filter->setCondition(function() {
                return '';
            })->__getCondition('deleted');
        }, 'InvalidArgumentException', 'Condition must be array or Grido\Components\Filters\Condition. Type "string" given.');

        Assert::exception(function() use ($filter) {
            $filter->setCondition(new \stdClass)->__getCondition('deleted');
        }, 'InvalidArgumentException', 'Condition must be array or Grido\Components\Filters\Condition. Type "object" given.');

        //array of array condition
        $filter->setCondition(array('deleted' => array('status', '= ?', 'deleted')));
        Assert::same(array('status = ?', 'deleted'), $filter->__getCondition('deleted')->__toArray());

        //array of object condition
        $filter->setCondition(array('deleted' => new Condition('status', '= ?', 'deleted')));
        Assert::same(array('status = ?', 'deleted'), $filter->__getCondition('deleted')->__toArray());

        //@deprecated
        Assert::error(function() use ($filter) {
            $filter->setCondition(Filter::CONDITION_CUSTOM);
        }, E_USER_DEPRECATED, "Condition type ':condition-custom:' is deprecated, check out the new usage.");

        //CONDITION TESTS - TODO: move to special file?
        Assert::exception(function() use ($filter) {
            $filter->setCondition(array('deleted' => array(array('status', 'orr'), '= ?', 'deleted')))->__getCondition('deleted')->__toArray();
        }, 'InvalidArgumentException', "The even values of column must be Filter::OPERATOR_AND or Filter::OPERATOR_OR, 'orr' given.");

        $filter->setCondition(new Condition(array('column1', 'or', 'column2'), '= ?', 'value'));
        Assert::same(array('(column1 = ? OR column2 = ?)', 'value', 'value'), $filter->__getCondition('.')->__toArray());

        $filter->setCondition(new Condition('column1', 'BETWEEN ? AND ?', array('value', 'value2')));
        Assert::same(array('column1 BETWEEN ? AND ?', 'value', 'value2'), $filter->__getCondition('.')->__toArray());

        Assert::exception(function() use ($filter) {
            $filter->setCondition(new Condition('column1', 'BETWEEN ? AND ?', 'value'))->__getCondition('.')->__toArray();
        }, 'InvalidArgumentException', "Condition 'BETWEEN ? AND ?' requires 2 values.");
    }

    function testSetWhere()
    {
        $grid = new Grid;
        $where = function() {};
        $filter = $grid->addFilterText('filter', 'Filter')
            ->setWhere($where);

        $condition = $filter->__getCondition('value');
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

        Assert::same(array('filter LIKE ?', '%TEST%'), $filter->__getCondition('TEST')->__toArray());
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
