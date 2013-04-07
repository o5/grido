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
     * @param mixed $item
     * @throws \InvalidArgumentException
     * @return void
     */
    public function render($item)
    {
        if ($this->customRender) {
            echo callback($this->customRender)->invokeArgs(array($item));
            return;
        }

        $pk = $this->getPrimaryKey();

        if (!$item || ($this->disable && callback($this->disable)->invokeArgs(array($item)))) {
            return;
        } elseif (!$this->getGrid()->getPropertyAccessor()->hasProperty($item, $pk)) {
            throw new \InvalidArgumentException("Primary key '$pk' not found.");
        }

        if ($this->customHref) {
            $href = callback($this->customHref)->invokeArgs(array($item));
        } else {
            $this->arguments[$pk] = $this->getGrid()->getPropertyAccessor()->getProperty($item, $pk);
            $href = $this->presenter->link($this->getDestination(), $this->arguments);
        }

        $text = $this->translate($this->label);
        $this->icon ? $text = ' '.$text : $text;

        $el = $this->getElementPrototype()
            ->href($href)
            ->setText($text);

        if ($this->confirm) {
            $el->attrs['data-grido-confirm'] = $this->translate(
                is_callable($this->confirm)
                    ? callback($this->confirm)->invokeArgs(array($item))
                    : $this->confirm
            );
        }

        if ($this->icon) {
            $el->insert(0,\Nette\Utils\Html::el('i')->setClass(array("icon-$this->icon")));
        }

        echo $el->render();
    }
}