<?php
/**
 * Contains the class to manipulate entities's data
 *
 * @package org.outlet-orm
 * @subpackage entity
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */

/**
 * Class with helper functions for entity manipulation
 *
 * @package org.outlet-orm
 * @subpackage entity
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */
class OutletEntityHelper
{
	/**
	 * Singleton usage
	 *
	 * @var OutletEntityHelper
	 */
	private static $instance;

	/**
	 * Returns an instance of OutletEntityHelper (Singleton way)
	 *
	 * @return OutletEntityHelper
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Translates an value to the expected value as defined in the configuration
	 *
	 * @param string $type
	 * @param mixed $value
	 * @return mixed
	 */
	public function fromSqlValue($type, $value)
	{
		if (is_null($value)) {
			return null;
		}

		switch ($type) {
			case 'date':
			case 'datetime':
				return $value instanceof DateTime ? $value : new DateTime($value);
			case 'int':
				return (int) $value;
			case 'float':
				return (float) $value;
			case 'bool':
				return $value == 1;
			default:
				return $value;
		}
	}

	/**
	 * Returns a converted associated array
	 *
	 * @param OutletEmbeddableEntity $entity
	 * @param object $obj
	 * @return array
	 */
	public function extractValues(OutletEmbeddableEntity $entity, $obj)
	{
		$properties = array();

		foreach ($entity->getProperties() as $prop) {
			$properties[$prop->getName()] = $this->getPropertyValue($entity, $prop->getName(), $obj);
		}

		return $properties;
	}

	/**
	 * Returns a converted associated array
	 *
	 * @param OutletEmbeddableEntity $entity
	 * @param object $obj
	 * @return array
	 */
	public function extractValuesToSql(OutletEmbeddableEntity $entity, $obj)
	{
		$properties = array();

		foreach ($this->extractValues($entity, $obj) as $prop => $value) {
			$property = $entity->getProperty($prop);
			$properties[$property->getColumn()] = OutletWriteQuery::toSqlValue($property->getType(), $value);
		}

		return $properties;
	}

	/**
	 * Extracts the primary keys
	 *
	 * @param OutletEmbeddableEntity $entity
	 * @param object $obj
	 * @return array
	 */
	public function extractPrimaryKeysValues(OutletEmbeddableEntity $entity, $obj)
	{
		$primaryKeys = array();

		foreach ($entity->getPrimaryKeys() as $primaryKey) {
			$prop = $entity->getProperty($primaryKey);

			$primaryKeys[$prop->getName()] = $this->getPropertyValue($entity, $prop->getName(), $obj);
		}

		return $primaryKeys;
	}

	/**
	 * Returns the property value, checking if entity uses getters and setters
	 *
	 * @param OutletEmbeddableEntity $entity
	 * @param string $propertyName
	 * @param mixed $obj
	 * @return mixed
	 */
	public function getPropertyValue(OutletEmbeddableEntity $entity, $propertyName, $obj)
	{
		if ($entity->useGettersAndSetters('get' . $propertyName)) {
			$prop = 'get' . $propertyName;

			return $obj->$prop();
		} else {
			return $obj->$propertyName;
		}
	}

	/**
	 * Configures object property, checking if entity uses getters and setters
	 *
	 * @param OutletEmbeddableEntity $entity
	 * @param string $propertyName
	 * @param object $obj
	 * @param mixed $value
	 */
	public function setPropertyValue(OutletEmbeddableEntity $entity, $propertyName, $obj, $value)
	{
		if ($entity->useGettersAndSetters('set' . $propertyName)) {
			$prop = 'set' . $propertyName;

			$obj->$prop($value);
		} else {
			$obj->$propertyName = $value;
		}
	}

	/**
	 * Populate the object from an associative array
	 *
	 * @param OutletEmbeddableEntity $entity
	 * @param object $obj
	 * @param array $properties
	 */
	public function populateObject(OutletEmbeddableEntity $entity, $obj, array $properties)
	{
		foreach ($properties as $property => $value) {
			$property = $entity->getPropertyByColumn($property);

			$this->setPropertyValue(
				$entity,
				$property->getName(),
				$obj,
				$this->fromSqlValue(
					$property->getType(),
					$value
				)
			);
		}
	}
}