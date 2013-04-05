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

/**
 * Href action.
 *
 * @package     Grido
 * @subpackage  Components\Actions
 * @author      Petr Bugyík
 *
 * @property-read \Nette\Utils\Html $elementPrototype
 * @property-write array $customHref
 * @property-write string|callback $confirm
 * @property string $icon
 */
class Href extends Action
{
    /** @var callback for custom href attribute creating */
    protected $customHref;

    /** @var string */
    protected $icon;

    /** @var string|callback */
    protected $confirm;

    /**
     * Sets callback for custom link creating.
     * @param callback $callback
     * @return Href
     */
    public function setCustomHref($callback)
    {
        $this->customHref = $callback;
        return $this;
    }

    /**
     * Sets twitter bootstrap icon class.
     * @param string $iconName
     * @return Href
     */
    public function setIcon($iconName)
    {
        $this->icon = $iconName;
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

    /**********************************************************************************************/

    /**
     * Returns element prototype (<a> html tag).
     * @return \Nette\Utils\Html
     */
    public function getElementPrototype()
    {
        if (!$this->elementPrototype) {
            $this->elementPrototype = \Nette\Utils\Html::el('a')
                ->setClass(array('no-ajax grid-action-' . $this->getName(), 'btn', 'btn-mini'));
        }

        return $this->elementPrototype;
    }

    /**
     * Return element.
     * @param $item
     * @return \Nette\Utils\Html
     */
    public function getElement($item)
    {
        $pk = $this->getPrimaryKey();

        $text = $this->translate($this->label);
        $this->icon ? $text = ' ' . $text : $text;

        $el = clone $this->getElementPrototype();
        $el->setText($text);

        if ($this->customHref) {
            $el->href(callback($this->customHref)->invokeArgs(array($item)));
        } else if ($this->getGrid()->getPropertyAccessor()->hasProperty($item, $pk)) {
            $this->arguments[$pk] = $this->getGrid()->getPropertyAccessor()->getProperty($item, $pk);
            $el->href($this->presenter->link($this->getDestination(), $this->arguments));
        }

        if ($this->confirm) {
            $el->attrs['data-grido-confirm'] = $this->translate(
                is_callable($this->confirm)
                    ? callback($this->confirm)->invokeArgs(array($item))
                    : $this->confirm
            );
        }

        if ($this->icon) {
            $el->insert(0, \Nette\Utils\Html::el('i')->setClass(array("icon-$this->icon")));
        }

        return $el;
    }

    /**
     * @param mixed $item
     * @return void
     */
    public function render($item)
    {
        if ($this->disable && callback($this->disable)->invokeArgs(array($item))) {
            return;
        }

        $el = $this->getElement($item);

        if ($this->customRender) {
            echo callback($this->customRender)->invokeArgs(array($item, $el));
            return;
        }

        echo $el->render();
    }
}