<?php

/**
 * Test: Nette Database.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

namespace Grido\Tests;

use Grido\Components\Exports\CsvExport;
use Tester\Assert,
    Grido\Grid,
    Grido\Components\Filters\Condition;

require_once __DIR__ . '/TestCase.php';

class NetteDatabaseTest extends DataSourceTestCase
{
    function setUp()
    {
        Helper::grid(function(Grid $grid, TestPresenter $presenter) {
            $database = $presenter->context->getByType('Nette\Database\Context');
            $grid->setModel($database->table('user'));
            $grid->setDefaultPerPage(3);

            $grid->addColumnText('firstname', 'Firstname')
                ->setEditable([$this, 'editableCallbackTest'])
                ->setSortable();
            $grid->addColumnText('surname', 'Surname');
            $grid->addColumnText('gender', 'Gender');
            $grid->addColumnText('phone', 'Phone')
                ->setColumn('telephonenumber')
                ->setFilterText();

            $grid->addFilterText('name', 'Name')
                ->setColumn('firstname')
                ->setColumn('surname', Condition::OPERATOR_AND)
                ->setSuggestion('firstname');

            $grid->addColumnText('country', 'Country')
                ->setSortable()
                ->setColumn('country.title')
                ->setFilterText()
                    ->setSuggestion(function($row) { return $row->country->title; });

            $grid->addFilterCheck('male', 'Only male')
                ->setCondition([
                    TRUE => ['gender', '= ?', 'male']
                ]);

            $grid->addFilterCheck('tall', 'Only tall')
                ->setWhere(function($value, \Nette\Database\Table\Selection $fluent) {
                    Assert::true($value);
                    $fluent->where('[centimeters] >= ?', 180);
                });

            $grid->addExport(new CsvExport(), 'csv');

        })->run();
    }
}

run(__FILE__);
