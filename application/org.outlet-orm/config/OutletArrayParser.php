<?php
/**
 * Contains the class to use array config
 * 
 * @package org.outlet-orm
 * @subpackage config
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */

/**
 * Class to create an OutletConfig from an array 
 * 
 * @package org.outlet-orm
 * @subpackage config
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */
class OutletArrayParser
{
	/**
	 * Creates the configuration from an array
	 * 
	 * @param array $configArray
	 * @return OutletConfig
	 */
	public function parse(array $configArray)
	{
		$config = $this->getConfigClass();
		
		$this->validateConfig($configArray);
		$this->parseConnection($config, $configArray['connection']);
		$this->parseEntities($config, $configArray['classes']);
		
		return $config;
	}
	
	/**
	 * Returns an OutletConfig instance (testing reasons)
	 * 
	 * @return OutletConfig
	 */
	protected function getConfigClass()
	{
		return new OutletConfig();
	}
	
	/**
	 * Validate the configuration
	 * 
	 * @param array $config
	 * @throws OutletArrayParserException
	 */
	public function validateConfig(array $config)
	{
		if (!isset($config['connection'])) {
			throw new OutletArrayParserException('Element [connection] not found in configuration');
		}
		
		if (!isset($config['connection']['dsn']) && !isset($config['connection']['pdo'])) {
			throw new OutletArrayParserException('You must set either [connection][pdo] or [connection][dsn] in configuration');
		}
		
		if (!isset($config['connection']['dialect'])) {
			throw new OutletArrayParserException('Element [connection][dialect] not found in configuration');
		}
		
		if (!isset($config['classes'])) {
			throw new OutletArrayParserException('Element [classes] missing in configuration');
		}
	}
	
	/**
	 * Parses the connection config
	 * 
	 * @param OutletConfig $config
	 * @param array $configArray
	 * @throws OutletArrayParserException
	 */
	public function parseConnection(OutletConfig $config, array $configArray)
	{
		if (isset($configArray['pdo'])) {
			if (!$configArray['pdo'] instanceof PDO) {
				throw new OutletArrayParserException('You must pass a PDO instance on $pdo');
			}
			
			$conn = new OutletConnectionConfig(
				$configArray['dialect'],
				null,
				null,
				null,
				$configArray['pdo']
			);
		} else {
			$conn = new OutletConnectionConfig(
				$configArray['dialect'],
				$configArray['dsn'],
				isset($configArray['username']) ? $configArray['username'] : null,
				isset($configArray['password']) ? $configArray['password'] : null
			);
		}
		
		$config->setConnectionConfig($conn);
	}
	
	/**
	 * Parses the entities configuration
	 * 
	 * @param OutletConfig $config
	 * @param array $classes
	 */
	public function parseEntities(OutletConfig $config, array $classes)
	{
		foreach ($classes as $className => $class) {
			$entity = new OutletEntity($className, $class['table']);
			
			if (isset($class['plural'])) {
				$entity->setPlural($class['plural']);
			}
			
			if (isset($class['sequenceName'])) {
				$entity->setSequenceName($class['sequenceName']);
			}
			
			$this->parseProperties($entity, $class['props']);
			
			$config->addEntity($entity);
		}
		
		foreach ($classes as $className => $class) {
			if (isset($class['associations'])) {
				$this->parseAssociations($config, $config->getEntity($className), $class['associations']);
			}
		}
	}
	
	/**
	 * Parses the entity's properties
	 * 
	 * @param OutletEntity $entity
	 * @param array $properties
	 */
	public function parseProperties(OutletEntity $entity, array $properties)
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
	 * @param OutletConfig $config
	 * @param OutletEntity $entity
	 * @param array $associations
	 */
	public function parseAssociations(OutletConfig $config, OutletEntity $entity, array $associations)
	{
		foreach ($associations as $association) {
			switch ($association[0]) {
				case 'many-to-one':
					$entity->addAssociation($this->createManyToOneAssociation($config, $association));
					break;
				case 'many-to-many':
					$entity->addAssociation($this->createManyToManyAssociation($config, $entity, $association));
					break;
				case 'one-to-one':
					$entity->addAssociation($this->createOneToOneAssociation($config, $association));
					break;
				case 'one-to-many':
					$entity->addAssociation($this->createOneToManyAssociation($config, $entity, $association));
					break;
			}
		}
	}
	
	/**
	 * Create a many-to-one association based on entity config
	 * 
	 * @param OutletConfig $config
	 * @param array $association
	 * @return OutletManyToOneAssociation
	 */
	public function createManyToOneAssociation(OutletConfig $config, array $association)
	{
		$relatedEntity = $config->getEntity($association[1]);

		$assoc = new OutletManyToOneAssociation($relatedEntity, $association[2]['key'], $relatedEntity->getMainPrimaryKey());
		
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
	 * @param OutletConfig $config
	 * @param OutletEntity $entity
	 * @param array $association
	 * @return OutletManyToManyAssociation
	 */
	public function createManyToManyAssociation(OutletConfig $config, OutletEntity $entity, array $association)
	{
		$relatedEntity = $config->getEntity($association[1]);
		
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
	 * @param OutletConfig $config
	 * @param array $association
	 * @return OutletOneToOneAssociation
	 */
	public function createOneToOneAssociation(OutletConfig $config, array $association)
	{
		$relatedEntity = $config->getEntity($association[1]);
		
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
	 * @param OutletConfig $config
	 * @param OutletEntity $entity
	 * @param array $association
	 * @return OutletOneToManyAssociation
	 */
	public function createOneToManyAssociation(OutletConfig $config, OutletEntity $entity, array $association)
	{
		$relatedEntity = $config->getEntity($association[1]);
		
		$assoc = new OutletOneToManyAssociation($relatedEntity, $association[2]['key'], $entity->getMainPrimaryKey());
		
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