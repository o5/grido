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
 * @property-write array $customHref
 */
class Href extends Action
{
    /** @var array callback */
    public $onClick;

    /** @var string first param for method $presenter->link() */
    protected $destination;

    /** @var array second param for method $presenter->link() */
    protected $arguments = array();

    /** @var callback for custom href attribute creating */
    protected $customHref;

    /**
     * @param \Grido\Grid $grid
     * @param string $name
     * @param string $label
     * @param string $destination - first param for method $presenter->link()
     * @param array $args - second param for method $presenter->link()
     */
    public function __construct($grid, $name, $label, $destination = NULL, array $args = NULL)
    {
        parent::__construct($grid, $name, $label);

        $this->destination = $destination;
        $this->arguments = $args;
    }

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

    /**********************************************************************************************/

    /**
     * @param $item
     * @return \Nette\Utils\Html
     */
    public function getElement($item)
    {
        $element = parent::getElement($item);
        $primaryKey = $this->getPrimaryKey();
        $propertyAccessor = $this->grid->propertyAccessor;

        if ($this->customRender) {
            return $element;
        } elseif ($this->customHref) {
            $href = callback($this->customHref)->invokeArgs(array($item));
        } elseif ($this->onClick) {
            $href = $this->link('click!', $propertyAccessor->getProperty($item, $primaryKey));
        } else {
            $this->arguments[$primaryKey] = $propertyAccessor->getProperty($item, $primaryKey);
            $href = $this->presenter->link($this->getDestination(), $this->arguments);
        }

        $element->href($href);

        return $element;
    }

    /**
     * @internal
     * @return string
     */
    protected function getDestination()
    {
        if ($this->destination === NULL) {
            $this->destination = $this->name;
        }

        return $this->destination;
    }

    /**********************************************************************************************/

    /**
     * @internal
     * @param $id
     */
    public function handleClick($id)
    {
        $this->onClick($id, $this);
    }
}
