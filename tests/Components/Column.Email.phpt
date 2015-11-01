<?php

/**
 * Test: Email column.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

require_once __DIR__ . '/../bootstrap.php';

use Tester\Assert,
    Grido\Grid;

test(function() {
    $grid = new Grid;
    $testRow = ['column' => 'spam@bugyik.cz'];

    $column = $grid->addColumnEMail('column', 'Column');
    Assert::same('<a href="mailto:spam&#64;bugyik.cz">spam@bugyik.cz</a>', (string) $column->render($testRow));

    $column->setReplacement(['spam@bugyik.cz' => 'noreply@bugyik.cz']);
    Assert::same('<a href="mailto:noreply&#64;bugyik.cz">noreply@bugyik.cz</a>', (string) $column->render($testRow));

    $column->setTruncate(15);
    Assert::same('<a href="mailto:noreply&#64;bugyik.cz" title="noreply&#64;bugyik.cz">noreply@bugyik…</a>', (string) $column->render($testRow));

    Assert::same('<a href="mailto:&amp;lt;script&amp;gt;alert(&amp;quot;XSS&amp;quot;)&amp;lt;/script&amp;gt;a">&amp;lt;script&amp;gt;alert(&amp;quot;XSS&amp;quot;)&amp;lt;/script&amp;gt;a</a>', (string) $column->render(['column' => '<script>alert("XSS")</script>a']));
});
