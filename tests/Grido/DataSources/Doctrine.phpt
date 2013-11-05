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

require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/files/doctrine/entities/Country.php';
require_once __DIR__ . '/files/doctrine/entities/User.php';

class DoctrineTest extends DataSourceTestCase
{
    function setUp()
    {
        Helper::grid(function(Grid $grid, TestPresenter $presenter) {
            $repository = $presenter->context->doctrine->entityManager->getRepository('Grido\Tests\Entities\User');
            $model = new \Grido\DataSources\Doctrine(
                $repository->createQueryBuilder('a') // We need to create query builder with inner join.
                    ->addSelect('c')                 // This will produce less SQL queries with prefetch.
                    ->innerJoin('a.country', 'c'),
                array('country' => 'c.title'));      // Map country column to the title of the Country entity

            $grid->setModel($model);
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
                ->setSuggestion(function(array $row) {
                    return $row['a_firstname'];
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
                ->setWhere(function($value, \Doctrine\ORM\QueryBuilder $qb) {
                    Assert::true($value);
                    $qb->andWhere("a.centimeters >= :height")->setParameter('height', 180);
                });

            $grid->setExport();

        })->run();
    }
}

run(__FILE__);
