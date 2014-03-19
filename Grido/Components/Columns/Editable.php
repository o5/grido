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
 * @author      Jakub Kopřiva
 *
 * @property bool $editable
 * @property callback $editableCallback
 * @property \Nette\Forms\IControl $editableControl
 */
class Editable extends \Grido\Components\Columns\Column
{
    /** @var bool */
    protected $editable = FALSE;

    /** @var callback function for custom handling with edited data */
    protected $editableCallback = NULL;

    /** @var \Nette\Forms\IControl Custom control for inline editing */
    protected $editableControl = NULL;

    /**
     * @param Grido\Grid $grid
     * @param string $name
     * @param string $label
     */
    public function __construct($grid, $name, $label)
    {
        parent::__construct($grid, $name, $label);
    }

    /******************************* Setters ******************************************************/

    /**
     * Set column as editable
     * @param callback $callback
     * @return Column
     */
    public function setEditable($callback = NULL)
    {
        $this->editable = TRUE;
        $this->editableCallback = $callback;
        $this->getGrid()->setClientSideOptions(array('inlineEditable' => true));
        return $this;
    }

    /**
     * Set control for inline editation
     * @param \Nette\Forms\IControl $control
     * @return \Grido\Components\Columns\Column
     */
    public function setEditableControl($control)
    {
        if ($this->isEditable()) {
            $this->editableControl = $control;
        }

        return $this;
    }

    /******************************* Getters ******************************************************/


    /**
     * Returns header cell prototype (<th> html tag).
     * @return \Nette\Utils\Html
     */
    public function getHeaderPrototype()
    {
        $th = parent::getHeaderPrototype();

        $th->data['test'] = 'test';

        if ($this->isEditable()) {
            $th->data['grido-editableControl-handler'] = $this->link('editableControl!');
            $th->data['grido-editable-handler'] = $this->link('editable!');
        }

        $this->headerPrototype = $th;
        return $this->headerPrototype;
    }

    /**
     * Returns control for editation
     * @param string $oldValue value to be inserted in control
     * @returns \Nette\Forms\IControl
     */
    function getEditableControl($oldValue)
    {
        $this->getGrid()->saveState($this->params);

        if ($this->isEditable()) {
            if ($this->editableControl == NULL) {
                $this->editableControl = new \Nette\Forms\Controls\TextInput($this->label);
            }
            $this->editableControl->setValue($oldValue);
            $uniqueId = preg_replace("/[^a-zA-Z]*/", "", $this->getColumn()) . rand(0, 2147483647) . rand(0, 2147483647);
            $this->getForm()->addComponent($this->editableControl, 'edit'.$uniqueId);
            return $this->editableControl;
        }
    }

    /******************************* Bool checkers ************************************************/

    /**
     * @return bool
     * @internal
     */
    public function isEditable() {
        $this->getGrid()->saveState($this->params);

        return $this->editable;
    }

    /******************************* Handlers for inline edit *************************************/

    /**
     * Handle action after editation form was submitted by AJAX
     * @internal
     */
    public function handleEditable($primaryKey, $oldValue, $newValue, $columnName)
    {
        $this->getGrid()->saveState($this->params);

        if ($this->isEditable()) {
            if ($this->editableCallback != NULL) {
                callback($this->editableCallback)->invokeArgs(array(array(
                    'primaryKeyValue' => $primaryKey,
                    'oldValue' => $oldValue,
                    'newValue' => $newValue,
                    'columnName' => $columnName
                )));
            } else {
                \Nette\Diagnostics\FireLogger::log($this->getGrid()->getModel()->update($primaryKey, $columnName, $oldValue, $newValue));
            }
        } else {
            $this->presenter->terminate();
        }
    }

    /**
     * Handler for returning the HTML prototype of editable control
     * @internal
     */
    public function handleEditableControl($oldValue)
    {
        if ($this->isEditable()) {
            $controlPrototype = $this->getEditableControl($oldValue)->getControl()->render();
            $html = new \Nette\Application\Responses\TextResponse($controlPrototype);
            $this->presenter->sendResponse($html);
        }
    }

}
