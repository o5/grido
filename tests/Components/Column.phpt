<?php

/**
 * Test: Column.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

namespace Grido\Tests;

use Tester\Assert,
    Grido\Grid,
    Grido\Components\Columns\Column;

require_once __DIR__ . '/../bootstrap.php';

class ColumnTest extends \Tester\TestCase
{
    function testSetSortable()
    {
        $grid = new Grid;
        $column = $grid->addColumnText('column', 'Column')->setSortable();
        Assert::true($column->isSortable());

        $column->setSortable(FALSE);
        Assert::false($column->isSortable());
    }

    function testSetReplacement()
    {
        $grid = new Grid;
        $column = $grid->addColumnText('column', 'Column')->setReplacement(array('value' => 'new_value', 'replace' => '%value it!'));
        Assert::same('new_value', $column->render(array('column' => 'value')));
        Assert::same('unknown', $column->render(array('column' => 'unknown')));
        Assert::same('replace it!', $column->render(array('column' => 'replace'))); //Column::VALUE_IDENTIFIER

        $value = new \stdClass;
        Assert::same($value, $column->render(array('column' => $value)));

        $column->setReplacement(array('value' => 'new_value', NULL => 'IS NULL'));
        Assert::same('IS NULL', $column->render(array('column' => NULL)));

        $column->setReplacement(array('value' => 'new_value', '' => 'IS EMPTY'));
        Assert::same('IS NULL', $column->render(array('column' => '')));

        $column->setReplacement(array(TRUE => 'Yes', FALSE => 'No'));
        Assert::same('No', $column->render(array('column' => FALSE)));
    }

    function testSetColumn()
    {
        $grid = new Grid;
        $column = $grid->addColumnText('column', 'Column');
        Assert::same('column', $column->column);

        $column->setColumn('new_column');
        Assert::same('new_column', $column->column);
    }

    function testSetDefaultSort()
    {
        $grid = new Grid;
        $grid->addColumnText('column', 'Column')->setDefaultSort(Column::ORDER_DESC);
        Assert::same(array('column' => Column::ORDER_DESC), $grid->defaultSort);
    }

    function testSetCustomRender()
    {
        $grid = new Grid;
        $column = $grid->addColumnText('column', 'Column')->setCustomRender(function($row) {
            return 'CUSTOM_RENDER-' . $row['column'];
        });
        Assert::same('CUSTOM_RENDER-TEST', $column->render(array('column' => 'TEST')));

        Helper::grid(function(Grid $grid){
            $grid->setModel(array(array('id' => 1, 'column' => 'TEST')));
            $grid->addColumnText('column', 'Column')->setCustomRender(__DIR__ . '/files/Column.customRender.latte', array('var' => 'TEST'));
        })->run();

        ob_start();
            Helper::$grid->render();
        $output = ob_get_clean();
        $node = \Tester\DomQuery::fromHtml($output)->find('.grid-cell-column');
        Assert::same('TEST-CUSTOM_TEMPLATE-TEST', trim((string) $node[0]));
    }

    function testSetCustomRenderExport()
    {
        $grid = new Grid;
        $test = array('column' => 'TEST');
        $column = $grid->addColumnText('column', 'Column')->setCustomRenderExport(function($row) use ($test) {
            Assert::same($row, $test);
            return 'CUSTOM_RENDER_EXPORT-' . $row['column'];
        });
        Assert::same('CUSTOM_RENDER_EXPORT-TEST', $column->renderExport($test));
    }

    function testSetTruncate()
    {
        $grid = new Grid;
        $column = $grid->addColumnText('column', 'Column')->setTruncate(5);
        Assert::same("valu…", $column->render(array('column' => 'valuee')));

        $grid = new Grid;
        $column = $grid->addColumnText('column', 'Column')->setTruncate(5, '--');
        Assert::same('val--', $column->render(array('column' => 'valuee')));
    }

    function testSetCellCallback()
    {
        Helper::grid(function(Grid $grid) {
            $testRow = array('id' => 1, 'column' => 'Value');
            $grid->setModel(array($testRow));
            $grid->addColumnText('column', 'Column')->setCellCallback(function($row, $td) use ($testRow) {
                Assert::same($testRow, $row);
                $td->class[] = 'test_class';
                return $td;
            });
        })->run();

        ob_start();
            Helper::$grid->render();
        $output = ob_get_clean();
        $node = \Tester\DomQuery::fromHtml($output)->find('.grid-cell-column');
        Assert::same('grid-cell-column test_class', (string) $node[0]->attributes());
    }

    function testGetCellPrototype()
    {
        $grid = new Grid;
        $column = $grid->addColumnText('column', 'Column');
        Assert::type('\Nette\Utils\Html', $column->getCellPrototype());
        Assert::same('td', $column->getCellPrototype()->getName());
    }

    function testGetHeaderPrototype()
    {
        $grid = new Grid;
        $column = $grid->addColumnText('column', 'Column');
        Assert::type('\Nette\Utils\Html', $column->getHeaderPrototype());
        Assert::same('th', $column->getHeaderPrototype()->getName());
    }

    function testGetSort()
    {
        $grid = new Grid;
        $column = $grid->addColumnText('column', 'Column');
        Assert::null($column->sort);

        Helper::grid(function(Grid $grid){
            $grid->setModel(array());
            $grid->addColumnText('column', 'Column')->setDefaultSort(Column::ORDER_ASC);
            $grid->data;
        })->run();

        Assert::same(Column::ORDER_ASC, Helper::$grid->getColumn('column')->sort);
    }

    function testHasFilter()
    {
        $grid = new Grid;
        $column = $grid->addColumnText('column', 'Column');
        Assert::false($column->hasFilter());

        $grid = new Grid;
        $grid->addColumnText('column', 'Column')->setFilterText();
        Assert::true($grid->getColumn('column')->hasFilter());
    }

    function testRender()
    {
        $grid = new Grid;
        $column = $grid->addColumnText('column', 'Column');
        Assert::same('test', $column->render(array('column' => 'test')));
        Assert::same('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', $column->render(array('column' => '<script>alert("XSS")</script>')));
    }

    function testRenderExport()
    {
        $grid = new Grid;
        $column = $grid->addColumnText('column', 'Column')->setReplacement(array(
            'value' => 'new_value', 'html' => \Nette\Utils\Html::el('b')->setText('html')
        ));
        Assert::same('new_value', $column->renderExport(array('column' => 'value')));
        Assert::same('test', $column->renderExport(array('column' => 'test')));
        Assert::same('html', $column->renderExport(array('column' => 'html')));
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
        Assert::type('\Grido\Components\Columns\Column', $component);
        Assert::same($label, $component->label);

        $name = 'foo.bar';
        $grid->addColumnText($name, $label);
        $component = $grid->getColumn($name);
        Assert::type('\Grido\Components\Columns\Text', $component);
        Assert::type('\Grido\Components\Columns\Column', $component);
        Assert::same($label, $component->label);

        $name = 'email';
        $grid->addColumnEmail($name, $label);
        $component = $grid->getColumn($name);
        Assert::type('\Grido\Components\Columns\Email', $component);
        Assert::type('\Grido\Components\Columns\Text', $component);
        Assert::same($label, $component->label);

        $name = 'link';
        $grid->addColumnLink($name, $label);
        $component = $grid->getColumn($name);
        Assert::type('\Grido\Components\Columns\Link', $component);
        Assert::type('\Grido\Components\Columns\Text', $component);
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
        }, 'Nette\InvalidArgumentException');
        Assert::same(NULL, $grid->getColumn('column', FALSE));

        $grid = new Grid;
        Assert::null($grid->getColumn('column'));
    }

    function testSetFilter() //setFilter*()
    {
        $name = 'column';
        $label = 'Column';

        $grid = new Grid;
        $fiter = $grid->addColumnText($name, $label)->setFilterText();
        Assert::type('\Grido\Components\Filters\Text', $fiter);
        Assert::same($name, $fiter->name);
        Assert::same($label, $fiter->label);

        $grid = new Grid;
        $fiter = $grid->addColumnText($name, $label)->setFilterDate();
        Assert::type('\Grido\Components\Filters\Date', $fiter);
        Assert::same($name, $fiter->name);
        Assert::same($label, $fiter->label);

        $grid = new Grid;
        $fiter = $grid->addColumnText($name, $label)->setFilterDateRange();
        Assert::type('\Grido\Components\Filters\DateRange', $fiter);
        Assert::same($name, $fiter->name);
        Assert::same($label, $fiter->label);

        $grid = new Grid;
        $fiter = $grid->addColumnText($name, $label)->setFilterCheck();
        Assert::type('\Grido\Components\Filters\Check', $fiter);
        Assert::same($name, $fiter->name);
        Assert::same($label, $fiter->label);

        $grid = new Grid;
        $items = array('one' => 'One');
        $fiter = $grid->addColumnText($name, $label)->setFilterSelect($items);
        Assert::type('\Grido\Components\Filters\Select', $fiter);
        Assert::same($name, $fiter->name);
        Assert::same($label, $fiter->label);
        Assert::same($items, $fiter->control->items);

        $grid = new Grid;
        $fiter = $grid->addColumnText($name, $label)->setFilterNumber();
        Assert::type('\Grido\Components\Filters\Number', $fiter);
        Assert::same($name, $fiter->name);
        Assert::same($label, $fiter->label);

        $grid = new Grid;
        $fiter = $grid->addColumnText($name, $label)->setFilterCustom(new \Nette\Forms\Controls\TextArea);
        Assert::type('\Grido\Components\Filters\Custom', $fiter);
        Assert::same($name, $fiter->name);
    }
}

run(__FILE__);
