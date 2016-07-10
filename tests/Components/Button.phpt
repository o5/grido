<?php

/**
 * Test: Button.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

namespace Grido\Tests;

use Tester\Assert,
    Grido\Grid;

require_once __DIR__ . '/../bootstrap.php';

class ButtonTest extends \Tester\TestCase
{
    function testSetElementPrototype()
    {
        Helper::grid(function(Grid $grid){
            $element = \Nette\Utils\Html::el('a')
                ->setClass(['button'])
                ->setText('Add');
            $grid->addButton('add', 'Add')->setElementPrototype($element);
        })->run();

        ob_start();
        Helper::$grid->getButton('add')->render();
        $output = ob_get_clean();
        Assert::same('<a class="button" href="/test/add">Add</a>', $output);
    }

    function testSetIcon()
    {
        $grid = new Grid;
        $action = $grid->addButton('add', 'Add')->setIcon('plus');
        Assert::same('plus', $action->getOption('icon'));
    }

    function testSetOption()
    {
        $grid = new Grid;
        $button = $grid->addButton('add', 'Add')
            ->setOption('another', 'test')
            ->setOption('test', 'test')
            ->setOption('test', NULL);
        Assert::same(NULL, $button->getOption('test'));
        Assert::same('test', $button->getOption('another'));
        Assert::same(['another' => 'test'], $button->getOptions());
    }

    function testRender()
    {
        Helper::grid(function(Grid $grid) {
            $grid->addButton('add', 'Add');
        })->run();

        ob_start();
        Helper::$grid->getButton('add')->render();
        $output = ob_get_clean();
        Assert::same('<a class="grid-button-add" href="/test/add">Add</a>', $output);
    }

    /**********************************************************************************************/

    function testHasButtons()
    {
        $grid = new Grid;
        Assert::false($grid->hasButtons());

        $grid->addButton('add', 'Add');
        Assert::false($grid->hasButtons());
        Assert::true($grid->hasButtons(FALSE));
    }

    function testAddButton()
    {
        $grid = new Grid;
        $label = 'Add';

        $name = 'edit';
        $destination = 'add';
        $args = ['args'];
        $grid->addButton($name, $label, $destination, $args);
        $component = $grid->getButton($name);
        Assert::type('\Grido\Components\Button', $component);
        Assert::same($label, $component->label);
        Assert::same($destination, $component->destination);
        Assert::same($args, $component->arguments);

        // getter
        Assert::exception(function() use ($grid) {
            $grid->getButton('test');
        }, 'InvalidArgumentException', "Component with name 'test' does not exist.");
        Assert::null($grid->getButton('test', FALSE));

        $grid = new Grid;
        Assert::null($grid->getButton('action'));
    }
}

run(__FILE__);
