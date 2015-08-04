<?php

/**
 * Test: Event action.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

use Tester\Assert,
    Grido\Tests\Helper,
    Grido\Grid;

require_once __DIR__ . '/../bootstrap.php';

test(function() {
    Helper::grid(function(Grid $grid) {
        $grid->addActionEvent('delete', 'Delete')
            ->onClick[] = function($primaryValue) {
                Assert::same('value', $primaryValue);
            };
    })->run(array('grid-actions-delete-id' => 'value', 'do' => 'grid-actions-delete-click'));
});
