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
 * Nette Database data source.
 *
 * @package     Grido
 * @subpackage  DataSources
 * @author      Petr Bugyík
 *
 * @property-read int $count
 * @property-read array $data
 * @property-read \Nette\Database\Table\Selection $selection
 */
class NetteDatabase extends \Nette\Object implements IDataSource
{
    /** @var \Nette\Database\Table\Selection */
    protected $selection;

    /**
     * @param \Nette\Database\Table\Selection $selection
     */
    public function __construct(\Nette\Database\Table\Selection $selection)
    {
        $this->selection = $selection;
    }

    /**
     * @return \Nette\Database\Table\Selection
     */
    public function getSelection()
    {
        return $this->selection;
    }

    protected function removePlaceholders(array $condition)
    {
        $condition[0] = trim(str_replace(array('%s', '%i', '%f'), '?', $condition[0]));
        return isset($condition[1])
            ? array(str_replace(array('[', ']'), array('', ''), $condition[0]) => $condition[1])
            : array(str_replace(array('[', ']'), array('', ''), $condition[0]));
    }

    /*********************************** interface IDataSource ************************************/

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
        return $this->selection->count('*');
    }

    /**
     * @param array $condition
     */
    public function filter(array $condition)
    {
        $this->selection->where($this->removePlaceholders($condition));
    }

    /**
     * @param int $offset
     * @param int $limit
     */
    public function limit($offset, $limit)
    {
        $this->selection->limit($limit, $offset);
    }

    /**
     * @param array $sorting
     */
    public function sort(array $sorting)
    {
        foreach ($sorting as $column => $sort) {
            $this->selection->order("$column $sort");
        }
    }

    /**
     * @param mixed $column
     * @param array $conditions
     * @return array
     */
    public function suggest($column, array $conditions)
    {
        $selection = clone $this->selection;
        foreach ($conditions as $condition) {
            $selection->where($this->removePlaceholders($condition));
        }

        $items = array();
        if (is_callable($column)) {
            foreach ($selection as $item) {
                $value = $column($item);
                $items[$value] = $value;
            }
        } else {
            $items = $selection->fetchPairs($column, $column);
        }

        return array_keys($items);
    }
}
