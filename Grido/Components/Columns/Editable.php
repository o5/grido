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
     * Returns cell prototype (<td> html tag).
     * @param mixed $row
     * @return \Nette\Utils\Html
     */
    public function getCellPrototype($row = NULL)
    {
        $td = $this->cellPrototype;

        if ($td === NULL) { //cache
            $td = $this->cellPrototype = \Nette\Utils\Html::el('td')
                ->setClass(array('grid-cell-' . $this->getName()));
        }

        if ($this->cellCallback && $row !== NULL) {
            $td = clone $td;
            $td = callback($this->cellCallback)->invokeArgs(array($row, $td));
        }

        if ($this->isEditable()) {
            $td->data['grido-editableControl-handler'] = $this->link('editableControl!');
            $td->data['grido-editable-handler'] = $this->link('editable!');
        }
        return $td;
    }

    /**
     * Returns control for editation
     * @returns \Nette\Forms\IControl
     */
    function getEditableControl()
    {
        $this->getGrid()->saveState($this->params);

        if ($this->isEditable()) {
            if ($this->editableControl == NULL) {
                $this->editableControl = new \Nette\Forms\Controls\TextInput($this->label);
            }
            $this->getForm()->addComponent($this->editableControl, 'edit');
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
    public function handleEditable()
    {
        $this->getGrid()->saveState($this->params);

        if ($this->isEditable()) {
            if ($this->editableCallback != NULL) {
                \Nette\Diagnostics\FireLogger::log('YES');
                callback($this->editableCallback);
            } else {
                \Nette\Diagnostics\FireLogger::log('NO');
                //MAKE DATASOURCE OPERATIONS
            }
        } else {
            //NOT EDITABLE
        }
    }

    /**
     * Handler for returning the HTML prototype of editable control
     * @internal
     */
    public function handleEditableControl()
    {
        if ($this->isEditable()) {
            $controlPrototype = $this->getEditableControl()->getControl()->render();
            $html = new \Nette\Application\Responses\TextResponse($controlPrototype);
            $this->presenter->sendResponse($html);
        }
    }

}
