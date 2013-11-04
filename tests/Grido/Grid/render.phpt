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
require_once __DIR__ . '/../Helper.inc.php';


test(function()
{
    Helper::grid(function(Grid $grid) {
        $grid->model = json_decode(file_get_contents(__DIR__ . '/../DataSources/files/users.json'), 1);
        $grid->defaultPerPage = 4;
        $grid->rowCallback = function(array $row, \Nette\Utils\Html $tr) {
            $tr->class[] = $row['firstname'];
            return $tr;
        };

        $grid->addColumnText('firstname', 'Firstname')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('surname', 'Surname')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('gender', 'Gender')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnDate('birthday', 'Birthday')
            ->setSortable()
            ->setFilterDate();

        $grid->addActionHref('edit', 'Edit')
            ->elementPrototype->addAttributes(array('class' => 'xxx'));

        $grid->addActionEvent('delete', 'Delete')
            ->elementPrototype->class[] = 'yyy';

        $grid->addActionEvent('print', 'Print')
            ->elementPrototype = \Nette\Utils\Html::el('button');

        $grid->setOperation(array('print' => 'Print'), function(){});
        $grid->setExport();

    })->run();

    ob_start();
        Helper::$grid->render();
    $output = ob_get_clean();

    Assert::matchFile(__DIR__ . "/files/render.expect", $output);
});
