<?php

/**
 * Test: Paginator.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

use Tester\Assert,
    Grido\Components\Paginator;

require_once __DIR__ . '/../bootstrap.php';

test(function() {
    $paginator = function() {
        $paginator = new Paginator;
        $paginator->setItemsPerPage(10);
        $paginator->setItemCount(10000);
        return $paginator;
    };

    Assert::same(array(1,2,3,4,251,501,750,1000), $paginator()->getSteps());
    Assert::same(array(1,2,3,4,201,401,600,800,1000), $paginator()->setStepCount(5)->getSteps());
    Assert::same(array(1,2,3,4,5,251,501,750,1000), $paginator()->setStepRange(4)->getSteps());

    Assert::same(array(1,47,48,49,50,51,52,53,251,501,750,1000), $paginator()->setPage(50)->getSteps());
    Assert::same(array(1,47,48,49,50,51,52,53,201,401,600,800,1000), $paginator()->setPage(50)->setStepCount(5)->getSteps());
    Assert::same(array(1,46,47,48,49,50,51,52,53,54,251,501,750,1000), $paginator()->setPage(50)->setStepRange(4)->getSteps());

    Assert::same(array(1,251,501,750,997,998,999,1000), $paginator()->setPage(1000)->getSteps());
    Assert::same(array(1,201,401,600,800,997,998,999,1000), $paginator()->setPage(1000)->setStepCount(5)->getSteps());
    Assert::same(array(1,251,501,750,996,997,998,999,1000), $paginator()->setPage(1000)->setStepRange(4)->getSteps());
});
