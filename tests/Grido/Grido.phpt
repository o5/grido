<?php

require_once __DIR__ . '/../bootstrap.php';

test(function(){ //setModel()
    $grid = new \Grido\Grid;

    $grid->setModel(Mockery::mock('Grido\DataSources\IDataSource'));
    Assert::type('Grido\DataSources\IDataSource', $grid->getModel());

    $grid->setModel(Mockery::mock('\DibiFluent'));
    Assert::type('Grido\DataSources\Model', $grid->getModel());

    $grid->setModel(Mockery::mock('\DibiFluent'), TRUE);
    Assert::type('Grido\DataSources\Model', $grid->getModel());

    $grid->setModel(Mockery::mock('\Nette\Database\Table\Selection'));
    Assert::type('Grido\DataSources\Model', $grid->getModel());

    $grid->setModel(Mockery::mock('\Doctrine\ORM\QueryBuilder'));
    Assert::type('Grido\DataSources\Model', $grid->getModel());

    $grid->setModel(array('TEST' => 'TEST'));
    Assert::type('Grido\DataSources\Model', $grid->getModel());

    $grid->setModel(Mockery::mock('Grido\DataSources\IDataSource'), TRUE);
    Assert::type('Grido\DataSources\Model', $grid->getModel());

    Assert::exception(function() use ($grid) {
        $grid->setModel(Mockery::mock('BAD'));
    }, 'InvalidArgumentException');

    Assert::exception(function() use ($grid) {
        $grid->setModel(Mockery::mock('BAD'), TRUE);
    }, 'InvalidArgumentException');
});
