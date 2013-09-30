<?php

/**
 * Test: Filter.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

namespace Grido\Tests;

use Tester\Assert,
    Grido\Grid;

require_once __DIR__ . '/../bootstrap.php';

class FilterCheck extends \Tester\TestCase
{
    function testFormControl()
    {
        $grid = new Grid;
        $filter = $grid->addFilterCheck('check', 'Check');
        Assert::type('\Nette\Forms\Controls\Checkbox', $filter->control);
    }

    function testMakeFilter() //__makeFilter()
    {
        $grid = new Grid;
        $filter = $grid->addFilterCheck('check', 'Check');
        Assert::same(array(' ([check] IS NOT NULL )', ''), $filter->__makeFilter(TRUE));
    }

    function testChangeValue()
    {
        $grid = new Grid;
        $filter = $grid->addFilterCheck('check', 'Check');
        Assert::same(\Grido\Components\Filters\Check::TRUE, $filter->changeValue(TRUE));
        Assert::same(FALSE, $filter->changeValue(FALSE));
    }
}

run(__FILE__);
