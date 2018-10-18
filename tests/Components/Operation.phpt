<?php

/**
 * Test: Operation.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

namespace Grido\Tests;

require_once __DIR__ . '/../bootstrap.php';

use Tester\Assert,
    Grido\Grid,
    Grido\Components\Operation;

class OperationTest extends \Tester\TestCase
{
    function testSetConfirm()
    {
        Helper::grid(function(Grid $grid) {
            $grid->setModel([]);
            $grid->addColumnText('column', 'Column');
            $grid->setOperation(['edit' => 'Edit', 'delete' => 'Delete'], function() {})
                ->setConfirm('delete', 'Are you sure?');
            $grid->render();
        })->run();

        $formControl = Helper::$grid['form'][Operation::ID][Operation::ID];
        Assert::same($formControl->controlPrototype->getAttribute('data-grido-confirm-delete'), 'Are you sure?');
    }

    /**********************************************************************************************/

    function testGetPrimaryKey()
    {
        $grid = new Grid;
        $operation = $grid->setOperation([], []);
        Assert::same($grid->primaryKey, $operation->primaryKey);

        $primaryKey = 'xx';
        $operation->setPrimaryKey($primaryKey);
        Assert::same($primaryKey, $operation->primaryKey);
    }

    /**********************************************************************************************/

    function testHandleOperations()
    {
        $definition = function(Grid $grid, $strictMode = TRUE) {
            $grid->setStrictMode($strictMode);
            $grid->setModel([
                ['id' => 1, 'a' => 'A1', 'b' => 'B1'],
                ['id' => 2, 'a' => 'A2', 'b' => 'B2'],
                ['id' => 3, 'a' => 'A3', 'b' => 'B3'],
                ['id' => 4, 'a' => 'A4', 'b' => 'B4'],
                ['id' => 5, 'a' => 'A5', 'b' => 'B5'],
            ]);
            $grid->addColumnText('a', 'A');
            $grid->addColumnText('b', 'B');
            $grid->setOperation(['edit' => 'Edit', 'del' => 'Del'], function($operation, $id) {
                Assert::same('edit', $operation);
                Assert::same(['2', '4'], $id);
            });
        };
        Helper::grid(function(Grid $grid) use ($definition) {
            $definition($grid);
        });

        $params = [
            'do' => 'grid-form-submit',
            'count' => 10,
            Grid::BUTTONS => [Operation::ID => 'OK'],
            Operation::ID => [
                Operation::ID => 'edit',
                '2' => 'on',
                '4' => 'on',
                '9' => 'on'
        ]];

        Helper::request($params);

        $fakeParams = $params;
        $fakeParams[Operation::ID][Operation::ID] = 'fake';

        Assert::error(function() use ($fakeParams) {
            Helper::request($fakeParams);
        }, E_USER_NOTICE, "Operation with name 'fake' does not exist.");

        Helper::grid(function(Grid $grid) use ($definition) {
            $definition($grid, FALSE);
        })->run($fakeParams);
    }

    /**********************************************************************************************/

    function testHasOperations()
    {
        $grid = new Grid;
        Assert::false($grid->hasOperation());

        $grid->setOperation([], []);
        Assert::false($grid->hasOperation());
        Assert::true($grid->hasOperation(FALSE));
    }

    function testSetOperations()
    {
        $grid = new Grid;
        $operations = ['print' => 'Print', 'delete' => 'Delete'];
        $onSubmit = function() {};
        $grid->setOperation($operations, $onSubmit);
        $component = $grid->getOperation();
        Assert::type('\Grido\Components\Operation', $component);
        $componentId = \Grido\Components\Operation::ID;
        Assert::same($operations, $grid['form'][$componentId][$componentId]->items);
        Assert::same($component->onSubmit, [$onSubmit]);

        // getter
        $grid = new Grid;
        Assert::exception(function() use ($grid) {
            $grid->getOperation();
        }, 'Nette\InvalidArgumentException');

        Assert::null($grid->getOperation(FALSE));
    }
}

run(__FILE__);
