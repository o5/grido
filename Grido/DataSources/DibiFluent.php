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
 * Dibi Fluent data source.
 *
 * @package     Grido
 * @subpackage  DataSources
 * @author      Petr Bugyík
 *
 * @property-read int $count
 * @property-read array $data
 * @property-read \DibiFluent $fluent
 * @property-read int $limit
 * @property-read int $offset
 */
class DibiFluent extends Base implements IDataSource
{
    /** @var \DibiFluent */
    protected $fluent;

    /** @var int */
    protected $limit;

    /** @var int */
    protected $offset;

    /**
     * @param \DibiFluent $fluent
     */
    public function __construct(\DibiFluent $fluent)
    {
        $this->fluent = $fluent;
    }

    /**
     * @return \DibiFluent
     */
    public function getFluent()
    {
        return $this->fluent;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param string $column
     * @param array $conditions
     * @return array
     */
    public function suggest($column, array $conditions)
    {
        $fluent = clone $this->fluent;
        foreach ($conditions as $condition) {
            call_user_func_array(array($fluent, 'where'), $condition);
        }

        $items = array_keys($fluent->fetchPairs($column, $column));
        return $items;
    }

    /*********************************** interface IDataSource ************************************/

    /**
     * @return array
     */
    public function getData()
    {
        return $this->fluent->fetchAll($this->offset, $this->limit);
    }

    /**
     * @return int
     */
    public function getCount()
    {
        $fluent = clone $this->fluent;
        return $fluent->count();
    }

    /**
     * @param array $condition
     */
    public function filter(array $condition)
    {
        call_user_func_array(array($this->fluent, 'where'), $condition);
    }

    /**
     * @param int $offset
     * @param int $limit
     */
    public function limit($offset, $limit)
    {
        $this->offset = $offset;
        $this->limit = $limit;
    }

    /**
     * @param array $sorting
     */
    public function sort(array $sorting)
    {
        foreach ($sorting as $column => $sort) {
            $this->fluent->orderBy($column, $sort);
        }
    }
}
