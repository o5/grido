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

use Exception;
use Grido\Components\Filters\Condition;
use Nette\Templating\Helpers;
use Nextras\Dbal\Connection;
use Nextras\Dbal\QueryBuilder\QueryBuilder;
use Nextras\Dbal\Result\Result;
use Nextras\Dbal\Result\Row;

/**
 * Nextras dbal data source.
 *
 * @package     Grido
 * @subpackage  DataSources
 * @author      Milan Felix Sulc <sulcmil@gmail.com>
 * @author      Petr Bugyík
 */
class NextrasDbal implements IDataSource
{

    /** @var QueryBuilder */
    private $builder;

    /** @var Connection */
    private $connection;

    /**
     * @param Connection $connection
     * @param QueryBuilder $builder
     */
    public function __construct(Connection $connection, QueryBuilder $builder)
    {
        $this->connection = $connection;
        $this->builder = $builder;
    }

    /**
     * @param Condition $condition
     * @param QueryBuilder $builder
     */
    protected function makeWhere(Condition $condition, QueryBuilder &$builder)
    {
        if ($condition->callback) {
            callback($condition->callback)->invokeArgs(array($condition->value, $builder));
        } else {
            list ($column, $value) = $condition->__toArray();
            $column = str_replace('?', '%any', $column);
            $builder->where($column, $value);
        }

    }

    /**
     * @param QueryBuilder $builder
     * @return Result
     */
    protected function execute(QueryBuilder $builder)
    {
        return $this->connection->queryArgs(
            $builder->getQuerySql(),
            $builder->getQueryParameters()
        );
    }

    /**
     * INTERFACE  **************************************************************
     */

    /**
     * @return int
     */
    public function getCount()
    {
        $builder = clone $this->builder;
        return (int) $this->execute($builder->select('COUNT(*)'))->fetchField();
    }

    /**
     * @return Row[]
     */
    public function getData()
    {
        return $this->execute($this->builder)->fetchAll();
    }

    /**
     * @param array $conditions
     */
    public function filter(array $conditions)
    {
        foreach ($conditions as $condition) {
            $this->makeWhere($condition, $this->builder);
        }
    }

    /**
     * @param int $offset
     * @param int $limit
     */
    public function limit($offset, $limit)
    {
        $this->builder = $this->builder->limitBy($limit, $offset);
    }

    /**
     * @param array $sorting
     */
    public function sort(array $sorting)
    {
        foreach ($sorting as $column => $sort) {
            $this->builder = $this->builder->orderBy("$column $sort");
        }
    }

    /**
     * @param mixed $column
     * @param array $conditions
     * @param int $limit
     */
    public function suggest($column, array $conditions, $limit)
    {
        $builder = clone $this->builder;
        is_string($column) && $builder = $builder->orderBy($column);

        $builder->limitBy($limit);

        foreach ($conditions as $condition) {
            $this->makeWhere($builder);
        }

        $items = array();
        foreach ($builder as $row) {
            if (is_string($column)) {
                $value = (string)$row[$column];
            } elseif (is_callable($column)) {
                $value = (string)$column($row);
            } else {
                $type = gettype($column);
                throw new Exception("Column of suggestion must be string or callback, $type given.");
            }
            $items[$value] = Helpers::escapeHtml($value);
        }

        is_callable($column) && sort($items);
        return array_values($items);
    }
}