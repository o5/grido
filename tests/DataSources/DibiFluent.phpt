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

require_once __DIR__ . '/TestCase.php';

class DibiFluentTest extends DataSourceTestCase
{
    function setUp()
    {
        $that = $this;
        Helper::grid(function(Grid $grid, TestPresenter $presenter) use ($that) {
            $fluent = $presenter->context->dibi_sqlite
                ->select('u.*, c.title AS country')
                ->from('[user] u')
                ->leftJoin('[country] c')->on('u.country_code = c.code');

            $grid->setModel($fluent);
            $grid->setDefaultPerPage(3);

            $grid->addColumnText('firstname', 'Firstname')
                ->setEditable(callback($that, 'editableCallbackTest'))
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
                ->setFilterText()
                    ->setColumn('c.title')
                    ->setSuggestion('title');

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
