<?php
require_once 'application/org.outlet-orm/database/OutletConnection.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * OutletConnection test case.
 */
class OutletConnectionTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests OutletConnection->__construct()
	 */
	public function test__construct()
	{
		$pdo = $this->getMock('PDO', array(), array('sqlite::memory:'));
		$connection = new OutletConnection($pdo, 'mysql');
		
		$this->assertEquals($pdo, $connection->getPDO());
		$this->assertEquals('mysql', $connection->getDialect());
		$this->assertTrue($connection->isTransactionSupported());
	}

	/**
	 * Tests OutletConnection->getDialect()
	 */
	public function testSetDialect()
	{
		$connection = $this->getMock('OutletConnection', array('beginTransaction'), array(), '', false);
		$connection->setDialect('mysql');
		
		$this->assertEquals('mysql', $connection->getDialect());
	}

	/**
	 * Tests OutletConnection->getDialect()
	 * 
	 * @expectedException InvalidArgumentException
	 */
	public function testSetDialectInvalidDialect()
	{
		$connection = $this->getMock('OutletConnection', array('beginTransaction'), array(), '', false);
		$connection->setDialect('mysqlasdad');
	}

	/**
	 * Tests OutletConnection->setPDO()
	 */
	public function testSetPDO()
	{
		$pdo = $this->getMock('PDO', array(), array('sqlite::memory:'));
		$connection = $this->getMock('OutletConnection', array('beginTransaction'), array(), '', false);
		
		$connection->setPDO($pdo);
		$this->assertEquals($pdo, $connection->getPDO());
	}

	/**
	 * Tests OutletConnection->beginTransaction()
	 */
	public function testBeginTransaction()
	{
		$pdo = $this->getMock('PDO', array('beginTransaction', 'exec'), array('sqlite::memory:'));
		$connection = new OutletConnection($pdo, 'mysql');
		
		$pdo->expects($this->once())
			->method('beginTransaction')
			->will($this->returnValue(true));
		
		$pdo->expects($this->never())
			->method('exec');
			
		$this->assertTrue($connection->beginTransaction());
	}

	/**
	 * Tests OutletConnection->beginTransaction()
	 */
	public function testBeginTransactionConsecutiveCalls()
	{
		$pdo = $this->getMock('PDO', array('beginTransaction', 'exec'), array('sqlite::memory:'));
		$connection = new OutletConnection($pdo, 'mysql');
		
		$pdo->expects($this->once())
			->method('beginTransaction')
			->will($this->returnValue(true));
		
		$pdo->expects($this->never())
			->method('exec');
			
		$this->assertTrue($connection->beginTransaction());
		$this->assertEquals(1, $connection->getTransactionLevel());
		
		$this->assertTrue($connection->beginTransaction());
		$this->assertEquals(2, $connection->getTransactionLevel());
	}

	/**
	 * Tests OutletConnection->beginTransaction()
	 */
	public function testBeginTransactionNoTransactionSupport()
	{
		$pdo = $this->getMock('PDO', array('beginTransaction', 'exec'), array('sqlite::memory:'));
		$connection = new OutletConnection($pdo, 'mysql');
		
		$pdo->expects($this->once())
			->method('beginTransaction')
			->will($this->throwException(new PDOException('Test')));
		
		$pdo->expects($this->once())
			->method('exec')
			->with('BEGIN TRANSACTION')
			->will($this->returnValue(true));
			
		$this->assertTrue($connection->beginTransaction());
		$this->assertEquals(1, $connection->getTransactionLevel());
		$this->assertFalse($connection->isTransactionSupported());
	}

	/**
	 * Tests OutletConnection->commit()
	 */
	public function testCommit()
	{
		$pdo = $this->getMock('PDO', array('beginTransaction', 'exec', 'commit'), array('sqlite::memory:'));
		$connection = new OutletConnection($pdo, 'mysql');
		
		$pdo->expects($this->once())
			->method('beginTransaction')
			->will($this->returnValue(true));
		
		$pdo->expects($this->once())
			->method('commit')
			->will($this->returnValue(true));
		
		$pdo->expects($this->never())
			->method('exec');
			
		$this->assertTrue($connection->beginTransaction());
		$this->assertEquals(1, $connection->getTransactionLevel());
		
		$this->assertTrue($connection->commit());
		$this->assertEquals(0, $connection->getTransactionLevel());
	}

	/**
	 * Tests OutletConnection->commit()
	 */
	public function testCommitConsecutiveCalls()
	{
		$pdo = $this->getMock('PDO', array('beginTransaction', 'exec', 'commit'), array('sqlite::memory:'));
		$connection = new OutletConnection($pdo, 'mysql');
		
		$pdo->expects($this->once())
			->method('beginTransaction')
			->will($this->returnValue(true));
		
		$pdo->expects($this->once())
			->method('commit')
			->will($this->returnValue(true));
		
		$pdo->expects($this->never())
			->method('exec');
			
		$this->assertTrue($connection->beginTransaction());
		$this->assertEquals(1, $connection->getTransactionLevel());
			
		$this->assertTrue($connection->beginTransaction());
		$this->assertEquals(2, $connection->getTransactionLevel());
		
		$this->assertTrue($connection->commit());
		$this->assertEquals(1, $connection->getTransactionLevel());
		
		$this->assertTrue($connection->commit());
		$this->assertEquals(0, $connection->getTransactionLevel());
	}

	/**
	 * Tests OutletConnection->commit()
	 */
	public function testCommitNoTransactionSupport()
	{
		$pdo = $this->getMock('PDO', array('beginTransaction', 'exec', 'commit'), array('sqlite::memory:'));
		$connection = new OutletConnection($pdo, 'mysql');
		
		$pdo->expects($this->once())
			->method('beginTransaction')
			->will($this->throwException(new PDOException('Test')));
		
		$pdo->expects($this->never())
			->method('commit');
		
		$pdo->expects($this->exactly(2))
			->method('exec')
			->will($this->returnValue(true));
			
		$this->assertTrue($connection->beginTransaction());
		$this->assertEquals(1, $connection->getTransactionLevel());
		$this->assertFalse($connection->isTransactionSupported());
		$this->assertTrue($connection->commit());
		$this->assertEquals(0, $connection->getTransactionLevel());
	}

	/**
	 * Tests OutletConnection->rollBack()
	 */
	public function testRollBack()
	{
		$pdo = $this->getMock('PDO', array('beginTransaction', 'exec', 'rollBack'), array('sqlite::memory:'));
		$connection = new OutletConnection($pdo, 'mysql');
		
		$pdo->expects($this->once())
			->method('beginTransaction')
			->will($this->returnValue(true));
		
		$pdo->expects($this->once())
			->method('rollBack')
			->will($this->returnValue(true));
		
		$pdo->expects($this->never())
			->method('exec');
			
		$this->assertTrue($connection->beginTransaction());
		$this->assertEquals(1, $connection->getTransactionLevel());
		
		$this->assertTrue($connection->rollBack());
		$this->assertEquals(0, $connection->getTransactionLevel());
	}

	/**
	 * Tests OutletConnection->rollBack()
	 */
	public function testRollBackConsecutiveCalls()
	{
		$pdo = $this->getMock('PDO', array('beginTransaction', 'exec', 'rollBack'), array('sqlite::memory:'));
		$connection = new OutletConnection($pdo, 'mysql');
		
		$pdo->expects($this->once())
			->method('beginTransaction')
			->will($this->returnValue(true));
		
		$pdo->expects($this->once())
			->method('rollBack')
			->will($this->returnValue(true));
		
		$pdo->expects($this->never())
			->method('exec');
			
		$this->assertTrue($connection->beginTransaction());
		$this->assertEquals(1, $connection->getTransactionLevel());
			
		$this->assertTrue($connection->beginTransaction());
		$this->assertEquals(2, $connection->getTransactionLevel());
		
		$this->assertTrue($connection->rollBack());
		$this->assertEquals(1, $connection->getTransactionLevel());
		
		$this->assertTrue($connection->rollBack());
		$this->assertEquals(0, $connection->getTransactionLevel());
	}

	/**
	 * Tests OutletConnection->rollBack()
	 */
	public function testRollBackNoTransactionSupport()
	{
		$pdo = $this->getMock('PDO', array('beginTransaction', 'exec', 'rollBack'), array('sqlite::memory:'));
		$connection = new OutletConnection($pdo, 'mysql');
		
		$pdo->expects($this->once())
			->method('beginTransaction')
			->will($this->throwException(new PDOException('Test')));
		
		$pdo->expects($this->never())
			->method('rollBack');
		
		$pdo->expects($this->exactly(2))
			->method('exec')
			->will($this->returnValue(true));
			
		$this->assertTrue($connection->beginTransaction());
		$this->assertEquals(1, $connection->getTransactionLevel());
		$this->assertFalse($connection->isTransactionSupported());
		$this->assertTrue($connection->rollBack());
		$this->assertEquals(0, $connection->getTransactionLevel());
	}

	/**
	 * Tests OutletConnection->quote()
	 */
	public function testQuote()
	{
		$pdo = $this->getMock('PDO', array('quote'), array('sqlite::memory:'));
		$connection = new OutletConnection($pdo, 'mysql');
		
		$pdo->expects($this->once())
			->method('quote')
			->with("teste'flksd!'")
			->will($this->returnValue('teste\'flksd!\''));
		
		$this->assertEquals('teste\'flksd!\'', $connection->quote("teste'flksd!'"));
	}

	/**
	 * Tests OutletConnection->quote()
	 */
	public function testQuoteNotSupported()
	{
		$pdo = $this->getMock('PDO', array('quote'), array('sqlite::memory:'));
		$connection = new OutletConnection($pdo, 'mysql');
		
		$pdo->expects($this->once())
			->method('quote')
			->with(1)
			->will($this->returnValue(false));
		
		$this->assertEquals(1, $connection->quote(1));
	}

	/**
	 * Tests OutletConnection->quote()
	 */
	public function testQuoteNotSupportedWithString()
	{
		$pdo = $this->getMock('PDO', array('quote'), array('sqlite::memory:'));
		$connection = new OutletConnection($pdo, 'mysql');
		
		$pdo->expects($this->once())
			->method('quote')
			->with("teste'flksd!'")
			->will($this->returnValue(false));
		
		$this->assertEquals("'teste''flksd!'''", $connection->quote("teste'flksd!'"));
	}

	/**
	 * Tests OutletConnection->lastInsertId()
	 */
	public function testLastInsertId()
	{
		$pdo = $this->getMock('PDO', array('lastInsertId'), array('sqlite::memory:'));
		$connection = new OutletConnection($pdo, 'mysql');
		
		$pdo->expects($this->once())
			->method('lastInsertId')
			->with('')
			->will($this->returnValue(1));
			
		$this->assertEquals(1, $connection->lastInsertId());
	}

	/**
	 * Tests OutletConnection->lastInsertId()
	 */
	public function testLastInsertIdWithSequenceName()
	{
		$pdo = $this->getMock('PDO', array('lastInsertId'), array('sqlite::memory:'));
		$connection = new OutletConnection($pdo, 'mysql');
		
		$pdo->expects($this->once())
			->method('lastInsertId')
			->with('testeSeqId')
			->will($this->returnValue(1));
			
		$this->assertEquals(1, $connection->lastInsertId('testeSeqId'));
	}

	/**
	 * Tests OutletConnection->lastInsertId()
	 */
	public function testLastInsertIdMssql()
	{
		$pdo = $this->getMock('PDO', array('lastInsertId', 'query'), array('sqlite::memory:'));
		$stm = $this->getMock('PDOStatement', array('fetchColumn'));
		$connection = new OutletConnection($pdo, 'mssql');
		
		$pdo->expects($this->never())
			->method('lastInsertId');
		
		$pdo->expects($this->once())
			->method('query')
			->with('SELECT SCOPE_IDENTITY()')
			->will($this->returnValue($stm));
		
		$stm->expects($this->once())
			->method('fetchColumn')
			->with(0)
			->will($this->returnValue(1));
			
		$this->assertEquals(1, $connection->lastInsertId());
	}

	/**
	 * Tests OutletConnection->__call()
	 */
	public function test__call()
	{
		$pdo = $this->getMock('PDO', array('blablabla'), array('sqlite::memory:'));
		$connection = new OutletConnection($pdo, 'mysql');
		
		$pdo->expects($this->once())
			->method('blablabla')
			->with(1, 2, 3)
			->will($this->returnValue(false));
			
		$this->assertFalse($connection->blablabla(1, 2, 3));
	}
}