<?php
/**
 * Contains the class to configure Outlet's entity
 * 
 * @package org.outlet-orm
 * @subpackage entity
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */

/**
 * Class that maps the class on a database table
 * 
 * @package org.outlet-orm
 * @subpackage entity
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */
class OutletEntity
{
	/**
	 * Class name
	 * 
	 * @var string
	 */
	private $name;
	
	/**
	 * Table name
	 * 
	 * @var string
	 */
	private $table;
	
	/**
	 * The noun to use when referring to more than one entity
	 * 
	 * @var string
	 */
	private $plural;
	
	/**
	 * Since PostgreSQL uses sequences instead of auto increment columns, the PDO driver need the sequence name in order to get the generated new id.
	 * If not specified it will use the default: {table_name}_{column_name}_seq
	 * 
	 * @var string
	 */
	private $sequenceName;
	
	/**
	 * Property list
	 * 
	 * @var ArrayObject
	 */
	private $properties;
	
	/**
	 * Association list
	 * 
	 * @var ArrayObject
	 */
	private $associations;
	
	/**
	 * Primary key list
	 * 
	 * @var ArrayObject
	 */
	private $primaryKeys;
	
	/**
	 * Constructor
	 * 
	 * @param string $name
	 * @param string $table
	 * @param string $plural
	 * @param string $sequenceName
	 */
	public function __construct($name, $table, $plural = null, $sequenceName = null)
	{
		$this->setName($name);
		$this->setTable($table);
		$this->setPlural(!is_null($plural) ? $plural : $name . 's');
		$this->setSequenceName($sequenceName);
		$this->setProperties(new ArrayObject());
		$this->setAssociations(new ArrayObject());
		$this->setPrimaryKeys(new ArrayObject());
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
	 * @return string
	 */
	public function getTable()
	{
		return $this->table;
	}

	/**
	 * @param string $table
	 */
	public function setTable($table)
	{
		$this->table = $table;
	}

	/**
	 * @return string
	 */
	public function getPlural()
	{
		return $this->plural;
	}

	/**
	 * @param string $plural
	 */
	public function setPlural($plural)
	{
		$this->plural = $plural;
	}

	/**
	 * @return string
	 */
	public function getSequenceName()
	{
		return $this->sequenceName;
	}

	/**
	 * @param string $sequenceName
	 */
	public function setSequenceName($sequenceName)
	{
		$this->sequenceName = $sequenceName;
	}
	
	/**
	 * @return ArrayObject
	 */
	public function getProperties()
	{
		return $this->properties;
	}

	/**
	 * @param ArrayObject $properties
	 */
	public function setProperties(ArrayObject $properties)
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
		$this->getProperties()->offsetSet($property->getName(), $property);
		
		if ($property->getPrimaryKey()) {
			$this->getPrimaryKeys()->append($property->getName());
		}
	}
	
	/**
	 * Returns a property by name
	 * 
	 * @param name $name
	 * @return OutletEntityProperty
	 */
	public function getProperty($name)
	{
		if ($this->getProperties()->offsetExists($name)) {
			return $this->getProperties()->offsetGet($name);
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
	 * @return ArrayObject
	 */
	public function getAssociations()
	{
		return $this->associations;
	}

	/**
	 * @param ArrayObject $associations
	 */
	public function setAssociations(ArrayObject $associations)
	{
		$this->associations = $associations;
	}
	
	/**
	 * Append an association to entity
	 * 
	 * @param OutletAssociation $association
	 */
	public function addAssociation(OutletAssociation $association)
	{
		$this->getAssociations()->offsetSet($association->getName(), $association);
	}
	
	/**
	 * Returns the association with that entity
	 * 
	 * @param string $entityName
	 * @return OutletAssociation
	 */
	public function getAssociation($entityName)
	{
		if ($this->getAssociations()->offsetExists($entityName)) {
			return $this->getAssociations()->offsetGet($entityName);
		}
		
		return null;
	}
	
	/**
	 * @return ArrayObject
	 */
	public function getPrimaryKeys()
	{
		return $this->primaryKeys;
	}
	
	/**
	 * @param ArrayObject $primaryKeys
	 */
	public function setPrimaryKeys(ArrayObject $primaryKeys)
	{
		$this->primaryKeys = $primaryKeys;
	}
	
	/**
	 * @return string
	 */
	public function getMainPrimaryKey()
	{
		return $this->getPrimaryKeys()->offsetGet(0);
	}
	
	/**
	 * @return array
	 */
	public function getPrimaryKeysColumns()
	{
		$columns = array();
		
		foreach ($this->getPrimaryKeys() as $primaryKey) {
			$property = $this->getProperty($primaryKey);
			$columns[] = $property->getColumn();
		}
		
		return $columns;
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
	 * Extracts the primary keys values
	 * 
	 * @param object $obj
	 * @return array
	 */
	public function extractPrimaryKeysValues($obj)
	{
		return OutletEntityHelper::getInstance()->extractPrimaryKeysValues($this, $obj);
	}
	
	/**
	 * Extracts the object values
	 * 
	 * @param object $obj
	 * @return array
	 */
	public function extractValues($obj)
	{
		return OutletEntityHelper::getInstance()->extractValues($this, $obj);
	}
	
	/**
	 * Extracts the object values
	 * 
	 * @param object $obj
	 * @return array
	 */
	public function extractValuesToSql($obj)
	{
		return OutletEntityHelper::getInstance()->extractValuesToSql($this, $obj);
	}
}