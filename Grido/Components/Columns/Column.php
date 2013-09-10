<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
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
 * @property-write array $replacements
 * @property-write bool $sortable
 * @property string $column
 */
abstract class Column extends \Grido\Components\Component
{
    const ID = 'columns';

    const VALUE_IDENTIFIER = '%value';

    const ASC  = '↑';
    const DESC = '↓';

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

    /** @var mixed for custom rendering */
    protected $customRender;

    /** @var bool */
    protected $sortable = FALSE;

    /** @var array of arrays('pattern' => 'replacement') */
    protected $replacements = array();

    /** @var Closure */
    protected $truncate;

    /**
     * @param \Grido\Grid $grid
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
        $this->grid->setDefaultSort(array($this->name => $dir));
        return $this;
    }

    /**
     * @param mixed $customRender callback | string for name of template filename
     * @return Column
     */
    public function setCustomRender($customRender)
    {
        $this->customRender = $customRender;
        return $this;
    }

    /**
     * @param string $maxLen UTF-8 encoding
     * @param string $append UTF-8 encoding
     * @return Column
     */
    public function setTruncate($maxLen, $append = "\xE2\x80\xA6")
    {
        $this->truncate = function($string) use ($maxLen, $append) {
            return \Nette\Utils\Strings::truncate($string, $maxLen, $append);
        };
        return $this;
    }

    /**
     * @param callback $callback
     * @return \Grido\Components\Columns\Column
     */
    public function setCellCallback($callback)
    {
        $this->cellCallback = $callback;
        return $this;
    }

    /******************************* Aliases for filters ******************************************/

    /**
     * @return \Grido\Components\Filters\Text
     */
    public function setFilterText()
    {
        return $this->grid->addFilterText($this->name, $this->label);
    }

    /**
     * @return \Grido\Components\Filters\Date
     */
    public function setFilterDate()
    {
        return $this->grid->addFilterDate($this->name, $this->label);
    }

    /**
     * @return \Grido\Components\Filters\Check
     */
    public function setFilterCheck()
    {
        return $this->grid->addFilterCheck($this->name, $this->label);
    }

    /**
     * @param array $items
     * @return \Grido\Components\Filters\Select
     */
    public function setFilterSelect(array $items = NULL)
    {
        return $this->grid->addFilterSelect($this->name, $this->label, $items);
    }

    /**
     * @return \Grido\Components\Filters\Number
     */
    public function setFilterNumber()
    {
        return $this->grid->addFilterNumber($this->name, $this->label);
    }

    /**
     * @param \Nette\Forms\IControl $formControl
     * @return \Grido\Components\Filters\Custom
     */
    public function setFilterCustom(\Nette\Forms\IControl $formControl)
    {
        return $this->grid->addFilterCustom($this->name, $formControl);
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
            $this->headerPrototype->class[] = $this->getSort() == self::DESC
                ? 'desc'
                : 'asc';
        }

        return $this->headerPrototype;
    }

    /**
     * @internal - Do not call directly.
     * @return mixed
     */
    public function getColumn()
    {
        return $this->column ? $this->column : $this->getName();
    }

    /**
     * @internal - Do not call directly.
     * @return string
     */
    public function getSort()
    {
        if ($this->sort === NULL) {
            $name = $this->getName();

            $sort = isset($this->grid->sort[$name])
                ? $this->grid->sort[$name]
                : NULL;

            $this->sort = $sort === NULL ? NULL : strtolower($sort);
        }

        return $this->sort;
    }

    /**
     * @internal - Do not call directly.
     * @return mixed
     */
    public function getCustomRender()
    {
        return $this->customRender;
    }

    /**********************************************************************************************/

    /**
     * @internal - Do not call directly.
     * @return bool
     */
    public function isSortable()
    {
        return $this->sortable;
    }

    /**
     * @internal - Do not call directly.
     * @return bool
     */
    public function hasFilter()
    {
        return $this->grid->hasFilters() && $this->grid[Filter::ID]->getComponent($this->name, FALSE);
    }

    /**********************************************************************************************/

    /**
     * @internal - Do not call directly.
     * @param mixed $row
     * @return string
     */
    public function render($row)
    {
        if (is_callable($this->customRender)) {
            return callback($this->customRender)->invokeArgs(array($row));
        }

        $value = $this->getValue($row);
        if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
            $value = \Nette\Templating\Helpers::escapeHtml($value);
            $value = $this->applyReplacement($value);
        }

        return $this->formatValue($value);
    }

    /**
     * @internal - Do not call directly.
     * @param mixed $row
     * @return string
     */
    public function renderExport($row)
    {
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
            if (!$this->grid->propertyAccessor->hasProperty($row, $column)) {
                throw new \InvalidArgumentException("Column '$column' does not exist in datasource.");
            }
            return $this->grid->propertyAccessor->getProperty($row, $column);
        } elseif (is_callable($column)) {
            return callback($column)->invokeArgs(array($row));
        } else {
            throw new \InvalidArgumentException('Column must be string or callback.');
        }
    }

    protected function applyReplacement($value)
    {
        return (is_string($value) || $value == '' || $value === NULL) && isset($this->replacements[$value])
            ? str_replace(self::VALUE_IDENTIFIER, $value, $this->replacements[$value])
            : $value;
    }

    protected function formatValue($value)
    {
        if ($this->truncate) {
            $truncate = $this->truncate;
            $value = $truncate($value);
        }

        return $value;
    }
}
