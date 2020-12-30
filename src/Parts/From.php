<?php declare(strict_types = 1);

namespace WebChemistry\DQLBuilder\Parts;

class From {

	/** @var string */
	private $expression;

	public function __construct(string $expression) {
		$this->expression = $expression;
	}

	public function __toString(): string {
		return $this->expression;
	}

}
