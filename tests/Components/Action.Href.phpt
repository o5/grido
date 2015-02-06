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
            Helper::$grid->getAction('delete')->render(array('id' => 3));
        $output = ob_get_clean();
        Assert::same('<a class="grid-action-delete" href="/?id=3&amp;action=delete&amp;presenter=Test">Delete</a>', $output);

        ob_start();
            Helper::$grid->getAction('delete')->render(array('id' => 1));
        $output = ob_get_clean();
        Assert::same('<a class="grid-action-delete" href="/?id=1&amp;action=delete&amp;presenter=Test">Delete</a>', $output);

        ob_start();
            Helper::$grid->getAction('delete')->render(array('id' => NULL));
        $output = ob_get_clean();
        Assert::same('<a class="grid-action-delete" href="/?action=delete&amp;presenter=Test">Delete</a>', $output);

        ob_start();
            Helper::$grid->getAction('delete')->render(array('id' => FALSE));
        $output = ob_get_clean();
        Assert::same('<a class="grid-action-delete" href="/?id=0&amp;action=delete&amp;presenter=Test">Delete</a>', $output);

        ob_start();
            Helper::$grid->getAction('delete')->render(array('id' => 0));
        $output = ob_get_clean();
        Assert::same('<a class="grid-action-delete" href="/?id=0&amp;action=delete&amp;presenter=Test">Delete</a>', $output);
    }

    function testSetCustomHref()
    {
        $testRow = array('id' => 2, 'firstname' => 'Lucie');
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
}

run(__FILE__);
