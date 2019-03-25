<?php declare(strict_types = 1);

namespace WebChemistry\DQLBuilder;

use Doctrine\ORM\EntityManagerInterface;
use WebChemistry\DQLBuilder\Macros\BaseMacros;
use WebChemistry\DQLBuilder\Tokenizer\QueryTokenizer;

class Query {

	/** @var EntityManagerInterface */
	private $em;

	/** @var string */
	private $sql;

	/** @var array */
	private $parameters = [];

	/** @var BaseMacros */
	private $macros;

	public function __construct(EntityManagerInterface $em, BaseMacros $macros, string $sql, array $params = []) {
		$this->em = $em;
		$this->macros = $macros;
		$this->sql = Helpers::replaceParams($params, $sql);
		Helpers::searchForQuery($params, function ($query) {
			$this->addParameters($query->getParameters());
		});
	}

	public function create(string $sql, array $params) {
		return new static($this->em, $this->macros, $sql, $params);
	}

	public function getDQL() {
		return $this->sql;
	}

	public function setParameter($name, $value) {
		$this->parameters[$name] = $value;

		return $this;
	}

	public function setParameters(iterable $parameters) {
		$this->parameters = $parameters;

		return $this;
	}

	public function addParameters(array $parameters) {
		$this->parameters = array_merge($this->parameters, $parameters);

		return $this;
	}

	public function getParameters(): array {
		return $this->parameters;
	}

	public function getSQLStatements(): SQLStatements {
		$tokenizer = new QueryTokenizer($this->sql, [$this->macros, 'call']);

		return new SQLStatements($tokenizer->getResult(), $this->parameters, $tokenizer->getRsm());
	}

	public function getResult() {
		$stmt = $this->getSQLStatements();

		return $this->em->createNativeQuery($stmt->getSql(), $stmt->getRsm())
			->setParameters($stmt->getParameters())->getResult();
	}

	public function getArrayResult() {
		$stmt = $this->getSQLStatements();

		return $this->em->createNativeQuery($stmt->getSql(), $stmt->getRsm())
			->setParameters($stmt->getParameters())->getArrayResult();
	}

	public function getSingleScalarResult() {
		$stmt = $this->getSQLStatements();

		return $this->em->createNativeQuery($stmt->getSql(), $stmt->getRsm())
			->setParameters($stmt->getParameters())->getSingleScalarResult();
	}

	public function __toString(): string {
		return ($this->getSQLStatements())->getSql();
	}

}
