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
 * Defined method that must be implemented data source.
 *
 * @package     Grido
 * @subpackage  DataSources
 * @author      Petr Bugyík
 */
interface IDataSource
{
    /**
     * @return array
     */
    function getData();

    /**
     * @return int
     */
    function getCount();

    /**
     * @param array $condition
     * @return void
     */
    function filter(array $condition);

    /**
     * @param int $offset
     * @param int $limit
     * @return void
     */
    function limit($offset, $limit);

    /**
     * @param array $sorting
     * @return void
     */
    function sort(array $sorting);

    /**
     * @see \Grido\DataSources\Base::call()
     * @param string $method
     */
    function call($method);
}
