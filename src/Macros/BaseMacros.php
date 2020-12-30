<?php declare(strict_types = 1);

namespace WebChemistry\DQLBuilder\Macros;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\ResultSetMapping;
use Nette\NotImplementedException;
use WebChemistry\DQLBuilder\Tokenizer\QueryStatement;

class BaseMacros implements IMacro {

	/** @var array */
	private $functions = [];

	/** @var array */
	private $mapping = [];

	/** @var array */
	private $aliases = [];

	/** @var array */
	private $tableAliases = [];

	public function __construct(array $entityMap, EntityManagerInterface $em) {
		$entityMap = $this->processEntityMap($entityMap, $em);
		foreach ($entityMap as $name => [$metadata, $alias]) {
			$this->mapping[$name] = [$metadata, $alias];
			$this->functions[$name] = new EntityMacro($metadata, $em, $name, $metadata->getName(), $alias);
		}
		$this->functions['integer'] = [$this, 'select'];
		$this->functions['string'] = [$this, 'select'];
		$this->functions['boolean'] = [$this, 'select'];
		$this->functions['discriminator'] = [$this, 'discriminator'];
		$this->functions['type'] = [$this, 'type'];
	}

	public function call(QueryStatement $stmt, ResultSetMapping $rsm) {
		$callback = $this->functions[$stmt->getAndIncrementCurrentPath()];

		if ($callback instanceof IMacro) {
			return $callback->call($stmt, $rsm);
		}

		return $callback($stmt, $rsm);
	}

	private function extractLastName(string $entity): string {
		$explode = explode('\\', $entity);
		return $explode[count($explode) - 1];
	}

	private function getAlias(string $table) {
		if (isset($this->tableAliases[$table])) {
			return $this->tableAliases[$table];
		}

		$len = strlen($table);
		$alias = '';
		$i = 0;
		while ($i < $len) {
			$alias .= $table[$i];
			if (!isset($this->aliases[$alias])) {
				break;
			}
			$i++;
		}

		if ($i >= $len) {
			throw new \Exception('Duplicated alias ' . $alias);
		}
		$this->aliases[$alias] = true;
		$this->tableAliases[$table] = $alias;

		return $alias;
	}

	private function processEntityMap(array $entities, EntityManagerInterface $em): array {
		$ret = [];
		foreach ($entities as $name => $args) {
			if (is_string($args)) {
				$metadata = $em->getClassMetadata($args);

				if (!is_string($name)) {
					$name = lcfirst($this->extractLastName($args));
				}
				$ret[$name] = [$metadata, $this->getAlias($metadata->getTableName())];
			} else {
				throw new NotImplementedException();
			}
		}

		return $ret;
	}

	protected function discriminator(QueryStatement $stmt, ResultSetMapping $rsm) {
		$class = $stmt->getArguments()[0];

		/** @var $metadata ClassMetadata */
		[$metadata, $alias] = $this->mapping[$class];

		return $alias . '.' . $metadata->discriminatorColumn['name'];
	}

	protected function type(QueryStatement $stmt, ResultSetMapping $rsm) {
		$class = $stmt->getArguments()[0];

		/** @var $metadata ClassMetadata */
		[$metadata] = $this->mapping[$class];

		return $metadata->discriminatorValue;
	}

	protected function select(QueryStatement $stmt, ResultSetMapping $rsm) {
		$column = $stmt->getArguments()[0];
		$rsm->addScalarResult($column, $column, $stmt->getCurrentPath());

		return $column;
	}

}
