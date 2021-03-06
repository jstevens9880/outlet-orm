<?php
/**
 * Contains the class to configure Outlet's entity
 *
 * @package org.outlet-orm
 * @subpackage entity
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */

/**
 * Class that maps the class's property with the database info
 *
 * @package org.outlet-orm
 * @subpackage entity
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */
class OutletEntityProperty
{
	/**
	 * Property name
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Column name
	 *
	 * @var string
	 */
	private $column;

	/**
	 * Column data type
	 *
	 * @var string
	 */
	private $type;

	/**
	 * Whether this property is (part of) the primary key
	 *
	 * @var boolean
	 */
	private $primaryKey;

	/**
	 * Whether this property is automatically incremented by the database
	 *
	 * @var boolean
	 */
	private $autoIncrement;

	/**
	 * A default value
	 *
	 * @var string|int
	 */
	private $defaultValue;

	/**
	 * A default sql expression or function
	 *
	 * @var string
	 */
	private $defaultExpression;

	/**
	 * Class name of embedded type
	 *
	 * @var string
	 */
	private $ref;

	/**
	 * Constructor
	 *
	 * @param string $name
	 * @param string $column
	 * @param string $type
	 * @param boolean $primaryKey
	 * @param boolean $autoIncrement
	 * @param string|int $defaultValue
	 * @param string $defaultExpression
	 * @param string $ref
	 */
	public function __construct($name, $column, $type, $primaryKey = false, $autoIncrement = false, $defaultValue = null, $defaultExpression = null, $ref = null)
	{
		$this->setName($name);
		$this->setColumn($column);
		$this->setType($type);
		$this->setRef($ref);
		$this->setPrimaryKey($primaryKey);
		$this->setAutoIncrement($autoIncrement);
		$this->setDefaultValue($defaultValue);
		$this->setDefaultExpression($defaultExpression);
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
	public function getColumn()
	{
		return $this->column;
	}

	/**
	 * @param string $column
	 */
	public function setColumn($column)
	{
		$this->column = $column;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param string $type
	 */
	public function setType($type)
	{
		$this->type = $type;
	}

	/**
	 * @return boolean
	 */
	public function getPrimaryKey()
	{
		return $this->primaryKey;
	}

	/**
	 * @param boolean $primaryKey
	 */
	public function setPrimaryKey($primaryKey)
	{
		$this->primaryKey = $primaryKey;
	}

	/**
	 * @return boolean
	 */
	public function getAutoIncrement()
	{
		return $this->autoIncrement;
	}

	/**
	 * @param boolean $autoIncrement
	 */
	public function setAutoIncrement($autoIncrement)
	{
		$this->autoIncrement = $autoIncrement;
	}

	/**
	 * @return string|int
	 */
	public function getDefaultValue()
	{
		return $this->defaultValue;
	}

	/**
	 * @param string|int $defaultValue
	 */
	public function setDefaultValue($defaultValue)
	{
		$this->defaultValue = $defaultValue;
	}

	/**
	 * @return boolean
	 */
	public function hasDefaultValue()
	{
		return !is_null($this->defaultValue);
	}

	/**
	 * @return string
	 */
	public function getDefaultExpression()
	{
		return $this->defaultExpression;
	}

	/**
	 * @return boolean
	 */
	public function hasDefaultExpression()
	{
		return !is_null($this->defaultExpression);
	}

	/**
	 * @param string $defaultExpression
	 */
	public function setDefaultExpression($defaultExpression)
	{
		$this->defaultExpression = $defaultExpression;
	}

	/**
	 * @return string
	 */
	public function getRef()
	{
		return $this->ref;
	}

	/**
	 * @param string $ref
	 */
	public function setRef($ref)
	{
		$this->ref = $ref;
	}

	/**
	 * @throws OutletConfigException
	 * @return OutletEmbeddableEntity
	 */
	public function getEmbeddedEntity()
	{
		if (!$this->isEmbedded()) {
			throw new OutletConfigException('The property type must be embedded to use this method.');
		}

		return OutletEntityManager::getInstance()->getEntity($this->getRef());
	}

	/**
	 * @return boolean
	 */
	public function isEmbedded()
	{
		return $this->getType() == 'embedded';
	}
}