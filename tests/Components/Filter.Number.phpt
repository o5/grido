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
    Grido\Grid;

class FilterNumberTest extends \Tester\TestCase
{
    function testFormControl()
    {
        $grid = new Grid;
        $filter = $grid->addFilterNumber('number', 'Number');
        Assert::type('Nette\Forms\Controls\TextInput', $filter->control);
        Assert::same(0, strpos($filter->control->controlPrototype->title, 'You can use <, <=, >, >=, <>. e.g. ">='));
        Assert::same(['text', 'number'], $filter->control->controlPrototype->class);
    }

    function testGetCondition()
    {
        $grid = new Grid;
        $filter = $grid->addFilterNumber('number', 'Number');
        Assert::same(['number = ?', '12.34'], $filter->__getCondition('=12.34')->__toArray());
        Assert::same(['number = ?', '-12.34'], $filter->__getCondition('-12,34')->__toArray());
        Assert::same(['number = ?', '12.34'], $filter->__getCondition('**12.34')->__toArray());
        Assert::same(['number = ?', '12'], $filter->__getCondition('12')->__toArray());
        Assert::same(['number <> ?', '12.34'], $filter->__getCondition('<>12.34')->__toArray());
        Assert::same(['number > ?', '12.34'], $filter->__getCondition('>12.34')->__toArray());
        Assert::same(['number < ?', '12'], $filter->__getCondition('<12')->__toArray());
        Assert::same(['number >= ?', '12.34'], $filter->__getCondition('>=12.34')->__toArray());
        Assert::same(['number <= ?', '12.34'], $filter->__getCondition('<=12.34')->__toArray());
    }
}

run(__FILE__);
