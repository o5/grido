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

use Nette\InvalidArgumentException;
use Nette\Utils\Strings;

/**
 * Array data source.
 *
 * @package     Grido
 * @subpackage  DataSources
 * @author      Josef Kříž <pepakriz@gmail.com>
 */
class ArraySource extends Base implements IDataSource
{

    /** @var array */
    private $selection;


    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->selection = $data;
    }


    /**
     * @return array
     */
    public function getData()
    {
        return $this->selection;
    }


    /**
     * @return int
     */
    public function getCount()
    {
        return count($this->selection);
    }


    /**
     * @param array $condition
     * @return void
     */
    public function filter(array $condition)
    {
        $this->selection = $this->getFilter($this->selection, $condition);
    }


    /**
     * @param int $offset
     * @param int $limit
     * @return void
     */
    public function limit($offset, $limit)
    {
        $this->selection = array_slice($this->selection, $offset, $limit);
    }


    /**
     * @param array $sorting
     * @return void
     */
    public function sort(array $sorting)
    {
        foreach ($sorting as $column => $sort) {
            $data = array();
            foreach ($this->selection as $item) {
                $data[$item[$column]][] = $item;
            }

            if ($sort === 'ASC') {
                ksort($data);
            } else {
                krsort($data);
            }

            $this->selection = array();
            foreach($data as $i) {
                foreach($i as $item) {
                    $this->selection[] = $item;
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
        $selection = $this->selection;

        foreach ($conditions as $condition) {
            $selection = $this->getFilter($selection, $condition);
        }

        $suggestions = array();
        foreach ($selection as $row) {
            $suggestions[] = (string)$row[$column];
        }

        return $suggestions;
    }


    protected function formatFilterCondition(array $condition)
    {
        $matches = Strings::matchAll($condition[0], '/\[([\w_-]+)\]* ([\w=]+) ([%\w]+)/');
        $column = NULL;

        if ($matches) {
            foreach ($matches as $match) {
                return array(
                    $match[1],
                    $match[2],
                    trim(str_replace(array('%s', '%i', '%f'), '?', $match[3])),
                );
            }
        } else {
            return $condition;
        }
    }


    /**
     * @param array $condition
     * @return void
     */
    protected function getFilter($selection, array $condition)
    {
        $value = $condition[1];
        $condition = $this->formatFilterCondition($condition);

        return array_filter($selection, function ($row) use ($value, $condition) {
            if ($condition[1] === 'LIKE') {
                if (strlen($value) <= 2) {
                    return TRUE;
                }
                return stripos($row[$condition[0]], substr($value, 1, -1)) !== FALSE;
            }
            if ($condition[1] === '=') {
                return $row[$condition[0]] == $value;
            }
            if ($condition[1] === '!=') {
                return $row[$condition[0]] != $value;
            }
            if ($condition[1] === 'IS' && $condition[2] == 'NULL') {
                return $row[$condition[0]] == NULL;
            }
        });
    }
}
