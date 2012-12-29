<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 */

namespace Grido;

/**
 * Nette Database data source.
 *
 * @package     Grido
 * @subpackage  DataSources
 * @author      Petr Bugyík
 *
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
     * @return int
     */
    public function getCount()
    {
        return $this->selection->count();
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->selection;
    }

    /**********************************************************************************************/

    /**
     * @param array $condition
     */
    public function filter(array $condition)
    {
        $this->selection->where($this->removePlaceholders($condition));
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
     * @param int $offset
     * @param int $limit
     */
    public function limit($offset, $limit)
    {
        $this->selection->limit($limit, $offset);
    }

    /**
     * @param string $column
     * @param array $conditions
     * @return array
     */
    public function suggest($column, array $conditions)
    {
        $selection = clone $this->selection;
        foreach ($conditions as $condition) {
            $selection->where($this->removePlaceholders($condition));
        }

        return array_keys($selection->fetchPairs($column, $column));
    }

    private function removePlaceholders(array $condition)
    {
        $condition[0] = trim(str_replace(array('%s', '%i', '%f'), '?', $condition[0]));
        return array(str_replace(array('[', ']'), array('`', '`'), $condition[0]) => $condition[1]);
    }
}
