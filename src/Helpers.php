<?php declare(strict_types = 1);

namespace WebChemistry\DQLBuilder;

use Nette\StaticClass;

class Helpers {

	use StaticClass;

	public static function replace(int $position, string $haystack, string $replace): string {
		$pos = strpos($haystack, '?');
		if ($pos !== false) {
			$haystack = substr($haystack, 0, $pos) . $replace . substr($haystack, $pos + 1);
		}

		return str_replace('?' . $position, $replace, $haystack);
	}

	public static function toString($param): string {
		if ($param instanceof QueryBuilder) {
			return '(' . $param->getQuery()->getDQL() . ')';
		} else if ($param instanceof Query) {
			return '(' . $param->getDQL() . ')';
		}

		return (string) $param;
	}

	public static function replaceParams(array $params, string $expression): string {
		foreach ($params as $pos => $param) {
			$param = self::toString($param);
			$expression = self::replace($pos, $expression, $param);
		}

		return $expression;
	}

	public static function searchForQuery(array $params, callable $callback): void {
		foreach ($params as $param) {
			if ($param instanceof Query) {
				$callback($param);
			} else if ($param instanceof QueryBuilder) {
				$callback($param);
			}
		}
	}

}
