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
	 * Current config parser
	 *
	 * @var OutletConfigParser
	 */
	private $parser;

	/**
	 * Connection configuration
	 *
	 * @var OutletConnectionConfig
	 */
	private $connectionConfig;

	/**
	 * Entities config list
	 *
	 * @var array
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
		$parser = new OutletArrayParser($config);

		return $parser->getConfig();
	}

	/**
	 * Create an OutletConfig objet from a XML file or XML string
	 *
	 * @param string $xml
	 * @return OutletConfig
	 */
	public static function createFromXml($xml)
	{
		$parser = new OutletXmlParser($xml);

		return $parser->getConfig();
	}

	/**
	 * Initializes OutletConfig::$entities
	 *
	 * @param OutletConfigParser $parser
	 */
	public function __construct(OutletConfigParser $parser)
	{
		$this->setEntities(array());
		$this->parser = $parser;
	}

	/**
	 * Returns the current parser
	 *
	 * @return OutletConfigParser
	 */
	protected function getParser()
	{
		return $this->parser;
	}

	/**
	 * @return OutletConnectionConfig
	 */
	public function getConnectionConfig()
	{
		if (is_null($this->connectionConfig)) {
			$this->getParser()->createConnectionConfig();
		}

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
	 * @return array
	 */
	public function getEntities()
	{
		return $this->entities;
	}

	/**
	 * @param array $entities
	 */
	public function setEntities(array $entities)
	{
		$this->entities = $entities;
	}

	/**
	 * Append an entity into entities list
	 *
	 * @param OutletEmbeddableEntity $entity
	 */
	public function addEntity(OutletEmbeddableEntity $entity)
	{
		$this->entities[$entity->getName()] = $entity;
	}

	/**
	 * Get an entity configuration by the name
	 *
	 * @param string $name
	 * @return OutletEntity
	 */
	public function getEntity($name)
	{
		$this->getParser()->createEntityConfig($name);

		if ($this->entityExists($name)) {
			return $this->entities[$name];
		}

		return null;
	}

	/**
	 * Returns if the entity exists
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function entityExists($name)
	{
		return isset($this->entities[$name]);
	}
}