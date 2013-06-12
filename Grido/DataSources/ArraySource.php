<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 */

namespace Grido\DataSources;

/**
 * Array data source.
 *
 * @package     Grido
 * @subpackage  DataSources
 * @author      Josef Kříž <pepakriz@gmail.com>
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

    protected function formatFilterCondition(array $condition)
    {
        $matches = \Nette\Utils\Strings::matchAll($condition[0], '/\[([\w_-]+)\]* ([\w\!<>=]+) ([%\w]+)/');

        if (!$matches) {
            return $condition;
        }

        return array(
            $matches[0][1],
            $matches[0][2],
            trim(str_replace(array('%s', '%i', '%f'), '?', $matches[0][3]))
        );
    }

    /**
     * @param array $data
     * @param array $condition
     * @return void
     */
    protected function getFilter(array $data, array $condition)
    {
        $value = $condition[1];
        $condition = $this->formatFilterCondition($condition);

        return array_filter($data, function ($row) use ($value, $condition) {
            if ($condition[1] === 'LIKE') {
                if (strlen($value) <= 2) {
                    return TRUE;
                }
                return stripos($row[$condition[0]], substr($value, 1, -1)) !== FALSE;

            } else if ($condition[1] === '=') {
                return $row[$condition[0]] == $value;

            } elseif ($condition[1] === 'IS' && $condition[2] == 'NULL') {
                return $row[$condition[0]] == NULL;

            } elseif (in_array($condition[1], array('<', '<=', '>', '>='))) {
                return eval("return {$row[$condition[0]]}{$condition[1]}{$value};");
            }
        });
    }

    /*********************************** interface IDataSource ************************************/

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return count($this->data);
    }

    /**
     * @param array $condition
     * @return void
     */
    public function filter(array $condition)
    {
        $this->data = $this->getFilter($this->data, $condition);
    }

    /**
     * @param int $offset
     * @param int $limit
     * @return void
     */
    public function limit($offset, $limit)
    {
        $this->data = array_slice($this->data, $offset, $limit);
    }

    /**
     * @param array $sorting
     * @return void
     */
    public function sort(array $sorting)
    {
        foreach ($sorting as $column => $sort) {
            $data = array();
            foreach ($this->data as $item) {
                $data[(string) $item[$column]][] = $item; //HOTFIX: (string)
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
            $data = $this->getFilter($data, $condition);
        }

        $suggestions = array();
        foreach ($data as $row) {
            $suggestions[] = (string)$row[$column];
        }

        return $suggestions;
    }
}
