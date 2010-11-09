<?php
/**
 * File level comment
 * 
 * @package org.outlet-orm
 * @subpackage association
 * @author Alvaro Carrasco
 */

/**
 * Class for many-to-many association
 * 
 * @package org.outlet-orm
 * @subpackage association
 * @author Alvaro Carrasco
 */
class OutletManyToManyAssociation extends OutletAssociation
{
	/**
	 * @var string
	 */
	protected $linkingTable;
	
	/**
	 * @var string
	 */
	protected $tableKeyLocal;
	
	/**
	 * @var string
	 */
	protected $tableKeyForeign;

	/**
	 * @param OutletEntity $entity
	 * @param string $key
	 * @param string $refKey
	 * @param string $linkingTable
	 * @param string $tableKeyLocal
	 * @param string $tableKeyForeign
	 * @param string $name
	 */
	public function __construct(OutletEntity $entity, $key, $refKey, $linkingTable, $tableKeyLocal, $tableKeyForeign, $name = null)
	{
		$this->setEntity($entity);
		$this->setKey($key);
		$this->setRefKey($refKey);
		$this->setLinkingTable($linkingTable);
		$this->setTableKeyLocal($tableKeyLocal);
		$this->setTableKeyForeign($tableKeyForeign);
		$this->setName(!is_null($name) ? $name : $entity->getPlural());
	}

	/**
	 * @return the string
	 */
	public function getLinkingTable()
	{
		return $this->linkingTable;
	}

	/**
	 * @param string $linkingTable
	 */
	public function setLinkingTable($linkingTable)
	{
		$this->linkingTable = $linkingTable;
	}

	/**
	 * @return the string
	 */
	public function getTableKeyLocal()
	{
		return $this->tableKeyLocal;
	}

	/**
	 * @param string $tableKeyLocal
	 */
	public function setTableKeyLocal($tableKeyLocal)
	{
		$this->tableKeyLocal = $tableKeyLocal;
	}

	/**
	 * @return the string
	 */
	public function getTableKeyForeign()
	{
		return $this->tableKeyForeign;
	}

	/**
	 * @param string $tableKeyForeign
	 */
	public function setTableKeyForeign($tableKeyForeign)
	{
		$this->tableKeyForeign = $tableKeyForeign;
	}
}