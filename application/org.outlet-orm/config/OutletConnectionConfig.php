<?php
/**
 * Contains the class to configure Outlet's connection
 * 
 * @package org.outlet-orm
 * @subpackage config
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */

/**
 * Class that handles the connection configuration
 * 
 * @package org.outlet-orm
 * @subpackage config
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */
class OutletConnectionConfig
{
	const MYSQL = 'mysql';
	const MSSQL = 'mssql';
	const PGSQL = 'pgsql';
	const SQLITE = 'sqlite';
	
	/**
	 * A PDO connection string
	 * 
	 * @var string
	 */
	private $dsn;
	
	/**
	 * The dialect to use. It can be 'mysql', 'mssql', 'pgsql' or 'sqlite'
	 * 
	 * @var string
	 */
	private $dialect;
	
	/**
	 * A username if the database driver requires it
	 * 
	 * @var string
	 */
	private $userName;
	
	/**
	 * A password if the database driver requires it
	 * 
	 * @var string
	 */
	private $password;
	
	/**
	 * PDO Object
	 * 
	 * @var PDO
	 */
	private $pdo;
	
	/**
	 * Constructor
	 * 
	 * @param string $dialect
	 * @param string $dsn
	 * @param string $userName
	 * @param string $password
	 * @param PDO $pdo
	 * @throws OutletConfigException
	 */
	public function __construct($dialect, $dsn = null, $userName = null, $password = null, PDO $pdo = null)
	{
		if (is_null($dsn) && is_null($pdo)) {
			throw new OutletConfigException('You must set either $pdo or $dsn in configuration');
		}
		
		if (!is_null($pdo)) {
			$this->setDialect($dialect);
			$this->setPdo($pdo);
		} else {
			$this->setDsn($dsn);
			$this->setDialect($dialect);
			$this->setUserName($userName);
			$this->setPassword($password);
		
			if ($dialect != self::SQLITE && is_null($userName)) {
				throw new OutletConfigException('You must provide at least the connection\'s username');
			}
		}
	}
	
	/**
	 * @return string
	 */
	public function getDsn()
	{
		return $this->dsn;
	}

	/**
	 * @param string $dsn
	 */
	public function setDsn($dsn)
	{
		$this->dsn = $dsn;
	}

	/**
	 * @return string
	 */
	public function getDialect()
	{
		return $this->dialect;
	}

	/**
	 * @param string $dialect
	 */
	public function setDialect($dialect)
	{
		if (!in_array($dialect, array(self::SQLITE, self::MYSQL, self::MSSQL, self::PGSQL))) {
			throw new InvalidArgumentException('Invalid dialect! It can just be: "sqlite", "mysql", "mssql", or "pgsql"');
		}
		
		$this->dialect = $dialect;
	}

	/**
	 * @return string
	 */
	public function getUserName()
	{
		return $this->userName;
	}

	/**
	 * @param string $userName
	 */
	public function setUserName($userName)
	{
		$this->userName = $userName;
	}

	/**
	 * @return string
	 */
	public function getPassword()
	{
		return $this->password;
	}

	/**
	 * @param string $password
	 */
	public function setPassword($password)
	{
		$this->password = $password;
	}

	/**
	 * @return PDO
	 */
	public function getPdo()
	{
		return $this->pdo;
	}

	/**
	 * @param PDO $pdo
	 */
	public function setPdo(PDO $pdo)
	{
		$this->pdo = $pdo;
	}
	
	/**
	 * Creates a new PDO connection with the defined configuration
	 */
	protected function createPdoConnection()
	{
		$this->setPdo(new PDO($this->getDsn(), $this->getUserName(), $this->getPassword()));
	}
	
	/**
	 * Creates a new OutletConnection
	 * 
	 * @return OutletConnection
	 */
	public function createOutletConnection()
	{
		if (is_null($this->getPdo())) {
			$this->createPdoConnection();
		}
			
		return new OutletConnection($this->getPdo(), $this->getDialect());
	}
}