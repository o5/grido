<?php

/**
 * Test: Dibi fluent.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

namespace Grido\Tests;

use Tester\Assert,
    Grido\Grid,
    Grido\Components\Filters\Condition;

require_once __DIR__ . '/DataSource.TestCase.php';

class DibiFluentTest extends DataSourceTestCase
{
    /** @var \DibiFluent */
    public $fluent;

    function setUp()
    {
        $that = $this;
        Helper::grid(function(Grid $grid, TestPresenter $presenter) use ($that) {
            $that->fluent = $presenter->context->dibi_sqlite
                ->select('u.*, c.title AS country')
                ->from('[user] u')
                ->join('[country] c')->on('u.country_code = c.code');

            $grid->setModel($that->fluent);
            $grid->setDefaultPerPage(3);

            $grid->addColumnText('firstname', 'Firstname')
                ->setSortable();
            $grid->addColumnText('surname', 'Surname');
            $grid->addColumnText('gender', 'Gender');
            $grid->addColumnText('telephonenumber', 'Phone');

            $grid->addFilterText('name', 'Name')
                ->setColumn('surname')
                ->setColumn('firstname', Condition::OPERATOR_AND);

            $grid->addColumnText('country', 'Country')
                ->setSortable()
                ->setFilterText()
                    ->setSuggestion();

            $grid->addFilterCheck('male', 'Only male')
                ->setCondition(array(
                    TRUE => array('gender', '= ?', 'male')
                ));

            $grid->setExport();

        })->run();
    }

    function testSetWhere()
    {
        $that = $this;
        Helper::grid(function(Grid $grid) use ($that) {
            $grid->setModel($that->fluent);
            $grid->addFilterCheck('male', 'Only male')
                ->setWhere(function($value, \DibiFluent $fluent) {
                    Assert::true($value);
                    $fluent->where('[gender] = %s', 'male');
                });

        })->run(array('grid-filter' => array('male' => TRUE)));

        Helper::$grid->data;
        Assert::same(19, Helper::$grid->count);
    }
}

run(__FILE__);
