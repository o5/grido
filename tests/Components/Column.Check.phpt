<?php

/**
 * Test: Check column.
 *
 * @author     Tomáš Pilař
 * @package    Grido\Tests
 */

require_once __DIR__ . '/../bootstrap.php';

use Tester\Assert,
	Grido\Grid;

test(function() {
	$grid = new Grid;

	$column = $grid->addColumnCheck('column', 'Column');
	Assert::same('Yes', $column->render(array('column' => TRUE)));
	Assert::same('No', $column->render(array('column' => FALSE)));

	Assert::same('Yes', $column->render(array('column' => 1)));
	Assert::same('No', $column->render(array('column' => 0)));

	Assert::same('Yes', $column->render(array('column' => 't')));
	Assert::same('No', $column->render(array('column' => '')));

	Assert::same('No', $column->render(array('column' => NULL)));
});
