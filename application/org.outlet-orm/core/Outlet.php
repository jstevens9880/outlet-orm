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
	 * Initialize outlet with an array configuration
	 *
	 * @param array|string $config Array configuration, XML file or XML string
	 */
	public static function init($config)
	{
		if (is_array($config)) {
			self::$instance = new self(OutletConfig::createFromArray($config));
		} else {
			self::$instance = new self(OutletConfig::createFromXml($config));
		}
	}

	/**
	 * @return Outlet instance
	 */
	public static function getInstance()
	{
		if (!self::$instance) {
			throw new OutletException('You must first initialize Outlet by calling Outlet::init()');
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
		$this->getEntityManager()->setConfig($config);
	}

	/**
	 * Returns the entity manager instance
	 *
	 * @return OutletEntityManager
	 */
	protected function getEntityManager()
	{
		return OutletEntityManager::getInstance();
	}

	/**
	 * Clears the mappers cache
	 *
	 * @see OutletMapper::clearCache()
	 */
	public function clearCache()
	{
		$this->getEntityManager()->clearCache();
	}

	/**
     * function createSqlQuery
     *
	 * Prepares and executes a query. Returning either the resulting recordset from a SELECT or
     * the boolean return value from the called PDOStatement::execute.
     *
     * Passing values to bind to the statement is optional. If values are passed for binding they
     * must be in an array. The array format is detailed below:
     * 1) Used in replacing question marks in the statement:
     *    array(1 => 'value', 2 => 'value')
     * 2) Used in replacing question marks in the statement also identifying the data type:
     *    array(1 => array('value', PDO::PARAM_INT), 2 => array('value',PDO::PARAM_STR))
     * 3) Used in replacing question named placeholders in the statement:
     *    array(':placeholder1' => 'value', ':placeholder2' => 'value')
     * 4) Used in replacing question marks in the statement also identifying the data type:
     *    array(':placeholder1' => array('value', PDO::PARAM_INT), ':placeholder2' => array('value',PDO::PARAM_STR))
     *
     * While it is possible to simply pass a SQL string into this function for preparation and execution,
     * it is best if you are passing in user generated variables to use the $bindValues parameter in order
     * to help prevent sql injections.
	 *
	 * @param string $sql
	 * @param array  $bindValues
	 * @return mixed 
	 */
	public function createSqlQuery($sql, array $bindValues = array())
	{
		$stm = $this->getEntityManager()->getConnection()->prepare(
            $sql
        );
        foreach ($bindValues as $k => $v) {
            if (is_array($v)) {
                $stm->bindValue($k, $v[0], $v[1]);
            } else {
                $stm->bindValue($k, $v);
            }
        }
        $execResult = $stm->execute();
        return (strtolower(substr(trim($sql), 0, 3)) == 'sel') ? $stm->fetchAll(PDO::FETCH_ASSOC) : $execResult;
	}

	/**
	 * Starts a transaction
	 *
	 * @see OutletConnection::beginTransaction()
	 */
	public function beginTransaction()
	{
		$this->getEntityManager()->getConnection()->beginTransaction();
	}

	/**
	 * Commits a transaction
	 *
	 * @see OutletConnection::commit()
	 */
	public function commit()
	{
		$this->getEntityManager()->getConnection()->commit();
	}

	/**
	 * Rolls back a transaction
	 *
	 * @see OutletConnection::rollback()
	 */
	public function rollback()
	{
		$this->getEntityManager()->getConnection()->rollback();
	}

	/**
	 * Persist the passed entity to the database by executing an INSERT or an UPDATE
	 *
	 * @param object $obj
	 */
	public function save(&$obj)
	{
		$this->getEntityManager()->save($obj);
	}

	/**
	 * Reload the object from the database
	 *
	 * @param OutletProxy $obj
	 */
	public function refresh(OutletProxy &$obj)
	{
		$this->getEntityManager()->refresh($obj);
	}

	/**
	 * Performs a DELETE statement for the corresponding entity
	 *
	 * @param string $entityName Class of the entity (not the proxy)
	 * @param mixed $id Primary key of the entity
	 */
	public function delete($entityName, $id)
	{
		$this->getEntityManager()->delete($entityName, $id);
	}

	/**
	 * Deletes the object from the database
	 *
	 * @param OutletProxy $obj
	 */
	public function deleteObj(OutletProxy $obj)
	{
		$this->getEntityManager()->deleteObj($obj);
	}

	/**
	 * Select ONE entity from the database using it's primary key
	 *
	 * @param string $entityName Class to load
	 * @param mixed $pk primary key value
	 * @return object entity of class $cls
	 */
	public function load($entityName, $pk)
	{
		return $this->getEntityManager()->load($entityName, $pk);
	}

	/**
	 * Create an OutletSelectQuery selecting from an entity table
	 *
	 * @param string $entityName entity table to select from
	 * @return OutletSelectQuery unexecuted
	 */
	public function from($entityName)
	{
		return OutletQuery::select()->from($entityName);
	}

	/**
	 * Select entities from the database.
	 *
	 * @param string $entityName Name of the class as mapped on the configuration
	 * @param string $criteria Optional query to execute as a prepared statement
	 * @param string $params Optional parameters to bind to the query
	 * @return array Collection returned by the query
	 */
	public function select($entityName, $criteria = '', array $params = array())
	{
		$query = $this->getQueryWithCriteria($entityName, $criteria, $params);

		return $query->find();
	}

	/**
	 * Execute a full select but only return the first result
	 *
	 * @param string $entityName entity class
	 * @param string $criteria query to filter by
	 * @param array $params values to replace parameterized values in $query
	 * @return mixed first result row, null if no results are returned
	 */
	public function selectOne($entityName, $criteria = '', array $params = array())
	{
		$query = $this->getQueryWithCriteria($entityName, $criteria, $params);

		return $query->findOne();
	}

	/**
	 * Creates a select query
	 *
	 * @param string $entityName
	 * @param string $criteria
	 * @param array $params
	 * @return OutletSelectQuery
	 */
	protected function getQueryWithCriteria($entityName, $criteria = '', array $params = array())
	{
		$query = $this->from($entityName);

		if (strlen($criteria) > 0) {
			$query->where(trim(str_ireplace('where', '', $criteria)), $params);
		}

		return $query;
	}
}