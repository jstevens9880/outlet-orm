<?php
/**
 * Contains the class to configure Outlet
 * 
 * @package org.outlet-orm
 * @subpackage config
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */

/**
 * Class that configures entities and connection 
 * 
 * @package org.outlet-orm
 * @subpackage config
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */
class OutletConfig
{
	/**
	 * Connection configuration
	 * 
	 * @var OutletConnectionConfig
	 */
	private $connectionConfig;
	
	/**
	 * Entities config list
	 * 
	 * @var ArrayObject
	 */
	private $entities;
	
	/**
	 * Create an OutletConfig object from an array
	 * 
	 * @param array $config
	 * @return OutletConfig
	 */
	public static function createFromArray(array $config)
	{
		$parser = new OutletArrayParser();
		
		return $parser->parse($config);
	}
	
	/**
	 * Create an OutletConfig objet from a XML file or XML string
	 * 
	 * @param string $xml
	 * @return OutletConfig
	 */
	public static function createFromXml($xml)
	{
		$parser = new OutletXmlParser();
		
		if (file_exists($xml)) {
			$config = $parser->parseFromFile($xml);
		} else {
			$config = $parser->parseFromString($xml);
		}
		
		return $config;
	}
	
	/**
	 * Initializes OutletConfig::$entities
	 */
	public function __construct()
	{
		$this->setEntities(new ArrayObject());
	}
	
	/**
	 * @return OutletConnectionConfig
	 */
	public function getConnectionConfig()
	{
		return $this->connectionConfig;
	}

	/**
	 * @param OutletConnectionConfig $connectionConfig
	 */
	public function setConnectionConfig(OutletConnectionConfig $connectionConfig)
	{
		$this->connectionConfig = $connectionConfig;
	}
	
	/**
	 * Creates a new connection based on configuration
	 * 
	 * @see getConnectionConfig::createOutletConnection()
	 * @return OutletConnection
	 */
	public function createConnection()
	{
		return $this->getConnectionConfig()->createOutletConnection();
	}
	
	/**
	 * @return ArrayObject
	 */
	public function getEntities()
	{
		return $this->entities;
	}

	/**
	 * @param ArrayObject $entities
	 */
	public function setEntities(ArrayObject $entities)
	{
		$this->entities = $entities;
	}
	
	/**
	 * Append an entity into entities list
	 * 
	 * @param OutletEntity $entity
	 */
	public function addEntity(OutletEntity $entity)
	{
		$this->getEntities()->offsetSet($entity->getName(), $entity);
	}
	
	/**
	 * Get an entity configuration by the name
	 * 
	 * @param string $name
	 * @return OutletEntity
	 */
	public function getEntity($name)
	{
		if ($this->getEntities()->offsetExists($name)) {
			return $this->getEntities()->offsetGet($name);
		}
		
		return null;
	}
}