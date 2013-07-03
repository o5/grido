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
     * @param $item
     * @return \Nette\Utils\Html
     */
    public function getElement($item)
    {
        $el = parent::getElement($item);

        $pk = $this->getPrimaryKey();
        $hasPk = $this->grid->propertyAccessor->hasProperty($item, $pk);

        $href = NULL;
        if ($this->customHref) {
            $href = callback($this->customHref)->invokeArgs(array($item));
        } elseif ($hasPk) {
            $this->arguments[$pk] = $this->grid->propertyAccessor->getProperty($item, $pk);
            $href = $this->presenter->link($this->getDestination(), $this->arguments);
        }

        if ($href) {
            $el->href($href);
        }

        return $el;
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
}
