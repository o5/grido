<?php

/**
 * Test: Action's event component.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Helper.inc.php';

use Tester\Assert,
    Grido\Grid;

class ActionEventTest extends Tester\TestCase
{
    function testOnclick()
    {
        Helper::grid(function(Grid $grid) {

            $grid->addActionEvent('delete', 'Delete')
                ->onClick[] = function($primaryValue) {
                    Assert::same('value', $primaryValue);
                };
        });

        Helper::request(array('grid-actions-delete-id' => 'value', 'do' => 'grid-actions-delete-click'));
    }
}

run(__FILE__);
