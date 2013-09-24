<?php

/**
 * Test: Filter.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

require_once __DIR__ . '/../bootstrap.php';

use Tester\Assert,
    Grido\Grid;

class FilterSelect extends Tester\TestCase
{
    function testFormControl()
    {
        $grid = new Grid;
        $items = array('one' => 'One');
        $filter = $grid->addFilterSelect('select', 'Select', $items);
        Assert::type('Nette\Forms\Controls\SelectBox', $filter->control);
        Assert::same($items, $filter->control->items);
    }

    function testMakeFilter() //__makeFilter()
    {
        $grid = new Grid;
        $filter = $grid->addFilterSelect('select', 'Select');
        Assert::same(array(' ([select] = %s )', 'TEST'), $filter->__makeFilter('TEST'));
    }
}

run(__FILE__);
