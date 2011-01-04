<?php
require_once 'application/org.outlet-orm/database/OutletQuery.php';
require_once 'application/org.outlet-orm/database/OutletWriteQuery.php';
require_once 'application/org.outlet-orm/database/OutletUpdateQuery.php';
require_once 'PHPUnit/Framework/TestCase.php';

eval('
	class OutletUpdateQueryMock extends OutletUpdateQuery
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
 * OutletUpdateQuery test case.
 */
class OutletUpdateQueryTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests OutletUpdateQuery->__construct()
	 */
	public function test__construct()
	{
		$outletInsertQuery = $this->getMock('OutletUpdateQueryMock', array('intoTable'), array('TableTest'), '', true);

		$outletInsertQuery->table = '{TableTest}';

		$this->assertEquals('{TableTest}', $outletInsertQuery->table);
	}

	/**
	 * Tests OutletUpdateQuery->toSql()
	 */
	public function testToSql()
	{
		$outletInsertQuery = $this->getMock('OutletUpdateQueryMock', array('intoTable'), array(), '', false);

		$outletInsertQuery->table = 'TestTable';
		$outletInsertQuery->fields = array('test_column' => 1);
		$outletInsertQuery->criteria = array('test_column2 = 1', 'test_column3 = 1');

		$this->assertEquals('UPDATE TestTable SET test_column = ? WHERE test_column2 = 1 AND test_column3 = 1', $outletInsertQuery->toSql());
	}

	/**
	 * Tests OutletUpdateQuery->toSql()
	 *
	 * @expectedException BadMethodCallException
	 */
	public function testToSqlTableIsNull()
	{
		$outletInsertQuery = $this->getMock('OutletUpdateQueryMock', array('intoTable'), array(), '', false);

		$outletInsertQuery->table = null;

		$outletInsertQuery->toSql();
	}

	/**
	 * Tests OutletUpdateQuery->toSql()
	 *
	 * @expectedException BadMethodCallException
	 */
	public function testToSqlNoFields()
	{
		$outletInsertQuery = $this->getMock('OutletUpdateQueryMock', array('intoTable'), array(), '', false);

		$outletInsertQuery->table = 'TestTable';
		$outletInsertQuery->fields = array();

		$outletInsertQuery->toSql();
	}

	/**
	 * Tests OutletUpdateQuery->toSql()
	 *
	 * @expectedException BadMethodCallException
	 */
	public function testToSqlNoCriteria()
	{
		$outletInsertQuery = $this->getMock('OutletUpdateQueryMock', array('intoTable'), array(), '', false);

		$outletInsertQuery->table = 'TestTable';
		$outletInsertQuery->fields = array('test_column' => 1);
		$outletInsertQuery->criteria = array();

		$outletInsertQuery->toSql();
	}
}