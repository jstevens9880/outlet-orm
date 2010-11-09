<?php
/**
 * File level comment
 * 
 * @package org.outlet-orm
 * @subpackage association
 * @author Alvaro Carrasco
 */

/**
 * Class for many-to-one association
 * 
 * @package org.outlet-orm
 * @subpackage association
 * @author Alvaro Carrasco
 */
class OutletManyToOneAssociation extends OutletAssociation
{
	/**
	 * @var boolean
	 */
	protected $optional;

	/**
	 * @param OutletEntity $entity
	 * @param string $key
	 * @param string $refKey
	 * @param string $name
	 * @param string $optional
	 */
	public function __construct(OutletEntity $entity, $key, $refKey, $name = null, $optional = false)
	{
		$this->setEntity($entity);
		$this->setKey($key);
		$this->setRefKey($refKey);
		$this->setName(!is_null($name) ? $name : $entity->getName());
		$this->setOptional($optional);
	}

	/**
	 * @return boolean
	 */
	public function isOptional()
	{
		return $this->optional;
	}
	
	/**
	 * @param boolean $optional
	 */
	public function setOptional($optional)
	{
		$this->optional = $optional;
	}
}