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

use Doctrine\ORM\Tools\Pagination\Paginator,
    Grido\Components\Filters\Condition;

/**
 * Doctrine data source.
 *
 * @package     Grido
 * @subpackage  DataSources
 * @author      Martin Jantosovic <martin.jantosovic@freya.sk>
 * @author      Petr Bugyík
 *
 * @property-read Doctrine\ORM\QueryBuilder $qb
 * @property-read array $filterMapping
 * @property-read array $sortMapping
 * @property-read int $count
 * @property-read array $data
 */
class Doctrine extends \Nette\Object implements IDataSource
{
    /** @var Doctrine\ORM\QueryBuilder */
    protected $qb;

    /** @var array Map column to the query builder */
    protected $filterMapping;

    /** @var array Map column to the query builder */
    protected $sortMapping;

    protected $rand;

    /**
     * If $sortMapping is not set and $filterMapping is set,
     * $filterMapping will be used also as $sortMapping.
     * @param Doctrine\ORM\QueryBuilder $qb
     * @param array $filterMapping Maps columns to the DQL columns
     * @param array $sortMapping Maps columns to the DQL columns
     */
    public function __construct(\Doctrine\ORM\QueryBuilder $qb, $filterMapping = NULL, $sortMapping = NULL)
    {
        $this->qb = $qb;
        $this->filterMapping = $filterMapping;
        $this->sortMapping = $sortMapping;

        if (!$this->sortMapping && $this->filterMapping) {
            $this->sortMapping = $this->filterMapping;
        }
    }

    /**
     * @return \Doctrine\ORM\Query
     */
    public function getQuery()
    {
        return $this->qb->getQuery();
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getQb()
    {
        return $this->qb;
    }

    /**
     * @return array|NULL
     */
    public function getFilterMapping()
    {
        return $this->filterMapping;
    }

    /**
     * @return array|NULL
     */
    public function getSortMapping()
    {
        return $this->sortMapping;
    }

    /**
     * @param Condition $condition
     * @param \Doctrine\ORM\QueryBuilder $qb
     */
    protected function makeWhere(Condition $condition, \Doctrine\ORM\QueryBuilder $qb = NULL)
    {
        $qb = $qb === NULL
            ? $this->qb
            : $qb;

        if ($condition->callback) {
            return callback($condition->callback)->invokeArgs(array($condition->value, $qb));
        }

        $columns = $condition->column;
        foreach ($columns as $column) {
            if (!Condition::isOperator($column)) {
                $columns[$column] = isset($this->filterMapping[$column])
                    ? $this->filterMapping[$column]
                    : $this->qb->getRootAlias() . '.' . $column;
            }
        }

        list($where) = $condition->__toArray(NULL, NULL, FALSE);
        $where = str_replace(array_keys($columns), array_values($columns), $where);

        $rand = $this->getRand();
        $where = preg_replace_callback('/\?/', function() use ($rand) {
            static $i = -1; $i++;
            return ":$rand{$i}";
        }, $where);

        $qb->andWhere($where);

        foreach ($condition->getValueForColumn() as $i => $val) {
            $qb->setParameter("$rand{$i}", $val);
        }
    }

    /**
     * @return string
     */
    protected function getRand()
    {
        do {
            $rand = \Nette\Utils\Strings::random(4, 'a-z');
        } while (isset($this->rand[$rand]));

        $this->rand[$rand] = $rand;
        return $rand;
    }

    /*********************************** interface IDataSource ************************************/

    /**
     * @return int
     */
    public function getCount()
    {
        $paginator = new Paginator($this->getQuery());
        return $paginator->count();
    }

    /**
     * It is possible to use query builder with additional columns.
     * In this case, only item at index [0] is returned, because
     * it should be an entity object.
     * @return array
     */
    public function getData()
    {
        // Paginator is better if the query uses ManyToMany associations
        $usePaginator = $this->qb->getMaxResults() !== NULL || $this->qb->getFirstResult() !== NULL;
        $data = array();

        if ($usePaginator) {
            $paginator = new Paginator($this->getQuery());

            // Convert paginator to the array
            foreach ($paginator as $result) {
                // Return only entity itself
                $data[] = is_array($result)
                    ? $result[0]
                    : $result;
            }
        } else {

            foreach ($this->qb->getQuery()->getResult() as $result) {
                // Return only entity itself
                $data[] = is_array($result)
                    ? $result[0]
                    : $result;
            }
        }

        return $data;
    }

    /**
     * Sets filter.
     * @param array $conditions
     */
    public function filter(array $conditions)
    {
        foreach ($conditions as $condition) {
            $this->makeWhere($condition);
        }
    }

    /**
     * Sets offset and limit.
     * @param int $offset
     * @param int $limit
     */
    public function limit($offset, $limit)
    {
        $this->qb->setFirstResult($offset)
            ->setMaxResults($limit);
    }

    /**
     * Sets sorting.
     * @param array $sorting
     */
    public function sort(array $sorting)
    {
        foreach ($sorting as $key => $value) {
            $column = isset($this->sortMapping[$key])
                ? $this->sortMapping[$key]
                : $this->qb->getRootAlias() . '.' . $key;

            $this->qb->addOrderBy($column, $value);
        }
    }

    /**
     * @param string $column
     * @param array $conditions
     * @return array
     */
    public function suggest($column, array $conditions)
    {
        $qb = clone $this->qb;
        foreach ($conditions as $condition) {
            $this->makeWhere($condition, $qb);
        }

        $items = array();
        foreach ($qb->getQuery()->getScalarResult() as $row) {
            $mapping = isset($this->filterMapping[$column])
                ? str_replace('.', '_', $this->filterMapping[$column])
                : $qb->getRootAlias() . '_' . $column;

            $value = (string) $row[$mapping];
            $items[$value] = $value;
        }

        $items = array_values($items);
        sort($items);

        return $items;;
    }
}
