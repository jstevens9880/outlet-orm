<?php
require_once 'application/org.outlet-orm/database/OutletQuery.php';
require_once 'application/org.outlet-orm/database/OutletWriteQuery.php';
require_once 'application/org.outlet-orm/database/OutletInsertQuery.php';

require_once 'PHPUnit/Framework/TestCase.php';

eval('
	class OutletInsertQueryMock extends OutletInsertQuery
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
 * OutletInsertQuery test case.
 */
class OutletInsertQueryTest extends PHPUnit_Framework_TestCase
{

	/**
	 * Tests OutletInsertQuery->into()
	 */
	public function testInto()
	{
		$outletInsertQuery = $this->getMock('OutletInsertQueryMock', array('intoTable'));

		$outletInsertQuery->expects($this->once())
						  ->method('intoTable')
						  ->with('{TestTable}')
						  ->will($this->returnValue($outletInsertQuery));

		$this->assertEquals($outletInsertQuery, $outletInsertQuery->into('TestTable'));
	}

	/**
	 * Tests OutletInsertQuery->intoTable()
	 */
	public function testIntoTable()
	{
		$outletInsertQuery = $this->getMock('OutletInsertQueryMock', array('into'));

		$outletInsertQuery->table = 'TestTable';

		$this->assertEquals($outletInsertQuery, $outletInsertQuery->intoTable('TestTable'));
		$this->assertEquals('TestTable', $outletInsertQuery->table);
	}

	/**
	 * Tests OutletInsertQuery->toSql()
	 */
	public function testToSql()
	{
		$outletInsertQuery = $this->getMock('OutletInsertQueryMock', array('intoTable'));

		$outletInsertQuery->table = 'TestTable';
		$outletInsertQuery->fields = array('test_column' => '1');

		$this->assertEquals('INSERT INTO TestTable (test_column) VALUES (?)', $outletInsertQuery->toSql());
	}

	/**
	 * Tests OutletInsertQuery->toSql()
	 */
	public function testToSqlIsExpression()
	{
		$outletInsertQuery = $this->getMock('OutletInsertQueryMock', array('isExpression'));

		$outletInsertQuery->expects($this->once())
						  ->method('isExpression')
						  ->with('test_column')
						  ->will($this->returnValue(true));

		$outletInsertQuery->table = 'TestTable';
		$outletInsertQuery->fields = array('test_column' => '1');

		$this->assertEquals('INSERT INTO TestTable (test_column) VALUES (1)', $outletInsertQuery->toSql());
	}

	/**
	 * Tests OutletInsertQuery->toSql()
	 *
	 * @expectedException BadMethodCallException
	 */
	public function testToSqlTableIsNull()
	{
		$outletInsertQuery = $this->getMock('OutletInsertQueryMock', array('isExpression'));

		$outletInsertQuery->table = null;

		$outletInsertQuery->toSql();
	}

	/**
	 * Tests OutletInsertQuery->toSql()
	 *
	 * @expectedException BadMethodCallException
	 */
	public function testToSqlNoFields()
	{
		$outletInsertQuery = $this->getMock('OutletInsertQueryMock', array('isExpression'));

		$outletInsertQuery->table = 'TestTable';
		$outletInsertQuery->fields = array();

		$outletInsertQuery->toSql();
	}
}