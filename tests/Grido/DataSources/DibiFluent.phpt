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
            $grid->addColumnText('phone', 'Phone')
                ->setColumn('telephonenumber')
                ->setFilterText();

            $grid->addFilterText('name', 'Name')
                ->setColumn('surname')
                ->setColumn('firstname', Condition::OPERATOR_AND)
                ->setSuggestion(function(\DibiRow $row) {
                    return $row['firstname'];
                });

            $grid->addColumnText('country', 'Country')
                ->setSortable()
                ->setFilterText()
                    ->setSuggestion();

            $grid->addFilterCheck('male', 'Only male')
                ->setCondition(array(
                    TRUE => array('gender', '= ?', 'male')
                ));

            $grid->addFilterCheck('tall', 'Only tall')
                ->setWhere(function($value, \DibiFluent $fluent) {
                    Assert::true($value);
                    $fluent->where('[centimeters] >= %i', 180);
                });

            $grid->setExport();

        })->run();
    }
}

run(__FILE__);
