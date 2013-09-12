<?php

/**
 * Test: Href action.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Helper.inc.php';

use Tester\Assert,
    Grido\Grid;

test(function() {
    $testRow = array('id' => 2, 'firstname' => 'Lucie');
    Helper::grid(function(Grid $grid) use ($testRow) {
        $grid->addActionHref('delete', 'Delete')
            ->setCustomHref(function($row) use ($testRow) {
                Assert::same($testRow, $row);
                return "/edit/{$row['id']}/{$row['firstname']}/";
            });
    });

    Helper::request();

    ob_start();
        Helper::$grid->getAction('delete')->render($testRow);
    Assert::same('<a class="grid-action-delete btn btn-mini" href="/edit/2/Lucie/">Delete</a>', ob_get_clean());
});
