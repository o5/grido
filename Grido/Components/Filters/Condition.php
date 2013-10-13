<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 */

namespace Grido\Components\Filters;

/**
 * Filter condition wrapper.
 *
 * @package     Grido
 * @subpackage  Components\Filters
 * @author      Petr Bugyík
 *
 * @property-read callable $callback
 * @property-write array $column
 * @property-write string $condition
 * @property-write array $value
 */
class Condition extends \Nette\Object
{
    /** @var array */
    protected $column;

    /** @var string */
    protected $condition;

    /** @var array */
    protected $value;

    /** @var callable */
    protected $callback;

    /**
     * @param mixed $column
     * @param string $condition
     * @param mixed $value
     */
    public function __construct($column, $condition, $value = NULL)
    {
        $this->setColumn($column);
        $this->setCondition($condition);
        $this->setValue($value);
    }

    /**
     * @param mixed $column
     * @return \Grido\Components\Filters\Condition
     */
    public function setColumn($column)
    {
        if (is_string($column)) {
            $column = (array) $column;
        } elseif (is_array($column)) {
            //check validity
            for ($i = 0; $i < count($column); $i++) {
                $item = strtoupper($column[$i]);
                if ($i & 1 && !in_array($item, array(Filter::OPERATOR_AND, Filter::OPERATOR_OR))) {
                    throw new \InvalidArgumentException("The even values of column must be Filter::OPERATOR_AND or Filter::OPERATOR_OR, '$column[$i]' given.");
                }
            }
        }

        $this->column = $column;
        return $this;
    }

    /**
     * @param string $condition
     * @return \Grido\Components\Filters\Condition
     */
    public function setCondition($condition)
    {
        $this->condition = $condition;
        return $this;
    }

    /**
     * @param mixed $value
     * @return \Grido\Components\Filters\Condition
     */
    public function setValue($value)
    {
        $this->value = (array) $value;
        return $this;
    }

    /**********************************************************************************************/

    /**
     * @return array
     */
    public function getColumn()
    {
        return $this->column;
    }

    /**
     * @return string
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * @return array
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return array
     */
    public function getValueForColumn()
    {
        $values = array();
        foreach ($this->getColumn() as $column) {
            if (!in_array(strtoupper($column), array(Filter::OPERATOR_AND, Filter::OPERATOR_OR))) {
                foreach ($this->getValue() as $val) {
                    $values[] = $val;
                }
            }
        }

        return $values;
    }

    /**
     * @return callable
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**********************************************************************************************/

    /**
     * @param mixed $column
     * @param string $condition
     * @param mixed $value
     * @return \Grido\Components\Filters\Condition
     */
    public static function setup($column, $condition, $value)
    {
        return new self($column, $condition, $value);
    }

    /**
     * @return \Grido\Components\Filters\Condition
     */
    public static function setupEmpty()
    {
        return new self(NULL, '0 = 1');
    }

    /**
     * @param array $condition
     * @throws \InvalidArgumentException
     * @return \Grido\Components\Filters\Condition
     */
    public static function setupFromArray(array $condition)
    {
        if (count($condition) !== 3) {
            throw new \InvalidArgumentException("Condition array must contain 3 items.");
        }

        return new self($condition[0], $condition[1], $condition[2]);
    }

    /**
     * @param callabke $callback
     * @param string $value
     * @return \Grido\Components\Filters\Condition
     */
    public static function setupFromCallback($callback, $value)
    {
        $self = new self(NULL, NULL);
        $self->value = $value;
        $self->callback = $callback;

        return $self;
    }

    /**********************************************************************************************/

    /**
     * @param string $prefix - column prefix
     * @param string $suffix - column suffix
     * @param bool $brackets - add brackets when multiple where
     * @return array
     */
    public function __toArray($prefix = NULL, $suffix = NULL, $brackets = TRUE)
    {
        $condition = array();
        $columns = $this->getColumn();
        $addBrackets = $brackets && count($columns) > 1;

        if ($addBrackets) {
            $condition[] = '(';
        }

        foreach ($columns as $column) {
            $operator = strtoupper($column);
            $condition[] = in_array($operator, array(Filter::OPERATOR_AND, Filter::OPERATOR_OR))
                ? " $operator "
                : "{$prefix}$column{$suffix} {$this->getCondition()}";
        }

        if ($addBrackets) {
            $condition[] = ')';
        }

        if (($count = substr_count($this->getCondition(), '?')) && $count !== count($this->getValue())) {
            throw new \InvalidArgumentException("Condition '{$this->getCondition()}' requires $count values.");
        }

        return $condition
            ? array_values(array_merge(array(implode('', $condition)), $this->getValueForColumn()))
            : (array) $this->getCondition();
    }
}
