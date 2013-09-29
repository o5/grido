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
        $column = $grid->addColumnText('column', 'Column')->setReplacement(array('value' => 'new_value'));
        Assert::same('new_value', $column->render(array('column' => 'value')));
        Assert::same('normal', $column->render(array('column' => 'normal')));

        $value = new stdClass;
        Assert::same($value, $column->render(array('column' => $value)));

        $column = $grid->addColumnText('date', 'Date')->setReplacement(array(NULL => 'NEVER', '' => 'NEVER'));
        Assert::same('NEVER', $column->render(array('date' => '')));
        Assert::same('NEVER', $column->render(array('date' => NULL)));
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
        $grid->addColumnText('column', 'Column')->setDefaultSort(Column::DESC);
        Assert::same(array('column' => Column::DESC), $grid->defaultSort);
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
            $grid->addColumnText('column', 'Column')->setCustomRender(__DIR__ . '/files/Column.customRender.latte');
        })->run();

        ob_start();
            Helper::$grid->render();
        $node = Tester\DomQuery::fromHtml(ob_get_clean())->find('.grid-cell-column');
        Assert::same('CUSTOM_TEMPLATE-TEST', trim((string) $node[0]));
    }

    function testSetCustomRenderExport()
    {
        $grid = new Grid;
        $column = $grid->addColumnText('column', 'Column')->setCustomRenderExport(function($row) {
            return 'CUSTOM_RENDER_EXPORT-' . $row['column'];
        });
        Assert::same('CUSTOM_RENDER_EXPORT-TEST', $column->renderExport(array('column' => 'TEST')));
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
        $node = Tester\DomQuery::fromHtml(ob_get_clean())->find('.grid-cell-column');
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
            $grid->addColumnText('column', 'Column')->setDefaultSort(Column::ASC);
            $grid->data;
        })->run();

        Assert::same(Column::ASC, Helper::$grid->getColumn('column')->sort);
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
            'value' => 'new_value', 'html' => Nette\Utils\Html::el('b')->setText('html')
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

        $name = 'mail';
        $grid->addColumnMail($name, $label);
        $component = $grid->getColumn($name);
        Assert::type('\Grido\Components\Columns\Mail', $component);
        Assert::type('\Grido\Components\Columns\Text', $component);
        Assert::same($label, $component->label);

        $name = 'href';
        $grid->addColumnHref($name, $label);
        $component = $grid->getColumn($name);
        Assert::type('\Grido\Components\Columns\Href', $component);
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

        Assert::error(function() use ($grid, $label) {
            $name = 'deprecated';
            $grid->addColumn($name, $label, \Grido\Components\Columns\Column::TYPE_DATE);
            $component = $grid->getColumn($name);
            Assert::type('\Grido\Components\Columns\Date', $component);
            Assert::type('\Grido\Components\Columns\Column', $component);
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
