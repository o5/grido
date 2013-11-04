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

require_once __DIR__ . '/DataSource.TestCase.php';

class ArraySourceTest extends DataSourceTestCase
{
    function setUp()
    {
        Helper::grid(function(Grid $grid) {
            $grid->setModel(json_decode(file_get_contents(__DIR__ . '/files/users.json'), 1));
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
        Helper::grid(function(Grid $grid) {
            $grid->setModel(json_decode(file_get_contents(__DIR__ . '/files/users.json'), 1));
            $grid->addFilterCheck('male', 'Only male')
                ->setWhere(function($value, array $row) {
                    Assert::true($value);
                    return $row['gender'] == 'male';
                });

        })->run(array('grid-filter' => array('male' => TRUE)));

        Helper::$grid->data;
        Assert::same(19, Helper::$grid->count);
    }

    function testCompare()
    {
        $source = new \Grido\DataSources\ArraySource(array());

        Assert::true($source->compare('Lucie', 'LIKE ?', '%Lu%'));
        Assert::true($source->compare('Lucie', 'LIKE ?', '%ie'));
        Assert::true($source->compare('Lucie', 'LIKE ?', 'lu%'));
        Assert::false($source->compare('Lucie', 'LIKE ?', 'ie%'));
        Assert::false($source->compare('Lucie', 'LIKE ?', '%lu'));

        Assert::true($source->compare('Lucie', '=', 'Lucie'));
        Assert::false($source->compare('Lucie', '=', 'lucie'));

        Assert::true($source->compare(NULL, 'IS NULL', NULL));
        Assert::false($source->compare('', 'IS NULL', NULL));

        Assert::true($source->compare('', 'IS NOT NULL', NULL));
        Assert::true($source->compare('NULL', 'IS NOT NULL', NULL));

        Assert::true($source->compare('3', '> ?', 2));
        Assert::false($source->compare(3, '> ?', 4));

        Assert::true($source->compare('3', '>= ?', 3));
        Assert::true($source->compare('3', '<= ?', 3));

        Assert::true($source->compare(2, '< ?', 3));

        Assert::error(function() use ($source) {
            Assert::true($source->compare(2, 'SOMETHING ?', 3));
        }, 'InvalidArgumentException', "Condition 'SOMETHING ?' not implemented yet.");
    }
}

run(__FILE__);
