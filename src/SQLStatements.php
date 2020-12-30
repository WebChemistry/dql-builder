<?php declare(strict_types = 1);

namespace WebChemistry\DQLBuilder;

use Doctrine\ORM\Query\ResultSetMapping;

class SQLStatements {

	/** @var string */
	private $sql;

	/** @var array */
	private $params;

	/** @var ResultSetMapping */
	private $rsm;

	public function __construct(string $sql, array $params, ResultSetMapping $rsm) {
		$this->sql = $sql;
		$this->params = $params;
		$this->rsm = $rsm;
	}

	/**
	 * @return string
	 */
	public function getSql(): string {
		return $this->sql;
	}

	/**
	 * @return ResultSetMapping
	 */
	public function getRsm(): ResultSetMapping {
		return $this->rsm;
	}

	/**
	 * @return array
	 */
	public function getParameters(): array {
		return $this->params;
	}

}
