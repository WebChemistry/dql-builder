<?php declare(strict_types = 1);

namespace WebChemistry\DQLBuilder\Macros;

use Doctrine\ORM\Query\ResultSetMapping;
use WebChemistry\DQLBuilder\Tokenizer\QueryStatement;

interface IMacro {

	public function call(QueryStatement $stmt, ResultSetMapping $rsm);

}
