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
 * Dibi Fluent data source.
 *
 * @package     Grido
 * @subpackage  DataSources
 * @author      Petr Bugyík
 *
 * @property-read int $count
 * @property-read array $data
 */
class DibiFluent extends \Nette\Object implements IDataSource
{
    /** @var DibiFluent */
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
     * @return int
     */
    public function getCount()
    {
        $fluent = clone $this->fluent;
        return $fluent->count();
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->fluent->fetchAll($this->offset, $this->limit);
    }

    /**********************************************************************************************/

    /**
     * @param array $condition
     */
    public function filter(array $condition)
    {
        call_user_func_array(array($this->fluent, 'where'), $condition);
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
}
