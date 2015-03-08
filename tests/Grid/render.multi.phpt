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



test(function() {

    $baseGrid = function($grid, TestPresenter $presenter) {
        $data = $presenter->context->dibi_sqlite
            ->select('u.*, c.title AS country')
            ->from('[user] u')
            ->leftJoin('[country] c')->on('u.country_code = c.code')
            ->fetchAll();
        $grid->setModel($data);
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

    Helper::grid(function($grid, TestPresenter $presenter) use ($baseGrid) {
        $grid->filterRenderType = Filter::RENDER_INNER;
        $baseGrid($grid, $presenter);
    })->run();

    ob_start();
        Helper::$grid->render();
    $gridOne = ob_get_clean();

    /*****************************************************************************************/

    Helper::grid(function($grid, TestPresenter $presenter) use ($baseGrid, $addAction) {
        $grid->filterRenderType = Filter::RENDER_INNER;
        $baseGrid($grid, $presenter);
        $addAction($grid);
    })->run();

    ob_start();
        Helper::$grid->render();
    $gridTwo = ob_get_clean();

    /*****************************************************************************************/

    Helper::grid(function($grid, TestPresenter $presenter) use ($baseGrid, $addFilters) {
        $grid->filterRenderType = Filter::RENDER_INNER;
        $baseGrid($grid, $presenter);
        $addFilters($grid);
    })->run();

    ob_start();
        Helper::$grid->render();
    $gridThree = ob_get_clean();

    /*****************************************************************************************/

    Helper::grid(function($grid, TestPresenter $presenter) use ($baseGrid, $addAction, $addFilters) {
        $grid->filterRenderType = Filter::RENDER_INNER;
        $baseGrid($grid, $presenter);
        $addAction($grid);
        $addFilters($grid);
    })->run();

    ob_start();
        Helper::$grid->render();
    $gridFour = ob_get_clean();

    /*****************************************************************************************/

    Helper::grid(function($grid, TestPresenter $presenter) use ($baseGrid) {
        $grid->filterRenderType = Filter::RENDER_OUTER;
        $baseGrid($grid, $presenter);
    })->run();

    ob_start();
        Helper::$grid->render();
    $gridFive = ob_get_clean();

    /*****************************************************************************************/

    Helper::grid(function($grid, TestPresenter $presenter) use ($baseGrid, $addAction) {
        $grid->filterRenderType = Filter::RENDER_OUTER;
        $baseGrid($grid, $presenter);
        $addAction($grid);
    })->run();

    ob_start();
        Helper::$grid->render();
    $gridSix = ob_get_clean();

    /*****************************************************************************************/

    Helper::grid(function($grid, TestPresenter $presenter) use ($baseGrid, $addFilters) {
        $grid->filterRenderType = Filter::RENDER_OUTER;
        $baseGrid($grid, $presenter);
        $addFilters($grid);
    })->run();

    ob_start();
        Helper::$grid->render();
    $gridSeven = ob_get_clean();

    /*****************************************************************************************/

    Helper::grid(function($grid, TestPresenter $presenter) use ($baseGrid, $addAction, $addFilters) {
        $grid->filterRenderType = Filter::RENDER_OUTER;
        $baseGrid($grid, $presenter);
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
