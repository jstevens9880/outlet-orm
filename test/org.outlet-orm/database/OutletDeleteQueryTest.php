<?php
require_once 'application/org.outlet-orm/database/OutletQuery.php';
require_once 'application/org.outlet-orm/database/OutletDeleteQuery.php';

require_once 'PHPUnit/Framework/TestCase.php';

eval('
	class OutletDeleteQueryMock extends OutletDeleteQuery
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
 * OutletDeleteQuery test case.
 */
class OutletDeleteQueryTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests OutletDeleteQuery->from()
	 */
	public function testFrom()
	{
		$outletDeleteQuery = $this->getMock('OutletDeleteQuery', array('fromTable'));

		$outletDeleteQuery->expects($this->once())
						  ->method('fromTable')
						  ->with('{Test}')
						  ->will($this->returnValue($outletDeleteQuery));

		$this->assertEquals($outletDeleteQuery, $outletDeleteQuery->from('Test'));
	}

	/**
	 * Tests OutletDeleteQuery->fromTable()
	 */
	public function testFromTable()
	{
		$outletDeleteQuery = $this->getMock('OutletDeleteQueryMock', array('toSql'));

		$this->assertEquals($outletDeleteQuery, $outletDeleteQuery->fromTable('TableTest'));
		$this->assertEquals('TableTest', $outletDeleteQuery->table);
	}

	/**
	 * Tests OutletDeleteQuery->toSql()
	 */
	public function testToSql()
	{
		$outletDeleteQuery = $this->getMock('OutletDeleteQueryMock', array('fromTable'));

		$outletDeleteQuery->table = 'test';
		$outletDeleteQuery->criteria = array('test_column = 1');

		$this->assertEquals('DELETE FROM test WHERE test_column = 1', $outletDeleteQuery->toSql());
	}

	/**
	 * Tests OutletDeleteQuery->toSql()
	 *
	 * @expectedException BadMethodCallException
	 */
	public function testToSqlTableIsNull()
	{
		$outletDeleteQuery = $this->getMock('OutletDeleteQueryMock', array('fromTable'));

		$outletDeleteQuery->table = null;

		$outletDeleteQuery->toSql();
	}

	/**
	 * Tests OutletDeleteQuery->toSql()
	 *
	 * @expectedException BadMethodCallException
	 */
	public function testToSqlNoCreteria()
	{
		$outletDeleteQuery = $this->getMock('OutletDeleteQueryMock', array('fromTable'));

		$outletDeleteQuery->table = 'test';
		$outletDeleteQuery->criteria = array();

		$outletDeleteQuery->toSql();
	}
}