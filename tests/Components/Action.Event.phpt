<?php

/**
 * Test: Event action.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

namespace Grido\Tests;

use Tester\Assert,
    Grido\Tests\Helper,
    Grido\Grid,
    Grido\Components\Actions\Event;

require_once __DIR__ . '/../bootstrap.php';

class ActionEventTest extends \Tester\TestCase
{
    function testRender()
    {
        Helper::grid(function(Grid $grid) {
            $grid->addActionEvent('delete', 'Delete', function() {});
        })->run();

        ob_start();
            Helper::$grid->getAction('delete')->render(['id' => 3]);
        $output = ob_get_clean();
        Assert::same('<a class="grid-action-delete" href="/test/?grid-actions-delete-id=3&amp;do=grid-actions-delete-click">Delete</a>', $output);
    }

    function testSetOnclick()
    {
        $parameters = ['grid-actions-delete-id' => 'value', 'do' => 'grid-actions-delete-click'];
        $onClick = function($id, Event $event) {
            Assert::same('value', $id);
        };

        // set over constructor
        Helper::grid(function(Grid $grid) use ($onClick) {
            $action = $grid->addActionEvent('delete', 'Delete', $onClick);
            Assert::same($action->getOnClick(), $onClick);
        })->run($parameters);

        // set over setter
        Helper::grid(function(Grid $grid) use ($onClick) {
            $action = $grid->addActionEvent('delete', 'Delete')->setOnclick($onClick);
            Assert::same($action->getOnClick(), $onClick);
        })->run($parameters);

        // set over public property
        Helper::grid(function(Grid $grid) use ($onClick) {
            $action = $grid->addActionEvent('delete', 'Delete');
            $action->onClick = $onClick;
            Assert::same($action->getOnClick(), $onClick);
        })->run($parameters);
    }

    function testError()
    {
        Assert::exception(function() {
            Helper::grid(function(Grid $grid) {
                $grid->setModel([]);
                $grid->addColumnText('test', 'Test');
                $grid->addActionEvent('delete', 'Delete');
                $grid->render();
            })->run();
        }, '\Grido\Exception', "Callback onClick in action 'delete' must be set.");
    }

    function testHandleClick()
    {
        Helper::grid(function(Grid $grid) {
            $grid->addActionEvent('delete', 'Delete', function($id) {
                Assert::same(1, $id);
            });
        })->run();

        Helper::$grid->getAction('delete')->handleClick(1);
    }
}

run(__FILE__);