<?php

/**
 * Test: Nette Database.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

namespace Grido\Tests;

use Tester\Assert,
    Grido\Grid,
    Grido\Components\Filters\Condition;

require_once __DIR__ . '/TestCase.php';

class NetteDatabaseTest extends DataSourceTestCase
{
    function setUp()
    {
        Helper::grid(function(Grid $grid, TestPresenter $presenter) {
            $grid->setModel($presenter->context->ndb_sqlite->table('user'));
            $grid->setDefaultPerPage(3);

            $grid->addColumnText('firstname', 'Firstname')
                ->setSortable();
            $grid->addColumnText('surname', 'Surname');
            $grid->addColumnText('gender', 'Gender');
            $grid->addColumnText('phone', 'Phone')
                ->setColumn('telephonenumber')
                ->setFilterText();

            $grid->addFilterText('name', 'Name')
                ->setColumn('surname')
                ->setColumn('firstname', Condition::OPERATOR_AND)
                ->setSuggestion(function(\Nette\Database\Table\ActiveRow $row) {
                    return $row['firstname'];
            });

            $grid->addColumnText('country', 'Country')
                ->setSortable()
                ->setColumn('country.title')
                ->setFilterText()
                    ->setSuggestion(function($row) { return $row->country->title; });

            $grid->addFilterCheck('male', 'Only male')
                ->setCondition(array(
                    TRUE => array('gender', '= ?', 'male')
                ));

            $grid->addFilterCheck('tall', 'Only tall')
                ->setWhere(function($value, \Nette\Database\Table\Selection $fluent) {
                    Assert::true($value);
                    $fluent->where('[centimeters] >= ?', 180);
                });

            $grid->setExport();

        })->run();
    }
}

run(__FILE__);
