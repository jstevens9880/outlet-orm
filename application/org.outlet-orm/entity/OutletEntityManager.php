<?php
/**
 * Contains the main class to manipulate the database data
 *
 * @package org.outlet-orm
 * @subpackage entity
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */

/**
 * Class to manipulate the database data
 *
 * @package org.outlet-orm
 * @subpackage entity
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */
class OutletEntityManager
{
	/**
	 * Singleton instance
	 *
	 * @var OutletEntityManager
	 */
	private static $instance;

	/**
	 * @var OutletCache
	 */
	private $cache;

	/**
	 * @var OutletConfig
	 */
	private $config;

	/**
	 * Outlet Connection
	 *
	 * @var OutletConnection
	 */
	private $connection;

	/**
	 * Returns the singleton instance
	 *
	 * @return OutletEntityManager
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructs a new instance of OutletEntityManager
	 *
	 * @param OutletConfig $config
	 */
	public function __construct()
	{
		$this->cache = new OutletCache();
	}

	/**
	 * @param OutletConfig $config
	 */
	public function setConfig(OutletConfig $config)
	{
		$this->config = $config;
	}

	/**
	 * @return OutletConfig
	 */
	public function getConfig()
	{
		return $this->config;
	}

	/**
	 * Retrieves the cache object
	 *
	 * @return OutletCache
	 */
	protected function getCache()
	{
		return $this->cache;
	}

	/**
	 * Retrieves the connection
	 *
	 * @see OutletConfig::createConnection()
	 * @return OutletConnection
	 */
	public function getConnection()
	{
		if (is_null($this->connection)) {
			$this->setConnection($this->getConfig()->createConnection());
		}

		return $this->connection;
	}

	/**
	 * Configures the connection object
	 *
	 * @param OutletConnection $connection
	 */
	public function setConnection(OutletConnection $connection)
	{
		$this->connection = $connection;
	}

	/**
	 * @param string|object $class
	 * @return OutletEntity
	 */
	public function getEntity($class)
	{
		$entityName = $this->getEntityName($class);

		if ($entity = $this->getConfig()->getEntity($entityName)) {
			return $entity;
		}

		throw new OutletConfigException('Entity [' . $entityName . '] has not been defined in the configuration.');
	}

	/**
	 * Return the entity class of an object as defined in the config
	 * For example, it will return 'User' when passed an instance of User or User_OutletProxy
	 *
	 * @param string|object $entity
	 * @return string
	 */
	protected function getEntityName($entity)
	{
		$className = is_string($entity) ? $entity : get_class($entity);

		return $this->getProxyFactory()->getEntityName($className);
	}

	/**
	 * Returns the proxy factory (created for testing)
	 *
	 * @return OutletProxyFactory
	 */
	protected function getProxyFactory()
	{
		return OutletProxyFactory::getInstance();
	}

	/**
	 * Saves the object
	 *
	 * @param object $obj
	 * @throws PDOException
	 */
	public function save(&$obj)
	{
		$con = $this->getConnection();

		try {
			$con->beginTransaction();
			$this->persistObject($obj);
			$con->commit();
		} catch (PDOException $e) {
			$con->rollBack();

			throw $e;
		}
	}

	/**
	 * Persist an entity to the database by performing either an INSERT or an UPDATE
	 *
	 * @param object $obj
	 */
	protected function persistObject(&$obj)
	{
		if ($this->isNew($obj)) {
			$this->insert($obj, $this->getEntity($obj));
		} else {
			$this->update($obj, $this->getEntity($obj));
		}
	}

	/**
	 * Determine if an object has been saved by seeing if it's actually a proxy
	 *
	 * @param object $obj the entity to check
	 * @return bool true if entity is new, false otherwise
	 */
	protected function isNew($obj)
	{
		return !$obj instanceof OutletProxy;
	}

	/**
	 * Saves the object on the cache
	 *
	 * @param OutletEntity $entity
	 * @param object $obj
	 * @param array $arrayValues
	 */
	protected function saveToCache(OutletEntity $entity, $obj, array $arrayValues)
	{
		$this->getCache()->persist(
			$entity->getName(),
			$entity->extractPrimaryKeysValues($obj),
			array('obj' => $obj, 'original' => $arrayValues)
		);
	}

	/**
	 * Saves the one to many relationships for an entity
	 *
	 * @param object $obj entity to save one to many relationship for
	 */
	protected function saveOneToMany($obj, OutletEntity $entity)
	{
		$associations = $entity->getOneToManyAssociations();

		if (count($associations)) {
			$pks = $entity->extractPrimaryKeysValues($obj);

			foreach ($associations as $association) {
				$key = $association->getKey();
				$getter = 'get' . $association->getName();
				$setter = 'set' . $association->getName();

				$foreignMap = $association->getEntity();

				if ($children = $obj->$getter(null)) {
					if (!$children instanceof OutletCollection) {
						$arr = $children->getArrayCopy();

						$children = $obj->$getter();
						$children->exchangeArray($arr);
					}

					if ($children->isRemoveAll()) {
						//TODO Make it work with composite keys
						OutletQuery::delete()->from($foreignMap->getName())
											 ->where('{' . $foreignMap->getName() . '.' . $association->getKey() . '} = ?')
											 ->execute($pks);
					}

					foreach ($children->getArrayCopy() as $i => $child) {
						//TODO make it work with composite keys
						$this->getEntityHelper()->setPropertyValue($foreignMap, $key, $child, current($pks));
						$this->save($child);

						$children->offsetSet($i, $child);
					}

					$obj->$setter($children);
				}
			}
		}
	}

	/**
	 * Saves the many to many relationships for an entity
	 * @param object $obj entity to save many to many relationship for
	 */
	protected function saveManyToMany($obj, OutletEntity $map)
	{
		$associations = $map->getManyToManyAssociations();

		if (count($associations)) {
			$pks = $map->extractPrimaryKeysValues($obj);

			foreach ($associations as $association) {
				$key_column = $association->getTableKeyLocal();
				$ref_column = $association->getTableKeyForeign();

				$table = $association->getLinkingTable();
				$getter = 'get' . $association->getName();
				$setter = 'set' . $association->getName();

				$children = $obj->$getter();

				// if removing all connections
				if ($children->isRemoveAll()) {
					//TODO Make it work with composite keys
					OutletQuery::delete()->fromTable($table)
										 ->where($key_column . ' = ?')
										 ->execute(array_values($pks));
				}

				$new = $children->getLocalIterator();

				foreach ($new as $child) {
					if ($child instanceof OutletProxy) {
						$child_pks = $this->getEntity($child)->extractPrimaryKeysValues($child);
						$id = current($child_pks);
					} else {
						$id = $child;
					}

					OutletQuery::insert()->intoTable($table)
										 ->set($key_column, current($pks), 'int')
										 ->set($ref_column, $id, 'int')
										 ->execute();
				}

				$obj->$setter($children);
			}
		}
	}

	/**
	 * Saves the many to one relationships for an entity
	 * @param object $obj entity to save many to one relationship for
	 */
	protected function saveManyToOne($obj, OutletEntity $map)
	{
		foreach ($map->getManyToOneAssociations() as $association) {
			$getter = 'get' . $association->getName();

			if ($foreignObj = $obj->$getter()) {
				$foreignEntity = $this->getEntity($foreignObj);

				if ($this->isNew($foreignObj)) {
					$this->insert($foreignObj, $foreignEntity);
				}

				$this->getEntityHelper()->setPropertyValue(
					$map,
					$association->getKey(),
					$obj,
					$this->getEntityHelper()->getPropertyValue(
						$foreignEntity,
						$association->getRefKey(),
						$foreignObj
					)
				);
			}
		}
	}

	/**
	 * Saves the one to one relationships for an entity
	 * @param object $obj entity to save one to one relationship for
	 */
	protected function saveOneToOne($obj, OutletEntity $map)
	{
		foreach ($map->getOneToOneAssociations() as $association) {
			$getter = 'get' . $association->getName();

			if ($ent = $obj->$getter()) {
				$this->persistObject($ent);
				$foreignMap = $this->getEntity($ent);

				$this->getEntityHelper()->setPropertyValue(
					$map,
					$association->getKey(),
					$obj,
					$this->getEntityHelper()->getPropertyValue(
						$foreignMap,
						$association->getRefKey(),
						$ent
					)
				);
			}
		}
	}

	/**
	 * Inserts an entity into the database including relationships.
	 *
	 * @param object $obj
	 * @param OutletEntity $entity
	 */
	protected function insert(&$obj, OutletEntity $entity)
	{
		$this->saveOneToOne($obj, $entity);
		$this->saveManyToOne($obj, $entity);

		$entityHelper = $this->getEntityHelper();
		$query = OutletQuery::insert()->into($entity->getName());

		foreach ($entity->getProperties() as $propName => $property) {
			if (!$property->getAutoIncrement()) {
				if (is_null($entityHelper->getPropertyValue($entity, $propName, $obj)) && $property->hasDefaultValue()) {
					$entityHelper->setPropertyValue($entity, $propName, $obj, $property->getDefaultValue());
				}

				if (is_null($entityHelper->getPropertyValue($entity, $propName, $obj)) && $property->hasDefaultExpression()) {
					$query->setExpression('{' . $entity->getName() . '.' . $propName . '}', $property->getDefaultExpression());
				} else {
					$query->set(
						'{' . $entity->getName() . '.' . $propName . '}',
						$entityHelper->getPropertyValue($entity, $propName, $obj),
						$property->getType()
					);
				}
			}
		}

		$query->execute();
		$obj = $this->createProxyObject($entity, $obj);

		$this->saveOneToMany($obj, $entity);
		$this->saveManyToMany($obj, $entity);

		$this->saveToCache($entity, $obj, $entity->extractValuesToSql($obj));
	}

	/**
	 * Fill a proxy with the inserted values
	 *
	 * @param OutletEntity $map
	 * @param object $obj
	 * @return OutletProxy
	 */
	protected function createProxyObject(OutletEntity $entity, $obj)
	{
		$entityHelper = $this->getEntityHelper();
		$proxy = $this->getProxyFactory()->getProxy($entity->getName());

		foreach ($entity->getProperties() as $propName => $property) {
			if ($property->getAutoIncrement()) {
				$id = $this->getConnection()->lastInsertId($entity->getSequenceName());
				$id = $entityHelper->fromSqlValue($property->getType(), $id);

				$entityHelper->setPropertyValue($entity, $propName, $proxy, $id);
			} else {
				$entityHelper->setPropertyValue($entity, $propName, $proxy, $entityHelper->getPropertyValue($entity, $propName, $obj));
			}
		}

		foreach ($entity->getAssociations() as $association) {
			if ($association instanceof OutletOneToManyAssociation || $association instanceof OutletManyToManyAssociation) {
				$getter = 'get' . $association->getName();
				$setter = 'set' . $association->getName();

				if ($ref = $obj->$getter()) {
					$proxy->$setter($ref);
				}
			}
		}

		return $proxy;
	}

	/**
	 * Check to see if an entity values (row) have been modified
	 *
	 * @param object $obj entity to inspect
	 * @return array fields that have changed
	 */
	protected function getModifiedFields($obj)
	{
		$entity = $this->getEntity($obj);

		$data = $this->getCache()->retrieve($entity->getName(), $entity->extractPrimaryKeysValues($obj));

		$new = $entity->extractValuesToSql($data['obj']);
		$diff = array_diff_assoc($data['original'], $new);

		return array_keys($diff);
	}

	/**
	 * Updates an entity in the database including relationships.
	 *
	 * @see OutletMapper::save(&$obj)
	 * @param object $obj entity to update
	 */
	protected function update(&$obj, OutletEntity $entity)
	{
		$this->saveOneToOne($obj, $entity);
		$this->saveManyToOne($obj, $entity);

		if ($mod = $this->getModifiedFields($obj, $entity)) {
			$entityHelper = $this->getEntityHelper();
			$query = OutletQuery::update($entity->getName());

			foreach ($mod as $key) {
				$property = $entity->getPropertyByColumn($key);

				if (is_null($property)) {
					$property = $entity->getProperty($key);
				}

				$key = $property->getName();

				if (!$property->getPrimaryKey()) {
					$value = $entityHelper->getPropertyValue($entity, $key, $obj);

					$query->set(
						'{' . $entity->getName() . '.' . $key . '}',
						$value,
						$property->getType()
					);
				}
			}

			$whereValues = array();

			foreach ($entity->getPrimaryKeys() as $pk) {
				$prop = $entity->getProperty($pk);

				$whereValues[] = $query->toSqlValue(
					$prop->getType(),
					$entityHelper->getPropertyValue($entity, $prop->getName(), $obj)
				);

				$query->where('{' . $entity->getName() . '.' . $prop->getName() . '} = ?');
			}

			$query->execute($whereValues);
		}

		$this->saveOneToMany($obj, $entity);
		$this->saveManyToMany($obj, $entity);

		$this->saveToCache($entity, $obj, $entity->extractValuesToSql($obj));
	}

	/**
	 * Reload the object data directly from the database
	 *
	 * @param object $obj
	 * @return OutletProxy
	 */
	public function refresh(&$obj)
	{
		$entity = $this->getEntity($obj);
		$pks = $entity->extractPrimaryKeysValues($obj);

		return $this->load($entity->getName(), $pks, false);
	}

	/**
	 * Loads an entity by its primary key
	 *
	 * @param string $entityName Entity class
	 * @param mixed $pk Primary key
	 * @return object the entity, if the entity can't be found returns null
	 */
	public function load($entityName, $pk, $useCache = true)
	{
		if (!$pk) {
			throw new OutletException("Must pass a valid primary key value, passed: " . var_export($pk, true));
		}

		$pks = !is_array($pk) ? array($pk) : array_values($pk);
		$data = $this->getCache()->retrieve($entityName, $pks);

		if ($useCache && $data) {
			return $data['obj'];
		} else {
			return $this->retrieveFromDatabase($this->getEntity($entityName), $pks);
		}
	}

	/**
	 * Fill an object with database data
	 *
	 * @param OutletEntity $entity
	 * @param array $primaryKeys
	 * @return OutletProxy
	 */
	protected function retrieveFromDatabase(OutletEntity $entity, array $primaryKeys)
	{
		$entityName = $entity->getName();
		$query = OutletQuery::select()->from($entityName);

		foreach ($entity->getPrimaryKeys() as $property) {
			$query->where('{' . $entityName . '.' . $property . '} = ?');
		}

		if ($row = $query->execute($primaryKeys)->fetch(PDO::FETCH_ASSOC)) {
			$obj = $this->getProxyFactory()->getProxy($entityName);

			$entity->populate($obj, $row);
			$this->saveToCache($entity, $obj, $row);

			return $obj;
		}

		return null;
	}

	/**
	 * Deletes the object from the database
	 *
	 * @param OutletProxy $obj
	 */
	public function deleteObj(OutletProxy $obj)
	{
		$entity = $this->getEntity($obj);

		$this->performDelete($entity, array_values($entity->extractPrimaryKeysValues($obj)));
	}

	/**
	 * Perform a DELETE statement for the corresponding entity
	 *
	 * @param string $entityName
	 * @param mixed $id
	 */
	public function delete($entityName, $id)
	{
		if (!is_array($id)) {
			$id = array($id);
		}

		$this->performDelete($this->getEntity($entityName), $id);
	}

	/**
	 * Performs the DELETE query
	 *
	 * @param OutletEntity $entity
	 * @param array $primaryKeys
	 */
	protected function performDelete(OutletEntity $entity, array $primaryKeys)
	{
		$query = OutletQuery::delete()->from($entity->getName());

		foreach ($entity->getPrimaryKeys() as $pk) {
			$query->where('{' . $entity->getName() . '.' . $pk . '} = ?');
		}

		$query->execute($primaryKeys);
		$this->getCache()->remove($entity->getName(), $primaryKeys);
	}

	/**
	 * Return the entity for a database row
	 *
	 * @param string $clazz Class to populate
	 * @param array $row database row
	 * @return object populated entity
	 */
	public function getEntityForRow($entityName, array $row)
	{
		$entity = $this->getEntity($entityName);
		$pkValues = array();

		foreach ($entity->getPrimaryKeys() as $pk) {
			$pkValues[] = $row[$entity->getProperty($pk)->getColumn()];
		}

		$data = $this->getCache()->retrieve($entityName, $pkValues);

		if ($data) {
			return $data['obj'];
		} else {
			$obj = $this->getProxyFactory()->getProxy($entityName);
			$entity->populate($obj, $row);

			$this->saveToCache($entity, $obj, $entity->extractValuesToSql($obj));

			return $obj;
		}
	}

	/**
	 * Clears the cache
	 */
	public function clearCache()
	{
		$this->getCache()->clear();
	}

	/**
	 * Returns the helper to entity manipulation
	 *
	 * @return OutletEntityHelper
	 */
	protected function getEntityHelper()
	{
		return OutletEntityHelper::getInstance();
	}
}