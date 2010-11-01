<?php
/**
 * File level comment
 * 
 * @package org.outlet-orm
 * @subpackage database
 * @author Alvaro Carrasco
 */

/**
 * Outlet wrapper for a database connection.
 * 
 * @package org.outlet-orm
 * @subpackage database
 * @author Alvaro Carrasco
 */
class OutletConnection
{
	/**
	 * Stores the database type
	 * 
	 * @var string
	 */
	private $dialect;
	
	/**
	 * Stores a PDO database connection
	 * 
	 * @var PDO
	 */
	private $pdo;
	
	/**
	 * Stores the nested transaction level
	 * 
	 * @var int
	 */
	protected $nestedTransactionLevel;
	
	/**
	 * Assume that the driver supports transactions until proven otherwise
	 * 
	 * @see OutletConnection::beginTransaction()
	 * @var boolean
	 */
	protected $driverSupportsTransactions;

	/**
	 * Constructs a new instance of OutletConnection
	 * 
	 * @param PDO $pdo
	 * @param string $dialect It can be 'sqlite', 'mysql', 'mssql', or 'pgsql'
	 */
	public function __construct(PDO $pdo, $dialect)
	{
		$this->nestedTransactionLevel = 0;
		$this->driverSupportsTransactions = true;
		$this->setPDO($pdo);
		$this->setDialect($dialect);
	}

	/**
	 * The dialect, can be 'sqlite', 'mysql', 'mssql', or 'pgsql'
	 * 
	 * @return string
	 */
	public function getDialect()
	{
		return $this->dialect;
	}
	
	/**
	 * Configures the dialect, can be 'sqlite', 'mysql', 'mssql', or 'pgsql'
	 * 
	 * @param string $dialect
	 */
	public function setDialect($dialect)
	{
		if (!in_array($dialect, array('sqlite', 'mysql', 'mssql', 'pgsql'))) {
			throw new InvalidArgumentException('Invalid dialect! It can just be: "sqlite", "mysql", "mssql", or "pgsql"');
		}
		
		$this->dialect = $dialect;
	}

	/**
	 * The PHP Database Object
	 * 
	 * @link http://us2.php.net/pdo
	 * @return PDO
	 */
	public function getPDO()
	{
		return $this->pdo;
	}

	/**
	 * Just for testing
	 *  
	 * @link http://us2.php.net/pdo
	 * @param PDO $pdo
	 */
	public function setPDO(PDO $pdo)
	{
		$this->pdo = $pdo;
	}
	
	/**
	 * Returns if the driver supports transaction
	 * 
	 * @return boolean
	 */
	public function isTransactionSupported()
	{
		return $this->driverSupportsTransactions;
	}
	
	/**
	 * Returns the nested transaction level
	 * 
	 * @return int
	 */
	public function getTransactionLevel()
	{
		return $this->nestedTransactionLevel;
	}

	/**
	 * Begins a database transaction
	 * 
	 * @return boolean true if successful, false otherwise 
	 */
	public function beginTransaction()
	{
		if (!$this->nestedTransactionLevel++) {
			try {
				return $this->pdo->beginTransaction();
			} catch (PDOException $e) {
				if ($this->isTransactionSupported()) {
					$this->driverSupportsTransactions = false;
				}
				
				return $this->exec('BEGIN TRANSACTION');
			}
		}
		
		return true;
	}

	/**
	 * Commit a database transaction
	 * 
	 * @see OutletConnection::beginTransaction
	 * @return bool true if successful, false otherwise
	 */
	public function commit()
	{
		if (!--$this->nestedTransactionLevel) {
			if ($this->isTransactionSupported()) {
				return $this->pdo->commit();
			} else {
				return $this->exec('COMMIT TRANSACTION');
			}
		}
		
		return true;
	}

	/**
	 * Rollback the current database transaction
	 * 
	 * @see OutletConnection::beginTransaction()
	 * @return bool true if successful, false otherwise
	 */
	public function rollBack()
	{
		if (!--$this->nestedTransactionLevel) {
			if ($this->isTransactionSupported()) {
				return $this->pdo->rollBack();
			} else {
				$this->exec('ROLLBACK TRANSACTION');
			}
		}
		
		return true;
	}

	/**
	 * Quotes a value to escape special characters, protects against sql injection attacks
	 * 
	 * @param mixed $value value to escape
	 * @return string the escaped value 
	 */
	public function quote($value)
	{
		$quoted = $this->pdo->quote($value);
		
		if (!$this->hasQuoteSupport($value, $quoted)) {
			$quoted = is_int($value) ? $value : "'" . str_replace("'", "''", $value) . "'";
		}
		
		return $quoted;
	}
	
	/**
	 * Checks if the driver has quote support (odbc doesn't support quote and returns false)
	 * 
	 * @param mixed $value
	 * @param midex $quotedValue
	 * @return boolean
	 */
	protected function hasQuoteSupport($value, $quotedValue)
	{
		return !($value !== false && $quotedValue === false);
	}

	/**
	 * Automagical __call method, overloaded to allow transparent callthrough to the pdo object
	 * 
	 * @param object $method method to call 
	 * @param object $args arguments to pass to method
	 * @return mixed result from the pdo function call
	 */
	public function __call($method, $args)
	{
		return call_user_func_array(array($this->pdo, $method), $args);
	}

	/**
	 * Returns last generated ID
	 * If using PostgreSQL the $sequenceName needs to be specified
	 * 
	 * @param string $sequenceName [optional] The sequence name, defaults to ''
	 * @return int the last insert id
	 */
	public function lastInsertId($sequenceName = '')
	{
		if ($this->getDialect() == 'mssql') {
			return $this->query('SELECT SCOPE_IDENTITY()')->fetchColumn(0);
		} else {
			return $this->pdo->lastInsertId($sequenceName);
		}
	}
}