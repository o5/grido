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

use Nette\Utils\Strings,
    Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Doctrine data source.
 *
 * @package     Grido
 * @subpackage  DataSources
 * @author      Martin Jantosovic <martin.jantosovic@freya.sk>
 *
 * @property-read int $count
 * @property-read array $data
 * @property-read Doctrine\ORM\QueryBuilder $qb
 * @property-read array $filterMapping
 * @property-read array $sortMapping
 */
class Doctrine extends Base implements IDataSource
{
    /** @var Doctrine\ORM\QueryBuilder */
    protected $qb;

    /** @var array Map column to the query builder */
    protected $filterMapping;

    /** @var array Map column to the query builder */
    protected $sortMapping;

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
     * @return Doctrine\ORM\Query
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

    protected function formatFilterCondition(array $condition)
    {
        $matches = Strings::matchAll($condition[0], '/\[([\w_-]+)\]*/');
        $column = NULL;

        if ($matches) {
            foreach ($matches as $match) {
                $column = $match[1];
                $mapping = isset($this->filterMapping[$column])
                    ? $this->filterMapping[$column]
                    : $this->qb->getRootAlias() . '.' . $column;

                $condition[0] = Strings::replace($condition[0], '/' . preg_quote($match[0], '/') . '/', $mapping);
                $condition[0] = trim(str_replace(array('%s', '%i', '%f'), ':' . $column, $condition[0]));
            }
        }

        if (!$column) {
            $column = count($this->qb->getParameters()) + 1;
            $condition[0] = trim(str_replace(array('%s', '%i', '%f'), '?' . $column, $condition[0]));
        }

        return array(
            $condition[0],
            isset($condition[1])
                ? $condition[1]
                : NULL, $column
        );
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
            $condition = $this->formatFilterCondition($condition);
            $qb->andWhere($condition[0]);

            if ($condition[1]) {
                $qb->setParameter($condition[2], $condition[1]);
            }
        }

        $suggestions = array();
        foreach ($qb->getQuery()->getScalarResult() as $row) {
            $suggestions[] = $row[$qb->getRootAlias() . '_' . $column];
        }

        return $suggestions;
    }

    /*********************************** interface IDataSource ************************************/

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
     * @return int
     */
    public function getCount()
    {
        $paginator = new Paginator($this->getQuery());
        return $paginator->count();
    }

    /**
     * Set filter.
     * @param array $condition
     */
    public function filter(array $condition)
    {
        $condition = $this->formatFilterCondition($condition);
        $this->qb->andWhere($condition[0]);

        if (isset($condition[1]) && isset($condition[2])) {
            $this->qb->setParameter($condition[2], $condition[1]);
        }
    }

    /**
     * Set offset and limit.
     * @param int $offset
     * @param int $limit
     */
    public function limit($offset, $limit)
    {
        $this->qb->setFirstResult($offset)
            ->setMaxResults($limit);
    }

    /**
     * Set sorting.
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
}
