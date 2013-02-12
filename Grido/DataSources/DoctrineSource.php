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

use Doctrine\ORM\Tools\Pagination\Paginator,
	Nette\Utils\Strings;

/**
 * Doctrine source
 *
 * @package     Grido
 * @subpackage  DataSources
 * @author      Martin Jantosovic <martin.jantosovic@freya.sk>
 *
 * @property-read int $count
 * @property-read array $data
 */
class DoctrineSource extends \Nette\Object implements IDataSource {

	/** @var Doctrine\ORM\QueryBuilder */
	private $qb;

	/** @var array Map column to the query builder */
	private $filterMapping;

	/** @var array Map column to the query builder */
	private $sortMapping;

    /**
	 * If $sortMapping is not set and $filterMapping is set,
	 * $filterMapping will be used also as $sortMapping.
	 *
     * @param Doctrine\ORM\QueryBuilder $qb
     * @param array $filterMapping Maps columns to the DQL columns
     * @param array $sortMapping Maps columns to the DQL columns
     */
    public function __construct(\Doctrine\ORM\QueryBuilder $qb, $filterMapping = NULL, $sortMapping = NULL) {
        $this->qb = $qb;
		$this->filterMapping = $filterMapping;
		$this->sortMapping = $sortMapping;

		if (!$this->sortMapping && $this->filterMapping)
			$this->sortMapping = $this->filterMapping;
    }

	/**
	 * @return Doctrine\ORM\Query
	 */
	public function getQuery() {
		return $this->qb->getQuery();
	}

    /**
     * @return int
     */
    public function getCount() {
		$paginator = new Paginator($this->getQuery());
		return $paginator->count();
    }

    /**
	 * It is possible to use query builder with additional columns.
	 * In this case, only item at index [0] is returned, because
	 * it should be an entity object.
	 *
     * @return array
     */
    public function getData() {
		// Paginator is better if the query uses ManyToMany associations
		$usePaginator = $this->qb->getMaxResults() !== NULL || $this->qb->getFirstResult() !== NULL;
		$data = [];
		if ($usePaginator) {
			$paginator = new Paginator($this->getQuery());
			// Convert paginator to the array
			foreach ($paginator as $result)
				// Return only entity itself
				$data[] = is_array($result) ? $result[0] : $result;
		} else {
			foreach ($this->qb->getQuery()->getResult() as $result)
				// Return only entity itself
				$data[] = is_array($result) ? $result[0] : $result;
		}
		return $data;
    }

    /**********************************************************************************************/

    /**
	 * Set filter
	 *
     * @param array $condition
     */
    public function filter(array $condition) {
		$condition = $this->formatFilterCondition($condition);
		$this->qb->andWhere($condition[0]);
		if ($condition[1])
			$this->qb->setParameter($condition[2], $condition[1]);
    }

    /**
	 * Set sorting
	 *
     * @param array $sorting
     */
    public function sort(array $sorting) {
		foreach ($sorting as $key => $value) {
			$column = isset($this->sortMapping[$key])
				? $this->sortMapping[$key]
				: $this->qb->getRootAlias() . '.' . $key;
			$this->qb->addOrderBy($column, $value);
		}
    }

    /**
	 * Set offset and limit
	 *
     * @param int $offset
     * @param int $limit
     */
    public function limit($offset, $limit) {
		$this->qb->setFirstResult($offset)
			->setMaxResults($limit);
    }

    /**
     * @param string $column
     * @param array $conditions
     * @return array
     */
    public function suggest($column, array $conditions) {
        $qb = clone $this->qb;

        foreach ($conditions as $condition) {
			$condition = $this->formatFilterCondition($condition);
			$qb->andWhere($condition[0]);
			if ($condition[1])
				$qb->setParameter($condition[2], $condition[1]);
        }

		$suggestions = [];
		foreach ($qb->getQuery()->getResult() as $row)
			$suggestions[] = $row->$column;
        return $suggestions;
    }

    private function formatFilterCondition(array $condition) {
		$matches = Strings::matchAll($condition[0], '/\[([\w_-]+)\]*/');
		$column = NULL;

		if ($matches) {
			foreach ($matches as $match) {
				$column = $match[1];
				$mapping = isset($this->filterMapping[$column])
					? $this->filterMapping[$column]
					: $this->qb->getRootAlias() . '.' . $column;
				$condition[0] = Strings::replace($condition[0], '/' . preg_quote($match[0], '/') . '/', $mapping);
		        $condition[0] = trim(str_replace([ '%s', '%i', '%f' ], ':' . $column, $condition[0]));
			}
		}

		if (!$column) {
			$column = count($this->qb->getParameters()) + 1;
			$condition[0] = trim(str_replace([ '%s', '%i', '%f' ], '?' . $column, $condition[0]));
		}

        return [ $condition[0], isset($condition[1]) ? $condition[1] : NULL, $column ];
    }

}
