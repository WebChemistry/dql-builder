<?php declare(strict_types = 1);

namespace WebChemistry\DQLBuilder;

use Doctrine\ORM\EntityManagerInterface;
use function GuzzleHttp\Psr7\str;
use WebChemistry\DQLBuilder\Macros\BaseMacros;
use WebChemistry\DQLBuilder\Parts\From;
use WebChemistry\DQLBuilder\Parts\ICollection;
use WebChemistry\DQLBuilder\Parts\JoinCollection;
use WebChemistry\DQLBuilder\Parts\Select;
use WebChemistry\DQLBuilder\Parts\StringCollection;
use WebChemistry\DQLBuilder\Parts\Where;
use WebChemistry\DQLBuilder\Parts\WhereCollection;

class QueryBuilder {

	/** @var EntityManagerInterface */
	private $em;

	/** @var array */
	protected $parts = [
		'select' => [],
		'where' => [],
		'from' => null,
		'order' => [],
		'group' => [],
		'join' => [],
	];

	/** @var array */
	protected $parameters = [];

	/** @var int|null */
	protected $maxResults;

	/** @var int|null */
	protected $offset;

	/** @var BaseMacros */
	private $macros;

	public function __construct(EntityManagerInterface $em, BaseMacros $macros) {
		$this->em = $em;
		$this->parts = [
			'select' => new StringCollection(),
			'where' => new WhereCollection(),
			'from' => null,
			'order' => new StringCollection(),
			'group' => new StringCollection(),
			'join' => new JoinCollection(),
		];
		$this->macros = $macros;
	}

	public function create() {
		return new static($this->em);
	}

	public function setMaxResults(?int $maxResults) {
		$this->maxResults = $maxResults;

		return $this;
	}

	public function setOffset(?int $offset) {
		$this->offset = $offset;

		return $this;
	}

	protected function toString(&$expression) {
		if ($expression instanceof self) {
			$expression = '(' . $expression . ')';
		} else if (!is_string($expression)) {
			$expression = (string) $expression;
		}

		return $expression;
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

	protected function parseParams(string $expression, array $params): string {
		Helpers::searchForQuery($params, function ($query) {
			$this->addParameters($query->getParameters());
		});
		
		return Helpers::replaceParams($params, $expression);
	}

	public function select($select, array $params = []) {
		$this->parts['select']->clean();
		$this->addSelect($select, $params);

		return $this;
	}

	public function addSelect($select, array $params = []) {
		$this->parts['select']->add($this->parseParams($select, $params));

		return $this;
	}

	public function from(string $expression, array $params = []) {
		$this->parts['from'] = new From($this->parseParams($expression, $params));

		return $this;
	}

	public function leftJoin(string $entity, string $column, string $alias) {
		$this->parts['join']->add('LEFT', $entity, $column, $alias);

		return $this;
	}

	public function orderBy(string $column, string $type = 'ASC') {
		$this->parts['order']->clean();
		$this->addOrderBy($column, $type);

		return $this;
	}

	public function addOrderBy(string $column, string $type = 'ASC') {
		$this->parts['order']->add($column . ' ' . $type);

		return $this;
	}

	public function groupBy(string $expression) {
		$this->parts['group']->clean();
		$this->addGroupBy($expression);

		return $this;
	}

	public function addGroupBy(string $expression) {
		$this->parts['group']->add($expression);

		return $this;
	}

	public function where($expression) {
		$this->parts['where']->clean();
		$this->andWhere($expression);

		return $this;
	}

	public function andWhere($expression) {
		$this->parts['where']->add(new Where($expression));

		return $this;
	}

	public function orWhere($expression) {
		$this->parts['where']->add(new Where($expression, 'OR'));

		return $this;
	}

	protected function buildPart(string $type, ?string $stmt, array $opts = []): string {
		$parts = $this->parts[$type];
		if (!$parts) {
			return '';
		}
		if ($parts instanceof ICollection && !$parts->has()) {
			return '';
		}
		return ($stmt !== null ? $stmt . ' ' : '') . $this->parts[$type] . ' ';
	}

	public function getQuery() {
		$query = new Query($this->em, $this->macros, $this->selfToString());
		$query->setParameters($this->parameters);

		return $query;
	}

	public function __toString(): string {
		return $this->selfToString();
	}

	protected function selfToString(): string {
		$sql = $this->buildPart('select', 'SELECT');
		$sql .= $this->buildPart('from', 'FROM');
		$sql .= $this->buildPart('join', null);
		$sql .= $this->buildPart('where', 'WHERE');
		$sql .= $this->buildPart('group', 'GROUP BY');
		$sql .= $this->buildPart('order', 'ORDER BY');

		if ($this->maxResults) {
			$sql .= 'LIMIT ' . $this->maxResults . ' ';
		}
		if ($this->offset) {
			$sql .= 'OFFSET ' . $this->offset . ' ';
		}

		return substr($sql, 0, -1);
	}

}
