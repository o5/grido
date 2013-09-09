<?php

/**
 * Test: Action's "href" component.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Helper.inc';

use Tester\Assert,
    Grido\Grid;

class ActionHrefTest extends Tester\TestCase
{
    function testSetCustomHref()
    {
        $testItem = array('id' => 2, 'firstname' => 'Lucie');
        Helper::grid(function(Grid $grid) use ($testItem) {
            $grid->addActionHref('delete', 'Delete')
                ->setCustomHref(function($item) use ($testItem) {
                    Assert::same($testItem, $item);
                    return "/edit/{$item['id']}/{$item['firstname']}/";
                });
        });

        Helper::request();

        ob_start();
            Helper::$grid->getAction('delete')->render($testItem);
        Assert::same('<a class="grid-action-delete btn btn-mini" href="/edit/2/Lucie/">Delete</a>', ob_get_clean());
    }
}

run(__FILE__);
