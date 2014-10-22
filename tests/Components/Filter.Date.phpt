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

class FilterDateTest extends \Tester\TestCase
{
    function testFormControl()
    {
        $grid = new Grid;
        $filter = $grid->addFilterDate('date', 'Date');
        Assert::type('Nette\Forms\Controls\TextInput', $filter->control);
        Assert::same('off', $filter->control->controlPrototype->attrs['autocomplete']);
        Assert::same(array('text', 'date'), $filter->control->controlPrototype->class);
    }

    function testGetCondition()
    {
        $grid = new Grid;
        $filter = $grid->addFilterDate('date', 'Date');

        Assert::same(array('date LIKE ?', '2012-12-21%'), $filter->__getCondition('21.12.2012')->__toArray());
        Assert::same(array('0 = 1'), $filter->__getCondition('TEST BAD INPUT')->__toArray());

        $filter
            ->setDateFormatInput('d/m/Y')
            ->setDateFormatOutput('d.m.Y');

        Assert::same(array('date LIKE ?', '21.12.2012'), $filter->__getCondition('21/12/2012')->__toArray());
        Assert::same(array('0 = 1'), $filter->__getCondition('21.12.2012')->__toArray()); //test bad input
    }
}

run(__FILE__);
