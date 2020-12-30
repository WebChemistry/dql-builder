<?php declare(strict_types = 1);

namespace WebChemistry\DQLBuilder\Tokenizer;

use Doctrine\ORM\Query\ResultSetMapping;
use LogicException;

class QueryTokenizer {

	/** @var string */
	private $string;

	/** @var int */
	private $length;

	/** @var string */
	private $result;

	/** @var int */
	private $pos = 0;

	/** @var callable */
	private $callback;

	/** @var string */
	private $word;

	/** @var ResultSetMapping */
	private $rsm;

	/** @var bool */
	private $inProcess = false;

	/** @var int */
	private $inProcessPosition;

	public function __construct(string $string, callable $callback) {
		$this->string = $string . ' ';
		$this->length = strlen($this->string);
		$this->callback = $callback;
		$this->rsm = new ResultSetMapping();
	}

	public function setCallback(callable $callback) {
		$this->callback = $callback;
	}

	public function getResult(): string {
		try {
			while ($token = $this->nextToken()) {
				$this->inProcess = false;
				$this->result .= ($this->callback)($token, $this->rsm) . ($token->isFunction() ? '' : $this->word);
			}
		} catch (TokenizerEnds $e) {
			if ($this->inProcess) {
				throw new LogicException(substr($this->string, $this->inProcessPosition, -1));
			}

			return substr($this->result, 0, -1);
		}
	}

	/**
	 * @return ResultSetMapping
	 */
	public function getRsm(): ResultSetMapping {
		return $this->rsm;
	}

	public function nextWord(): string {
		if ($this->pos < $this->length) {
			return $this->word = $this->string[$this->pos++];
		}

		throw new TokenizerEnds();
	}

	public function whilePercent(): void {
		while (($word = $this->nextWord()) !== '%') {
			$this->result .= $word;
		}
	}

	/**
	 * <stateName>[.<stateName>(<stateParams>)]
	 */
	public function prepareToken(): QueryStatement {
		$paths = [];

		do {
			$paths[] = $this->stateName();
		} while ($this->word === '.');

		// function
		if ($this->word === '(') {
			$args = $this->stateFunction();
			return new QueryStatement($paths, $args, true);
		}

		return new QueryStatement($paths);
	}

	protected function stateName(): string {
		$name = '';
		while (ctype_alnum($word = $this->nextWord())) {
			$name .= $word;
		}

		return $name;
	}

	protected function stateFunction(): array {
		$arguments = [];
		while ($this->word !== ')') {
			$arguments[] = $this->stateArgument();
		}

		if (count($arguments) === 1 && !$arguments[0]) {
			return [];
		}

 		return $arguments;
	}

	protected function stateArgument() {
		$body = '';

		while (($word = $this->nextWord()) !== ')' && $word !== ',') {
			$body .= $word;
		}

		return trim($body);
	}

	public function nextToken(): QueryStatement {
		$this->whilePercent();

		$this->inProcess = true;
		$this->inProcessPosition = $this->pos;

		return $this->prepareToken();
	}

}
