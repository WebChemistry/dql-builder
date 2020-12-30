<?php declare(strict_types = 1);

namespace WebChemistry\DQLBuilder\DI;

use Nette\DI\CompilerExtension;
use Nette\DI\MissingServiceException;
use WebChemistry\DQLBuilder\Bridges\Filter\DQLDataSourceFactory;
use WebChemistry\DQLBuilder\IQueryFactory;
use WebChemistry\DQLBuilder\Macros\BaseMacros;
use WebChemistry\DQLBuilder\QueryBuilder;
use WebChemistry\DQLBuilder\QueryFactory;
use WebChemistry\Filter\DataSource\DataSourceRegistry;
use WebChemistry\Filter\DataSource\IDataSourceFactory;

class DQLBuilderExtension extends CompilerExtension {

	/** @var array */
	public $defaults = [
		'entities' => [],
	];

	public function loadConfiguration(): void {
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults);

		// Base macros
		$macros = $builder->addDefinition($this->prefix('baseMacros'))
			->setFactory(BaseMacros::class, [$config['entities']]);

		$builder->addDefinition($this->prefix('factory'))
			->setFactory(QueryFactory::class, [$macros])
			->setType(IQueryFactory::class);

		/*$builder->addDefinition($this->prefix('dataSourceFactory'))
			->setType(IDataSourceFactory::class)
			->setFactory(DQLDataSourceFactory::class);*/
	}

	public function beforeCompile() {
		$builder = $this->getContainerBuilder();

		try {
			$builder->getDefinitionByType(DataSourceRegistry::class)
				->addSetup('addFactory', [QueryBuilder::class, $this->prefix('@dataSourceFactory')]);
		} catch (MissingServiceException $e) {
		}
	}

}
