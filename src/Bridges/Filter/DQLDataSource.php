<?php declare(strict_types = 1);

namespace WebChemistry\DQLBuilder\Bridges\Filter;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use WebChemistry\DQLBuilder\Query;
use WebChemistry\DQLBuilder\QueryBuilder;
use WebChemistry\Filter\DataSource\IDataSource;

class DQLDataSource implements IDataSource {

	/** @var QueryBuilder */
	private $query;

	/** @var EntityManagerInterface */
	private $em;

	public function __construct(QueryBuilder $query, EntityManagerInterface $em) {
		$this->em = $em;
		$this->query = $query;
	}

	public function getItemCount(): int {
		$rsm = new ResultSetMapping();
		$rsm->addScalarResult('cnt', 'cnt', 'integer');
		$query = $this->query->getQuery();
		$sql = 'SELECT COUNT(*) AS cnt FROM (' . $query . ') xc';

		$result = $this->em->createNativeQuery($sql, $rsm)->setParameters($query->getParameters())->getSingleScalarResult();

		return (int) $result;
	}

	public function getData(?int $limit, ?int $offset): iterable {
		return $this->query->setMaxResults($limit)->setOffset($offset)->getQuery()->getResult();
	}

}
