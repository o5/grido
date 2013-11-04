<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Grido\DataSources;

use Grido\Components\Filters\Condition;

/**
 * Array data source.
 *
 * @package     Grido
 * @subpackage  DataSources
 * @author      Josef Kříž <pepakriz@gmail.com>
 * @author      Petr Bugyík
 *
 * @property-read array $data
 * @property-read int $count
 */
class ArraySource extends \Nette\Object implements IDataSource
{
    /** @var array */
    protected $data;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @param Condition $condition
     * @param array $data
     * @return array
     */
    protected function makeWhere(Condition $condition, array $data = NULL)
    {
        $data = $data === NULL
            ? $this->data
            : $data;

        $that = $this;
        return array_filter($data, function ($row) use ($condition, $that) {
            if ($condition->callback) {
                return callback($condition->callback)->invokeArgs(array($condition->value, $row));
            }

            $i = 0;
            $results = array();
            foreach ($condition->column as $column) {
                if (Condition::isOperator($column)) {
                    $results[] = " $column ";

                } else {
                    $i = count($condition->condition) > 1 ? $i : 0;
                    $results[] = (int) $that->compare($row[$column], $condition->condition[$i], $condition->value[$i]);

                    $i++;
                }
            }

            $result = implode('', $results);
            return count($condition->column) === 1
                ? (bool) $result
                : eval("return $result;");
        });
    }

    /**
     * @param string $actual
     * @param string $condition
     * @param mixed $expected
     * @throws \InvalidArgumentException
     * @return bool
     */
    public function compare($actual, $condition, $expected)
    {
        $expected = current((array) $expected);
        $cond = str_replace(' ?', '', $condition);
        if ($cond === 'LIKE') {
            $pattern = str_replace('%', '.*', preg_quote($expected));
            return (bool) preg_match("/^{$pattern}$/i", $actual);

        } else if ($cond === '=') {
            return $actual == $expected;

        } elseif ($cond === 'IS NULL') {
            return $actual === NULL;

        } elseif ($cond === 'IS NOT NULL') {
            return $actual !== NULL;

        } elseif (in_array($cond, array('<', '<=', '>', '>='))) {
            return eval("return {$actual} {$cond} {$expected};");

        } else {
            throw new \InvalidArgumentException("Condition '$condition' not implemented yet.");
        }
    }

    /*********************************** interface IDataSource ************************************/

    /**
     * @return int
     */
    public function getCount()
    {
        return count($this->data);
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $conditions
     */
    public function filter(array $conditions)
    {
        foreach ($conditions as $condition) {
            $this->data = $this->makeWhere($condition);
        }
    }

    /**
     * @param int $offset
     * @param int $limit
     */
    public function limit($offset, $limit)
    {
        $this->data = array_slice($this->data, $offset, $limit);
    }

    /**
     * @param array $sorting
     */
    public function sort(array $sorting)
    {
        if (count($sorting) > 1) {
            throw new \Exception('Multi-column sorting is not implemented yet.');
        }

        foreach ($sorting as $column => $sort) {
            $data = array();
            foreach ($this->data as $item) {
                $sorter = (string) $item[$column];
                $data[$sorter][] = $item;
            }

            if ($sort === 'ASC') {
                ksort($data);
            } else {
                krsort($data);
            }

            $this->data = array();
            foreach($data as $i) {
                foreach($i as $item) {
                    $this->data[] = $item;
                }
            }
        }
    }

    /**
     * @param string $column
     * @param array $conditions
     * @return array
     */
    public function suggest($column, array $conditions)
    {
        $data = $this->data;
        foreach ($conditions as $condition) {
            $data = $this->makeWhere($condition, $data);
        }

        $items = array();
        foreach ($data as $row) {
            $value = (string) $row[$column];
            $items[$value] = $value;
        }

        $items = array_values($items);
        sort($items);

        return $items;;
    }
}
