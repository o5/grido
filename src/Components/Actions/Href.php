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
     * @param array|null $args - second param for method $presenter->link()
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
     * @param mixed $row
     * @return \Nette\Utils\Html
     * @internal
     */
    public function getElement($row)
    {
        $element = parent::getElement($row);

        if ($this->customHref) {
            $href = callback($this->customHref)->invokeArgs(array($row));
        } else {
            $primaryKey = $this->getPrimaryKey();
            $primaryValue = $this->grid->getProperty($row, $primaryKey);

            $this->arguments[$primaryKey] = $primaryValue;
            $href = $this->presenter->link($this->getDestination(), $this->arguments);
        }

        $element->href($href);

        return $element;
    }

    /**
     * @return string
     * @internal
     */
    public function getDestination()
    {
        if ($this->destination === NULL) {
            $this->destination = $this->getName();
        }

        return $this->destination;
    }

    /**
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }
}
