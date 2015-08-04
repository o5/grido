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
 * The interface defines methods that must be implemented by each data source.
 *
 * @package     Grido
 * @subpackage  DataSources
 * @author      Petr Bugyík
 */
interface IDataSource
{
    /**
     * @return int
     */
    public function getCount();

    /**
     * @return array
     */
    public function getData();

    /**
     * @param array $condition
     * @return void
     */
    public function filter(array $condition);

    /**
     * @param int $offset
     * @param int $limit
     * @return void
     */
    public function limit($offset, $limit);

    /**
     * @param array $sorting
     * @return void
     */
    public function sort(array $sorting);

    /**
     * @param mixed $column
     * @param array $conditions
     * @param int $limit
     * @return array
     */
    public function suggest($column, array $conditions, $limit);
}
