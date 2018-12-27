<?php declare(strict_types = 1);

namespace WebChemistry\DQLBuilder\Bridges\Filter;

use Doctrine\ORM\EntityManagerInterface;
use WebChemistry\Filter\DataSource\IDataSource;
use WebChemistry\Filter\DataSource\IDataSourceFactory;

class DQLDataSourceFactory implements IDataSourceFactory {

	/** @var EntityManagerInterface */
	private $em;

	public function __construct(EntityManagerInterface $em) {
		$this->em = $em;
	}

	public function create($source, array $options): IDataSource {
		return new DQLDataSource($source, $this->em, $options);
	}

}
