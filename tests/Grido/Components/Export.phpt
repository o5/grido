<?php

/**
 * Test: Export.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

namespace Grido\Tests;

use Tester\Assert,
    Grido\Grid,
    Grido\Tests\Helper,
    Grido\Components\Export;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Helper.inc.php';

class ExportTest extends \Tester\TestCase
{
    function testHasExport()
    {
        $grid = new Grid;
        Assert::false($grid->hasExport());

        $grid->setExport();
        Assert::false($grid->hasExport());
        Assert::true($grid->hasExport(FALSE));
    }

    function testSetExport()
    {
        $grid = new Grid;
        $label = 'export';

        $grid->setExport($label);
        $component = $grid->getExport();
        Assert::type('\Grido\Components\Export', $component);
        Assert::same($label, $component->label);

        Helper::grid(function(Grid $grid) {
            $grid->setExport();
            $component = $grid->getExport();
            Assert::same('Grid', $component->label);
        })->run();

        unset($grid[Export::ID]);
        // getter
        Assert::exception(function() use ($grid) {
            $grid->getExport();
        }, 'InvalidArgumentException');
    }
}

run(__FILE__);
