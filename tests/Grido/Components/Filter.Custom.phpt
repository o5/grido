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

class FilterCustomTest extends \Tester\TestCase
{
    function testFormControl()
    {
        $grid = new Grid;
        $control = new \Nette\Forms\Controls\TextArea;
        $filter = $grid->addFilterCustom('custom', $control);
        Assert::same($control, $filter->control);
    }

    function testGetCondition()
    {
        $grid = new Grid;
        $control = new \Nette\Forms\Controls\TextArea;
        $filter = $grid->addFilterCustom('custom', $control);
        Assert::same(array('custom = ?', 'TEST'), $filter->__getCondition('TEST')->__toArray());
    }
}

run(__FILE__);
