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

class FilterSelectTest extends \Tester\TestCase
{
    function testFormControl()
    {
        $grid = new Grid;
        $items = ['one' => 'One'];
        $filter = $grid->addFilterSelect('select', 'Select', $items);
        Assert::type('Nette\Forms\Controls\SelectBox', $filter->control);
        Assert::same($items, $filter->control->items);
    }

    function testGetCondition()
    {
        $grid = new Grid;
        $filter = $grid->addFilterSelect('select', 'Select');
        Assert::same(['select = ?', 'TEST'], $filter->__getCondition('TEST')->__toArray());
    }
}

run(__FILE__);
