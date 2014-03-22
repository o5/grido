<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2014 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Grido\Components\Columns;

/**
 * An inline editable column.
 *
 * @package     Grido
 * @subpackage  Components\Columns
 * @author      Jakub Kopřiva <kopriva.jakub@gmail.com>
 * @author      Petr Bugyík
 *
 * @property \Nette\Forms\IControl $editableControl
 * @property callback $editableCallback
 */
abstract class Editable extends Column
{
    const CLIENT_SIDE_OPTIONS = 'editable';

    /** @var bool */
    protected $editable = FALSE;

    /** @var bool */
    protected $editableDisabled = FALSE;

    /** @var callback function for custom handling with edited data; function($id, $value, $columnName) {} */
    protected $editableCallback;

    /** @var \Nette\Forms\IControl Custom control for inline editing */
    protected $editableControl;

    /**
     * Sets column as editable.
     * @param callback $callback function($id, $value, $columnName) {}
     * @param \Nette\Forms\IControl $control
     * @return Editable
     */
    public function setEditable($callback = NULL, \Nette\Forms\IControl $control = NULL)
    {
        $this->editable = TRUE;

        $this->setEditableCallback($callback);
        $control === NULL ?: $this->setEditableControl($control);
        $this->setGridOptions();

        return $this;
    }

    /**
     * Sets control for inline editation.
     * @param \Nette\Forms\IControl $control
     * @return Editable
     */
    public function setEditableControl(\Nette\Forms\IControl $control)
    {
        $this->isEditable() ?: $this->setEditable();
        $this->editableControl = $control;

        return $this;
    }

    /**
     * Sets editable callback.
     * @param callback $callback
     * @return Editable
     */
    public function setEditableCallback($callback)
    {
        $this->isEditable() ?: $this->setEditable();
        $this->editableCallback = $callback;

        return $this;
    }

    /**
     * @return Editable
     */
    public function disableEditable()
    {
        $this->editable = FALSE;
        $this->editableDisabled = TRUE;

        return $this;
    }

    protected function setGridOptions()
    {
        $options = $this->grid->getClientSideOptions();
        if (!isset($options[self::CLIENT_SIDE_OPTIONS])) { //only once
            $this->grid->setClientSideOptions(array(self::CLIENT_SIDE_OPTIONS => TRUE));
            $this->grid->onRender[] = function(\Grido\Grid $grid)
            {
                foreach ($grid->getComponent(Column::ID)->getComponents() as $column) {
                    $columnName = $column->getColumn();
                    $callbackNotSet = $column instanceof Editable && $column->isEditable() && $column->getEditableCallback() === NULL;
                    if ($callbackNotSet && (!is_string($columnName) || strpos($columnName, '.'))) {
                        throw new \InvalidArgumentException("Editable column '{$column->name}' has error: You must define an own editable callback.");
                    }
                }
            };
        }
    }

    /**********************************************************************************************/

    /**
     * Returns header cell prototype (<th> html tag).
     * @return \Nette\Utils\Html
     */
    public function getHeaderPrototype()
    {
        $th = parent::getHeaderPrototype();

        if ($this->isEditable()) {
            $th->data['grido-editable-handler'] = $this->link('editable!');
            $th->data['grido-editableControl-handler'] = $this->link('editableControl!');
        }

        return $th;
    }

    /**
     * Returns control for editation.
     * @param string $value old value to be inserted in control
     * @returns \Nette\Forms\Controls\TextInput
     */
    public function getEditableControl($value)
    {
        if ($this->editableControl === NULL) {
            $this->editableControl = new \Nette\Forms\Controls\TextInput;
            $this->editableControl->controlPrototype->class[] = 'form-control';
        }

        $this->editableControl->setValue($value);
        $this->getForm()->addComponent($this->editableControl, 'edit' . $this->getName());

        return $this->editableControl;
    }

    /**
     * @return callback
     * @internal
     */
    public function getEditableCallback()
    {
        return $this->editableCallback;
    }

    /**
     * @return bool
     * @internal
     */
    public function isEditable()
    {
        return $this->editable;
    }

    /**
     * @return bool
     * @internal
     */
    public function isEditableDisabled()
    {
        return $this->editableDisabled;
    }

    /**********************************************************************************************/

    /**
     * @internal
     */
    public function handleEditable($id, $value)
    {
        $this->grid->onRegistered($this->grid);

        if (!$this->presenter->isAjax() || !$this->isEditable()) {
            $this->presenter->terminate();
        }

        $success = $this->editableCallback
            ? callback($this->editableCallback)->invokeArgs(array($id, $value, $this->getName()))
            : $this->grid->model->update($id, array($this->getColumn() => $value), $this->grid->primaryKey);

        $response = new \Nette\Application\Responses\JsonResponse(array('updated' => $success));
        $this->presenter->sendResponse($response);
    }

    /**
     * @internal
     */
    public function handleEditableControl($oldValue)
    {
        $this->grid->onRegistered($this->grid);

        if (!$this->presenter->isAjax() || !$this->isEditable()) {
            $this->presenter->terminate();
        }

        $control = $this->getEditableControl($oldValue)->getControl()->render();
        $response = new \Nette\Application\Responses\TextResponse($control);
        $this->presenter->sendResponse($response);
    }
}
