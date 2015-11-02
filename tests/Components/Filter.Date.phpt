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
        Assert::same(['text', 'date'], $filter->control->controlPrototype->class);
    }

    function testGetCondition()
    {
        $grid = new Grid;
        $filter = $grid->addFilterDate('date', 'Date');

        Assert::same(['date LIKE ?', '2012-12-21%'], $filter->__getCondition('21.12.2012')->__toArray());
        Assert::same(['0 = 1'], $filter->__getCondition('TEST BAD INPUT')->__toArray());

        $formatInput = 'd/m/Y';
        $formatOutpu = 'd.m.Y';
        $filter
            ->setDateFormatInput($formatInput)
            ->setDateFormatOutput($formatOutpu);

        Assert::same($formatInput, $filter->getDateFormatInput());
        Assert::same($formatOutpu, $filter->getDateFormatOutput());

        Assert::same(['date LIKE ?', '21.12.2012'], $filter->__getCondition('21/12/2012')->__toArray());
        Assert::same(['0 = 1'], $filter->__getCondition('21.12.2012')->__toArray()); //test bad input
    }
}

run(__FILE__);
