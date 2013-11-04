<?php

/**
 * Test: Href column.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

require_once __DIR__ . '/../bootstrap.php';

use Tester\Assert,
    Grido\Grid;

test(function() {
    $grid = new Grid;
    $testRow = array('column' => 'http://google.cz');

    $column = $grid->addColumnHref('column', 'Column');
    Assert::same('<a href="http://google.cz" target="_blank">http://google.cz</a>', (string) $column->render($testRow));

    $column->setReplacement(array('http://google.cz' => 'http://google.com'));
    Assert::same('<a href="http://google.com" target="_blank">http://google.com</a>', (string) $column->render($testRow));

    $column->setTruncate(15);
    Assert::same("<a href=\"http://google.com\" target=\"_blank\" title=\"http://google.com\">http://google…</a>", (string) $column->render($testRow));

    Assert::same('<a href="&amp;lt;script&amp;gt;alert(&amp;quot;XSS&amp;quot;)&amp;lt;/script&amp;gt;" target="_blank">&amp;lt;script&amp;gt;alert(&amp;quot;XSS&amp;quot;)&amp;lt;/script&amp;gt;</a>', (string) $column->render(array('column' => '<script>alert("XSS")</script>')));
});
