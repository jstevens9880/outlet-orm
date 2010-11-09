<?php
/**
 * File level comment
 * 
 * @package org.outlet-orm
 * @subpackage core
 * @author Alvaro Carrasco
 */

/**
 * Outlet main class
 * 
 * @package org.outlet-orm
 * @subpackage core
 * @author Alvaro Carrasco
 */
class Outlet
{
	/**
	 * @var Outlet
	 */
	private static $instance;
	
	/**
	 * @var OutletConfig
	 */
	private $config;
	
	/**
	 * @var OutletMapper
	 */
	private $mapper;
	
	/**
	 * 
	 * @var array
	 */
	private $map = array();
	
	/**
	 * Outlet Connection
	 *
	 * @var OutletConnection
	 */
	private $connection;

	/**
	 * Initialize outlet with an array configuration
	 * 
	 * @param array|string $conf Array configuration, XML file or XML string
	 */
	public static function init($conf)
	{
		if (is_array($conf)) {
			self::$instance = new self(OutletConfig::createFromArray($conf));
		} else {
			self::$instance = new self(OutletConfig::createFromXml($conf));
		}
	}

	/**
	 * @return Outlet instance
	 */
	public static function getInstance()
	{
		if (!self::$instance) {
			throw new OutletException('You must first initialize Outlet by calling Outlet::init($conf)');
		}
		
		return self::$instance;
	}

	/**
	 * Just for tests
	 * 
	 * @param Outlet $instance
	 */
	public static function setInstance(Outlet $instance = null)
	{
		self::$instance = $instance;
	}
	
	/**
	 * Constructs a new instance of Outlet
	 * 
	 * @param OutletConfig $config
	 */
	public function __construct(OutletConfig $config)
	{
		$this->setConfig($config);
		$this->mapper = new OutletMapper();
	}
	
	/**
	 * @return OutletConfig
	 */
	public function getConfig()
	{
		return $this->config;
	}
	
	/**
	 * @param OutletConfig $config
	 */
	protected function setConfig(OutletConfig $config)
	{
		$this->config = $config;
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
	 * @param string $class
	 * @return OutletEntity
	 */
	public function getEntityMap($class)
	{
		if ($entity = $this->getConfig()->getEntity($class)) {
			return $entity;
		}
		
		throw new OutletConfigException('Entity [' . $class . '] has not been defined in the configuration.');
	}

	/**
	 * Persist the passed entity to the database by executing an INSERT or an UPDATE
	 *
	 * @param object $obj
	 * @return OutletProxy object representing the Entity
	 */
	public function save(&$obj)
	{
		$con = $this->getConnection();
		
		$con->beginTransaction();
		$return = $this->mapper->save($obj);
		$con->commit();
		
		return $return;
	}

	public function refresh(&$obj)
	{
		return $this->mapper->refresh($obj);
	}

	/**
	 * Perform a DELETE statement for the corresponding entity
	 * 
	 * @param string $clazz Class of the entity (not the proxy) 
	 * @param mixed $id Primary key of the entity
	 * @return bool true if delete succeeded, false otherwise
	 */
	public function delete($clazz, $id)
	{
		if (!is_array($id)) {
			$id = array($id);
		}
		
		$pks = $this->getEntityMap($clazz)->getPrimaryKeys();
		
		$pk_q = array();
		
		foreach ($pks as $pk) {
			$pk_q[] = '{' . $clazz . '.' . $pk . '} = ?';
		}
		
		$q = "DELETE FROM {" . "$clazz} WHERE " . implode(' AND ', $pk_q);
		$q = $this->mapper->processQuery($q);
		
		$stmt = $this->getConnection()->prepare($q);
		
		$res = $stmt->execute($id);
		
		// remove from identity map
		$this->mapper->clear($clazz, $id);
		
		return $res;
	}

	/**
	 * Quotes a value to protect against SQL injection attackes
	 * 
	 * @see OutletConnection::quote($v)
	 * @param mixed $val value to quote
	 * @return mixed quoted value
	 */
	public function quote($val)
	{
		return $this->getConnection()->quote($val);
	}

	/**
	 * Select entities from the database.
	 *
	 * @param string $clazz Name of the class as mapped on the configuration
	 * @param string $query Optional query to execute as a prepared statement
	 * @param string $params Optional parameters to bind to the query
	 * @return array Collection returned by the query
	 */
	public function select($clazz, $query = '', $params = array())
	{
		// select plus criteria
		$q = "SELECT {" . "$clazz}.* FROM {" . $clazz . "} " . $query;
		
		$proxyclass = "{$clazz}_OutletProxy";
		$collection = array();
		
		$stmt = $this->query($q, $params);
		
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$collection[] = $this->getEntityForRow($clazz, $row);
		}
		
		return $collection;
	}

	/**
	 * Generate the proxy classes that will perform the actual work
	 * 
	 * This method creates a string with the class definitions and then
	 * uses eval to create them. For better performance it's recommented
	 * that, instead of calling this method, you use the outletgen.php 
	 * script to generate the proxies and include them directly. This will
	 * allow byte-code caches to cache the proxies code. 
	 */
	public function createProxies()
	{
		eval($this->getProxyGenerator()->generate());
	}

	/**
	 * Returns the proxy generator
	 * 
	 * @return OutletProxyGenerator
	 */
	protected function getProxyGenerator()
	{
		//TODO refactor using OutletConfig...
		
		return new OutletProxyGenerator($this->map);
	}

	/**
	 * Select ONE entity from the database using it's primary key
	 * 
	 * @param string $cls Class to load
	 * @param mixed $pk primary key value
	 * @return object entity of class $cls
	 */
	public function load($cls, $pk)
	{
		return $this->mapper->load($cls, $pk);
	}

	/**
	 * Returns last generated ID
	 * If using PostgreSQL the $sequenceName needs to be specified
	 * 
	 * @param string $sequenceName sequence name to look for the last insert id in, required for PostgreSQL
	 * @return int The last insert id
	 */
	public function getLastInsertId($sequenceName = '')
	{
		return $this->getConnection()->lastInsertId($sequenceName);
	}

	/**
	 * Return the entity for a database row
	 * This method checks the identity map
	 *
	 * @param string $clazz Class to populate
	 * @param array $row database row
	 * @return object populated entity
	 */
	public function getEntityForRow($clazz, array $row)
	{
		$map = $this->getEntityMap($clazz);
		
		// get the pk values in order to check the map		
		$pkValues = array();
		foreach ($map->getPrimaryKeys() as $pk) {
			$pkValues[] = $row[$map->getProperty($pk)->getColumn()];
		}
		
		$data = $this->mapper->get($clazz, $pkValues);
		
		if ($data) {
			return $data['obj'];
		} else {
			// TODO: cast values on populateObject
			$proxyclass = $clazz . '_OutletProxy';
			$obj = new $proxyclass();
			OutletEntityHelper::getInstance()->populateObject($map, $obj, $row);
			
			if ($this->mapper->onHydrate) {
				if (!function_exists($this->mapper->onHydrate)) {
					throw new OutletException('The function ' . $this->mapper->onHydrate . ' does not exists');
				}
				
				call_user_func($this->mapper->onHydrate, $obj);
			}
			
			// add it to the cache
			$this->mapper->set($clazz, $pkValues, array('obj' => $obj, 'original' => $map->extractValues($obj)));
			
			return $obj;
		}
	}

	/**
	 * Execute a full select but only return the first result
	 * 
	 * @param string $clazz entity class
	 * @param string $query query to filter by
	 * @param array $params values to replace parameterized values in $query
	 * @return mixed first result row, null if no results are returned
	 */
	public function selectOne($clazz, $query = '', $params = array())
	{
		$res = $this->select($clazz, $query, $params);
		
		if (count($res)) {
			return $res[0];
		} else {
			return null;
		}
	}

	/**
	 * Clears the mappers cache
	 * @see OutletMapper::clearCache()
	 */
	public function clearCache()
	{
		$this->mapper->clearCache();
	}

	/**
	 * Executes a query
	 * @param string $query query to execute
	 * @param array $params values to replace parameterized placeholders with
	 * @return PDOStatement statement that was executed
	 */
	public function query($query = '', array $params = array())
	{
		// process the query
		$q = $this->mapper->processQuery($query);
		
		$stmt = $this->getConnection()->prepare($q);
		$stmt->execute($params);
		
		return $stmt;
	}

	/**
	 * Parse a query containing outlet entities and return a PDOStatement (not executed)
	 * 
	 * @param string $query
	 * @return PDOStatement
	 */
	public function prepare($query)
	{
		$q = $this->mapper->processQuery($query);
		
		return $this->getConnection()->prepare($q);
	}

	/**
	 * Create an OutletQuery selecting from an entity table
	 * @param string $from entity table to select from
	 * @return OutletQuery unexecuted
	 */
	public function from($from)
	{
		$q = new OutletQuery();
		$q->from($from);
		
		return $q;
	}

	/**
	 * @param $obj
	 * @return array
	 */
	public function toArray($obj)
	{
		return $this->mapper->toArray($obj);
	}

	/**
	 * Sets onHydrate method
	 * 
	 * @param string $callback Callback function
	 */
	public function onHydrate($callback)
	{
		$this->mapper->onHydrate = $callback;
	}
}