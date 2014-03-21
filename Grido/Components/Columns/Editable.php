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
 * Eitable Column grid.
 *
 * @package     Grido
 * @subpackage  Components\Columns\Editable
 * @author      Jakub Kopřiva <kopriva.jakub@gmail.com>
 * @author      Petr Bugyík
 *
 * @property-write bool $editable
 * @property-write \Nette\Forms\IControl $editableControl
 */
abstract class Editable extends \Grido\Components\Columns\Column
{
    /** @var bool */
    protected $editable = FALSE;

    /** @var callback function for custom handling with edited data */
    protected $editableCallback;

    /** @var \Nette\Forms\IControl Custom control for inline editing */
    protected $editableControl;

    /**
     * Sets column as editable.
     * @param callback $callback function($id, $values, $idCol) {}
     * @return Editable
     */
    public function setEditable($callback = NULL)
    {
        $this->editable = TRUE;
        $this->editableCallback = $callback;
        $this->grid->setClientSideOptions(array('editable' => true));

        return $this;
    }

    /**
     * Sets control for inline editation.
     * @param \Nette\Forms\IControl $control
     * @return Editable
     */
    public function setEditableControl($control)
    {
        $this->editable ?: $this->setEditable();
        $this->editableControl = $control;

        return $this;
    }

    /**********************************************************************************************/

    /**
     * Returns header cell prototype (<th> html tag).
     * @return \Nette\Utils\Html
     */
    public function getHeaderPrototype()
    {
        $th = parent::getHeaderPrototype();

        if ($this->editable) {
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
        }

        $this->editableControl->setValue($value);
        $this->getForm()->addComponent($this->editableControl, 'edit' . $this->getName());

        return $this->editableControl;
    }

    /******************************* Handlers for inline edit *************************************/

    /**
     * @internal
     */
    public function handleEditable($primaryKey, $oldValue, $newValue, $columnName)
    {
        $this->grid->onRegistered($this->grid);

        if (!$this->getPresenter()->isAjax() || !$this->editable) {
            $this->getPresenter()->terminate();
        }

        $values = array($columnName => $newValue);
        $success = $this->editableCallback
            ? callback($this->editableCallback)->invokeArgs(array($primaryKey, $values, $this->grid->primaryKey))
            : $this->grid->model->update($primaryKey, $values, $this->grid->primaryKey);

        $response = new \Nette\Application\Responses\JsonResponse(array('updated' => $success));
        $this->presenter->sendResponse($response);
    }

    /**
     * @internal
     */
    public function handleEditableControl($oldValue)
    {
        $this->grid->onRegistered($this->grid);

        if (!$this->getPresenter()->isAjax() || !$this->editable) {
            $this->getPresenter()->terminate();
        }

        $control = $this->getEditableControl($oldValue)->getControl()->render();
        $response = new \Nette\Application\Responses\TextResponse($control);
        $this->presenter->sendResponse($response);
    }
}
