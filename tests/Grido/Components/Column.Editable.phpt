<?php

/**
 * Test: Editable column.
 *
 * @author     Jakub Kopřiva <kopriva.jakub@gmail.com>
 * @package    Grido\Tests
 */

namespace Grido\Tests;

use Tester\Assert,
    Grido\Grid;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Helper.inc.php';

class EditableTest extends \Tester\TestCase
{
    function testSetEditable()
    {
        // NOT EDITABLE
        $grid = new Grid();
        $column = $grid->addColumnText('column', 'Column');
        Assert::same(FALSE, $column->editable);
        Assert::same(NULL, $column->editableCallback);
        Assert::type('\Nette\Forms\Controls\TextInput', $column->editableControl);

        // EDITABLE
        $grid = new Grid();
        $column = $grid->addColumnText('column', 'Column')->setEditable();
        Assert::same(TRUE, $column->editable);
        Assert::same(FALSE, $column->editableDisabled);
        Assert::same(NULL, $column->editableCallback);
        Assert::type('\Nette\Forms\Controls\TextInput', $column->editableControl);

        // EDITABLE AND DISABLED
        $grid = new Grid();
        $column = $grid->addColumnText('column', 'Column')->setEditable();
        $column->disableEditable();
        Assert::same(FALSE, $column->editable);
        Assert::same(TRUE, $column->editableDisabled);
        Assert::same(NULL, $column->editableCallback);
        Assert::type('\Nette\Forms\Controls\TextInput', $column->editableControl);

        // EDITABLE AND AN OWN CALLBACK VIA PARAM
        $callback = callback($this, 'test');
        $grid = new Grid();
        $column = $grid->addColumnText('column', 'Column')->setEditable($callback);
        Assert::same(TRUE, $column->editable);
        Assert::same(FALSE, $column->editableDisabled);
        Assert::same($callback, $column->editableCallback);
        Assert::type('\Nette\Forms\Controls\TextInput', $column->editableControl);

        // EDITABLE AND AN OWN CALLBACK VIA METHOD
        $callback = callback($this, 'test');
        $grid = new Grid();
        $column = $grid->addColumnText('column', 'Column')->setEditable();
        $column->setEditableCallback($callback);
        Assert::same(TRUE, $column->editable);
        Assert::same(FALSE, $column->editableDisabled);
        Assert::same($callback, $column->editableCallback);
        Assert::type('\Nette\Forms\Controls\TextInput', $column->editableControl);

        // EDITABLE AND AN OWN CALLBACK, CONTROL VIA PARAM
        $callback = callback($this, 'test');
        $control = new \Nette\Forms\Controls\SelectBox(array('1','2','3'));
        $grid = new Grid();
        $column = $grid->addColumnText('column', 'Column')->setEditable($callback, $control);
        Assert::same(TRUE, $column->editable);
        Assert::same(FALSE, $column->editableDisabled);
        Assert::same($callback, $column->editableCallback);
        Assert::same($control, $column->editableControl);

        // EDITABLE AND AN OWN CONTROL VIA METHOD
        $callback = callback($this, 'test');
        $control = new \Nette\Forms\Controls\SelectBox(array('1','2','3'));
        $grid = new Grid();
        $column = $grid->addColumnText('column', 'Column')->setEditable();
        $column->setEditableControl($control);
        Assert::same(TRUE, $column->editable);
        Assert::same(FALSE, $column->editableDisabled);
        Assert::same(NULL, $column->editableCallback);
        Assert::same($control, $column->editableControl);

        // EDITABLE AND AN OWN VALUE CALLBACK VIA METHOD
        $valueCallback = callback($this, 'test');
        $grid = new Grid();
        $column = $grid->addColumnText('column', 'Column')->setEditable();
        $column->setEditableValueCallback($valueCallback);
        Assert::same(TRUE, $column->editable);
        Assert::same(FALSE, $column->editableDisabled);
        Assert::same($valueCallback, $column->editableValueCallback);

        Helper::grid(function(Grid $grid) {
            $grid->setModel(array());
            $grid->addColumnText('text', 'Text');
            $grid->addColumnNumber('number', 'Number');
            $grid->addColumnDate('date', 'Date');
            $grid->addColumnHref('href', 'Href');
            $grid->addColumnEmail('email', 'Email');
            $grid->setEditableColumns();
        })->run();

        foreach (Helper::$grid->getComponent(\Grido\Components\Columns\Column::ID)->getComponents() as $column) {
            Assert::type('\Grido\Components\Columns\Editable', $column);
            Assert::true($column->isEditable());
        }
    }

    function testHandleEditable()
    {
        $oldValue = 'Trommler';
        $newValue = 'Test';
        $id = 1;

        //copy current db
        $database = __DIR__  .  '/../DataSources/files/users.s3db';
        $editableSuffix = '.editable';
        copy($database, $database . $editableSuffix);

        Helper::grid(function(Grid $grid) use ($editableSuffix) {
            $dsn = $grid->presenter->context->ndb_sqlite->getDsn() . $editableSuffix;
            $connection = new \Nette\Database\Connection($dsn);

            $grid->setModel($connection->table('user'));
            $grid->presenter->forceAjaxMode = TRUE;
            $grid->addColumnText('firstname', 'Firstname')->setEditable();
            $grid->addColumnText('surname', 'Surname');
            $grid->addColumnText('gender', 'Gender');
        });

        ob_start();
            Helper::request(array(
                'do' => 'grid-columns-firstname-editable',
                'grid-columns-firstname-id' => $id,
                'grid-columns-firstname-newValue' => $newValue,
                'grid-columns-firstname-oldValue' => $oldValue
            ));
        ob_clean();

        //TEST INSIDE EDITABLE CALLBACK
        Helper::grid(function(Grid $grid) use ($editableSuffix, $newValue, $oldValue, $id) {

            $dsn = $grid->presenter->context->ndb_sqlite->getDsn() . $editableSuffix;
            $connection = new \Nette\Database\Connection($dsn);

            $grid->setModel($connection->table('user'));
            $grid->presenter->forceAjaxMode = TRUE;
            $grid->addColumnText('firstname', 'Firstname')->setEditable(
                function($_id, $_newValue, $_oldValue, $_column) use ($newValue, $oldValue, $id) {
                    Assert::same($_id, $id);
                    Assert::same($_newValue, $newValue);
                    Assert::same($_oldValue, $oldValue);
                    Assert::type('Grido\Components\Columns\Editable',$_column);
                    return true;
                });
            $grid->addColumnText('surname', 'Surname');
            $grid->addColumnText('gender', 'Gender');
        });

        ob_start();
            Helper::request(array(
                'do' => 'grid-columns-firstname-editable',
                'grid-columns-firstname-id' => $id,
                'grid-columns-firstname-newValue' => $newValue,
                'grid-columns-firstname-oldValue' => $oldValue
            ));
        ob_clean();

        //cleaup
        unlink($database . $editableSuffix);
    }

    function testHandleEditableControl()
    {
        Helper::grid(function(Grid $grid) {
            $grid->setModel(array());
            $grid->presenter->forceAjaxMode = TRUE;
            $grid->addColumnText('firstname', 'Firstname')->setEditable(NULL, new TextInput);
        });

        ob_start();
            Helper::request(array(
                'do' => 'grid-columns-firstname-editableControl',
                'grid-columns-firstname-value' => 'Test',
            ));
        $output = ob_get_clean();
        Assert::same('<input type="text" name="editfirstname" id="frmform-editfirstname" value="Test" />', $output);
    }
}

class TextInput extends \Nette\Forms\Controls\TextInput
{
    public function getControl()
    {
        return new Html(parent::getControl());
    }
}

class Html extends \Nette\Utils\Html
{
    private $control;

    public function __construct($control)
    {
        $this->control = $control;
    }

    public function render($indent = NULL)
    {
        print $this->control->render();
    }
}

$test = new EditableTest();
$test->run();
