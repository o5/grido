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
 * @property-write array $customRender
 * @property-write array $disable
 * @property-write string|callback $confirm
 * @property-write string $icon
 * @property string $primaryKey
 */
abstract class Action extends \Grido\Components\Base
{
    const ID = 'actions';

    const TYPE_HREF = 'Grido\Components\Actions\Href';

    /** @var callback for custom rendering */
    protected $customRender;

    /** @var callback for disabling */
    protected $disable;

    /** @var Html <a> html tag */
    protected $elementPrototype;

    /** @var string - name of primary key f.e.: link->('Article:edit', array($primaryKey => 1)) */
    protected $primaryKey;

    /** @var string|callback */
    protected $confirm;

    /** @var array user options */
    private $options = array();

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
     * @return Href
     */
    public function setConfirm($confirm)
    {
        $this->confirm = $confirm;
        return $this;
    }

    /** @deprecated */
    public function setIcon($iconName)
    {
        trigger_error(__METHOD__ . '() is deprecated; use ' . __CLASS__ . '::setOption(\'icon\', ...) instead.', E_USER_WARNING);
        $this->setOption('icon', $iconName);
        return $this;
    }

    /**
     * Sets user-specific option.
     * Options recognized by default template
     * - 'icon' - Sets twitter bootstrap icon class.
     *
     * @param  string key
     * @param  mixed  value
     * @return Href
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

    /**
     * Returns user-specific option.
     * @param  string key
     * @param  mixed  default value
     * @return mixed
     */
    final public function getOption($key, $default = NULL)
    {
        return isset($this->options[$key]) ? $this->options[$key] : $default;
    }

    /**
     * Returns user-specific options.
     * @return array
     */
    final public function getOptions()
    {
        return $this->options;
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
                ->setClass(array('grid-action-' . $this->getName()));
        }

        return $this->elementPrototype;
    }

    /**
     * @param $item
     * @return Html
     * @throws \InvalidArgumentException
     */
    protected function getElement($item)
    {
        $primaryKey = $this->getPrimaryKey();
        $propertyAccessor = $this->grid->propertyAccessor;

        if (!$this->customRender && !$propertyAccessor->hasProperty($item, $primaryKey)) {
            throw new \InvalidArgumentException("Primary key '$primaryKey' not found.");
        }

        $text = $this->translate($this->label);

        $element = clone $this->getElementPrototype()
            ->add(htmlspecialchars((string) $text, ENT_NOQUOTES));

        if ($this->confirm) {
            $element->attrs['data-grido-confirm'] = $this->translate(
                is_callable($this->confirm)
                    ? callback($this->confirm)->invokeArgs(array($item))
                    : $this->confirm
            );
        }

        return $element;
    }

    /**
     * @internal
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
     * @param mixed $item
     * @throws \InvalidArgumentException
     * @return void
     */
    public function render($item)
    {
        if (!$item || ($this->disable && callback($this->disable)->invokeArgs(array($item)))) {
            return;
        }

        $element = $this->getElement($item);

        if ($this->customRender) {
            echo callback($this->customRender)->invokeArgs(array($item, $element));
            return;
        }

        echo $element->render();
    }
}
