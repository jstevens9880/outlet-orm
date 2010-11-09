<?php
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
	 * Translates a PHP value to a SQL Value
	 * 
	 * @param string $type
	 * @param mixed $value
	 * @return mixed
	 */
	public function toSqlValue($type, $value)
	{
		if (is_null($value)) {
			return null;
		}
		
		switch ($type) {
			case 'date':
				return $value->format('Y-m-d');
			case 'datetime':
				return $value->format('Y-m-d H:i:s');
			case 'int':
			case 'bool':
				return (int) $value;
			case 'float':
				return (float) $value;
			default:
				return $value;
		}
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
	 * @param OutletEntity $entity
	 * @param object $obj
	 * @return array
	 */
	public function extractValues(OutletEntity $entity, $obj)
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
	 * @param OutletEntity $entity
	 * @param object $obj
	 * @return array
	 */
	public function extractValuesToSql(OutletEntity $entity, $obj)
	{
		$properties = array();
		
		foreach ($this->extractValues($entity, $obj) as $prop => $value) {
			$property = $entity->getProperty($prop);
			$properties[$property->getColumn()] = $this->toSqlValue($property->getType(), $value);
		}
		
		return $properties;
	}
	
	public function extractPrimaryKeysValues(OutletEntity $entity, $obj)
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
	 * @param OutletEntity $entity
	 * @param string $propertyName
	 * @param mixed $obj
	 * @return mixed
	 */
	public function getPropertyValue(OutletEntity $entity, $propertyName, $obj)
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
	 * @param OutletEntity $entity
	 * @param string $propertyName
	 * @param object $obj
	 * @param mixed $value
	 */
	public function setPropertyValue(OutletEntity $entity, $propertyName, $obj, $value)
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
	 * @param OutletEntity $entity
	 * @param object $obj
	 * @param array $properties
	 */
	public function populateObject(OutletEntity $entity, $obj, array $properties)
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