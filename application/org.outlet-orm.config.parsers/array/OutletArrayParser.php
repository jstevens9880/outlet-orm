<?php
/**
 * Contains the class to use array config
 *
 * @package org.outlet-orm.config.parsers
 * @subpackage array
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */

/**
 * Class to create an OutletConfig from an array
 *
 * @package org.outlet-orm.config.parsers
 * @subpackage array
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */
class OutletArrayParser implements OutletConfigParser
{
	/**
	 * Source for parsing
	 *
	 * @var array
	 */
	private $source;

	/**
	 * Config instance
	 *
	 * @var OutletConfig
	 */
	private $config;

	/**
	 * Array parser constructor
	 *
	 * @param array $source
	 */
	public function __construct(array $source)
	{
		$this->source = $source;
		$this->validate();
	}

	/**
	 * Returns an OutletConfig instance
	 *
	 * @return OutletConfig
	 */
	public function getConfig()
	{
		if (is_null($this->config)) {
			$this->config = new OutletConfig($this);
		}

		return $this->config;
	}

	/**
	 * Returns the array source
	 *
	 * @return array
	 */
	public function getSource()
	{
		return $this->source;
	}

	/**
	 * Validate the configuration
	 *
	 * @throws OutletArrayParserException
	 */
	protected function validate()
	{
		$source = $this->getSource();

		if (!isset($source['connection'])) {
			throw new OutletArrayParserException('Element [connection] not found in configuration.');
		}

		if (!isset($source['connection']['dsn']) && !isset($source['connection']['pdo'])) {
			throw new OutletArrayParserException('You must set either [connection][pdo] or [connection][dsn] in configuration.');
		} elseif (isset($source['connection']['pdo']) && !$source['connection']['pdo'] instanceof PDO) {
			throw new OutletArrayParserException('You must pass a PDO instance on $pdo');
		}

		if (!isset($source['connection']['dialect'])) {
			throw new OutletArrayParserException('Element [connection][dialect] not found in configuration.');
		}

		if (!isset($source['classes']) || count($source['classes']) == 0) {
			throw new OutletArrayParserException('You must set [classes] in configuration with at least one entity.');
		}
	}

	/**
	 * Creates the connection config
	 */
	public function createConnectionConfig()
	{
		$source = $this->getSource();

		if (isset($source['connection']['pdo'])) {
			$conn = new OutletConnectionConfig(
				$source['connection']['dialect'],
				null,
				null,
				null,
				$source['connection']['pdo']
			);
		} else {
			$conn = new OutletConnectionConfig(
				$source['connection']['dialect'],
				$source['connection']['dsn'],
				isset($source['connection']['username']) ? $source['connection']['username'] : null,
				isset($source['connection']['password']) ? $source['connection']['password'] : null
			);
		}

		$this->getConfig()->setConnectionConfig($conn);
	}

	/**
	 * Returns if the entity exists on config source
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function entityExists($name)
	{
		$source = $this->getSource();

		return isset($source['classes'][$name]);
	}

	/**
	 * Creates the entity config
	 *
	 * @param string $name
	 * @return OutletEntity
	 */
	public function createEntityConfig($name)
	{
		$config = $this->getConfig();

		if (!$config->entityExists($name)) {
			if (!$this->entityExists($name)) {
				throw new OutletArrayParserException('There\'s no entity with name "' . $name . '" on the config source.');
			}

			$source = $this->getSource();
			$classConfig = $source['classes'][$name];
			$entity = $this->parseClass($name, $classConfig);

			$config->addEntity($entity);

			if (isset($classConfig['associations'])) {
				$this->parseAssociations($entity, $classConfig['associations']);
			}
		}
	}
	/**
	 * Parses an entity
	 *
	 * @param string $name
	 * @param array $classConfig
	 */
	protected function parseClass($name, $classConfig)
	{
		$entity = new OutletEntity($name, $classConfig['table']);

		if (isset($classConfig['plural'])) {
			$entity->setPlural($classConfig['plural']);
		}

		if (isset($classConfig['sequenceName'])) {
			$entity->setSequenceName($classConfig['sequenceName']);
		}

		$this->parseProperties($entity, $classConfig['props']);

		return $entity;
	}

	/**
	 * Creates the embeddable entity config
	 *
	 * @param string $name
	 */
	/*public function createEmbeddableEntityConfig($name)
	{
		//TODO implement...
	}*/

	/**
	 * Parses the entity's properties
	 *
	 * @param OutletEntity $entity
	 * @param array $properties
	 */
	protected function parseProperties(OutletEntity $entity, array $properties)
	{
		foreach ($properties as $propertyName => $property) {
			$prop = new OutletEntityProperty($propertyName, $property[0], $property[1]);

			if (isset($property[2])) {
				if (isset($property[2]['pk'])) {
					$prop->setPrimaryKey(true);
				}

				if (isset($property[2]['autoIncrement'])) {
					$prop->setAutoIncrement(true);
				}

				if (isset($property[2]['default'])) {
					$prop->setDefaultValue($property[2]['default']);
				}

				if (isset($property[2]['defaultExpr'])) {
					$prop->setDefaultExpression($property[2]['defaultExpr']);
				}
			}

			$entity->addProperty($prop);
		}
	}

	/**
	 * Parses the entity's associations
	 *
	 * @param OutletEntity $entity
	 * @param array $associations
	 */
	protected function parseAssociations(OutletEntity $entity, array $associations)
	{
		foreach ($associations as $association) {
			$relatedEntity = $this->getConfig()->getEntity($association[1]);

			switch ($association[0]) {
				case 'many-to-one':
					$entity->addAssociation($this->createManyToOneAssociation($relatedEntity, $association));
					break;
				case 'many-to-many':
					$entity->addAssociation($this->createManyToManyAssociation($entity, $relatedEntity, $association));
					break;
				case 'one-to-one':
					$entity->addAssociation($this->createOneToOneAssociation($relatedEntity, $association));
					break;
				case 'one-to-many':
					$entity->addAssociation($this->createOneToManyAssociation($entity, $relatedEntity, $association));
					break;
			}
		}
	}

	/**
	 * Create a many-to-one association based on entity config
	 *
	 * @param OutletEntity $relatedEntity
	 * @param array $association
	 * @return OutletManyToOneAssociation
	 */
	protected function createManyToOneAssociation(OutletEntity $relatedEntity, array $association)
	{
		$assoc = new OutletManyToOneAssociation(
			$relatedEntity,
			$association[2]['key'],
			$relatedEntity->getMainPrimaryKey()
		);

		if (isset($association[2]['name'])) {
			$assoc->setName($association[2]['name']);
		}

		if (isset($association[2]['refKey'])) {
			$assoc->setRefKey($association[2]['refKey']);
		}

		if (isset($association[2]['optional'])) {
			$assoc->setOptional($association[2]['optional']);
		}

		return $assoc;
	}

	/**
	 * Create a many-to-many association based on entity config
	 *
	 * @param OutletEntity $entity
	 * @param OutletEntity $relatedEntity
	 * @param array $association
	 * @return OutletManyToManyAssociation
	 */
	protected function createManyToManyAssociation(OutletEntity $entity, OutletEntity $relatedEntity, array $association)
	{
		$assoc = new OutletManyToManyAssociation(
			$relatedEntity,
			$relatedEntity->getMainPrimaryKey(),
			$entity->getMainPrimaryKey(),
			$association[2]['table'],
			$association[2]['tableKeyLocal'],
			$association[2]['tableKeyForeign']
		);

		if (isset($association[2]['key'])) {
			$assoc->setKey($association[2]['key']);
		}

		if (isset($association[2]['refKey'])) {
			$assoc->setRefKey($association[2]['refKey']);
		}

		if (isset($association[2]['name'])) {
			$assoc->setName($association[2]['name'] . 's');
		}

		if (isset($association[2]['plural'])) {
			$assoc->setName($association[2]['plural']);
		}

		return $assoc;
	}

	/**
	 * Create a one-to-one association based on entity config
	 *
	 * @param OutletEntity $relatedEntity
	 * @param array $association
	 * @return OutletOneToOneAssociation
	 */
	protected function createOneToOneAssociation(OutletEntity $relatedEntity, array $association)
	{
		$assoc = new OutletOneToOneAssociation(
			$relatedEntity,
			$association[2]['key'],
			$relatedEntity->getMainPrimaryKey()
		);

		if (isset($association[2]['name'])) {
			$assoc->setName($association[2]['name']);
		}

		if (isset($association[2]['refKey'])) {
			$assoc->setRefKey($association[2]['refKey']);
		}

		if (isset($association[2]['optional'])) {
			$assoc->setOptional($association[2]['optional']);
		}

		return $assoc;
	}

	/**
	 * Create a one-to-many association based on entity config
	 *
	 * @param OutletEntity $entity
	 * @param OutletEntity $relatedEntity
	 * @param array $association
	 * @return OutletOneToManyAssociation
	 */
	protected function createOneToManyAssociation(OutletEntity $entity, OutletEntity $relatedEntity, array $association)
	{
		$assoc = new OutletOneToManyAssociation(
			$relatedEntity,
			$association[2]['key'],
			$entity->getMainPrimaryKey()
		);

		if (isset($association[2]['refKey'])) {
			$assoc->setRefKey($association[2]['refKey']);
		}

		if (isset($association[2]['name'])) {
			$assoc->setName($association[2]['name'] . 's');
		}

		if (isset($association[2]['plural'])) {
			$assoc->setName($association[2]['plural']);
		}

		return $assoc;
	}
}