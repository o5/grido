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

class FilterDate extends \Tester\TestCase
{
    function testFormControl()
    {
        $grid = new Grid;
        $filter = $grid->addFilterDate('date', 'Date');
        Assert::type('Nette\Forms\Controls\TextInput', $filter->control);
        Assert::same('off', $filter->control->controlPrototype->attrs['autocomplete']);
        Assert::same(array('text', 'date'), $filter->control->controlPrototype->class);
    }

    function testMakeFilter() //__makeFilter()
    {
        $grid = new Grid;
        $filter = $grid->addFilterDate('date', 'Date');
        Assert::same(array(' ([date] LIKE %s )', '%TEST%'), $filter->__makeFilter('TEST'));
    }
}

run(__FILE__);
