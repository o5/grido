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
        Helper::grid(function(Grid $grid, TestPresenter $presenter) {
            $fluent = $presenter->context->getService('dibi_sqlite')
                ->select('u.*, c.title AS country')
                ->from('[user] u')
                ->leftJoin('[country] c')->on('u.country_code = c.code');

            $grid->setModel($fluent);
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
                ->setFilterText()
                    ->setColumn('c.title')
                    ->setSuggestion('title');

            $grid->addFilterCheck('male', 'Only male')
                ->setCondition([
                    TRUE => ['gender', '= ?', 'male']
                ]);

            $grid->addFilterCheck('tall', 'Only tall')
                ->setWhere(function($value, \DibiFluent $fluent) {
                    Assert::true($value);
                    $fluent->where('[centimeters] >= %i', 180);
                });

            $limit = 100;
            $export = $grid->setExport()->setFetchLimit($limit);
            Assert::same($limit, $export->getFetchLimit());

        })->run();
    }
}

run(__FILE__);
