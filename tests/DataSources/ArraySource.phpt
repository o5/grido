<?php

/**
 * Test: ArraySource.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

namespace Grido\Tests;

use Tester\Assert,
    Grido\Grid,
    Grido\Components\Filters\Condition;

require_once __DIR__ . '/TestCase.php';

class ArraySourceTest extends DataSourceTestCase
{
    function setUp()
    {
        $that = $this;
        Helper::grid(function(Grid $grid, TestPresenter $presenter) use ($that) {
            $data = $presenter->context->dibi_sqlite
                ->select('u.*, c.title AS country')
                ->from('[user] u')
                ->leftJoin('[country] c')->on('u.country_code = c.code')
                ->fetchAll();
            $grid->setModel($data);
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
                    ->setSuggestion();

            $grid->addFilterCheck('male', 'Only male')
                ->setCondition(array(
                    TRUE => array('gender', '= ?', 'male')
                ));

            $grid->addFilterCheck('tall', 'Only tall')
                ->setWhere(function($value, $row) {
                    Assert::true($value);
                    return $row['centimeters'] >= 180;
                });

            $grid->setExport();

        })->run();
    }

    function testCompare()
    {
        $source = new \Grido\DataSources\ArraySource(array());

        Assert::true($source->compare('Lucie', 'LIKE ?', '%Lu%'));
        Assert::true($source->compare('Lucie', 'LIKE ?', '%ie'));
        Assert::true($source->compare('Lucie', 'LIKE ?', 'lu%'));
        Assert::true($source->compare('Lucie/Lucy', 'LIKE ?', 'Lucie/L%'));
        Assert::false($source->compare('Lucie', 'LIKE ?', 'ie%'));
        Assert::false($source->compare('Lucie', 'LIKE ?', '%lu'));
        Assert::true($source->compare('Žluťoučký kůň', 'LIKE ?', 'zlutou%'));
        Assert::true($source->compare('Žluťoučký kůň', 'LIKE ?', 'žlutou%'));

        Assert::true($source->compare('Lucie', '=', 'Lucie'));
        Assert::false($source->compare('Lucie', '=', 'lucie'));

        Assert::true($source->compare('Lucie', '<>', 'Petr'));
        Assert::false($source->compare('Lucie', '<>', 'Lucie'));

        Assert::true($source->compare(NULL, 'IS NULL', NULL));
        Assert::false($source->compare('', 'IS NULL', NULL));

        Assert::true($source->compare('', 'IS NOT NULL', NULL));
        Assert::true($source->compare('NULL', 'IS NOT NULL', NULL));

        Assert::true($source->compare('3', '> ?', 2));
        Assert::false($source->compare(3, '> ?', 4));

        Assert::true($source->compare('3', '>= ?', 3));
        Assert::true($source->compare('3', '<= ?', 3));

        Assert::true($source->compare(2, '< ?', 3));
        Assert::true($source->compare(NULL, '< ?', 3));

        Assert::error(function() use ($source) {
            Assert::true($source->compare(2, 'SOMETHING ?', 3));
        }, 'Grido\Exception', "Condition 'SOMETHING ?' not implemented yet.");
    }
}

run(__FILE__);
