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

use Grido\Components\Exports\BaseExport;
use Grido\Components\Exports\CsvExport;
use Grido\Grid;
use Grido\Helpers;
use Grido\Components\Actions\Action;
use Grido\Components\Columns\Column;
use Grido\Components\Filters\Filter;
use Grido\Components\Columns\Editable;

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
    /** @var bool */
    protected $hasColumns;

    /** @var bool */
    protected $hasFilters;

    /** @var bool */
    protected $hasActions;

    /** @var bool */
    protected $hasOperation;

    /** @var bool */
    protected $hasExport;

    /**
     * Returns column component.
     * @param string $name
     * @param bool $need
     * @return Editable
     */
    public function getColumn($name, $need = TRUE)
    {
        return $this->hasColumns()
            ? $this->getComponent(Column::ID)->getComponent(Helpers::formatColumnName($name), $need)
            : NULL;
    }

    /**
     * Returns filter component.
     * @param string $name
     * @param bool $need
     * @return Filter
     */
    public function getFilter($name, $need = TRUE)
    {
        return $this->hasFilters()
            ? $this->getComponent(Filter::ID)->getComponent(Helpers::formatColumnName($name), $need)
            : NULL;
    }

    /**
     * Returns action component.
     * @param string $name
     * @param bool $need
     * @return Action
     */
    public function getAction($name, $need = TRUE)
    {
        return $this->hasActions()
            ? $this->getComponent(Action::ID)->getComponent($name, $need)
            : NULL;
    }

    /**
     * Returns operations component.
     * @param bool $need
     * @return Operation
     */
    public function getOperation($need = TRUE)
    {
        return $this->getComponent(Operation::ID, $need);
    }

    /**
     * Returns export component.
     * @param string $name
     * @param bool $need
     * @return CsvExport
     */
    public function getExport($name = NULL, $need = TRUE)
    {
        if (is_bool($name) || $name === NULL) { // deprecated
            trigger_error('This usage of ' . __METHOD__ . '() is deprecated,
            please write name of export to first parameter.', E_USER_DEPRECATED);
            $export = $this->getComponent(BaseExport::ID, $name);
            if ($export) {
                $export = $export->getComponent(CsvExport::CSV_ID, is_bool($name) ? $name : TRUE);
            }
            return $export;
        }
        return $this->hasExport()
            ? $this->getComponent(BaseExport::ID)->getComponent(Helpers::formatColumnName($name), $need)
            : NULL;
    }

    /**
     * @param bool $need
     * @return BaseExport[]
     */
    public function getExports($need = TRUE)
    {
        $export = $this->getComponent(BaseExport::ID, $need);
        if ($export) {
            $export = $export->getComponents();
        }
        return $export;
    }

    /**********************************************************************************************/

    /**
     * @param bool $useCache
     * @return bool
     * @internal
     */
    public function hasColumns($useCache = TRUE)
    {
        $hasColumns = $this->hasColumns;

        if ($hasColumns === NULL || $useCache === FALSE) {
            $container = $this->getComponent(Column::ID, FALSE);
            $hasColumns = $container && count($container->getComponents()) > 0;
            $this->hasColumns = $useCache ? $hasColumns : NULL;
        }

        return $hasColumns;
    }

    /**
     * @param bool $useCache
     * @return bool
     * @internal
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
     * @param bool $useCache
     * @return bool
     * @internal
     */
    public function hasActions($useCache = TRUE)
    {
        $hasActions = $this->hasActions;

        if ($hasActions === NULL || $useCache === FALSE) {
            $container = $this->getComponent(Action::ID, FALSE);
            $hasActions = $container && count($container->getComponents()) > 0;
            $this->hasActions = $useCache ? $hasActions : NULL;
        }

        return $hasActions;
    }

    /**
     * @param bool $useCache
     * @return bool
     * @internal
     */
    public function hasOperation($useCache = TRUE)
    {
        $hasOperation = $this->hasOperation;

        if ($hasOperation === NULL || $useCache === FALSE) {
            $hasOperation = (bool) $this->getComponent(Operation::ID, FALSE);
            $this->hasOperation = $useCache ? $hasOperation : NULL;
        }

        return $hasOperation;
    }

    /**
     * @param bool $useCache
     * @return bool
     * @internal
     */
    public function hasExport($useCache = TRUE)
    {
        $hasExport = $this->hasExport;

        if ($hasExport === NULL || $useCache === FALSE) {
            $hasExport = (bool) $this->getExports(FALSE);
            $this->hasExport = $useCache ? $hasExport : NULL;
        }

        return $hasExport;
    }

    /**********************************************************************************************/

    /**
     * @param string $name
     * @param string $label
     * @return Columns\Text
     */
    public function addColumnText($name, $label)
    {
        return new Columns\Text($this, $name, $label);
    }

    /**
     * @param string $name
     * @param string $label
     * @return Columns\Email
     */
    public function addColumnEmail($name, $label)
    {
        return new Columns\Email($this, $name, $label);
    }

    /**
     * @param string $name
     * @param string $label
     * @return Columns\Link
     */
    public function addColumnLink($name, $label)
    {
        return new Columns\Link($this, $name, $label);
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $dateFormat
     * @return Columns\Date
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
     * @return Columns\Number
     */
    public function addColumnNumber($name, $label, $decimals = NULL, $decPoint = NULL, $thousandsSep = NULL)
    {
        return new Columns\Number($this, $name, $label, $decimals, $decPoint, $thousandsSep);
    }

    /**********************************************************************************************/

    /**
     * @param string $name
     * @param string $label
     * @return Filters\Text
     */
    public function addFilterText($name, $label)
    {
        return new Filters\Text($this, $name, $label);
    }

    /**
     * @param string $name
     * @param string $label
     * @return Filters\Date
     */
    public function addFilterDate($name, $label)
    {
        return new Filters\Date($this, $name, $label);
    }

    /**
     * @param string $name
     * @param string $label
     * @return Filters\DateRange
     */
    public function addFilterDateRange($name, $label)
    {
        return new Filters\DateRange($this, $name, $label);
    }

    /**
     * @param string $name
     * @param string $label
     * @return Filters\Check
     */
    public function addFilterCheck($name, $label)
    {
        return new Filters\Check($this, $name, $label);
    }

    /**
     * @param string $name
     * @param string $label
     * @param array $items
     * @return Filters\Select
     */
    public function addFilterSelect($name, $label, array $items = NULL)
    {
        return new Filters\Select($this, $name, $label, $items);
    }

    /**
     * @param string $name
     * @param string $label
     * @return Filters\Number
     */
    public function addFilterNumber($name, $label)
    {
        return new Filters\Number($this, $name, $label);
    }

    /**
     * @param string $name
     * @param \Nette\Forms\IControl $formControl
     * @return Filters\Custom
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
     * @param array $arguments
     * @return Actions\Href
     */
    public function addActionHref($name, $label, $destination = NULL, array $arguments = [])
    {
        return new Actions\Href($this, $name, $label, $destination, $arguments);
    }

    /**
     * @param string $name
     * @param string $label
     * @param callback $onClick
     * @return Actions\Event
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
    public function setOperation(array $operations, $onSubmit)
    {
        return new Operation($this, $operations, $onSubmit);
    }

    /**
     * @param string $label of exporting file
     * @return Export
     *
     * @deprecated
     */
    public function setExport($label = NULL)
    {
        trigger_error(__METHOD__ . '() is deprecated; use addExport instead.', E_USER_DEPRECATED);
        return $this->addExport(new CsvExport($label), CsvExport::CSV_ID);
    }

    /**
     * @param BaseExport $export
     * @param string $name Component name
     * @return BaseExport
     */
    public function addExport(BaseExport $export, $name)
    {
        $container = $this->getComponent(BaseExport::ID, FALSE);
        if (!$container) {
            $container = new \Nette\ComponentModel\Container();
            $this->addComponent($container, BaseExport::ID);
        }
        $container->addComponent($export, $name);
        return $export;
    }

    /**
     * Sets all columns as editable.
     * First parameter is optional and is for implementation of method for saving modified data.
     * @param callback $callback function($id, $newValue, $oldValue, Editable $column) {}
     * @return Grid
     */
    public function setEditableColumns($callback = NULL)
    {
        $this->onRender[] = function(Grid $grid) use ($callback) {
            if (!$grid->hasColumns()) {
                return;
            }

            foreach ($grid->getComponent(Column::ID)->getComponents() as $column) {
                if ($column instanceof Editable && !$column->isEditableDisabled() && !$column->editableCallback) {
                    $column->setEditable($callback);
                }
            }
        };

        return $this;
    }
}
