<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 */

namespace Grido\Components;

use Grido\Grid;

/**
 * Operation with one or more rows.
 *
 * @package     Grido
 * @subpackage  Components
 * @author      Petr Bugyík
 *
 * @property-read string $primaryKey
 */
class Operation extends Base
{
    const ID = 'operations';

    /** @var array callback on operation submit */
    public $onSubmit;

    /** @var string */
    protected $primaryKey;

    /**
     * @param \Grido\Grid $grid
     * @param array $operations
     * @param callback $onSubmit - callback after operation submit
     */
    public function __construct($grid, $operations, $onSubmit)
    {
        $this->grid = $grid;
        $grid->addComponent($this, self::ID);

        $grid['form'][$grid::BUTTONS]->addSubmit(self::ID, 'OK')
            ->onClick[] = $this->handleOperations;

        $grid['form']->addContainer(self::ID)
            ->addSelect(self::ID, 'Selected', $operations)
            ->setPrompt('Selected...');

        $that = $this;
        $grid->onRender[] = function(Grid $grid) use ($that) {
            $that->addCheckers($grid['form'][Operation::ID]);
        };

        $this->onSubmit[] = $onSubmit;
    }

    /**
     * Sets client side confirm for operation.
     * @param string $operation
     * @param string $message
     * @return Operation
     */
    public function setConfirm($operation, $message)
    {
        $this->grid->onRender[] = function(Grid $grid) use ($operation, $message){
            $grid['form'][Operation::ID][Operation::ID]->controlPrototype->attrs["data-grido-$operation"] = $message;
        };

        return $this;
    }

    /**
     * Sets primary key.
     * @param string $primaryKey
     * @return Operation
     */
    public function setPrimaryKey($primaryKey)
    {
        $this->primaryKey = $primaryKey;
        return $this;
    }

    /**********************************************************************************************/

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        if ($this->primaryKey === NULL) {
            $this->primaryKey = $this->grid->primaryKey;
        }

        return $this->primaryKey;
    }

    /**********************************************************************************************/

    /**
     * @internal
     * @param \Nette\Forms\Controls\SubmitButton $button
     */
    public function handleOperations(\Nette\Forms\Controls\SubmitButton $button)
    {
        $form = $button->getForm();
        $this->addCheckers($form[self::ID]);

        $values = $form[self::ID]->values;
        if (empty($values[self::ID])) {
            $this->grid->reload();
        }

        $ids = array();
        $operation = $values[self::ID];
        unset($values[self::ID]);

        foreach ($values as $key => $val) {
            if ($val) {
                $ids[] = $key;
            }
        }

        $this->onSubmit($operation, $ids);
    }

    /**
     * @internal
     * @param \Nette\Forms\Container $container
     */
    public function addCheckers(\Nette\Forms\Container $container)
    {
        $items = $this->grid->getData();
        $primaryKey = $this->getPrimaryKey();
        $propertyAccessor = $this->grid->getPropertyAccessor();

        foreach ($items as $item) {
            $container->addCheckbox($propertyAccessor->getProperty($item, $primaryKey));
        }
    }
}
