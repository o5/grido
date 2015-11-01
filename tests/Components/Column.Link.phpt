<?php

/**
 * Test: Link column.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

require_once __DIR__ . '/../bootstrap.php';

use Tester\Assert,
    Grido\Grid;

test(function() {
    $grid = new Grid;
    $testRow = ['column' => 'http://support.google.com'];

    $column = $grid->addColumnLink('column', 'Column');
    Assert::same('<a href="http://support.google.com" target="_blank" rel="noreferrer">support.google.com</a>', (string) $column->render($testRow));

    $column->setReplacement(['http://support.google.com' => 'https://support.google.com']);
    Assert::same('<a href="https://support.google.com" target="_blank" rel="noreferrer">support.google.com</a>', (string) $column->render($testRow));

    $column->setTruncate(15);
    Assert::same('<a href="https://support.google.com" target="_blank" rel="noreferrer" title="https://support.google.com">support.google…</a>', (string) $column->render($testRow));

    $xss = ['column' => '<script>alert("XSS")</script>'];
    Assert::same('<a href="http://&amp;lt;script&amp;gt;alert(&amp;quot;XSS&amp;quot;)&amp;lt;/script&amp;gt;" target="_blank" rel="noreferrer">&amp;lt;script&amp;gt;alert(&amp;quot;XSS&amp;quot;)&amp;lt;/script&amp;gt;</a>', (string) $column->render($xss));

    $testRow = ['column' => 'www.google.com'];
    Assert::same('<a href="http://www.google.com" target="_blank" rel="noreferrer">www.google.com</a>', (string) $column->render($testRow));

    $testRow = ['column' => 'ftp://google.com'];
    Assert::same('<a href="ftp://google.com" target="_blank" rel="noreferrer">ftp://google.com</a>', (string) $column->render($testRow));
});
