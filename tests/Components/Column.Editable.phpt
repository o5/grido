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

require_once __DIR__ . '/../DataSources/files/doctrine/entities/Country.php';
require_once __DIR__ . '/../DataSources/files/doctrine/entities/User.php';

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
        $rowCallback = callback($this, 'test');
        $grid = new Grid();
        $column = $grid->addColumnText('column', 'Column')->setEditable();
        $column->setEditableValueCallback($valueCallback);
        $column->setEditableRowCallback($rowCallback);
        Assert::same(TRUE, $column->editable);
        Assert::same(FALSE, $column->editableDisabled);
        Assert::same($valueCallback, $column->editableValueCallback);
        Assert::same($rowCallback, $column->editableRowCallback);

        Helper::grid(function(Grid $grid) {
            $grid->setModel(array());
            $grid->addColumnText('text', 'Text');
            $grid->addColumnNumber('number', 'Number');
            $grid->addColumnDate('date', 'Date');
            $grid->addColumnLink('link', 'Link');
            $grid->addColumnEmail('email', 'Email');
            $grid->setEditableColumns();
        })->run();

        Helper::$grid->onRender(Helper::$grid);

        foreach (Helper::$grid->getComponent(\Grido\Components\Columns\Column::ID)->getComponents() as $column) {
            Assert::type('\Grido\Components\Columns\Editable', $column);
            Assert::true($column->isEditable());
        }
    }

    function testSetEditableValueCallback()
    {
        Helper::grid(function(Grid $grid) {
            $row = array('id' => 1, 'name' => 'Lucy');
            $grid->setModel(array($row));
            $column = $grid->addColumnText('name', 'Name')
                ->setEditableValueCallback(function(array $item, \Grido\Components\Columns\Text $column) use ($row) {
                    Assert::same($row, $item);
                    return $item['name'] . '-TEST';
                });

            Assert::same('<td class="grid-cell-name editable" data-grido-editable-value="Lucy-TEST"></td>', (string) $column->getCellPrototype($row));

        })->run();
    }

    function testSetEditableRowCallback()
    {
        Helper::grid(function(Grid $grid) {
            $grid->setModel(array());
            $grid->presenter->forceAjaxMode = TRUE;
            $grid->addColumnText('firstname', 'Firstname')
                ->setEditable(function() {})
                ->setCustomRender(function() {});

            Assert::exception(function() use ($grid) {
                $grid->render();
            }, 'Grido\Exception', "Column 'firstname' has error: You must define callback via setEditableRowCallback().");

        })->run();

        $testedId = 2;
        Helper::grid(function(Grid $grid) use ($testedId) {
            $grid->setModel(array());
            $grid->presenter->forceAjaxMode = TRUE;
            $grid->addColumnText('firstname', 'Firstname')
                ->setEditable(function() {return TRUE;})
                ->setCustomRender(function($item) {return $item['firstname'] . '-TEST';})
                ->setEditableRowCallback(function($id, \Grido\Components\Columns\Text $column) use ($testedId) {
                    Assert::same($testedId, $id);
                    return array('firstname' => 'Lucy');
                });

            ob_start();
            $grid->render();
            ob_clean();
        });

        ob_start();
            Helper::request(array(
                'do' => 'grid-columns-firstname-editable',
                'grid-columns-firstname-id' => $testedId,
                'grid-columns-firstname-newValue' => 'newValue',
                'grid-columns-firstname-oldValue' => 'oldValue',
            ));
        $output = ob_get_clean();
        Assert::same('{"updated":true,"html":"Lucy-TEST"}', $output);
    }

    function testEditableCallback()
    {
        $checkException = function($grid) {
            Assert::exception(function() use ($grid) {
                $grid->render();
            }, 'Grido\Exception', "Column 'firstname' has error: You must define callback via setEditableCallback().");
        };

        //array source
        Helper::grid(function(Grid $grid) use ($checkException) {
            $grid->setModel(array());
            $grid->presenter->forceAjaxMode = TRUE;
            $grid->addColumnText('firstname', 'Firstname')
                ->setEditable();

            $checkException($grid);
        })->run();

        //dibi
        Helper::grid(function(Grid $grid, TestPresenter $presenter) use ($checkException) {
            $fluent = $presenter->context->dibi_sqlite
                ->select('u.*, c.title AS country')
                ->from('[user] u')
                ->join('[country] c')->on('u.country_code = c.code');
            $grid->setModel($fluent);
            $grid->addColumnText('firstname', 'Firstname')
                ->setEditable();

            $checkException($grid);
        })->run();

        //doctrine
        Helper::grid(function(Grid $grid, TestPresenter $presenter) use ($checkException) {
            $entityManager = $presenter->context->getByType('Doctrine\ORM\EntityManager');
            $repository = $entityManager->getRepository('Grido\Tests\Entities\User');
            $model = new \Grido\DataSources\Doctrine(
                $repository->createQueryBuilder('a') // We need to create query builder with inner join.
                    ->addSelect('c')                 // This will produce less SQL queries with prefetch.
                    ->innerJoin('a.country', 'c'),
                array('country' => 'c.title'));      // Map country column to the title of the Country entity

            $grid->setModel($model);
            $grid->addColumnText('firstname', 'Firstname')
                ->setEditable();

            $checkException($grid);
        })->run();

        //nette database
        Helper::grid(function(Grid $grid, TestPresenter $presenter) {
            $database = $presenter->context->getByType('Nette\Database\Context');
            $grid->setModel($database->table('user'), TRUE);
            $grid->addColumnText('firstname', 'Firstname')
                ->setEditable();

            ob_start();
            $grid->render();
            ob_clean();
        })->run();
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
            $database = new \Nette\Database\Context($connection);

            $grid->setModel($database->table('user'));
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
            $database = new \Nette\Database\Context($connection);

            $grid->setModel($database->table('user'));
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
            $grid->addColumnText('firstname', 'Firstname')->setEditable(function() {}, new TextInput);
        });

        ob_start();
            Helper::request(array(
                'do' => 'grid-columns-firstname-editableControl',
                'grid-columns-firstname-value' => 'Test',
            ));
        $output = ob_get_clean();
        Assert::same('<input type="text" name="editfirstname" id="frm-grid-form-editfirstname" value="Test">', $output);
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
