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

class FilterDateRangeTest extends \Tester\TestCase
{
    function testFormControl()
    {
        $grid = new Grid;
        $filter = $grid->addFilterDateRange('date', 'Daterange');
        Assert::type('Nette\Forms\Controls\TextInput', $filter->control);
        Assert::same('off', $filter->control->controlPrototype->attrs['autocomplete']);
        Assert::same(array('text', 'daterange'), $filter->control->controlPrototype->class);
    }

    function testGetCondition()
    {
        $grid = new Grid;
        $filter = $grid->addFilterDateRange('date', 'Daterange');
        Assert::same(array('date BETWEEN ? AND ?', '2012-12-21', '2012-12-22 23:59:59'), $filter->__getCondition('21.12.2012 - 22.12.2012')->__toArray());
        Assert::same(array('0 = 1'), $filter->__getCondition('TEST BAD INPUT')->__toArray());
        Assert::same(array('0 = 1'), $filter->__getCondition('21.12.2012-TEST BAD INPUT')->__toArray());

        $filter->setDateFormatOutput('Y-m-d', 'Y-m-d g:i:s');
        Assert::same(array('date BETWEEN ? AND ?', '2012-12-21', '2012-12-22 11:59:59'), $filter->__getCondition('21.12.2012 - 22.12.2012')->__toArray());

        $filter
            ->setMask('/(.*)\s?-\s?(.*)/')
            ->setDateFormatInput('d/m/Y')
            ->setDateFormatOutput('d.m.Y');
        Assert::same(array('date BETWEEN ? AND ?', '21.12.2012', '22.12.2012'), $filter->__getCondition('21/12/2012-22/12/2012')->__toArray());
        Assert::same(array('0 = 1'), $filter->__getCondition('21.12.2012-22.12.2012')->__toArray()); //test bad input

        $filter = $grid->addFilterDateRange('datetime', 'DateTimeRange')
            ->setDateFormatInput('d/m/Y H:i')
            ->setDateFormatOutput('Y-m-d H:i:s', 'Y-m-d H:i:s');
        Assert::same(array('datetime BETWEEN ? AND ?', '2012-12-21 22:35:00', '2012-12-22 08:05:00'), $filter->__getCondition('21/12/2012 22:35 - 22/12/2012 08:05')->__toArray());

        $filter->setDateFormatInput('d/m/Y g:i');
        Assert::same(array('datetime BETWEEN ? AND ?', '2012-12-21 10:35:00', '2012-12-22 08:05:00'), $filter->__getCondition('21/12/2012 10:35 - 22/12/2012 08:05')->__toArray());
    }
}

run(__FILE__);
