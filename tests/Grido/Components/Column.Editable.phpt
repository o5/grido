<?php

/**
 * Test: Editable column.
 *
 * @author     Jakub Kopřiva <kopriva.jakub@gmail.com>
 * @package    Grido\Tests
 */

namespace Grido\Tests;

use Tester\Assert,
    Grido\Grid,
    Grido\Components\Columns\Editable;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Helper.inc.php';

class EditableTest extends \Tester\TestCase
{
    function testSetEditable() //setFilter*()
    {
        // NOT EDITABLE
        $grid = new Grid();
        $col = $grid->addColumnText('column', 'Column');
        Assert::same(FALSE, $col->editable);
        Assert::same(NULL, $col->editableCallback);
        Assert::type('\Nette\Forms\Controls\TextInput', $col->editableControl);

        // EDITABLE
        $grid = new Grid();
        $col = $grid->addColumnText('column', 'Column')->setEditable();
        Assert::same(TRUE, $col->editable);
        Assert::same(FALSE, $col->editableDisabled);
        Assert::same(NULL, $col->editableCallback);
        Assert::type('\Nette\Forms\Controls\TextInput', $col->editableControl);

        // EDITABLE AND DISABLED
        $grid = new Grid();
        $col = $grid->addColumnText('column', 'Column');
        $col->setEditable();
        $col->disableEditable();
        Assert::same(FALSE, $col->editable);
        Assert::same(TRUE, $col->editableDisabled);
        Assert::same(NULL, $col->editableCallback);
        Assert::type('\Nette\Forms\Controls\TextInput', $col->editableControl);

        $callback = callback($this, 'test');
        $grid = new Grid();
        $col = $grid->addColumnText('column', 'Column');
        $col->setEditable($callback);
        Assert::same(TRUE, $col->editable);
        Assert::same(FALSE, $col->editableDisabled);
        Assert::same($callback, $col->editableCallback);
        Assert::type('\Nette\Forms\Controls\TextInput', $col->editableControl);

        $callback = callback($this, 'test');
        $grid = new Grid();
        $col = $grid->addColumnText('column', 'Column');
        $col->setEditable();
        $col->setEditableCallback($callback);
        Assert::same(TRUE, $col->editable);
        Assert::same(FALSE, $col->editableDisabled);
        Assert::same($callback, $col->editableCallback);
        Assert::type('\Nette\Forms\Controls\TextInput', $col->editableControl);

        $callback = callback($this, 'test');
        $control = new \Nette\Forms\Controls\SelectBox(array('1','2','3'));
        $grid = new Grid();
        $col = $grid->addColumnText('column', 'Column');
        $col->setEditable($callback, $control);
        Assert::same(TRUE, $col->editable);
        Assert::same(FALSE, $col->editableDisabled);
        Assert::same($callback, $col->editableCallback);
        Assert::same($control, $col->editableControl);

        $callback = callback($this, 'test');
        $control = new \Nette\Forms\Controls\SelectBox(array('1','2','3'));
        $grid = new Grid();
        $col = $grid->addColumnText('column', 'Column');
        $col->setEditable();
        $col->setEditableControl($control);
        Assert::same(TRUE, $col->editable);
        Assert::same(FALSE, $col->editableDisabled);
        Assert::same(NULL, $col->editableCallback);
        Assert::same($control, $col->editableControl);
    }

    function handleEditable() {
        // Not Implemented yet
    }

    function handleEditableControl() {
        // Not Implemented yet
    }
}

run(__FILE__);

