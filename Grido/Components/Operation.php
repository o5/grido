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
     * @param string $primaryKey
     */
    public function __construct($grid, $operations, $onSubmit)
    {
        $this->grid = $grid;
        $grid->addComponent($this, self::ID);

        $form = $this->getForm();
        $form[\Grido\Grid::BUTTONS]->addSubmit(self::ID, 'OK');
        $form->addContainer(self::ID)
            ->addSelect(self::ID, 'Selected', $operations)
            ->setPrompt('Selected...');

        $this->onSubmit[] = $onSubmit;
        $this->primaryKey = $this->primaryKey;
    }

    /**
     * Sets client side confirm for operation.
     * @param string $operation
     * @param string $message
     * @return Operation
     */
    public function setConfirm($operation, $message)
    {
        $form = $this->getForm();
        $form[self::ID][self::ID]->controlPrototype->attrs["data-grido-$operation"] = $message;
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
}
