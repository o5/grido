<?php

/**
 * Test: Date column.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

use Tester\Assert,
    Grido\Grid,
    Grido\Components\Columns\Date;

require_once __DIR__ . '/../bootstrap.php';

test(function() {
    $grid = new Grid;
    $column = $grid->addColumnDate('column', 'Column')->setReplacement(array(
        NULL => 'NULL', '2012-12-21' => 'The End of the World'
    ));

    Assert::same('NULL', $column->render(array('column' => NULL)));
    Assert::same('NULL', $column->renderExport(array('column' => NULL)));
    Assert::same('The End of the World', $column->render(array('column' => '2012-12-21')));
    Assert::same('The End of the World', $column->renderExport(array('column' => '2012-12-21')));
    Assert::same('01.01.1970', $column->render(array('column' => '<script>alert("XSS")</script>')));
    Assert::same('01.01.1970', $column->renderExport(array('column' => '<script>alert("XSS")</script>')));

    $input = '2012-12-12';
    $output = '12.12.2012';
    Assert::same($output, $column->render(array('column' => $input)));
    Assert::same($output, $column->render(array('column' => new DateTime($input))));
    Assert::same($output, $column->render(array('column' => strtotime($input))));
    Assert::same($output, $column->renderExport(array('column' => $input)));
    Assert::same($output, $column->renderExport(array('column' => new DateTime($input))));
    Assert::same($output, $column->renderExport(array('column' => strtotime($input))));

    $column->setDateFormat(Date::FORMAT_TEXT);
    $output = '12 Dec 2012';
    Assert::same($output, $column->render(array('column' => $input)));
    Assert::same($output, $column->render(array('column' => new DateTime($input))));
    Assert::same($output, $column->renderExport(array('column' => $input)));
    Assert::same($output, $column->renderExport(array('column' => new DateTime($input))));

    $column->setDateFormat(Date::FORMAT_DATETIME);
    $output = '12.12.2012 00:00:00';
    Assert::same($output, $column->render(array('column' => $input)));
    Assert::same($output, $column->render(array('column' => new DateTime($input))));
    Assert::same($output, $column->renderExport(array('column' => $input)));
    Assert::same($output, $column->renderExport(array('column' => new DateTime($input))));
});
