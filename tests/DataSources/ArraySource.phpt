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
    Grido\DataSources\ArraySource,
    Grido\Components\Filters\Condition;

require_once __DIR__ . '/TestCase.php';

class ArraySourceTest extends DataSourceTestCase
{
    function setUp()
    {
        Helper::grid(function(Grid $grid, TestPresenter $presenter) {
            $data = $presenter->context->getService('dibi_sqlite')
                ->select('u.*, c.title AS country')
                ->from('[user] u')
                ->leftJoin('[country] c')->on('u.country_code = c.code')
                ->fetchAll();
            $grid->setModel($data);
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
                    ->setSuggestion();

            $grid->addFilterCheck('male', 'Only male')
                ->setCondition([
                    TRUE => ['gender', '= ?', 'male']
                ]);

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
        $source = new ArraySource([]);

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
        }, 'Grido\Exception', "Condition 'SOMETHING ?' is not implemented yet.");
    }

    function testMakeWhere()
    {
        $data = [
            ['name' => 'AA', 'surname' => 'BB', 'city' => 'CC'],
            ['name' => 'CC', 'surname' => 'DD', 'city' => 'AA'],
            ['name' => 'EE', 'surname' => 'AA', 'city' => 'FF'],
            ['name' => 'AA', 'surname' => 'AA', 'city' => 'BB'],
            ['name' => 'AA', 'surname' => 'AA', 'city' => 'AA'],
        ];

        $source = new ArraySource($data);
        $source->filter([Condition::setup(['name'], '= ?', 'CC')]);
        Assert::same([1 => $data[1]], $source->data);

        $source = new ArraySource($data);
        $source->filter([Condition::setup(['name', 'OR', 'surname'], '= ?', 'AA')]);
        $expected = $data;
        unset($expected[1]);
        Assert::same($expected, $source->data);

        $source = new ArraySource($data);
        $source->filter([Condition::setup(['name', 'AND', 'surname'], '= ?', 'AA')]);
        Assert::same([3 => $data[3], 4 => $data[4]], $source->data);

        $source = new ArraySource($data);
        $source->filter([Condition::setup(['name', 'AND', 'surname', 'AND', 'city'], '= ?', 'AA')]);
        Assert::same([4 => $data[4]], $source->data);
    }
}

run(__FILE__);
