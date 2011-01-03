<?php
date_default_timezone_set('America/Sao_Paulo');

require_once 'application/org.outlet-orm/database/OutletQuery.php';
require_once 'application/org.outlet-orm/database/OutletWriteQuery.php';

require_once 'PHPUnit/Framework/TestCase.php';

eval('
	abstract class OutletWriteQueryMock extends OutletWriteQuery
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
 * OutletWriteQuery test case.
 */
class OutletWriteQueryTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests OutletWriteQuery->__construct()
	 */
	public function test__construct()
	{
		$outletWriteQuery = $this->getMock('OutletWriteQueryMock', array('toSql'), array(), '', false);

		$outletWriteQuery->fields = array();
		$outletWriteQuery->expressions = array();
		$outletWriteQuery->__construct();
	}

	/**
	 * Tests OutletWriteQuery->set()
	 */
	public function testSet()
	{
		$outletWriteQuery = $this->getMock('OutletWriteQueryMock', array('toSqlValue', 'toSql'), array(), '', false);

		$this->assertEquals($outletWriteQuery, $outletWriteQuery->set('propriedade', 'teste', 'string'));
		$this->assertEquals('teste', $outletWriteQuery->fields['propriedade']);
	}

	/**
	 * Tests OutletWriteQuery->setExpression()
	 */
	public function testSetExpression()
	{
		$outletWriteQuery = $this->getMock('OutletWriteQueryMock', array('toSqlValue', 'toSql'), array(), '', false);

		$this->assertEquals($outletWriteQuery, $outletWriteQuery->setExpression('propriedade', 'teste'));
		$this->assertEquals('teste', $outletWriteQuery->fields['propriedade']);
		$this->assertEquals('propriedade', in_array('propriedade', $outletWriteQuery->expressions));
	}

	/**
	 * Tests OutletWriteQuery::toSqlValue()
	 */
	public function testToSqlValue()
	{
		$outletWriteQuery = $this->getMock('OutletWriteQueryMock', array('toSql'), array(), '', false);

		$this->assertEquals(null, $outletWriteQuery->toSqlValue('date', null));
		$this->assertEquals('2010-11-11', $outletWriteQuery->toSqlValue('date', new DateTime('2010-11-11')));
		$this->assertEquals('2010-11-11 22:22:22', $outletWriteQuery->toSqlValue('datetime', new DateTime('2010-11-11 22:22:22')));
		$this->assertEquals(1, $outletWriteQuery->toSqlValue('int', 1));
		$this->assertEquals(1, $outletWriteQuery->toSqlValue('bool', 1));
		$this->assertEquals(1.2, $outletWriteQuery->toSqlValue('float', 1.2));
		$this->assertEquals('teste', $outletWriteQuery->toSqlValue('', 'teste'));
	}

	/**
	 * Tests OutletWriteQuery->isExpression()
	 */
	public function testIsExpression()
	{
		$outletWriteQuery = $this->getMock('OutletWriteQueryMock', array('toSql'), array(), '', false);

		$outletWriteQuery->expressions = array('teste');
		$this->assertTrue($outletWriteQuery->isExpression('teste'));
		$this->assertFalse($outletWriteQuery->isExpression('testeeeee'));
	}

	/**
	 * Tests OutletWriteQuery->getNonExpressionValues()
	 */
	public function testGetNonExpressionValues()
	{
		$outletWriteQuery = $this->getMock('OutletWriteQueryMock', array('isExpression', 'toSql'), array(), '', false);

		$outletWriteQuery->expects($this->once())
						 ->method('isExpression')
						 ->will($this->returnValue(false));

		$outletWriteQuery->fields = array('campo' => 'teste');

		$this->assertEquals(array('teste'), $outletWriteQuery->getNonExpressionValues());
	}
}

