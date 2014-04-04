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

    function testHandleEditable() {

        $oldValue = 'Trommler';
        $newValue = 'Test';

        ob_start();
        Helper::grid(function(Grid $grid) {
            $grid->setModel($grid->presenter->context->ndb_sqlite->table('user'));
            $grid->presenter->forceAjaxMode = TRUE;
            $grid->addColumnText('firstname', 'Firstname')
                    ->setEditable();
            $grid->addColumnText('surname', 'Surname');
            $grid->addColumnText('gender', 'Gender');
        });

        Helper::request(array(
            'do' => 'grid-columns-firstname-editable',
            'grid-columns-firstname-id' => 1,
            'grid-columns-firstname-newValue' => $newValue,
            'grid-columns-firstname-oldValue' => $oldValue
        ));
        ob_clean();

        $fn = Helper::$grid->data->select('*')->where('id',1)->fetch()->firstname;
        Assert::same($fn, $newValue);

        ob_start();
        Helper::grid(function(Grid $grid) {
            $grid->setModel($grid->presenter->context->ndb_sqlite->table('user'));
            $grid->presenter->forceAjaxMode = TRUE;
            $grid->addColumnText('firstname', 'Firstname')
                    ->setEditable();
            $grid->addColumnText('surname', 'Surname');
            $grid->addColumnText('gender', 'Gender');
        });

        Helper::request(array(
            'do' => 'grid-columns-firstname-editable',
            'grid-columns-firstname-id' => 1,
            'grid-columns-firstname-newValue' => $oldValue,
            'grid-columns-firstname-oldValue' => $newValue
        ));
        ob_clean();

        $fn = Helper::$grid->data->select('*')->where('id',1)->fetch()->firstname;
        Assert::same($fn, $oldValue);
    }

    function testHandleEditableControl() {
        ob_start();
        Helper::grid(function(Grid $grid) {
            $grid->setModel(array());
            $grid->presenter->forceAjaxMode = TRUE;
            $grid->addColumnText('firstname', 'Firstname')
                ->setEditable();
        });

        Helper::request(array(
            'do' => 'grid-columns-firstname-editableControl',
            'grid-columns-firstname-value' => 'Test',
        ));

        $response = ob_get_clean();

        // This test just wont work...

        Assert::same('<input type="text" name="editinterpret" class="form-control" id="frm-nazevDataGridu-form-editinterpret" required data-nette-rules=\'[{"op":":filled","msg":"This field is required."}]\' value="Test">', $response);
    }
}

run(__FILE__);

