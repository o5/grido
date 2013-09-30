<?php

/**
 * Test: Href action.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

use Tester\Assert,
    Grido\Tests\Helper,
    Grido\Grid;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Helper.inc.php';

test(function() {
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
    Assert::same('<a class="grid-action-delete btn btn-mini" href="/edit/2/Lucie/">Delete</a>', $output);
});
