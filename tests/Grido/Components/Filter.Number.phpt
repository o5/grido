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

class FilterNumber extends \Tester\TestCase
{
    function testFormControl()
    {
        $grid = new Grid;
        $filter = $grid->addFilterNumber('number', 'Number');
        Assert::type('Nette\Forms\Controls\TextInput', $filter->control);
        Assert::same(0, strpos($filter->control->controlPrototype->title, 'You can use <, <=, >, >=, <>. e.g. ">='));
        Assert::same(array('text', 'number'), $filter->control->controlPrototype->class);
    }

    function testMakeFilter() //__makeFilter()
    {
        $grid = new Grid;
        $filter = $grid->addFilterNumber('number', 'Number');
        Assert::same(array(' ([number] = %f )', '12.34'), $filter->__makeFilter('=12.34'));
        Assert::same(array(' ([number] = %f )', '-12.34'), $filter->__makeFilter('-12,34'));
        Assert::same(array(' ([number] = %f )', '12.34'), $filter->__makeFilter('**12.34'));
        Assert::same(array(' ([number] = %f )', '12'), $filter->__makeFilter('12'));
        Assert::same(array(' ([number] <> %f )', '12.34'), $filter->__makeFilter('<>12.34'));
        Assert::same(array(' ([number] > %f )', '12.34'), $filter->__makeFilter('>12.34'));
        Assert::same(array(' ([number] < %f )', '12'), $filter->__makeFilter('<12'));
        Assert::same(array(' ([number] >= %f )', '12.34'), $filter->__makeFilter('>=12.34'));
        Assert::same(array(' ([number] <= %f )', '12.34'), $filter->__makeFilter('<=12.34'));
    }
}

run(__FILE__);
