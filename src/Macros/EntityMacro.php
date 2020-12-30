<?php declare(strict_types = 1);

namespace WebChemistry\DQLBuilder\Macros;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Utility\PersisterHelper;
use WebChemistry\DQLBuilder\MissingMetadataException;
use WebChemistry\DQLBuilder\Tokenizer\QueryStatement;

class EntityMacro implements IMacro {

	/** @var string */
	private $name;

	/** @var string */
	private $class;

	/** @var ClassMetadata */
	private $metadata;

	/** @var string */
	private $alias;

	/** @var ResultSetMapping */
	private $rsm;

	/** @var EntityManagerInterface */
	private $em;

	public function __construct(ClassMetadata $metadata, EntityManagerInterface $em, string $name, string $class, string $alias) {
		$this->name = $name;
		$this->class = $class;
		$this->metadata = $metadata;
		$this->alias = $alias;
		$this->em = $em;
	}

	public function typeOf(QueryStatement $stmt) {
		$name = $stmt->getArguments()[0] ?? null;

		if (!$name && $stmt->isEnd()) {
			return $this->alias . '.' . $this->metadata->discriminatorColumn['name'];
		}

		$stmt->getAndIncrementCurrentPath();
		if ($stmt->getCurrentPath() === 'value') {
			return $this->metadata->discriminatorValue;
		}

		throw new \LogicException();
	}

	public function column(string $field) {
		if (!isset($this->metadata->fieldMappings[$field])) {
			if (!isset($this->metadata->associationMappings[$field])) {
				throw new MissingMetadataException("Field $field not exists in $this->class.");
			}

			return $this->alias . '.' . $this->metadata->getSingleAssociationJoinColumnName($field);
		}
		return $this->alias . '.' . $this->metadata->getColumnName($field);
	}

	public function selectAll() {
		$columns = [];
		$fields = [];
		$subClasses = $this->metadata->subClasses;
		array_unshift($subClasses, $this->class);

		foreach ($subClasses as $class) {
			$metadata = $this->em->getClassMetadata($class);

			// fields
			foreach ($metadata->getFieldNames() as $field) {
				if (isset($fields[$field])) {
					continue;
				}
				$fields[$field] = true;

				$alias = $this->getAliasColumn($column = $metadata->getColumnName($field));
				$columns[] = $this->selectColumn($column, $alias);

				$this->rsm->addFieldResult($this->alias, $alias, $field, $metadata->getName());
			}

			// associations
			foreach ($metadata->associationMappings as $mapping) {
				if (!$mapping['isOwningSide']) {
					continue;
				}
				if ($mapping['type'] === ClassMetadataInfo::MANY_TO_MANY) {
					continue;
				}
				if (isset($fields[$mapping['fieldName']])) {
					continue;
				}
				$fields[$mapping['fieldName']] = true;
				$type = PersisterHelper::getTypeOfColumn($mapping['joinColumns'][0]['referencedColumnName'], $this->metadata, $this->em);

				$alias = $this->getAliasColumn($column = $metadata->getSingleAssociationJoinColumnName($mapping['fieldName']));
				$this->rsm->addMetaResult($this->alias, $alias, $column, false, $type);

				$columns[] = $this->selectColumn($column, $alias);
			}
		}
		// discriminator
		if ($mapping = $this->metadata->discriminatorColumn) {
			$alias = $this->getAliasColumn($mapping['name']);
			$column = $mapping['name'];
			$this->rsm->setDiscriminatorColumn($this->alias, $alias);
			$this->rsm->addMetaResult($this->alias, $alias, $mapping['fieldName'], false, $mapping['type']);
			$columns[] = $this->selectColumn($column, $alias);
		}

		return implode(', ', $columns);
	}

	public function selectColumn(string $column, string $alias): string {
		return $this->alias . '.' . $column . ' AS ' . $alias;
	}

	public function getAliasColumn(string $column): string {
		return $this->alias . '_' . $column;
	}

	public function select(array $fields) {
		if (!$fields) {
			return $this->selectAll();
		}

		return implode(', ', array_map(function ($field) {
			// deprecated
			if ($field === 'discr') {
				$alias = $this->alias . '_discr';
				$metadata = $this->metadata->discriminatorColumn;
				$this->rsm->setDiscriminatorColumn($this->alias, $alias);
				$this->rsm->addMetaResult($this->alias, $alias, $metadata['fieldName'], false, $metadata['type']);

				return $this->alias . '.' . $metadata['name'] . ' AS ' . $alias;
			}
			if (!isset($this->metadata->fieldMappings[$field])) {
				if (!isset($this->metadata->associationMappings[$field])) {
					throw new MissingMetadataException("Field $field not exists in $this->class.");
				}

				$column = $this->metadata->getSingleAssociationJoinColumnName($field);
				$alias = $this->alias . '_' . $column;

				$this->rsm->addMetaResult($this->alias, $alias, $column, false, 'integer');

				return $this->alias . '.' . $column . ' AS ' . $alias;
			}

			$column = $this->metadata->getColumnName($field);
			$alias = $this->alias . '_' . $column;
			$this->rsm->addFieldResult($this->alias, $alias, $field, $this->metadata->fieldMappings[$field]['declared'] ?? $this->class);

			return $this->alias . '.' . $column . ' AS ' . $alias;
		}, $fields));
	}

	public function call(QueryStatement $stmt, ResultSetMapping $rsm) {
		$this->rsm = $rsm;

		switch ($stmt->getCurrentPath()) {
			case 'select':
				return $this->select($stmt->getArguments());
			case 'from':
				$this->rsm->addEntityResult($this->class, $this->alias);

				return $this->metadata->getTableName() . ' AS ' . $this->alias;
				// deprecated
			case 'discr':
				return $this->typeOf($stmt);
			case 'typeOf':
				return $this->typeOf($stmt);
			default:
				return $this->column($stmt->getCurrentPath());
		}
	}

}
