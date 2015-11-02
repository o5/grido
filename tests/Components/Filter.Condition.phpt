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
        Assert::same(['column'], $condition->column);

        $condition = new Condition(['column'], NULL);
        Assert::same(['column'], $condition->column);

        $condition = new Condition(['column', 'or', 'column2'], NULL);
        Assert::same(['column', 'or', 'column2'], $condition->column);

        Assert::exception(function() {
            new Condition(['column', 'or'], NULL);
        }, 'Grido\Exception', 'Count of column must be odd.');

        Assert::exception(function() {
            new Condition(['column', 'xxx', 'column2'], NULL);
        }, 'Grido\Exception', "The even values of column must be 'AND' or 'OR', 'xxx' given.");
    }

    function testSetCondition()
    {
        $condition = new Condition('column', 'condition');
        Assert::same(['condition'], $condition->condition);

        $condition = new Condition('column', ['condition']);
        Assert::same(['condition'], $condition->condition);
    }

    function testSetValue()
    {
        $condition = new Condition(NULL, NULL, 'value');
        Assert::same(['value'], $condition->value);

        $condition = new Condition(NULL, NULL, ['value', 'value2']);
        Assert::same(['value', 'value2'], $condition->value);
    }

    function testGetValueForColumn()
    {
        $condition = new Condition(['column'], NULL, ['value']);
        Assert::same(['value'], $condition->getValueForColumn());

        $condition = new Condition(['column', 'and', 'column2'], NULL, ['value']);
        Assert::same(['value', 'value'], $condition->getValueForColumn());
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
        Assert::same([], $condition->column);
        Assert::same(['0 = 1'], $condition->condition);
        Assert::same(['0 = 1'], $condition->__toArray());
    }

    function testSetupFromArray()
    {
        $array = ['column', 'condition', 'value'];
        $condition = Condition::setupFromArray($array);
        Assert::same(['column'], $condition->column);
        Assert::same(['condition'], $condition->condition);
        Assert::same(['value'], $condition->value);

        Assert::exception(function() {
            Condition::setupFromArray(['one']);
        }, 'Grido\Exception', 'Condition array must contain 3 items.');

        Assert::exception(function() {
            Condition::setupFromArray(['one', 'two', 'three', 'for']);
        }, 'Grido\Exception', 'Condition array must contain 3 items.');
    }

    function testSetupFromCallback()
    {
        $callback = function() {};
        $condition = Condition::setupFromCallback($callback, 'value');
        Assert::same($callback, $condition->callback);
        Assert::same('value', $condition->value);
    }

    function testGetColumnWithoutOperator()
    {
        $condition = new Condition(['column', 'and', 'column2'], NULL, ['value']);
        Assert::same(['column', 'column2'], $condition->getColumnWithoutOperator());
    }

    function testToArray()
    {
        $condition = new Condition('column', '<> ?', 'value');
        Assert::same(['column <> ?', 'value'], $condition->__toArray());
        Assert::same(['[column] <> ?', 'value'], $condition->__toArray('[', ']'));

        $condition = new Condition(['column', 'OR', 'column2'], '<> ?', 'value');
        Assert::same(['(column <> ? OR column2 <> ?)', 'value', 'value'], $condition->__toArray());
        Assert::same(['([column] <> ? OR [column2] <> ?)', 'value', 'value'], $condition->__toArray('[', ']'));
        Assert::same(['[column] <> ? OR [column2] <> ?', 'value', 'value'], $condition->__toArray('[', ']', FALSE));

        $condition = new Condition('column', 'BETWEEN ? AND ?', ['min', 'max']);
        Assert::same(['column BETWEEN ? AND ?', 'min', 'max'], $condition->__toArray());

        $condition = new Condition(['column', 'or', 'column2'], 'BETWEEN ? AND ?', ['min', 'max']);
        Assert::same(['(column BETWEEN ? AND ? OR column2 BETWEEN ? AND ?)', 'min', 'max', 'min', 'max'], $condition->__toArray());

        $condition = new Condition(NULL, '0 = 1');
        Assert::same(['0 = 1'], $condition->__toArray());

        $condition = new Condition(['column', 'or', 'column2'], ['= ?', '>= ?'], ['value', 'max']);
        Assert::same(['(column = ? OR column2 >= ?)', 'value', 'max'], $condition->__toArray());

        $condition = new Condition(['column', 'or', 'column2'], ['= ?', 'BETWEEN ? AND ?'], ['value', 'min', 'max']);
        Assert::same(['(column = ? OR column2 BETWEEN ? AND ?)', 'value', 'min', 'max'], $condition->__toArray());
    }
}

run(__FILE__);
