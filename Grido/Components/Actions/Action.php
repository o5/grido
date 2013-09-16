<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 */

namespace Grido\Components\Actions;

use Nette\Utils\Html;

/**
 * Action on one row.
 *
 * @package     Grido
 * @subpackage  Components\Actions
 * @author      Petr Bugyík
 *
 * @property-read Html $element
 * @property-write Html $elementPrototype
 * @property-write callback $customRender
 * @property-write callback $disable
 * @property-write string|callback $confirm
 * @property string $primaryKey
 * @property string $icon
 */
abstract class Action extends \Grido\Components\Component
{
    const ID = 'actions';

    /** @var Html <a> html tag */
    protected $elementPrototype;

    /** @var callback for custom rendering */
    protected $customRender;

    /** @var string - name of primary key f.e.: link->('Article:edit', array($primaryKey => 1)) */
    protected $primaryKey;

    /** @var callback for disabling */
    protected $disable;

    /** @var string|callback */
    protected $confirm;

    /** @var string */
    protected $icon;

    /**
     * @param \Grido\Grid $grid
     * @param string $name
     * @param string $label
     */
    public function __construct($grid, $name, $label)
    {
        $this->addComponentToGrid($grid, $name);

        $this->type = get_class($this);
        $this->label = $label;
    }

    /**
     * Sets html element.
     * @param Html $elementPrototype
     * @return Action
     */
    public function setElementPrototype(Html $elementPrototype)
    {
        $this->elementPrototype = $elementPrototype;
        return $this;
    }

    /**
     * Sets callback for custom rendering.
     * @param callback
     * @return Action
     */
    public function setCustomRender($callback)
    {
        $this->customRender = $callback;
        return $this;
    }

    /**
     * Sets primary key.
     * @param string $primaryKey
     * @return Action
     */
    public function setPrimaryKey($primaryKey)
    {
        $this->primaryKey = $primaryKey;
        return $this;
    }

    /**
     * Sets callback for disable.
     * Callback should return TRUE if the action is not allowed for current item.
     * @param callback
     * @return Action
     */
    public function setDisable($callback)
    {
        $this->disable = $callback;
        return $this;
    }

    /**
     * Sets client side confirm.
     * @param string|callback $confirm
     * @return Action
     */
    public function setConfirm($confirm)
    {
        $this->confirm = $confirm;
        return $this;
    }

    /**
     * Sets twitter bootstrap icon class.
     * @param string $iconName
     * @return Action
     */
    public function setIcon($iconName)
    {
        $this->icon = $iconName;
        return $this;
    }

    /**********************************************************************************************/

    /**
     * Returns element prototype (<a> html tag).
     * @return Html
     */
    public function getElementPrototype()
    {
        if (!$this->elementPrototype) {
            $this->elementPrototype = Html::el('a')
                ->setClass(array('grid-action-' . $this->getName(), 'btn', 'btn-mini'));
        }

        return $this->elementPrototype;
    }

    /**
     * @internal - Do not call directly.
     * @return string
     */
    public function getPrimaryKey()
    {
        if ($this->primaryKey === NULL) {
            $this->primaryKey = $this->grid->primaryKey;
        }

        return $this->primaryKey;
    }

    /**
     * @param mixed $row
     * @return Html
     * @throws \InvalidArgumentException
     */
    protected function getElement($row)
    {
        $primaryKey = $this->getPrimaryKey();
        $propertyAccessor = $this->grid->propertyAccessor;

        if (!$this->customRender && !$propertyAccessor->hasProperty($row, $primaryKey)) {
            throw new \InvalidArgumentException("Primary key '$primaryKey' not found.");
        }

        $text = $this->translate($this->label);
        $this->icon ? $text = ' ' . $text : $text;

        $element = clone $this->getElementPrototype()
            ->setText($text);

        if ($this->confirm) {
            $element->data['grido-confirm'] = $this->translate(
                is_callable($this->confirm)
                    ? callback($this->confirm)->invokeArgs(array($row))
                    : $this->confirm
            );
        }

        if ($this->icon) {
            $element->insert(0, Html::el('i')->setClass(array("icon-$this->icon")));
        }

        return $element;
    }

    /**
     * @param mixed $row
     * @throws \InvalidArgumentException
     * @return void
     */
    public function render($row)
    {
        if (!$row || ($this->disable && callback($this->disable)->invokeArgs(array($row)))) {
            return;
        }

        $element = $this->getElement($row);

        if ($this->customRender) {
            echo callback($this->customRender)->invokeArgs(array($row, $element));
            return;
        }

        echo $element->render();
    }
}
