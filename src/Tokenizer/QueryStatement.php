<?php declare(strict_types = 1);

namespace WebChemistry\DQLBuilder\Tokenizer;

class QueryStatement {

	/** @var array */
	private $paths;

	/** @var array */
	private $arguments;

	/** @var bool */
	private $isFunction;

	/** @var int */
	private $current = 0;

	/** @var int */
	private $last = 0;

	public function __construct(array $paths, array $arguments = [], bool $isFunction = false) {
		$this->paths = $paths;
		$this->arguments = $arguments;
		$this->isFunction = $isFunction;
		$this->last = count($paths) - 1;
	}

	public function getArguments(): array {
		return $this->arguments;
	}

	public function getAndIncrementCurrentPath() {
		if ($this->isEnd()) {
			return $this->paths[$this->current];
		}

		return $this->paths[$this->current++];
	}

	public function getCurrentPath() {
		return $this->paths[$this->current];
	}

	public function isEnd(): bool {
		return $this->last === $this->current;
	}

	public function isFunction(): bool {
		return $this->isFunction;
	}

}
