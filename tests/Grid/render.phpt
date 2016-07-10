<?php

/**
 * Test: Render 1.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

namespace Grido\Tests;

use Tester\Assert,
    Grido\Grid;

require_once __DIR__ . '/../bootstrap.php';


test(function()
{
    Helper::grid(function(Grid $grid, TestPresenter $presenter) {
        $data = $presenter->context->getService('dibi_sqlite')
            ->select('u.*, c.title AS country')
            ->from('[user] u')
            ->leftJoin('[country] c')->on('u.country_code = c.code')
            ->fetchAll();
        $grid->setModel($data);
        $grid->defaultPerPage = 4;
        $grid->rowCallback = function(\DibiRow $row, \Nette\Utils\Html $tr) {
            $tr->class[] = $row['firstname'];
            return $tr;
        };

        $grid->addColumnText('firstname', 'Firstname')
            ->setFilterText();

        $grid->addColumnText('surname', 'Surname')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('gender', 'Gender')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnDate('birthday', \Nette\Utils\Html::el('i')->class('icon-birthday'))
            ->setSortable()
            ->setFilterDate();

        $grid->addActionHref('edit', 'Edit')
            ->elementPrototype->addAttributes(['class' => 'xxx']);

        $grid->addActionEvent('delete', 'Delete', function() {})
            ->elementPrototype->class[] = 'yyy';

        $grid->addActionEvent('print', 'Print', function() {})
            ->elementPrototype = \Nette\Utils\Html::el('button');

        $grid->setOperation(['print' => 'Print'], function(){});
        $grid->setExport();

    })->run();

    ob_start();
        Helper::$grid->render();
    $output = ob_get_clean();

    Assert::matchFile(__DIR__ . "/files/render.expect", $output);
});
