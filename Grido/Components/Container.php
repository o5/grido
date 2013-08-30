<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 */

namespace Grido\Components;

use Grido\Components\Columns\Column,
    Grido\Components\Filters\Filter,
    Grido\Components\Actions\Action;


/**
 * Container of grid components.
 *
 * @package     Grido
 * @subpackage  Components
 * @author      Petr Bugyík
 *
 */
abstract class Container extends \Nette\Application\UI\Control
{
    /** @var bool cache */
    protected $hasFilters, $hasActions, $hasOperations, $hasExport;

    /**
     * Returns column component.
     * @param string $name
     * @param bool $need
     * @return Column
     */
    public function getColumn($name, $need = TRUE)
    {
        return $this[Column::ID]->getComponent($name, $need);
    }

    /**
     * Returns filter component.
     * @param string $name
     * @param bool $need
     * @return Filter
     */
    public function getFilter($name, $need = TRUE)
    {
        return $this[Filter::ID]->getComponent($name, $need);
    }

    /**
     * Returns action component.
     * @param string $name
     * @param bool $need
     * @return Action
     */
    public function getAction($name, $need = TRUE)
    {
        return $this[Action::ID]->getComponent($name, $need);
    }

    /**
     * Returns operations component.
     * @param bool $need
     * @return Operation
     */
    public function getOperations($need = TRUE)
    {
        return $this->getComponent(Operation::ID, $need);
    }

    /**
     * Returns export component.
     * @param bool $need
     * @return Export
     */
    public function getExport($need = TRUE)
    {
        return $this->getComponent(Export::ID, $need);
    }

    /**********************************************************************************************/

    /**
     * @internal
     * @param bool $useCache
     * @return bool
     */
    public function hasFilters($useCache = TRUE)
    {
        $hasFilters = $this->hasFilters;

        if ($hasFilters === NULL || $useCache === FALSE) {
            $container = $this->getComponent(Filter::ID, FALSE);
            $hasFilters = $container && count($container->getComponents()) > 0;
            $this->hasFilters = $useCache ? $hasFilters : NULL;
        }

        return $hasFilters;
    }

    /**
     * @internal
     * @param bool $useCache
     * @return bool
     */
    public function hasActions($useCache = TRUE)
    {
        $hasActions = $this->hasActions;

        if ($hasActions === NULL || $useCache === FALSE) {
            $container = $this->getComponent(Action::ID, FALSE);
            $hasActions= $container && count($container->getComponents()) > 0;
            $this->hasActions = $useCache ? $hasActions : NULL;
        }

        return $hasActions;
    }

    /**
     * @internal
     * @param bool $useCache
     * @return bool
     */
    public function hasOperations($useCache = TRUE)
    {
        $hasOperations = $this->hasOperations;

        if ($hasOperations === NULL || $useCache === FALSE) {
            $hasOperations = (bool) $this->getComponent(Operation::ID, FALSE);
            $this->hasOperations = $useCache ? $hasOperations : NULL;
        }

        return $hasOperations;
    }

    /**
     * @internal
     * @param bool $useCache
     * @return bool
     */
    public function hasExport($useCache = TRUE)
    {
        $hasExport = $this->hasExport;

        if ($hasExport === NULL || $useCache === FALSE) {
            $hasExport = (bool) $this->getComponent(Export::ID, FALSE);
            $this->hasExport = $useCache ? $hasExport : NULL;
        }

        return $hasExport;
    }

    /**********************************************************************************************/
    /**********************************************************************************************/

    /**
     * @param string $name
     * @param string $label
     * @return \Grido\Columns\Text
     */
    public function addColumnText($name, $label)
    {
        return new Columns\Text($this, $name, $label);
    }

    /**
     * @param string $name
     * @param string $label
     * @return \Grido\Columns\Mail
     */
    public function addColumnMail($name, $label)
    {
        return new Columns\Mail($this, $name, $label);
    }

    /**
     * @param string $name
     * @param string $label
     * @return \Grido\Columns\Href
     */
    public function addColumnHref($name, $label)
    {
        return new Columns\Href($this, $name, $label);
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $dateFormat
     * @return \Grido\Columns\Date
     */
    public function addColumnDate($name, $label, $dateFormat = NULL)
    {
        return new Columns\Date($this, $name, $label, $dateFormat);
    }

    /**
     * @param string $name
     * @param string $label
     * @param int $decimals number of decimal points
     * @param string $decPoint separator for the decimal point
     * @param string $thousandsSep thousands separator
     * @return \Grido\Columns\Number
     */
    public function addColumnNumber($name, $label, $decimals = NULL, $decPoint = NULL, $thousandsSep = NULL)
    {
        return new Columns\Number($this, $name, $label, $decimals, $decPoint, $thousandsSep);
    }

    /**********************************************************************************************/

    /**
     * @param string $name
     * @param string $label
     * @return \Grido\Filters\Text
     */
    public function addFilterText($name, $label)
    {
        return new Filters\Text($this, $name, $label);
    }

    /**
     * @param string $name
     * @param string $label
     * @return \Grido\Filters\Date
     */
    public function addFilterDate($name, $label)
    {
        return new Filters\Date($this, $name, $label);
    }

    /**
     * @param string $name
     * @param string $label
     * @return \Grido\Filters\Check
     */
    public function addFilterCheck($name, $label)
    {
        return new Filters\Check($this, $name, $label);
    }

    /**
     * @param string $name
     * @param string $label
     * @param array $items
     * @return \Grido\Filters\Select
     */
    public function addFilterSelect($name, $label, array $items = NULL)
    {
        return new Filters\Select($this, $name, $label, $items);
    }

    /**
     * @param string $name
     * @param string $label
     * @return \Grido\Filters\Number
     */
    public function addFilterNumber($name, $label)
    {
        return new Filters\Number($this, $name, $label);
    }

    /**
     * @param string $name
     * @param \Nette\Forms\IControl $formControl
     * @return \Grido\Filters\Custom
     */
    public function addFilterCustom($name, \Nette\Forms\IControl $formControl)
    {
        return new Filters\Custom($this, $name, NULL, $formControl);
    }

    /**********************************************************************************************/

    /**
     * @param string $name
     * @param string $label
     * @param string $destination
     * @param array $args
     * @return \Grido\Actions\Href
     */
    public function addActionHref($name, $label, $destination = NULL, array $args = NULL)
    {
        return new Actions\Href($this, $name, $label, $destination, $args);
    }

    /**
     * @param string $name
     * @param string $label
     * @param callback $onClick
     * @return \Grido\Actions\Event
     */
    public function addActionEvent($name, $label, $onClick = NULL)
    {
        return new Actions\Event($this, $name, $label, $onClick);
    }

    /**********************************************************************************************/

    /**
     * @param array $operations
     * @param callback $onSubmit - callback after operation submit
     * @return Operation
     */
    public function setOperations(array $operations, $onSubmit)
    {
        return new Operation($this, $operations, $onSubmit);
    }

    /**
     * @param string $label of exporting file
     * @return Export
     */
    public function setExport($label = NULL)
    {
        return new Export($this, $label);
    }
}
