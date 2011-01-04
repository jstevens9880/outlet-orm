<?php
/**
 * Contains the class to configure Outlet's embeddable entity
 *
 * @package org.outlet-orm
 * @subpackage entity
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */

/**
 * Class that maps a class that is embedded on other entity
 *
 * @package org.outlet-orm
 * @subpackage entity
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */
class OutletEmbeddableEntity
{
	/**
	 * Class name
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Property list
	 *
	 * @var array
	 */
	protected $properties;

	/**
	 * Constructor
	 *
	 * @param string $name
	 */
	public function __construct($name)
	{
		$this->setName($name);
		$this->setProperties(array());
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @return array
	 */
	public function getProperties()
	{
		return $this->properties;
	}

	/**
	 * @param array $properties
	 */
	public function setProperties(array $properties)
	{
		$this->properties = $properties;
	}

	/**
	 * Append a property to entity
	 *
	 * @param OutletEntityProperty $property
	 */
	public function addProperty(OutletEntityProperty $property)
	{
		$this->properties[$property->getName()] = $property;
	}

	/**
	 * Returns a property by name
	 *
	 * @param name $name
	 * @return OutletEntityProperty
	 */
	public function getProperty($name)
	{
		if (isset($this->properties[$name])) {
			return $this->properties[$name];
		}

		return null;
	}

	/**
	 * Returns a property by column name
	 *
	 * @param string $column
	 * @return OutletEntityProperty
	 */
	public function getPropertyByColumn($column)
	{
		foreach ($this->getProperties() as $property) {
			if ($property->getColumn() == $column) {
				return $property;
			}
		}

		return null;
	}

	/**
	 * Returns the properties that are embedded
	 *
	 * @return array
	 */
	public function getEmbeddedProperties()
	{
		$properties = array();

		foreach ($this->getProperties() as $property) {
			if ($property->isEmbedded()) {
				$properties[$property->getName()] = $property;
			}
		}

		return $properties;
	}

	/**
	 * Returns if entity uses getters and setters
	 *
	 * @param string $method
	 * @return boolean
	 */
	public function useGettersAndSetters($method)
	{
		if (!class_exists($this->getName())) {
			throw new BadMethodCallException('The class ' . $this->getName() . ' is not loaded!');
		}

		if (method_exists($this->getName(), '__call')) {
			return true;
		}

		return method_exists($this->getName(), $method);
	}

	/**
	 * Extracts the object values
	 *
	 * @param object $obj
	 * @return array
	 */
	public function extractValues($obj)
	{
		return $this->getEntityHelper()->extractValues($this, $obj);
	}

	/**
	 * Extracts the object values
	 *
	 * @param object $obj
	 * @return array
	 */
	public function extractValuesToSql($obj)
	{
		return $this->getEntityHelper()->extractValuesToSql($this, $obj);
	}

	/**
	 * Extracts the object values
	 *
	 * @param object $obj
	 * @return array
	 */
	public function populate($obj, array $properties)
	{
		return $this->getEntityHelper()->populateObject($this, $obj, $properties);
	}

	/**
	 * @return OutletEntityHelper
	 */
	protected function getEntityHelper()
	{
		return OutletEntityHelper::getInstance();
	}
}