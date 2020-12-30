<?php declare(strict_types = 1);

namespace WebChemistry\DQLBuilder;

interface IQueryFactory {

	/**
	 * @param string $sql
	 * @param array $params
	 * @return Query
	 */
	public function createQuery(string $sql, array $params = []);

	/**
	 * @return QueryBuilder
	 */
	public function createQueryBuilder();

}
