<?php
namespace Grido\DataSources;


use Doctrine\ORM\Tools\Pagination\Paginator;

use Grido\DataSources\Doctrine;

class RawDoctrine extends Doctrine{

	public function getData()
	{
		// Paginator is better if the query uses ManyToMany associations
		$result = $this->qb->getMaxResults() !== NULL || $this->qb->getFirstResult() !== NULL
			? new Paginator($this->getQuery())
			: $this->qb->getQuery()->getResult();


		return $result;
	}

}