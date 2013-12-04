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
 * Model of data source.
 *
 * @package     Grido
 * @subpackage  DataSources
 * @author      Petr Bugyík
 *
 * @property-read IDataSource $dataSource
 */
class Model extends \Nette\Object
{
    /** @var array */
    public $callback = array();

    /** @var IDataSource */
    protected $dataSource;

    /**
     * @param mixed $model
     * @throws \InvalidArgumentException
     */
    public function __construct($model)
    {
        if ($model instanceof \DibiFluent) {
            $dataSource = new DibiFluent($model);
        } elseif ($model instanceof \Nette\Database\Table\Selection) {
            $dataSource = new NetteDatabase($model);
        } elseif ($model instanceof \Doctrine\ORM\QueryBuilder) {
            $dataSource = new Doctrine($model);
        } elseif (is_array($model)) {
            $dataSource = new ArraySource($model);
        } elseif ($model instanceof IDataSource) {
            $dataSource = $model;
        } else {
            throw new \InvalidArgumentException('Model must implement \Grido\DataSources\IDataSource.');
        }

        $this->dataSource = $dataSource;
    }

    /**
     * @return \IDataSource
     */
    public function getDataSource()
    {
        return $this->dataSource;
    }

    public function __call($method, $args)
    {
        return isset($this->callback[$method])
            ? callback($this->callback[$method])->invokeArgs(array($this->dataSource, $args))
            : call_user_func_array(array($this->dataSource, $method), $args);
    }
}
