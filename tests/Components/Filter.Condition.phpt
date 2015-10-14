<?php

/**
 * Test: Filter.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

namespace Grido\Tests;

use Tester\Assert,
    Grido\Components\Filters\Condition;

require_once __DIR__ . '/../bootstrap.php';

class FilterConditionTest extends \Tester\TestCase
{
    function testSetColumn()
    {
        $condition = new Condition('column', NULL);
        Assert::same(array('column'), $condition->column);

        $condition = new Condition(array('column'), NULL);
        Assert::same(array('column'), $condition->column);

        $condition = new Condition(array('column', 'or', 'column2'), NULL);
        Assert::same(array('column', 'or', 'column2'), $condition->column);

        Assert::exception(function() {
            new Condition(array('column', 'or'), NULL);
        }, 'Grido\Exception', 'Count of column must be odd.');

        Assert::exception(function() {
            new Condition(array('column', 'xxx', 'column2'), NULL);
        }, 'Grido\Exception', "The even values of column must be 'AND' or 'OR', 'xxx' given.");
    }

    function testSetCondition()
    {
        $condition = new Condition('column', 'condition');
        Assert::same(array('condition'), $condition->condition);

        $condition = new Condition('column', array('condition'));
        Assert::same(array('condition'), $condition->condition);
    }

    function testSetValue()
    {
        $condition = new Condition(NULL, NULL, 'value');
        Assert::same(array('value'), $condition->value);

        $condition = new Condition(NULL, NULL, array('value', 'value2'));
        Assert::same(array('value', 'value2'), $condition->value);
    }

    function testGetValueForColumn()
    {
        $condition = new Condition(array('column'), NULL, array('value'));
        Assert::same(array('value'), $condition->getValueForColumn());

        $condition = new Condition(array('column', 'and', 'column2'), NULL, array('value'));
        Assert::same(array('value', 'value'), $condition->getValueForColumn());
    }

    function testIsOperator()
    {
        Assert::true(Condition::isOperator('and'));
        Assert::true(Condition::isOperator('OR'));
        Assert::false(Condition::isOperator('xxx'));
    }

    function testSetupEmpty()
    {
        $condition = Condition::setupEmpty();
        Assert::same(array(), $condition->column);
        Assert::same(array('0 = 1'), $condition->condition);
        Assert::same(array('0 = 1'), $condition->__toArray());
    }

    function testSetupFromArray()
    {
        $array = array('column', 'condition', 'value');
        $condition = Condition::setupFromArray($array);
        Assert::same(array('column'), $condition->column);
        Assert::same(array('condition'), $condition->condition);
        Assert::same(array('value'), $condition->value);

        Assert::exception(function() {
            Condition::setupFromArray(array('one'));
        }, 'Grido\Exception', 'Condition array must contain 3 items.');

        Assert::exception(function() {
            Condition::setupFromArray(array('one', 'two', 'three', 'for'));
        }, 'Grido\Exception', 'Condition array must contain 3 items.');
    }

    function testSetupFromCallback()
    {
        $callback = function() {};
        $condition = Condition::setupFromCallback($callback, 'value');
        Assert::same($callback, $condition->callback);
        Assert::same('value', $condition->value);
    }

    function testToArray()
    {
        $condition = new Condition('column', '<> ?', 'value');
        Assert::same(array('column <> ?', 'value'), $condition->__toArray());
        Assert::same(array('[column] <> ?', 'value'), $condition->__toArray('[', ']'));

        $condition = new Condition(array('column', 'OR', 'column2'), '<> ?', 'value');
        Assert::same(array('(column <> ? OR column2 <> ?)', 'value', 'value'), $condition->__toArray());
        Assert::same(array('([column] <> ? OR [column2] <> ?)', 'value', 'value'), $condition->__toArray('[', ']'));
        Assert::same(array('[column] <> ? OR [column2] <> ?', 'value', 'value'), $condition->__toArray('[', ']', FALSE));

        $condition = new Condition('column', 'BETWEEN ? AND ?', array('min', 'max'));
        Assert::same(array('column BETWEEN ? AND ?', 'min', 'max'), $condition->__toArray());

        $condition = new Condition(array('column', 'or', 'column2'), 'BETWEEN ? AND ?', array('min', 'max'));
        Assert::same(array('(column BETWEEN ? AND ? OR column2 BETWEEN ? AND ?)', 'min', 'max', 'min', 'max'), $condition->__toArray());

        $condition = new Condition(NULL, '0 = 1');
        Assert::same(array('0 = 1'), $condition->__toArray());

        $condition = new Condition(array('column', 'or', 'column2'), array('= ?', '>= ?'), array('value', 'max'));
        Assert::same(array('(column = ? OR column2 >= ?)', 'value', 'max'), $condition->__toArray());

        $condition = new Condition(array('column', 'or', 'column2'), array('= ?', 'BETWEEN ? AND ?'), array('value', 'min', 'max'));
        Assert::same(array('(column = ? OR column2 BETWEEN ? AND ?)', 'value', 'min', 'max'), $condition->__toArray());
    }
}

run(__FILE__);
