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
			if (!$prop->isEmbedded()) {
				$properties[$prop->getName()] = $this->getPropertyValue($entity, $prop->getName(), $obj);
			} else {
				$embeddedEntity = $prop->getEmbeddedEntity();
				$properties = array_merge($properties, $this->extractValues($embeddedEntity, $this->getPropertyValue($entity, $prop->getName(), $obj)));
			}
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

		foreach ($entity->getProperties() as $prop) {
			if (!$prop->isEmbedded()) {
				$properties[$prop->getColumn()] = OutletWriteQuery::toSqlValue($prop->getType(), $this->getPropertyValue($entity, $prop->getName(), $obj));
			} else {
				$embeddedEntity = $prop->getEmbeddedEntity();
				$properties = array_merge($properties, $this->extractValuesToSql($embeddedEntity, $this->getPropertyValue($entity, $prop->getName(), $obj)));
			}
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
		$embbedProps = array();

		foreach ($properties as $column => $value) {
			if ($property = $entity->getPropertyByColumn($column)) {
				$this->setPropertyValue(
					$entity,
					$property->getName(),
					$obj,
					$this->fromSqlValue(
						$property->getType(),
						$value
					)
				);
			} else {
				$embbedProps[$column] = $value;
			}
		}

		foreach ($entity->getEmbeddedProperties() as $property) {
			$embbedEntity = $property->getEmbeddedEntity();
			$embbedName = $embbedEntity->getName();
			$embeddedObj = new $embbedName;

			$this->populateObject($embbedEntity, $embeddedObj, $embbedProps);

			$this->setPropertyValue(
				$entity,
				$property->getName(),
				$obj,
				$embeddedObj
			);
		}
	}

	/**
	 * Populate the query with the properties
	 *
	 * @param OutletEmbeddableEntity $entity
	 * @param OutletInsertQuery $query
	 * @param object $obj
	 * @param OutletEmbeddableEntity $mainEntity
	 */
	public function fillInsertQuery(OutletEmbeddableEntity $entity, OutletInsertQuery $query, $obj, OutletEmbeddableEntity $mainEntity = null)
	{
		foreach ($entity->getProperties() as $propName => $property) {
			if (!$property->getAutoIncrement()) {
				$entityToUse = is_null($mainEntity) ? $entity : $mainEntity;

				if (is_null($this->getPropertyValue($entity, $propName, $obj)) && $property->hasDefaultValue()) {
					$this->setPropertyValue($entity, $propName, $obj, $property->getDefaultValue());
				}

				if (is_null($this->getPropertyValue($entity, $propName, $obj)) && $property->hasDefaultExpression()) {
					$query->setExpression('{' . $entityToUse->getName() . '.' . $propName . '}', $property->getDefaultExpression());
				} else {
					if (!$property->isEmbedded()) {
						$query->set(
							'{' . $entityToUse->getName() . '.' . $propName . '}',
							$this->getPropertyValue($entity, $propName, $obj),
							$property->getType()
						);
					} else {
						$this->fillInsertQuery(
							$property->getEmbeddedEntity(),
							$query,
							$this->getPropertyValue($entity, $propName, $obj),
							$entityToUse
						);
					}
				}
			}
		}
	}

	/**
	 * Populate the query with the properties
	 *
	 * @param OutletEmbeddableEntity $entity
	 * @param OutletUpdateQuery $query
	 * @param object $obj
	 * @param array $modifiedProperties
	 * @param OutletEmbeddableEntity $mainEntity
	 */
	public function fillUpdateQuery(OutletEmbeddableEntity $entity, OutletUpdateQuery $query, $obj, array $modifiedProperties, OutletEmbeddableEntity $mainEntity = null)
	{
		$entityToUse = is_null($mainEntity) ? $entity : $mainEntity;

		foreach ($modifiedProperties as $key) {
			$property = $entity->getPropertyByColumn($key);

			if (is_null($property)) {
				$property = $entity->getProperty($key);
			}

			if (!is_null($property)) {
				$key = $property->getName();

				if (!$property->getPrimaryKey()) {
					$value = $this->getPropertyValue($entity, $key, $obj);

					$query->set(
						'{' . $entityToUse->getName() . '.' . $key . '}',
						$value,
						$property->getType()
					);
				}
			}
		}

		foreach ($entity->getEmbeddedProperties() as $embeddedProp) {
			$this->fillUpdateQuery(
				$embeddedProp->getEmbeddedEntity(),
				$query,
				$this->getPropertyValue($entity, $embeddedProp->getName(), $obj),
				$modifiedProperties,
				$entityToUse
			);
		}
	}
}