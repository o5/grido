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
 * @property callback $editableValueCallback
 */
abstract class Editable extends Column
{
    /** @var bool */
    protected $editable = FALSE;

    /** @var bool */
    protected $editableDisabled = FALSE;

    /** @var \Nette\Forms\IControl Custom control for inline editing */
    protected $editableControl;

    /** @var callback for custom handling with edited data; function($id, $newValue, $oldValue, Editable $column) {} */
    protected $editableCallback;

    /** @var callback for custom value; function($row, Columns\Editable $column) {} */
    protected $editableValueCallback;

    /**
     * Sets column as editable.
     * @param callback $callback function($id, $newValue, $oldValue, Columns\Editable $column) {} {}
     * @param \Nette\Forms\IControl $control
     * @return Editable
     */
    public function setEditable($callback = NULL, \Nette\Forms\IControl $control = NULL)
    {
        $this->editable = TRUE;
        $this->setClientSideOptions();

        $callback && $this->setEditableCallback($callback);
        $control && $this->setEditableControl($control);

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
     * @param callback $callback function($id, $newValue, $oldValue, Columns\Editable $column) {}
     * @return Editable
     */
    public function setEditableCallback($callback)
    {
        $this->isEditable() ?: $this->setEditable();
        $this->editableCallback = $callback;

        return $this;
    }

    /**
     * Sets editable value callback.
     * @param callback $callback for custom value; function($row, Columns\Editable $column) {}
     * @return Editable
     */
    public function setEditableValueCallback($callback)
    {
        $this->isEditable() ?: $this->setEditable();
        $this->editableValueCallback = $callback;

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

    protected function setClientSideOptions()
    {
        $options = $this->grid->getClientSideOptions();
        if (!isset($options['editable'])) { //only once
            $this->grid->setClientSideOptions(array('editable' => TRUE));
            $this->grid->onRender[] = function(\Grido\Grid $grid)
            {
                foreach ($grid->getComponent(Column::ID)->getComponents() as $column) {
                    if (!$column instanceof Editable) {
                        continue;
                    }

                    $columnName = $column->getColumn();
                    $callbackNotSet = $column->isEditable() && $column->editableCallback === NULL;
                    if (($callbackNotSet && (!is_string($columnName) || strpos($columnName, '.'))) ||
                        ($callbackNotSet && !method_exists($grid->model->dataSource, 'update')))
                    {
                        throw new \Exception("Column '{$column->name}' has error: You must define an own editable callback.");
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
     * Returns cell prototype (<td> html tag).
     * @param mixed $row
     * @return \Nette\Utils\Html
     */
    public function getCellPrototype($row = NULL)
    {
        $td = parent::getCellPrototype($row);

        if ($this->isEditable() && $row !== NULL) {
            $td->data['grido-editable-value'] = $this->editableValueCallback === NULL
                ? parent::getValue($row)
                : callback($this->editableValueCallback)->invokeArgs(array($row, $this));
        }

        return $td;
    }

    /**
     * Returns control for editation.
     * @returns \Nette\Forms\Controls\TextInput
     */
    public function getEditableControl()
    {
        if ($this->editableControl === NULL) {
            $this->editableControl = new \Nette\Forms\Controls\TextInput;
            $this->editableControl->controlPrototype->class[] = 'form-control';
        }

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
     * @return callback
     * @internal
     */
    public function getEditableValueCallback()
    {
        return $this->editableValueCallback;
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
    public function handleEditable($id, $newValue, $oldValue)
    {
        $this->grid->onRegistered($this->grid);

        if (!$this->presenter->isAjax() || !$this->isEditable()) {
            $this->presenter->terminate();
        }

        $success = $this->editableCallback
            ? callback($this->editableCallback)->invokeArgs(array($id, $newValue, $oldValue, $this))
            : $this->grid->model->update($id, array($this->getColumn() => $newValue), $this->grid->primaryKey);

        $response = new \Nette\Application\Responses\JsonResponse(array('updated' => $success));
        $this->presenter->sendResponse($response);
    }

    /**
     * @internal
     */
    public function handleEditableControl($value)
    {
        $this->grid->onRegistered($this->grid);

        if (!$this->presenter->isAjax() || !$this->isEditable()) {
            $this->presenter->terminate();
        }

        $control = $this->getEditableControl()->setValue($value);
        $this->getForm()->addComponent($control, 'edit' . $this->getName());

        $response = new \Nette\Application\Responses\TextResponse($control->getControl()->render());
        $this->presenter->sendResponse($response);
    }
}
