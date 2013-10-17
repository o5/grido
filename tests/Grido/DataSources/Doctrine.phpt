<?php

/**
 * Test: Doctrine.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

namespace Grido\Tests;

use Tester\Assert,
    Grido\Grid,
    Grido\Components\Filters\Condition;

require_once __DIR__ . '/DataSource.TestCase.php';
require_once __DIR__ . '/files/doctrine/entities/Country.php';
require_once __DIR__ . '/files/doctrine/entities/User.php';

class DoctrineTest extends DataSourceTestCase
{
    /** @var Grido\DataSources\Doctrine */
    public $model;

    function setUp()
    {
        $that = $this;
        Helper::grid(function(Grid $grid, TestPresenter $presenter) use ($that) {
            $repository = $presenter->context->doctrine->entityManager->getRepository('Grido\Tests\Entities\User');
            $that->model = new \Grido\DataSources\Doctrine(
                $repository->createQueryBuilder('a') // We need to create query builder with inner join.
                    ->addSelect('c')                 // This will produce less SQL queries with prefetch.
                    ->innerJoin('a.country', 'c'),
                array('country' => 'c.title'));      // Map country column to the title of the Country entity

            $grid->setModel($that->model);
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
            $grid->setModel($that->model);
            $grid->addFilterCheck('male', 'Only male')
                ->setWhere(function($value, \Doctrine\ORM\QueryBuilder $qb) {
                    Assert::true($value);
                    $qb->andWhere("a.gender = :male")->setParameter('male', 'male');
                });

        })->run(array('grid-filter' => array('male' => TRUE)));

        Helper::$grid->data;
        Assert::same(19, Helper::$grid->count);
    }
}

run(__FILE__);
