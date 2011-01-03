<?php
/**
 * Provides the object cache
 *
 * @package org.outlet-orm
 * @subpackage core
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */

/**
 * Outlet object's cache
 *
 * @package org.outlet-orm
 * @subpackage core
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */
class OutletCache
{
	/**
	 * @var array
	 */
	private $cache;

	/**
	 * Class constructor
	 */
	public function __construct()
	{
		$this->clear();
	}

	/**
	 * Clears the Outlet Cache
	 */
	public function clear()
	{
		$this->cache = array();
	}

	/**
	 * Returns the cache (just for tests)
	 *
	 * @return array
	 */
	public function getCache()
	{
		return $this->cache;
	}

	/**
	 * Returns if theres a cache container for the entity
	 *
	 * @param string $entityName
	 * @return boolean
	 */
	protected function entityCacheExists($entityName)
	{
		return isset($this->cache[$entityName]);
	}

	/**
	 * Creates a cache container for the entity
	 *
	 * @param string $entityName
	 */
	protected function createEntityCache($entityName)
	{
		if (!$this->entityCacheExists($entityName)) {
			$this->cache[$entityName] = array();
		}
	}

	/**
	 * Returns if the entity's cache contains the object
	 *
	 * @param string $entityName
	 * @param string $hashedKeys
	 * @return boolean
	 */
	protected function entityCacheContains($entityName, $hashedKeys)
	{
		return $this->entityCacheExists($entityName) && isset($this->cache[$entityName][$hashedKeys]);
	}

	/**
	 * Returns a hashed value from primary keys
	 *
	 * @param array|int $pks
	 * @return string
	 */
	protected function hash($primaryKeys)
	{
		if (is_array($primaryKeys) && count($primaryKeys) == 1) {
			$primaryKeys = array_shift($primaryKeys);
		}

		return is_array($primaryKeys) ? implode(';', $primaryKeys) : $primaryKeys;
	}

	/**
	 * Save object to the identity map
	 *
	 * @param string $class Class to save as
	 * @param array $pks Primary key values
	 * @param array $data Data to save
	 */
	public function persist($entityName, array $primarykeys, array $data)
	{
		$this->createEntityCache($entityName);

		$this->cache[$entityName][$this->hash($primarykeys)] = $data;
	}

	/**
	 * Gets a class by primary key from the cache
	 *
	 * @param string $entityName Class to look up
	 * @param mixed $primaryKeys Primary key
	 * @return array array('obj' => Entity, 'original' => Original row used to populate entity)
	 */
	public function retrieve($entityName, array $primaryKeys)
	{
		$hashedKeys = $this->hash($primaryKeys);

		return $this->entityCacheContains($entityName, $hashedKeys) ? $this->cache[$entityName][$hashedKeys] : null;
	}

	/**
	 * Remove an entity from the cache
	 *
	 * @param $entityName Class entity is stored as
	 * @param $primaryKeys primary key
	 */
	public function remove($entityName, $primaryKeys)
	{
		$hashedKeys = $this->hash($primaryKeys);

		if ($this->entityCacheContains($entityName, $hashedKeys)) {
			$this->cache[$entityName][$hashedKeys] = null;
			unset($this->cache[$entityName][$hashedKeys]);
		}
	}

	/**
	 * Remove an entity from the cache
	 *
	 * @param $entityName Class entity is stored as
	 */
	public function clearEntityCache($entityName)
	{
		if ($this->entityCacheExists($entityName)) {
			$this->cache[$entityName] = null;
			unset($this->cache[$entityName]);
		}
	}
}