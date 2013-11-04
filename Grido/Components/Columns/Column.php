<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Grido\Components\Columns;

use Grido\Components\Filters\Filter;

/**
 * Column grid.
 *
 * @package     Grido
 * @subpackage  Components\Columns
 * @author      Petr Bugyík
 *
 * @property-read string $sort
 * @property-read \Nette\Utils\Html $cellPrototype
 * @property-read \Nette\Utils\Html $headerPrototype
 * @property-write callback $cellCallback
 * @property-write string $defaultSorting
 * @property-write mixed $customRender
 * @property-write mixed $customRenderExport
 * @property-write array $replacements
 * @property-write bool $sortable
 * @property string $column
 */
abstract class Column extends \Grido\Components\Component
{
    const ID = 'columns';

    const VALUE_IDENTIFIER = '%value';

    const ORDER_ASC  = '↑';
    const ORDER_DESC = '↓';

    /** @var string */
    protected $sort;

    /** @var string */
    protected $column;

    /** @var \Nette\Utils\Html <td> html tag */
    protected $cellPrototype;

    /** @var callback returns td html element; function($row, Html $td) */
    protected $cellCallback;

    /** @var \Nette\Utils\Html <th> html tag */
    protected $headerPrototype;

    /** @var mixed custom rendering */
    protected $customRender;

    /** @var mixed custom export rendering */
    protected $customRenderExport;

    /** @var bool */
    protected $sortable = FALSE;

    /** @var array of arrays('pattern' => 'replacement') */
    protected $replacements = array();

    /**
     * @param Grido\Grid $grid
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
     * @param bool $sortable
     * @return Column
     */
    public function setSortable($sortable = TRUE)
    {
        $this->sortable = (bool) $sortable;
        return $this;
    }

    /**
     * @param array $replacement array('pattern' => 'replacement')
     * @return Column
     */
    public function setReplacement(array $replacement)
    {
        $this->replacements = $this->replacements + $replacement;
        return $this;
    }

    /**
     * @param mixed $column
     * @return Column
     */
    public function setColumn($column)
    {
        $this->column = $column;
        return $this;
    }

    /**
     * @param string $dir
     * @return Column
     */
    public function setDefaultSort($dir)
    {
        $this->grid->setDefaultSort(array($this->getName() => $dir));
        return $this;
    }

    /**
     * @param mixed $callback callback or string for name of template filename
     * @return Column
     */
    public function setCustomRender($callback)
    {
        $this->customRender = $callback;
        return $this;
    }

    /**
     * @param mixed $callback|
     * @return Column
     */
    public function setCustomRenderExport($callback)
    {
        $this->customRenderExport = $callback;
        return $this;
    }

    /**
     * @param callback $callback
     * @return Column
     */
    public function setCellCallback($callback)
    {
        $this->cellCallback = $callback;
        return $this;
    }

    /**********************************************************************************************/

    /**
     * Returns cell prototype (<td> html tag).
     * @param mixed $row
     * @return \Nette\Utils\Html
     */
    public function getCellPrototype($row = NULL)
    {
        $td = $this->cellPrototype;

        if ($td === NULL) { //cache
            $td = $this->cellPrototype = \Nette\Utils\Html::el('td')
                ->setClass(array('grid-cell-' . $this->getName()));
        }

        if ($this->cellCallback && $row !== NULL) {
            $td = clone $td;
            $td = callback($this->cellCallback)->invokeArgs(array($row, $td));
        }

        return $td;
    }

    /**
     * Returns header cell prototype (<th> html tag).
     * @return \Nette\Utils\Html
     */
    public function getHeaderPrototype()
    {
        if (!$this->headerPrototype) {
            $this->headerPrototype = \Nette\Utils\Html::el('th')
                ->setClass(array('column', 'grid-header-' . $this->getName()));
        }

        if ($this->isSortable() && $this->getSort()) {
            $this->headerPrototype->class[] = $this->getSort() == self::ORDER_DESC
                ? 'desc'
                : 'asc';
        }

        return $this->headerPrototype;
    }

    /**
     * @return mixed
     * @internal
     */
    public function getColumn()
    {
        return $this->column ? $this->column : $this->getName();
    }

    /**
     * @return string
     * @internal
     */
    public function getSort()
    {
        if ($this->sort === NULL) {
            $name = $this->getName();

            $sort = isset($this->grid->sort[$name])
                ? $this->grid->sort[$name]
                : NULL;

            $this->sort = $sort === NULL ? NULL : $sort;
        }

        return $this->sort;
    }

    /**
     * @return mixed
     * @internal
     */
    public function getCustomRender()
    {
        return $this->customRender;
    }

    /**********************************************************************************************/

    /**
     * @return bool
     * @internal
     */
    public function isSortable()
    {
        return $this->sortable;
    }

    /**
     * @return bool
     * @internal
     */
    public function hasFilter()
    {
        return $this->grid->hasFilters() && $this->grid->getComponent(Filter::ID)->getComponent($this->getName(), FALSE);
    }

    /**********************************************************************************************/

    /**
     * @param mixed $row
     * @return string
     * @internal
     */
    public function render($row)
    {
        if (is_callable($this->customRender)) {
            return callback($this->customRender)->invokeArgs(array($row));
        }

        $value = $this->getValue($row);
        return $this->formatValue($value);
    }

    /**
     * @param mixed $row
     * @return string
     * @internal
     */
    public function renderExport($row)
    {
        if (is_callable($this->customRenderExport)) {
            return callback($this->customRenderExport)->invokeArgs(array($row));
        }

        $value = $this->getValue($row);
        return strip_tags($this->applyReplacement($value));
    }

    /**
     * @param mixed $row
     * @throws \InvalidArgumentException
     * @return mixed
     */
    protected function getValue($row)
    {
        $column = $this->getColumn();
        if (is_string($column)) {
            return $this->propertyAccessor->getProperty($row, $column);

        } elseif (is_callable($column)) {
            return callback($column)->invokeArgs(array($row));

        } else {
            throw new \InvalidArgumentException('Column must be string or callback.');
        }
    }

    /***
     * @param mixed $value
     * @return mixed
     */
    protected function applyReplacement($value)
    {
        return (is_string($value) || $value == '' || $value === NULL) && isset($this->replacements[$value])
            ? str_replace(static::VALUE_IDENTIFIER, $value, $this->replacements[$value])
            : $value;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    protected function formatValue($value)
    {
        if (is_null($value) || is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
            $value = \Nette\Templating\Helpers::escapeHtml($value);
            $value = $this->applyReplacement($value);
        }

        return $value;
    }

    /******************************* Aliases for filters ******************************************/

    /**
     * @return Grido\Components\Filters\Text
     */
    public function setFilterText()
    {
        return $this->grid->addFilterText($this->getName(), $this->label);
    }

    /**
     * @return Grido\Components\Filters\Date
     */
    public function setFilterDate()
    {
        return $this->grid->addFilterDate($this->getName(), $this->label);
    }

    /**
     * @return Grido\Components\Filters\Check
     */
    public function setFilterCheck()
    {
        return $this->grid->addFilterCheck($this->getName(), $this->label);
    }

    /**
     * @param array $items
     * @return Grido\Components\Filters\Select
     */
    public function setFilterSelect(array $items = NULL)
    {
        return $this->grid->addFilterSelect($this->getName(), $this->label, $items);
    }

    /**
     * @return Grido\Components\Filters\Number
     */
    public function setFilterNumber()
    {
        return $this->grid->addFilterNumber($this->getName(), $this->label);
    }

    /**
     * @param \Nette\Forms\IControl $formControl
     * @return Grido\Components\Filters\Custom
     */
    public function setFilterCustom(\Nette\Forms\IControl $formControl)
    {
        return $this->grid->addFilterCustom($this->getName(), $formControl);
    }
}
