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

/**
 * Nette Database data source.
 *
 * @package     Grido
 * @subpackage  DataSources
 * @author      Petr Bugyík
 *
 * @property-read \Nette\Database\Table\Selection $selection
 * @property-read int $count
 * @property-read array $data
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

    /**
     * @param Grido\Components\Filters\Condition $condition
     * @param \Nette\Database\Table\Selection $selection
     */
    protected function makeWhere(\Grido\Components\Filters\Condition $condition, \Nette\Database\Table\Selection $selection = NULL)
    {
        $selection = $selection === NULL
            ? $this->selection
            : $selection;

        if ($condition->callback) {
            callback($condition->callback)->invokeArgs(array($condition->value, $selection));
        } else {
            call_user_func_array(array($selection, 'where'), $condition->__toArray());
        }
    }

    /*********************************** interface IDataSource ************************************/

    /**
     * @return int
     */
    public function getCount()
    {
        return (int) $this->selection->count('*');
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->selection;
    }

    /**
     * @param array $conditions
     */
    public function filter(array $conditions)
    {
        foreach ($conditions as $condition) {
            $this->makeWhere($condition);
        }
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
            $this->makeWhere($condition, $selection);
        }

        $items = array();
        if (is_callable($column)) {
            foreach ($selection as $item) {
                $value = (string) $column($item);
                $items[$value] = $value;
            }
        } else {
            $data = $selection->fetchPairs($column, $column);
            foreach ($data as $value) {
                $value = (string) $value;
                $items[$value] = $value;
            }
        }

        $items = array_values($items);
        sort($items);

        return $items;;
    }
}
