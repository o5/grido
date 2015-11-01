<?php

/**
 * Test: Number column.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

require_once __DIR__ . '/../bootstrap.php';

use Tester\Assert,
    Grido\Grid;

test(function() {
    $grid = new Grid;

    $column = $grid->addColumnNumber('column', 'Column');
    Assert::same('12,346', $column->render(['column' => 12345.99]));

    $column->setNumberFormat(1, ',', '.');
    Assert::same('12.345,6', $column->render(['column' => '12345.55']));

    Assert::same('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;a', $column->render(['column' => '<script>alert("XSS")</script>a']));
});
