<?php

/**
 * Test: Action's component.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

require_once __DIR__ . '/../bootstrap.php';

use Tester\Assert,
    Grido\Grid;

class ActionTest extends Tester\TestCase
{
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
        Assert::same($label, $component->label);
        Assert::same($destination, $component->destination);
        Assert::same($args, $component->arguments);

        $name = 'event';
        $onClick = function() {};
        $grid->addActionEvent($name, $label, $onClick);
        $component = $grid->getAction($name);
        Assert::type('\Grido\Components\Actions\Event', $component);
        Assert::same($label, $component->label);
        Assert::same(array($onClick), $component->onClick);

        Assert::error(function() use ($grid, $label, $destination, $args) {
            $name = 'deprecated';
            $grid->addAction($name, $label, \Grido\Components\Actions\Action::TYPE_HREF, $destination, $args);
            $component = $grid->getAction($name);
            Assert::type('\Grido\Components\Actions\Href', $component);
            Assert::same($label, $component->label);
            Assert::same($destination, $component->destination);
            Assert::same($args, $component->arguments);
        }, E_USER_DEPRECATED);

        // getter
        Assert::exception(function() use ($grid) {
            $grid->getAction('action');
        }, 'InvalidArgumentException');
        Assert::null($grid->getAction('action', FALSE));

        $grid = new Grid;
        Assert::null($grid->getAction('action'));
    }
}

run(__FILE__);
