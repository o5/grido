<?php

/**
 * Test: Event action.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Helper.inc.php';

use Tester\Assert,
    Grido\Grid;

test(function() {
    Helper::grid(function(Grid $grid) {
        $grid->addActionEvent('delete', 'Delete')
            ->onClick[] = function($primaryValue) {
                Assert::same('value', $primaryValue);
            };
    })->run(array('grid-actions-delete-id' => 'value', 'do' => 'grid-actions-delete-click'));
});
