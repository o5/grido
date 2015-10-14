<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Grido\Components\Actions;

use Grido\Exception;
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
 * @property string $primaryKey
 * @property string $options
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

    /** @var string */
    protected $options;

    /**
     * @param \Grido\Grid $grid
     * @param string $name
     * @param string $label
     */
    public function __construct($grid, $name, $label)
    {
        $this->addComponentToGrid($grid, $name);

        $this->type = get_class($this);
        $this->label = $this->translate($label);
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
        $this->setOption('confirm', $confirm);
        return $this;
    }

    /**
     * Sets name of icon.
     * @param string $name
     * @return Action
     */
    public function setIcon($name)
    {
        $this->setOption('icon', $name);
        return $this;
    }

    /**
     * Sets user-specific option.
     * @param string $key
     * @param mixed $value
     * @return Action
     */
    public function setOption($key, $value)
    {
        if ($value === NULL) {
            unset($this->options[$key]);

        } else {
            $this->options[$key] = $value;
        }

        return $this;
    }

    /**********************************************************************************************/

    /**
     * Returns element prototype (<a> html tag).
     * @return Html
     * @throws Exception
     */
    public function getElementPrototype()
    {
        if ($this->elementPrototype === NULL) {
            $this->elementPrototype = Html::el('a')
                ->setClass(array('grid-action-' . $this->getName()))
                ->setText($this->label);
        }

        if (isset($this->elementPrototype->class) && is_string($this->elementPrototype->class)) {
            $this->elementPrototype->class = (array) $this->elementPrototype->class;
        } elseif (isset($this->elementPrototype->class) && !is_array($this->elementPrototype->class)) {
            throw new Exception('Attribute class must be string or array.');
        }

        return $this->elementPrototype;
    }

    /**
     * @return string
     * @internal
     */
    public function getPrimaryKey()
    {
        if ($this->primaryKey === NULL) {
            $this->primaryKey = $this->grid->getPrimaryKey();
        }

        return $this->primaryKey;
    }

    /**
     * @param mixed $row
     * @return Html
     * @internal
     */
    public function getElement($row)
    {
        $element = clone $this->getElementPrototype();

        if ($confirm = $this->getOption('confirm')) {
            $confirm = is_callable($confirm)
                ? callback($confirm)->invokeArgs(array($row))
                : $confirm;

            $element->data['grido-confirm'] = is_array($confirm)
                ? vsprintf($this->translate(array_shift($confirm)), $confirm)
                : $this->translate($confirm);
        }

        return $element;
    }

    /**
     * Returns user-specific option.
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getOption($key, $default = NULL)
    {
        return isset($this->options[$key])
            ? $this->options[$key]
            : $default;
    }

    /**
     * Returns user-specific options.
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**********************************************************************************************/

    /**
     * @param mixed $row
     * @throws Exception
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
