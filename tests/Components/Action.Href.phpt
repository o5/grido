<?php

/**
 * Test: Href action.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

namespace Grido\Tests;

use Tester\Assert,
    Grido\Tests\Helper,
    Grido\Grid;

require_once __DIR__ . '/../bootstrap.php';

class ActionHrefTest extends \Tester\TestCase
{
    function testRender()
    {
        Helper::grid(function(Grid $grid) {
            $grid->addActionHref('delete', 'Delete');
        })->run();

        ob_start();
            Helper::$grid->getAction('delete')->render(['id' => 3]);
        $output = ob_get_clean();
        Assert::same('<a class="grid-action-delete" href="/test/delete/3">Delete</a>', $output);

        ob_start();
            Helper::$grid->getAction('delete')->render(['id' => 1]);
        $output = ob_get_clean();
        Assert::same('<a class="grid-action-delete" href="/test/delete/1">Delete</a>', $output);

        ob_start();
            Helper::$grid->getAction('delete')->render(['id' => NULL]);
        $output = ob_get_clean();
        Assert::same('<a class="grid-action-delete" href="/test/delete">Delete</a>', $output);

        ob_start();
            Helper::$grid->getAction('delete')->render(['id' => FALSE]);
        $output = ob_get_clean();
        Assert::same('<a class="grid-action-delete" href="/test/delete/0">Delete</a>', $output);

        ob_start();
            Helper::$grid->getAction('delete')->render(['id' => 0]);
        $output = ob_get_clean();
        Assert::same('<a class="grid-action-delete" href="/test/delete/0">Delete</a>', $output);
    }

    function testSetCustomHref()
    {
        $testRow = ['id' => 2, 'firstname' => 'Lucie'];
        Helper::grid(function(Grid $grid) use ($testRow) {
            $grid->addActionHref('delete', 'Delete')
                ->setCustomHref(function($row) use ($testRow) {
                    Assert::same($testRow, $row);
                    return "/edit/{$row['id']}/{$row['firstname']}/";
                });
        })->run();

        ob_start();
            Helper::$grid->getAction('delete')->render($testRow);
        $output = ob_get_clean();
        Assert::same('<a class="grid-action-delete" href="/edit/2/Lucie/">Delete</a>', $output);
    }

    function testDestinationAndArguments()
    {
        Helper::grid(function(Grid $grid) {
            $destination = 'test';
            $arguments = ['test' => 'test'];

            $action = $grid->addActionHref('delete', 'Delete', $destination, $arguments);
            Assert::same($destination, $action->getDestination());
            Assert::same($arguments, $action->getArguments());
            Assert::same('<a class="grid-action-delete" href="/test/test/3?test=test">Delete</a>', (string) $action->getElement(['id' => 3]));

        })->run();
    }
}

run(__FILE__);
