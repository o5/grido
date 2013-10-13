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

require_once __DIR__ . '/DataSource.TestCase.php';

class NetteDatabaseTests extends DataSourceTestCase
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

            $grid->addFilterText('name', 'Name')
                ->setColumn('surname')
                ->setColumn('firstname', Condition::OPERATOR_AND);

            $renderer =function($row) { return $row->country->title; };
            $grid->addColumnText('country', 'Country')
                ->setSortable()
                ->setColumn('country.title') //for ordering/filtering
                ->setCustomRender($renderer)
                ->setCustomRenderExport($renderer)
                ->setFilterText()
                    ->setSuggestion($renderer);

            $grid->addFilterCheck('male', 'Only male')
                ->setCondition(array(
                    TRUE => array('gender', '= ?', 'male')
                ));

            $grid->setExport();

        })->run();
    }

    function testSetWhere()
    {
        Helper::grid(function(Grid $grid, TestPresenter $presenter) {
            $grid->setModel($presenter->context->ndb_sqlite->table('user'));
            $grid->addFilterCheck('male', 'Only male')
                ->setWhere(function($value, \Nette\Database\Table\Selection $connection) {
                    Assert::true($value);
                    $connection->where('gender = ?' ,'male');
                });

        })->run(array('grid-filter' => array('male' => TRUE)));

        Helper::$grid->data;
        Assert::same(19, Helper::$grid->count);
    }
}

run(__FILE__);
