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
     * @param mixed $item
     * @return \Nette\Utils\Html
     */
    public function getElement($item)
    {
        $element = parent::getElement($item);

        $href = '';
        $primaryKey = $this->getPrimaryKey();
        $primaryValue = $this->grid->propertyAccessor->hasProperty($item, $primaryKey)
            ? $this->grid->propertyAccessor->getProperty($item, $primaryKey)
            : NULL;

        if ($this->customHref) {
            $href = callback($this->customHref)->invokeArgs(array($item));
        } elseif ($primaryValue) {
            $href = $this->presenter->link($this->getDestination(), $this->getArguments($item));
        }

        $element->href($href);

        return $element;
    }

    /**
     * @internal - Do not call directly.
     * @return string
     */
    public function getDestination()
    {
        if ($this->destination === NULL) {
            $this->destination = $this->name;
        }

        return $this->destination;
    }

    /**
     * @internal - Do not call directly.
     * @return array
     */
    public function getArguments($item = NULL)
    {
        if ($this->arguments === NULL && $item !== NULL) {
            //@todo: remove code below
            $primaryKey = $this->getPrimaryKey();
            $primaryValue = $this->grid->propertyAccessor->hasProperty($item, $primaryKey)
                ? $this->grid->propertyAccessor->getProperty($item, $primaryKey)
                : NULL;

            $this->arguments[$primaryKey] = $primaryValue;
        }

        return $this->arguments;
    }
}
