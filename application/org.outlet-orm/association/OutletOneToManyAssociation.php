<?php
/**
 * File level comment
 * 
 * @package org.outlet-orm
 * @subpackage association
 * @author Alvaro Carrasco
 */

/**
 * Class for one-to-many association
 * 
 * @package org.outlet-orm
 * @subpackage association
 * @author Alvaro Carrasco
 */
class OutletOneToManyAssociation extends OutletAssociation
{
	/**
	 * @param OutletEntity $entity
	 * @param string $name
	 * @param string $key
	 * @param string $refKey
	 */
	public function __construct(OutletEntity $entity, $key, $refKey, $name = null)
	{
		$this->setEntity($entity);
		$this->setKey($key);
		$this->setRefKey($refKey);
		$this->setName(!is_null($name) ? $name : $entity->getName());
	}
}