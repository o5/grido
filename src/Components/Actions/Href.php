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
 * @property-read string $destination
 * @property-read array $arguments
 */
class Href extends Action
{
    /** @var string first param for method $presenter->link() */
    protected $destination;

    /** @var array second param for method $presenter->link() */
    protected $arguments = [];

    /** @var callback for custom href attribute creating */
    protected $customHref;

    /** @var bool flag if we should add filter values to arguments */
    protected $addFilterValues = FALSE;

    /**
     * @param \Grido\Grid $grid
     * @param string $name
     * @param string $label
     * @param string $destination - first param for method $presenter->link()
     * @param array $arguments - second param for method $presenter->link()
     */
    public function __construct($grid, $name, $label, $destination = NULL, array $arguments = [])
    {
        parent::__construct($grid, $name, $label);

        $this->destination = $destination;
        $this->arguments = $arguments;
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

    /**
     * Turns on adding filter values to link arguments.
     * @return Href
     */
    public function setAddFilterValues()
    {
        $this->addFilterValues = TRUE;
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
            $href = call_user_func_array($this->customHref, [$row]);
        } else {
            $primaryKey = $this->getPrimaryKey();
            $primaryValue = $this->grid->getProperty($row, $primaryKey);

            $arguments = $this->addFilterValues
                ? array_merge($this->composeFilterParams(), $this->arguments)
                : $this->arguments;
            $arguments[$primaryKey] = $primaryValue;

            $href = $this->presenter->link($this->getDestination(), $arguments);
        }

        $element->href($href);

        return $element;
    }

    /**
     * @return array
     * @internal
     */
    private function composeFilterParams()
    {
        $oarams = [];

        foreach ($this->grid->getActualFilter() as $key => $value) {
            $params[$this->grid->getName() . "-filter[$key]"] = $value;
        }

        foreach ($this->grid->sort as $key => $value) {
            $params[$this->grid->getName() . "-sort[$key]"] = $value;
        }

        if ($this->grid->page > 1) {
            $params[$this->grid->getName() . '-page'] = $this->grid->page;
        }

        if ($this->grid->perPage) {
            $params[$this->grid->getName() . '-perPage'] = $this->grid->perPage;
        }

        return $params;
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
     * @internal
     */
    public function getArguments()
    {
        return $this->arguments;
    }
}
