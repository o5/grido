<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Grido\Components\Filters;

use Grido\Helpers;
use Grido\Exception;

/**
 * Data filtering.
 *
 * @package     Grido
 * @subpackage  Components\Filters
 * @author      Petr Bugyík
 *
 * @property-read array $column
 * @property-read string $wrapperPrototype
 * @property-read \Nette\Forms\Controls\BaseControl $control
 * @property-write string $condition
 * @property-write callable $where
 * @property-write string $formatValue
 * @property-write string $defaultValue
 */
abstract class Filter extends \Grido\Components\Component
{
    const ID = 'filters';

    const VALUE_IDENTIFIER = '%value';

    const RENDER_INNER = 'inner';
    const RENDER_OUTER = 'outer';

    /** @var mixed */
    protected $optional;

    /** @var array */
    protected $column = array();

    /** @var string */
    protected $condition = '= ?';

    /** @var callable */
    protected $where;

    /** @var string */
    protected $formatValue;

    /** @var \Nette\Utils\Html */
    protected $wrapperPrototype;

    /** @var \Nette\Forms\Controls\BaseControl */
    protected $control;

    /**
     * @param \Grido\Grid $grid
     * @param string $name
     * @param string $label
     */
    public function __construct($grid, $name, $label)
    {
        $name = Helpers::formatColumnName($name);
        $this->addComponentToGrid($grid, $name);

        $this->label = $label;
        $this->type = get_class($this);

        $form = $this->getForm();
        $filters = $form->getComponent(self::ID, FALSE);
        if ($filters === NULL) {
            $filters = $form->addContainer(self::ID);
        }

        $filters->addComponent($this->getFormControl(), $name);
    }

    /**********************************************************************************************/

    /**
     * Map to database column.
     * @param string $column
     * @param string $operator
     * @return Filter
     * @throws Exception
     */
    public function setColumn($column, $operator = Condition::OPERATOR_OR)
    {
        $columnAlreadySet = count($this->column) > 0;
        if (!Condition::isOperator($operator) && $columnAlreadySet) {
            throw new Exception('Operator must be Condition::OPERATOR_AND or Condition::OPERATOR_OR.');
        }

        if ($columnAlreadySet) {
            $this->column[] = $operator;
            $this->column[] = $column;
        } else {
            $this->column[] = $column;
        }

        return $this;
    }

    /**
     * Sets custom condition.
     * @param $condition
     * @return Filter
     */
    public function setCondition($condition)
    {
        $this->condition = $condition;
        return $this;
    }

    /**
     * Sets custom "sql" where.
     * @param callable $callback function($value, $source) {}
     * @return Filter
     */
    public function setWhere($callback)
    {
        $this->where = $callback;
        return $this;
    }

    /**
     * Sets custom format value.
     * @param string $format for example: "%%value%"
     * @return Filter
     */
    public function setFormatValue($format)
    {
        $this->formatValue = $format;
        return $this;
    }

    /**
     * Sets default value.
     * @param string $value
     * @return Filter
     */
    public function setDefaultValue($value)
    {
        $this->grid->setDefaultFilter(array($this->getName() => $value));
        return $this;
    }

    /**********************************************************************************************/

    /**
     * @return array
     * @internal
     */
    public function getColumn()
    {
        if (!$this->column) {
            $column = $this->getName();
            if ($columnComponent = $this->grid->getColumn($column, FALSE)) {
                $column = $columnComponent->column; //use db column from column compoment
            }

            $this->setColumn($column);
        }

        return $this->column;
    }

    /**
     * @return \Nette\Forms\Controls\BaseControl
     * @internal
     */
    public function getControl()
    {
        if ($this->control === NULL) {
            $this->control = $this->getForm()->getComponent(self::ID)->getComponent($this->getName());
        }

        return $this->control;
    }

    /**
     * Returns wrapper prototype (<th> html tag).
     * @return \Nette\Utils\Html
     */
    public function getWrapperPrototype()
    {
        if (!$this->wrapperPrototype) {
            $this->wrapperPrototype = \Nette\Utils\Html::el('th')
                ->setClass(array('grid-filter-' . $this->getName()));
        }

        return $this->wrapperPrototype;
    }

    /**
     * @return string
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * @param mixed $value
     * @return Condition|bool
     * @throws Exception
     * @internal
     */
    public function __getCondition($value)
    {
        if ($value === '' || $value === NULL) {
            return FALSE; //skip
        }

        $condition = $this->getCondition();

        if ($this->where !== NULL) {
            $condition = Condition::setupFromCallback($this->where, $value);

        } elseif (is_string($condition)) {
            $condition = Condition::setup($this->getColumn(), $condition, $this->formatValue($value));

        } elseif (is_callable($condition)) {
            $condition = callback($condition)->invokeArgs(array($value));

        } elseif (is_array($condition)) {
            $condition = isset($condition[$value])
                ? $condition[$value]
                : Condition::setupEmpty();
        }

        if (is_array($condition)) { //for user-defined condition by array or callback
            $condition = Condition::setupFromArray($condition);

        } elseif ($condition !== NULL && !$condition instanceof Condition) {
            $type = gettype($condition);
            throw new Exception("Condition must be array or Condition object. $type given.");
        }

        return $condition;
    }

    /**********************************************************************************************/

    /**
     * Format value for database.
     * @param string $value
     * @return string
     */
    protected function formatValue($value)
    {
        if ($this->formatValue !== NULL) {
            return str_replace(static::VALUE_IDENTIFIER, $value, $this->formatValue);
        } else {
            return $value;
        }
    }

    /**
     * Value representation in URI.
     * @param string $value
     * @return string
     * @internal
     */
    public function changeValue($value)
    {
        return $value;
    }
}
