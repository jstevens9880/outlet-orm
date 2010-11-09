<?php
require_once 'application/org.outlet-orm/core/OutletException.php';
require_once 'application/org.outlet-orm/config/OutletConfigException.php';
require_once 'application/org.outlet-orm/config/OutletConnectionConfig.php';
require_once 'application/org.outlet-orm/database/OutletConnection.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * OutletConnectionConfig test case.
 */
class OutletConnectionConfigTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests OutletConnectionConfig->__construct()
	 * 
	 * @expectedException OutletConfigException
	 */
	public function test__constructNoPdoAndDns()
	{
		$config = new OutletConnectionConfig(OutletConnectionConfig::MYSQL);
	}
	
	/**
	 * Tests OutletConnectionConfig->__construct()
	 */
	public function test__constructWithPdo()
	{
		$pdo = $this->getMock('PDO', array(), array('sqlite::memory:'));
		$config = new OutletConnectionConfig(OutletConnectionConfig::MYSQL, null, null, null, $pdo);
		
		$this->assertEquals($pdo, $config->getPdo());
		$this->assertEquals(OutletConnectionConfig::MYSQL, $config->getDialect());
	}
	
	/**
	 * Tests OutletConnectionConfig->__construct()
	 */
	public function test__constructWithDsn()
	{
		$config = new OutletConnectionConfig(OutletConnectionConfig::MYSQL, 'mysql:host=localhost;dbname=db', 'admin', 'admin1');
		
		$this->assertEquals(OutletConnectionConfig::MYSQL, $config->getDialect());
		$this->assertEquals('mysql:host=localhost;dbname=db', $config->getDsn());
		$this->assertEquals('admin', $config->getUserName());
		$this->assertEquals('admin1', $config->getPassword());
	}
	
	/**
	 * Tests OutletConnectionConfig->__construct()
	 * 
	 * @expectedException OutletConfigException
	 */
	public function test__constructWithDsnMysqlWithoutUsername()
	{
		$config = new OutletConnectionConfig(OutletConnectionConfig::MYSQL, 'mysql:host=localhost;dbname=db', null, null);
	}
	
	/**
	 * Tests OutletConnectionConfig->__construct()
	 */
	public function test__constructWithDsnSqliteWithoutUsername()
	{
		$config = new OutletConnectionConfig(OutletConnectionConfig::SQLITE, 'sqlite::memory:', null, null);
		
		$this->assertEquals(OutletConnectionConfig::SQLITE, $config->getDialect());
		$this->assertEquals('sqlite::memory:', $config->getDsn());
	}

	/**
	 * Tests OutletConnectionConfig->setDialect()
	 */
	public function testSetDialect()
	{
		$config = $this->getMock('OutletConnectionConfig', array('getUserName'), array(), '', false);
		
		$config->setDialect(OutletConnectionConfig::MYSQL);
		
		$this->assertEquals(OutletConnectionConfig::MYSQL, $config->getDialect());
	}

	/**
	 * Tests OutletConnectionConfig->setDialect()
	 * 
	 * @expectedException InvalidArgumentException
	 */
	public function testSetDialectInvalidDialect()
	{
		$config = $this->getMock('OutletConnectionConfig', array('getUserName'), array(), '', false);
		
		$config->setDialect('blablabla');
	}

	/**
	 * Tests OutletConnectionConfig->createOutletConnection()
	 */
	public function testCreateOutletConnection()
	{
		$pdo = $this->getMock('PDO', array(), array('sqlite::memory:'));
		$config = $this->getMock('OutletConnectionConfig', array('getPdo', 'createPdoConnection', 'getDialect'), array(), '', false);
		
		$config->expects($this->exactly(2))
			   ->method('getPdo')
			   ->will($this->onConsecutiveCalls(null, $pdo));
		
		$config->expects($this->once())
			   ->method('createPdoConnection');
		
		$config->expects($this->once())
			   ->method('getDialect')
			   ->will($this->returnValue(OutletConnectionConfig::SQLITE));
			   
		$connection = $config->createOutletConnection();
		
		$this->assertEquals($pdo, $connection->getPdo());
		$this->assertEquals(OutletConnectionConfig::SQLITE, $connection->getDialect());
	}
}