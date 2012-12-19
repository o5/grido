<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 */

namespace Grido\Columns;

use Grido\Filters\Filter;

/**
 * Column grid.
 *
 * @package     Grido
 * @subpackage  Columns
 * @author      Petr Bugyík
 *
 * @property-read string $sort
 * @property-read \Nette\Utils\Html $cellPrototype
 * @property-write string $defaultSorting
 * @property-write callback $customRender
 * @property-write array $replacements
 * @property-write bool $sortable
 * @property string $column
 */
abstract class Column extends \Grido\Base
{
    const ID = 'columns';

    const TYPE_TEXT = 'Grido\Columns\Text';
    const TYPE_MAIL = 'Grido\Columns\Mail';
    const TYPE_HREF = 'Grido\Columns\Href';
    const TYPE_DATE = 'Grido\Columns\Date';

    const ASC  = '↑';
    const DESC = '↓';

    /** @var string */
    protected $sort;

    /** @var string */
    protected $column;

    /** @var \Nette\Utils\Html <td> html tag */
    protected $cellPrototype;

    /** @var callback for custom rendering */
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
     * @param string $type see filter's constants starting at TYPE_
     * @param mixed $optional if type is select, then this it items for select
     * @return Filter
     */
    public function setFilter($type = Filter::TYPE_TEXT, $optional = NULL)
    {
        return $this->grid->addFilter($this->name, $this->label, $type, $optional);
    }

    /**
     * @param string $column
     * @return Column
     */
    public function setColumn($column)
    {
        $this->column = $column;
        return $this;
    }

    /**
     * @param string $sorting
     * @return Column
     */
    public function setDefaultSorting($sorting)
    {
        $this->grid->setDefaultSorting(array($this->getName() => strtolower($sorting)));
        return $this;
    }


    /**
     * @param callback $callback array|closure
     * @return Column
     */
    public function setCustomRender($callback)
    {
        $this->customRender = $callback;
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

    /**********************************************************************************************/

    /**
     * Returns cell prototype (<td> html tag).
     * @return \Nette\Utils\Html
     */
    public function getCellPrototype()
    {
        if (!$this->cellPrototype) {
            $this->cellPrototype = \Nette\Utils\Html::el('td')
                ->setClass(array('grid-cell-' . $this->getName()));
        }

        return $this->cellPrototype;
    }

    /**
     * @internal
     * @return string
     */
    public function getColumn()
    {
        return $this->column ? $this->column : $this->getName();
    }

    /**
     * @internal
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

    /**********************************************************************************************/

    /**
     * @internal
     * @return bool
     */
    public function isSortable()
    {
        return $this->sortable;
    }

    /**
     * @internal
     * @return bool
     */
    public function hasFilter()
    {
        return $this->getForm()->getComponent(Filter::ID)->getComponent($this->name, FALSE);
    }

    /**********************************************************************************************/

    /**
     * @internal
     * @param mixed $row
     * @return void
     */
    public function render($row)
    {
        if ($this->customRender) {
            return callback($this->customRender)->invokeArgs(array($row));
        }

        $value = NULL;
        $column = $this->getColumn();
        $value = $row->$column;

        $value = \Nette\Templating\Helpers::escapeHtml($value);

        if (isset($this->replacements[$value])) {
            $value = str_replace('%value', $value, $this->replacements[$value]);
        }

        return $this->formatValue($value);
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
