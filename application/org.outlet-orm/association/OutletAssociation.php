<?php
/**
 * File level comment
 * 
 * @package org.outlet-orm
 * @subpackage association
 * @author Alvaro Carrasco
 */

/**
 * Parent class for associations
 * 
 * @package org.outlet-orm
 * @subpackage association
 * @author Alvaro Carrasco
 */
abstract class OutletAssociation
{
	/**
	 * @var OutletEntity
	 */
	protected $entity;
	
	/**
	 * @var string
	 */
	protected $name;
	
	/**
	 * @var string
	 */
	protected $key;
	
	/**
	 * @var string
	 */
	protected $refKey;

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
	public function getKey()
	{
		return $this->key;
	}
	
	/**
	 * @param string $key
	 */
	public function setKey($key)
	{
		$this->key = $key;
	}

	/**
	 * @return string
	 */
	public function getRefKey()
	{
		return $this->refKey;
	}
	
	/**
	 * @param string $refKey
	 */
	public function setRefKey($refKey)
	{
		$this->refKey = $refKey;
	}

	/**
	 * @return OutletEntity
	 */
	public function getEntity()
	{
		return $this->entity;
	}
	
	/**
	 * @param OutletEntity $entity
	 */
	public function setEntity(OutletEntity $entity)
	{
		$this->entity = $entity;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		if ($this instanceof OutletManyToOneAssociation) {
			return 'many-to-one';
		} elseif ($this instanceof OutletManyToManyAssociation) {
			return 'many-to-many';
		} elseif ($this instanceof OutletOneToOneAssociation) {
			return 'one-to-one';
		} elseif ($this instanceof OutletOneToManyAssociation) {
			return 'one-to-many';
		}
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		$s = '  > ' . $this->getName() . ' (' . $this->getType() . ', class=' . $this->getEntity()->getName() . ')' . "\n";

		return $s;
	}
}