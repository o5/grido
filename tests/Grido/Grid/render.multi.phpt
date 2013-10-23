<?php

/**
 * Test: Multi-render.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

namespace Grido\Tests;

use Tester\Assert,
    Grido\Components\Filters\Filter;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Helper.inc.php';



test(function() {

    $baseGrid = function($grid) {
        $grid->model = json_decode(file_get_contents(__DIR__ . '/../DataSources/files/users.json'), 1);
        $grid->defaultPerPage = 2;
        $grid->addColumnText('firstname', 'Firstname');
        $grid->addColumnText('surname', 'Surname');
        $grid->addColumnText('gender', 'Gender');
        $grid->addColumnText('birthday', 'Birthday');
    };

    $addFilters = function($grid) {
        $grid->getColumn('firstname')->setFilterText();
        $grid->getColumn('surname')->setFilterText();
        $grid->getColumn('gender')->setFilterText();
        $grid->getColumn('birthday')->setFilterDate();
    };

    $addAction = function($grid) {
        $grid->addActionHref('edit', 'Edit');
    };

    /*****************************************************************************************/

    Helper::grid(function($grid) use ($baseGrid) {
        $grid->filterRenderType = Filter::RENDER_INNER;
        $baseGrid($grid);
    })->run();

    ob_start();
        Helper::$grid->render();
    $gridOne = ob_get_clean();

    /*****************************************************************************************/

    Helper::grid(function($grid) use ($baseGrid, $addAction) {
        $grid->filterRenderType = Filter::RENDER_INNER;
        $baseGrid($grid);
        $addAction($grid);
    })->run();

    ob_start();
        Helper::$grid->render();
    $gridTwo = ob_get_clean();

    /*****************************************************************************************/

    Helper::grid(function($grid) use ($baseGrid, $addFilters) {
        $grid->filterRenderType = Filter::RENDER_INNER;
        $baseGrid($grid);
        $addFilters($grid);
    })->run();

    ob_start();
        Helper::$grid->render();
    $gridThree = ob_get_clean();

    /*****************************************************************************************/

    Helper::grid(function($grid) use ($baseGrid, $addAction, $addFilters) {
        $grid->filterRenderType = Filter::RENDER_INNER;
        $baseGrid($grid);
        $addAction($grid);
        $addFilters($grid);
    })->run();

    ob_start();
        Helper::$grid->render();
    $gridFour = ob_get_clean();

    /*****************************************************************************************/

    Helper::grid(function($grid) use ($baseGrid) {
        $grid->filterRenderType = Filter::RENDER_OUTER;
        $baseGrid($grid);
    })->run();

    ob_start();
        Helper::$grid->render();
    $gridFive = ob_get_clean();

    /*****************************************************************************************/

    Helper::grid(function($grid) use ($baseGrid, $addAction) {
        $grid->filterRenderType = Filter::RENDER_OUTER;
        $baseGrid($grid);
        $addAction($grid);
    })->run();

    ob_start();
        Helper::$grid->render();
    $gridSix = ob_get_clean();

    /*****************************************************************************************/

    Helper::grid(function($grid) use ($baseGrid, $addFilters) {
        $grid->filterRenderType = Filter::RENDER_OUTER;
        $baseGrid($grid);
        $addFilters($grid);
    })->run();

    ob_start();
        Helper::$grid->render();
    $gridSeven = ob_get_clean();

    /*****************************************************************************************/

    Helper::grid(function($grid) use ($baseGrid, $addAction, $addFilters) {
        $grid->filterRenderType = Filter::RENDER_OUTER;
        $baseGrid($grid);
        $addAction($grid);
        $addFilters($grid);
    })->run();

    ob_start();
        Helper::$grid->render();
    $gridEight = ob_get_clean();

    /*****************************************************************************************/

//    $output = $gridOne . $gridTwo . $gridThree . $gridFour . $gridFive . $gridSix . $gridSeven . $gridEight;
    $output = $gridOne . $gridTwo . $gridThree . $gridFour;
    Assert::matchFile(__DIR__ . "/files/render.multi.1.expect", $output);

    $output = $gridFive . $gridSix . $gridSeven . $gridEight;
    Assert::matchFile(__DIR__ . "/files/render.multi.2.expect", $output);
});
