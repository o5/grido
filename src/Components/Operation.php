<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Grido\Components;

use Grido\Grid;
use Grido\Helpers;
use Grido\Exception;

/**
 * Operation with one or more rows.
 *
 * @package     Grido
 * @subpackage  Components
 * @author      Petr Bugyík
 *
 * @property-read string $primaryKey
 * @method void onSubmit(string $operation, array $ids) Description
 */
class Operation extends Component
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
    public function __construct($grid, array $operations, $onSubmit)
    {
        $this->grid = $grid;
        $grid->addComponent($this, self::ID);

        $grid['form'][$grid::BUTTONS]->addSubmit(self::ID, 'OK')
            ->onClick[] = callback($this, 'handleOperations');

        $grid['form']->addContainer(self::ID)
            ->addSelect(self::ID, 'Selected', $operations)
            ->setPrompt('Grido.Selected');

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
        $message = $this->translate($message);
        $this->grid->onRender[] = function(Grid $grid) use ($operation, $message) {
            $grid['form'][Operation::ID][Operation::ID]->controlPrototype->data["grido-confirm-$operation"] = $message;
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
     * @param \Nette\Forms\Controls\SubmitButton $button
     * @internal
     */
    public function handleOperations(\Nette\Forms\Controls\SubmitButton $button)
    {
        $grid = $this->getGrid();
        $grid->onRegistered && $grid->onRegistered($grid);
        $form = $button->getForm();
        $this->addCheckers($form[self::ID]);

        $values = $form[self::ID]->values;
        if (empty($values[self::ID])) {
            $httpData = $form->getHttpData();
            if (!empty($httpData[self::ID][self::ID]) && $operation = $httpData[self::ID][self::ID]) {
                $grid->__triggerUserNotice("Operation with name '$operation' does not exist.");
            }

            $grid->reload();
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
     * @param \Nette\Forms\Container $container
     * @throws Exception
     * @internal
     */
    public function addCheckers(\Nette\Forms\Container $container)
    {
        $items = $this->grid->getData();
        $primaryKey = $this->getPrimaryKey();

        foreach ($items as $item) {
            try {
                $primaryValue = $this->grid->getProperty($item, $primaryKey);
                if (!isset($container[$primaryValue])) {
                    $container->addCheckbox(Helpers::formatColumnName($primaryValue))
                        ->controlPrototype->title = $primaryValue;
                }
            } catch (\Exception $e) {
                throw new Exception(
                    'You should define some else primary key via $grid->setPrimaryKey() '.
                    "because currently defined '$primaryKey' key is not suitable for operation feature."
                );
            }
        }
    }
}
