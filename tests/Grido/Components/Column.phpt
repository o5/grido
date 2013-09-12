<?php

/**
 * Test: Column.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Helper.inc.php';

use Tester\Assert,
    Grido\Grid,
    Grido\Components\Columns\Column;

class ColumnTest extends Tester\TestCase
{
    /**********************************************************************************************/

    function testHasColumns()
    {
        $grid = new Grid;
        Assert::false($grid->hasColumns());

        $grid->addColumnText('column', 'Column');
        Assert::false($grid->hasColumns());
        Assert::true($grid->hasColumns(FALSE));
    }

    function testAddColumn() //addColumn*()
    {
        $grid = new Grid;
        $label = 'Column';

        $name = 'text';
        $grid->addColumnText($name, $label);
        $component = $grid->getColumn($name);
        Assert::type('\Grido\Components\Columns\Text', $component);
        Assert::type('\Grido\Components\Columns\Column', $component);
        Assert::same($label, $component->label);

        $name = 'mail';
        $grid->addColumnMail($name, $label);
        $component = $grid->getColumn($name);
        Assert::type('\Grido\Components\Columns\Mail', $component);
        Assert::type('\Grido\Components\Columns\Column', $component);
        Assert::same($label, $component->label);

        $name = 'href';
        $grid->addColumnHref($name, $label);
        $component = $grid->getColumn($name);
        Assert::type('\Grido\Components\Columns\Href', $component);
        Assert::type('\Grido\Components\Columns\Column', $component);
        Assert::same($label, $component->label);

        $name = 'date';
        $format = \Grido\Components\Columns\Date::FORMAT_DATE;
        $grid->addColumnDate($name, $label, $format);
        $component = $grid->getColumn($name);
        Assert::type('\Grido\Components\Columns\Date', $component);
        Assert::type('\Grido\Components\Columns\Column', $component);
        Assert::same($label, $component->label);
        Assert::same($format, $component->dateFormat);

        $name = 'number';
        $decimals = 1;
        $decPoint = ',';
        $thousandsSep = '.';
        $grid->addColumnNumber($name, $label, $decimals, $decPoint, $thousandsSep);
        $component = $grid->getColumn($name);
        Assert::type('\Grido\Components\Columns\Number', $component);
        Assert::type('\Grido\Components\Columns\Column', $component);
        Assert::same($label, $component->label);
        Assert::same(array(
            \Grido\Components\Columns\Number::NUMBER_FORMAT_DECIMALS => $decimals,
            \Grido\Components\Columns\Number::NUMBER_FORMAT_DECIMAL_POINT => $decPoint,
            \Grido\Components\Columns\Number::NUMBER_FORMAT_THOUSANDS_SEPARATOR => $thousandsSep
        ), $component->numberFormat);

        // getter
        Assert::exception(function() use ($grid) {
            $grid->getColumn('column');
        }, 'InvalidArgumentException');
        Assert::same(NULL, $grid->getColumn('column', FALSE));

        $grid = new Grid;
        Assert::null($grid->getColumn('column'));
    }

    function testSetFilter() //setFilter*()
    {
        $grid = new Grid;
        $fiter = $grid->addColumnText('column', 'Column')->setFilterText();
        Assert::type('\Grido\Components\Filters\Text', $fiter);

        $grid = new Grid;
        $fiter = $grid->addColumnText('column', 'Column')->setFilterDate();
        Assert::type('\Grido\Components\Filters\Date', $fiter);

        $grid = new Grid;
        $fiter = $grid->addColumnText('column', 'Column')->setFilterCheck();
        Assert::type('\Grido\Components\Filters\Check', $fiter);

        $grid = new Grid;
        $fiter = $grid->addColumnText('column', 'Column')->setFilterSelect();
        Assert::type('\Grido\Components\Filters\Select', $fiter);

        $grid = new Grid;
        $fiter = $grid->addColumnText('column', 'Column')->setFilterNumber();
        Assert::type('\Grido\Components\Filters\Number', $fiter);

        $grid = new Grid;
        $fiter = $grid->addColumnText('column', 'Column')->setFilterCustom(new Nette\Forms\Controls\TextArea);
        Assert::type('\Grido\Components\Filters\Custom', $fiter);
    }
}

run(__FILE__);
