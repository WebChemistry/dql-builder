<?php declare(strict_types = 1);

namespace WebChemistry\DQLBuilder\Parts;

class JoinCollection implements ICollection {

	/** @var array */
	protected $parts = [];

	public function has(): bool {
		return (bool) $this->parts;
	}

	public function clean() {
		$this->parts = [];

		return $this;
	}

	public function add(string $type = 'LEFT', string $entity, string $column, string $alias) {
		$this->parts[$alias] = [$type, $entity, $column];

		return $this;
	}

	public function __toString() {
		$dql = '';
		foreach ($this->parts as $alias => [$type, $entity, $column]) {
			$dql .= strtoupper($type) . ' JOIN ' . "$entity.$column $alias";
		}

		return substr($dql, 0, -1);
	}

}
