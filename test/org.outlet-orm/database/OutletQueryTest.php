<?php
date_default_timezone_set('America/Sao_Paulo');

require_once 'application/org.outlet-orm/core/OutletException.php';
require_once 'application/org.outlet-orm/core/Outlet.php';
require_once 'application/org.outlet-orm/database/OutletQuery.php';
require_once 'application/org.outlet-orm/database/OutletQueryTranslator.php';
require_once 'application/org.outlet-orm/database/OutletWriteQuery.php';
require_once 'application/org.outlet-orm/database/OutletDeleteQuery.php';
require_once 'application/org.outlet-orm/database/OutletUpdateQuery.php';
require_once 'application/org.outlet-orm/database/OutletSelectQuery.php';
require_once 'application/org.outlet-orm/database/OutletDeleteQuery.php';
require_once 'application/org.outlet-orm/database/OutletInsertQuery.php';
require_once 'application/org.outlet-orm/database/OutletConnection.php';

require_once 'application/org.outlet-orm/core/OutletCache.php';
require_once 'application/org.outlet-orm/entity/OutletEntityManager.php';

require_once 'PHPUnit/Framework/TestCase.php';

eval('
	abstract class OutletQueryMock extends OutletQuery
	{
		public function __call($method, $args)
		{
			if (method_exists($this, $method)) {
				return call_user_func_array(array($this, $method), $args);
			}
		}

		public function __get($property)
		{
			if (isset($this->$property)) {
				return $this->$property;
			}
		}

		public function __set($property, $value)
		{
			$this->$property = $value;
		}
	}
');

/**
 * OutletQuery test case.
 */
class OutletQueryTest extends PHPUnit_Framework_TestCase
{

	/**
	 * Tests OutletQuery::delete()
	 */
	public function testDelete()
	{
		$this->assertType('OutletDeleteQuery', OutletQuery::delete());
	}

	/**
	 * Tests OutletQuery::update()
	 */
	public function testUpdate()
	{
		$this->assertType('OutletUpdateQuery', OutletQuery::update('ApiKey'));
	}

	/**
	 * Tests OutletQuery::insert()
	 */
	public function testInsert()
	{
		$this->assertType('OutletInsertQuery', OutletQuery::insert());
	}

	/**
	 * Tests OutletQuery::select()
	 */
	public function testSelect()
	{
		$this->assertType('OutletSelectQuery', OutletQuery::select());
	}

	/**
	 * Tests OutletQuery->__construct()
	 */
	public function test__construct()
	{
		$outletQuery = $this->getMock('OutletQueryMock', array('toSql'), array(), '', false);

		$outletQuery->criteria = array();

		$outletQuery->__construct();
	}

	/**
	 * Tests OutletQuery->where()
	 */
	public function testWhere()
	{
		$outletQuery = $this->getMock('OutletQueryMock', array('toSql'), array(), '', false);

		$this->assertEquals($outletQuery, $outletQuery->where('teste'));
		$this->assertTrue(in_array('teste', $outletQuery->criteria));
	}

	/**
	 * Tests OutletQuery->execute()
	 */
	public function testExecute()
	{
		$outletQuery = $this->getMock('OutletQueryMock', array('toSql', 'getEntityManager', 'getTranslator'), array(), '', false);
		$outletEntityManager = $this->getMock('OutletEntityManager', array('getConnection'), array(), '', false);
		$outletConnection = $this->getMock('OutletConnection', array('prepare'), array(), '', false);
		$pdoStatement = $this->getMock('PDOStatement', array('execute'));
		$outletQueryTranslator = $this->getMock('OutletQueryTranslator', array('translate'));
		$params = array('campo' => 'teste');

		$outletQuery->expects($this->once())
					->method('getEntityManager')
					->will($this->returnValue($outletEntityManager));

		$outletQuery->expects($this->once())
					->method('getTranslator')
					->will($this->returnValue($outletQueryTranslator));

		$outletEntityManager->expects($this->once())
							->method('getConnection')
							->will($this->returnValue($outletConnection));

		$outletConnection->expects($this->once())
						 ->method('prepare')
						 ->will($this->returnValue($pdoStatement));

		$pdoStatement->expects($this->once())
					 ->method('execute')
					 ->with($params)
					 ->will($this->returnValue(true));

		$outletQueryTranslator->expects($this->once())
							  ->method('translate')
							  ->with($outletQuery);

		$this->assertEquals($pdoStatement, $outletQuery->execute($params));
	}

	/**
	 * Tests OutletQuery->getEntityManager()
	 */
	public function testGetEntityManager()
	{
		$outletQuery = $this->getMock('OutletQueryMock', array('toSql'), array(), '', false);

		$this->assertEquals(OutletEntityManager::getInstance(), $outletQuery->getEntityManager());
	}

	/**
	 * Tests OutletQuery->getTranslator()
	 */
	public function testGetTranslator()
	{
		$outletQuery = $this->getMock('OutletQueryMock', array('toSql'), array(), '', false);

		$this->assertEquals(new OutletQueryTranslator(), $outletQuery->getTranslator());
	}

	/**
	 * Tests OutletQuery->translateQuery()
	 */
	public function testTranslateQuery()
	{
		$outletQuery = $this->getMock('OutletQueryMock', array('toSql'), array(), '', false);
	}
}