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

use Grido\Exception;

/**
 * Builds filter condition.
 *
 * @package     Grido
 * @subpackage  Components\Filters
 * @author      Petr Bugyík
 *
 * @property array $column
 * @property array $condition
 * @property mixed $value
 * @property-read callable $callback
 */
class Condition extends \Nette\Object
{
    const OPERATOR_OR = 'OR';
    const OPERATOR_AND = 'AND';

    /** @var array */
    protected $column;

    /** @var array */
    protected $condition;

    /** @var mixed */
    protected $value;

    /** @var callable */
    protected $callback;

    /**
     * @param mixed $column
     * @param mixed $condition
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
     * @throws Exception
     * @return Condition
     */
    public function setColumn($column)
    {
        if (is_array($column)) {
            $count = count($column);

            //check validity
            if ($count % 2 === 0) {
                throw new Exception('Count of column must be odd.');
            }

            for ($i = 0; $i < $count; $i++) {
                $item = $column[$i];
                if ($i & 1 && !self::isOperator($item)) {
                    $msg = "The even values of column must be 'AND' or 'OR', '$item' given.";
                    throw new Exception($msg);
                }
            }
        } else {
            $column = (array) $column;
        }

        $this->column = $column;
        return $this;
    }

    /**
     * @param mixed $condition
     * @return Condition
     */
    public function setCondition($condition)
    {
        $this->condition = (array) $condition;
        return $this;
    }

    /**
     * @param mixed $value
     * @return Condition
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
     * @return array
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
        if (count($this->condition) > 1) {
            return $this->value;
        }

        $values = array();
        foreach ($this->getColumn() as $column) {
            if (!self::isOperator($column)) {
                foreach ($this->getValue() as $val) {
                    $values[] = $val;
                }
            }
        }

        return $values;
    }

    /**
     * @return array
     */
    public function getColumnWithoutOperator()
    {
        $columns = array();
        foreach ($this->column as $column) {
            if (!self::isOperator($column)) {
                $columns[] = $column;
            }
        }

        return $columns;
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
     * Returns TRUE if $item is Condition:OPERATOR_AND or Condition:OPERATOR_OR else FALSE.
     * @param string $item
     * @return bool
     */
    public static function isOperator($item)
    {
        return in_array(strtoupper($item), array(self::OPERATOR_AND, self::OPERATOR_OR));
    }

    /**
     * @param mixed $column
     * @param string $condition
     * @param mixed $value
     * @return Condition
     */
    public static function setup($column, $condition, $value)
    {
        return new self($column, $condition, $value);
    }

    /**
     * @return Condition
     */
    public static function setupEmpty()
    {
        return new self(NULL, '0 = 1');
    }

    /**
     * @param array $condition
     * @throws Exception
     * @return Condition
     */
    public static function setupFromArray(array $condition)
    {
        if (count($condition) !== 3) {
            throw new Exception("Condition array must contain 3 items.");
        }

        return new self($condition[0], $condition[1], $condition[2]);
    }

    /**
     * @param callable $callback
     * @param mixed $value
     * @return Condition
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
     * @throws Exception
     * @return array
     */
    public function __toArray($prefix = NULL, $suffix = NULL, $brackets = TRUE)
    {
        $condition = array();
        $addBrackets = $brackets && count($this->column) > 1;

        if ($addBrackets) {
            $condition[] = '(';
        }

        $i = 0;
        foreach ($this->column as $column) {
            if (self::isOperator($column)) {
                $operator = strtoupper($column);
                $condition[] = " $operator ";

            } else {
                $i = count($this->condition) > 1 ? $i : 0;
                $condition[] = "{$prefix}$column{$suffix} {$this->condition[$i]}";

                $i++;
            }
        }

        if ($addBrackets) {
            $condition[] = ')';
        }

        return $condition
            ? array_values(array_merge(array(implode('', $condition)), $this->getValueForColumn()))
            : $this->condition;
    }
}
