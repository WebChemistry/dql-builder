<?php declare(strict_types = 1);

namespace WebChemistry\DQLBuilder;

use Doctrine\ORM\EntityManagerInterface;
use WebChemistry\DQLBuilder\Macros\BaseMacros;

class QueryFactory implements IQueryFactory {

	/** @var EntityManagerInterface */
	private $em;

	/** @var BaseMacros */
	private $macros;

	public function __construct(BaseMacros $macros, EntityManagerInterface $em) {
		$this->em = $em;
		$this->macros = $macros;
	}

	public function createQuery(string $sql, array $params = []) {
		return new Query($this->em, $this->macros, $sql, $params);
	}

	public function createQueryBuilder() {
		return new QueryBuilder($this->em, $this->macros);
	}

}
