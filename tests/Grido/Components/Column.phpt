<?php

/**
 * Test: Column's component.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Helper.inc';

use Tester\Assert,
    Grido\Grid,
    Grido\Components\Columns\Column;

class ColumnTest extends Tester\TestCase
{
    function testSetDefaultSort()
    {
        $grid = new Grid;
        $grid->setDefaultSort(array('a' => 'ASC', 'b' => 'desc', 'c' => 'Asc', 'd' => Column::DESC));
        Assert::same(array('a' => Column::ASC, 'b' => Column::DESC, 'c' => Column::ASC, 'd' => Column::DESC), $grid->defaultSort);

        Assert::exception(function() use ($grid) {
            $grid->setDefaultSort(array('a' => 'up'));
        }, 'InvalidArgumentException', "Dir 'up' for column 'a' is not allowed.");

        Assert::error(function() {
            $grid = new Grid;
            $grid->setModel(array());
            $grid->setDefaultSort(array('a' => 'asc'));
            $grid->getData();
        }, E_USER_NOTICE, "Column with name 'a' does not exist.");

        $grid = new Grid;
        $data = array(
            array('A' => 'A1', 'B' => 'B3'),
            array('A' => 'A2', 'B' => 'B2'),
            array('A' => 'A3', 'B' => 'B1'),
        );
        $grid->setModel($data);
        $grid->addColumnText('B', 'B');
        $grid->setDefaultSort(array('B' => 'asc'));
        $grid2 = clone $grid;

        $expected = array(
            array('A' => 'A3', 'B' => 'B1'),
            array('A' => 'A2', 'B' => 'B2'),
            array('A' => 'A1', 'B' => 'B3'),
        );
        Assert::same($expected, $grid->data);

        $grid2->sort['B'] = Column::DESC;
        Assert::same($data, $grid2->data);
    }

    /**********************************************************************************************/

    function testHandleSort()
    {
        Helper::grid(function(Grid $grid) {
            $grid->setDefaultPerPage(2);
            $grid->setModel(array(
                array('A' => 'A1', 'B' => 'B3'),
                array('A' => 'A2', 'B' => 'B2'),
                array('A' => 'A3', 'B' => 'B1'),
            ));
            $grid->addColumnText('B', 'B')->setSortable();
        });

        Helper::request(array('grid-page' => 2, 'grid-sort' => array('B' => Column::ASC), 'do' => 'grid-sort'));

        Assert::same(1, Helper::$grid->page); //test reset page after sorting
        Assert::same(array(
            array('A' => 'A3', 'B' => 'B1'),
            array('A' => 'A2', 'B' => 'B2'),
        ), Helper::$grid->data);
    }

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
        Assert::same($label, $component->label);

        $name = 'mail';
        $grid->addColumnMail($name, $label);
        $component = $grid->getColumn($name);
        Assert::type('\Grido\Components\Columns\Mail', $component);
        Assert::same($label, $component->label);

        $name = 'href';
        $grid->addColumnHref($name, $label);
        $component = $grid->getColumn($name);
        Assert::type('\Grido\Components\Columns\Href', $component);
        Assert::same($label, $component->label);

        $name = 'date';
        $format = \Grido\Components\Columns\Date::FORMAT_DATE;
        $grid->addColumnDate($name, $label, $format);
        $component = $grid->getColumn($name);
        Assert::type('\Grido\Components\Columns\Date', $component);
        Assert::same($label, $component->label);
        Assert::same($format, $component->dateFormat);

        $name = 'number';
        $decimals = 1;
        $decPoint = ',';
        $thousandsSep = '.';
        $grid->addColumnNumber($name, $label, $decimals, $decPoint, $thousandsSep);
        $component = $grid->getColumn($name);
        Assert::type('\Grido\Components\Columns\Number', $component);
        Assert::same($label, $component->label);
        Assert::same(array(
            \Grido\Components\Columns\Number::NUMBER_FORMAT_DECIMALS => $decimals,
            \Grido\Components\Columns\Number::NUMBER_FORMAT_DECIMAL_POINT => $decPoint,
            \Grido\Components\Columns\Number::NUMBER_FORMAT_THOUSANDS_SEPARATOR => $thousandsSep
        ), $component->numberFormat);

        Assert::error(function() use ($grid, $label) {
            $name = 'deprecated';
            $grid->addColumn($name, $label, \Grido\Components\Columns\Column::TYPE_DATE);
            $component = $grid->getColumn($name);
            Assert::type('\Grido\Components\Columns\Text', $component);
            Assert::same($label, $component->label);
        }, E_USER_DEPRECATED);

        // getter
        Assert::exception(function() use ($grid) {
            $grid->getColumn('column');
        }, 'InvalidArgumentException');
        Assert::same(NULL, $grid->getColumn('column', FALSE));

        $grid = new Grid;
        Assert::null($grid->getColumn('column'));
    }
}

run(__FILE__);
