<?php

/**
 * Test: Action.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

namespace Grido\Tests;

use Tester\Assert,
    Grido\Grid;

require_once __DIR__ . '/../bootstrap.php';

class ActionTest extends \Tester\TestCase
{
    function testSetElementPrototype()
    {
        Helper::grid(function(Grid $grid){
            $element = \Nette\Utils\Html::el('a')
                ->setClass(array('action'))
                ->setText('Edit');
            $grid->addActionHref('edit', 'Edit')->setElementPrototype($element);
        })->run();

        ob_start();
            Helper::$grid->getAction('edit')->render(array('id' => 11));
        $output = ob_get_clean();
        Assert::same('<a class="action" href="/?id=11&amp;action=edit&amp;presenter=Test">Edit</a>', $output);
    }

    function testSetCustomRender()
    {
        $testRow = array('id' => 11, 'column' => 'value');
        Helper::grid(function(Grid $grid) use ($testRow) {
            $grid->addActionHref('edit', 'Edit')
                ->setCustomRender(function($row, \Nette\Utils\Html $element) use ($testRow) {
                    Assert::same($testRow, $row);
                    unset($element->class);
                    $element->setText('TEST');
                    return $element;
                });
        })->run();

        ob_start();
            Helper::$grid->getAction('edit')->render($testRow);
        $output = ob_get_clean();
        Assert::same('<a href="/?id=11&amp;action=edit&amp;presenter=Test">TEST</a>', $output);
    }

    function testSetPrimaryKey()
    {
        Helper::grid(function(Grid $grid){
            $grid->addActionHref('edit', 'Edit')
                ->setPrimaryKey('primary');
        })->run();

        ob_start();
            Helper::$grid->getAction('edit')->render(array('primary' => 11));
        $output = ob_get_clean();
        Assert::same('<a class="grid-action-edit" href="/?primary=11&amp;action=edit&amp;presenter=Test">Edit</a>', $output);

        Assert::error(function(){
            Helper::$grid->getAction('edit')->render(array('id' => 11));
        }, 'Symfony\Component\PropertyAccess\Exception\NoSuchIndexException');
    }

    function testSetDisable()
    {
        Helper::grid(function(Grid $grid){
            $grid->addActionHref('delete', 'Delete')
                ->setDisable(function($row){
                    return $row['status'] == 'delete';
                });
        })->run();

        ob_start();
            Helper::$grid->getAction('delete')->render(array('id' => 2, 'status' => 'delete'));
        $output = ob_get_clean();
        Assert::same('', $output);

        ob_start();
            Helper::$grid->getAction('delete')->render(array('id' => 3, 'status' => 'published'));
        $output = ob_get_clean();
        Assert::same('<a class="grid-action-delete" href="/?id=3&amp;action=delete&amp;presenter=Test">Delete</a>', $output);
    }

    function testSetConfirm()
    {
        //test string
        Helper::grid(function(Grid $grid){
            $grid->addActionHref('delete', 'Delete')
                ->setConfirm('Are you sure?');
        })->run();

        ob_start();
            Helper::$grid->getAction('delete')->render(array('id' => 2));
        $output = ob_get_clean();
        Assert::same('<a class="grid-action-delete" href="/?id=2&amp;action=delete&amp;presenter=Test" data-grido-confirm="Are you sure?">Delete</a>', $output);

        //test callback
        $testRow = array('id' => 2, 'firstname' => 'Lucie');
        Helper::grid(function(Grid $grid) use ($testRow) {
            $grid->addActionHref('delete', 'Delete')
                ->setConfirm(function($row) use ($testRow) {
                    Assert::same($testRow, $row);
                    return "Are you sure you want to delete {$row['firstname']}?";
                });
        })->run();

        ob_start();
            Helper::$grid->getAction('delete')->render($testRow);
        $output = ob_get_clean();
        Assert::same('<a class="grid-action-delete" href="/?id=2&amp;action=delete&amp;presenter=Test" data-grido-confirm="Are you sure you want to delete Lucie?">Delete</a>', $output);

        $testRow = array('id' => 2, 'firstname' => 'Lucie');
        Helper::grid(function(Grid $grid) use ($testRow) {
            $grid->translator = new \Grido\Translations\FileTranslator('cs', array('Are you sure you want to delete user %s?' => 'Opravdu chceš smazat uživatele %s?'));
            $grid->addActionHref('delete', 'Delete')
                ->setConfirm(function($row) use ($testRow) {
                    Assert::same($testRow, $row);
                    return array("Are you sure you want to delete user %s?", $row['firstname']);
                });
        })->run();

        ob_start();
            Helper::$grid->getAction('delete')->render($testRow);
        $output = ob_get_clean();
        Assert::same('<a class="grid-action-delete" href="/?id=2&amp;action=delete&amp;presenter=Test" data-grido-confirm="Opravdu chceš smazat uživatele Lucie?">Delete</a>', $output);
    }

    function testSetIcon()
    {
        $grid = new Grid;
        $action = $grid->addActionHref('delete', 'Delete')->setIcon('delete');
        Assert::same('delete', $action->getOption('icon'));
    }

    /**********************************************************************************************/

    function testHasActions()
    {
        $grid = new Grid;
        Assert::false($grid->hasActions());

        $grid->addActionHref('action', 'Action');
        Assert::false($grid->hasActions());
        Assert::true($grid->hasActions(FALSE));
    }

    function testAddAction() //addAction*()
    {
        $grid = new Grid;
        $label = 'Action';

        $name = 'href';
        $destination = 'edit';
        $args = array('args');
        $grid->addActionHref($name, $label, $destination, $args);
        $component = $grid->getAction($name);
        Assert::type('\Grido\Components\Actions\Href', $component);
        Assert::type('\Grido\Components\Actions\Action', $component);
        Assert::same($label, $component->label);
        Assert::same($destination, $component->destination);
        Assert::same($args, $component->arguments);

        $name = 'event';
        $onClick = function() {};
        $grid->addActionEvent($name, $label, $onClick);
        $component = $grid->getAction($name);
        Assert::type('\Grido\Components\Actions\Event', $component);
        Assert::type('\Grido\Components\Actions\Action', $component);
        Assert::same($label, $component->label);
        Assert::same(array($onClick), $component->onClick);

        // getter
        Assert::exception(function() use ($grid) {
            $grid->getAction('action');
        }, 'InvalidArgumentException', "Component with name 'action' does not exist.");
        Assert::null($grid->getAction('action', FALSE));

        $grid = new Grid;
        Assert::null($grid->getAction('action'));
    }
}

run(__FILE__);
