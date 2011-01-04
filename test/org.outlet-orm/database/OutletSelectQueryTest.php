<?php
require_once 'application/org.outlet-orm/core/OutletException.php';
require_once 'application/org.outlet-orm/database/OutletQuery.php';
require_once 'application/org.outlet-orm/core/OutletCache.php';
require_once 'application/org.outlet-orm/entity/OutletEmbeddableEntity.php';
require_once 'application/org.outlet-orm/entity/OutletEntityProperty.php';
require_once 'application/org.outlet-orm/entity/OutletEntityManager.php';
require_once 'application/org.outlet-orm/proxy/OutletProxy.php';
require_once 'application/org.outlet-orm/proxy/OutletProxyGenerator.php';
require_once 'application/org.outlet-orm/proxy/OutletProxyFactory.php';
require_once 'application/org.outlet-orm/entity/OutletEmbeddableEntity.php';
require_once 'application/org.outlet-orm/entity/OutletEntity.php';
require_once 'application/org.outlet-orm/association/OutletAssociation.php';
require_once 'application/org.outlet-orm/database/OutletSelectQuery.php';

require_once 'PHPUnit/Framework/TestCase.php';

eval('
	class OutletSelectQueryMock extends OutletSelectQuery
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
 * OutletSelectQuery test case.
 */
class OutletSelectQueryTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests OutletSelectQuery->__construct()
	 */
	public function test__construct()
	{
		$outletSelectQuery = $this->getMock('OutletSelectQueryMock', array('toSql'), array(), '', true);

		$this->assertEquals($outletSelectQuery->fields, array());
		$this->assertEquals($outletSelectQuery->joins, array());
		$this->assertEquals($outletSelectQuery->orders, array());
		$this->assertEquals($outletSelectQuery->groups, array());
		$this->assertEquals($outletSelectQuery->groups, array());
		$this->assertEquals($outletSelectQuery->having, array());
		$this->assertEquals($outletSelectQuery->params, array());
	}

	/**
	 * Tests OutletSelectQuery->where()
	 */
	public function testWhere()
	{
		$outletSelectQuery = $this->getMock('OutletSelectQueryMock', array('appendField'));

		$this->assertEquals($outletSelectQuery, $outletSelectQuery->where('test', array('testParam')));
		$this->assertEquals(array('testParam'), $outletSelectQuery->params);
		$this->assertEquals(array('test'), $outletSelectQuery->criteria);

		$outletSelectQuery->where('test2', array('testParam2'));

		$this->assertEquals(array('testParam', 'testParam2'), $outletSelectQuery->params);
		$this->assertEquals(array('test', 'test2'), $outletSelectQuery->criteria);
	}

	/**
	 * Tests OutletSelectQuery->appendProperty()
	 */
	public function testAppendProperty()
	{
		$outletSelectQuery = $this->getMock('OutletSelectQueryMock', array('appendField'));

		$outletSelectQuery->expects($this->once())
						  ->method('appendField')
						  ->with('{Test}', 'TestAlias')
						  ->will($this->returnValue($outletSelectQuery));

		$this->assertEquals($outletSelectQuery, $outletSelectQuery->appendProperty('Test', 'TestAlias'));
	}

	/**
	 * Tests OutletSelectQuery->appendField()
	 */
	public function testAppendField()
	{
		$outletSelectQuery = $this->getMock('OutletSelectQueryMock', array('appendProperty'));

		$this->assertEquals($outletSelectQuery, $outletSelectQuery->appendField('test', 'TestAlias'));
		$this->assertEquals($outletSelectQuery->fields['test'], 'test as TestAlias');
		$this->assertEquals($outletSelectQuery, $outletSelectQuery->appendField('test'));
		$this->assertEquals($outletSelectQuery->fields['test'], 'test');
	}

	/**
	 * Tests OutletSelectQuery->from()
	 */
	public function testFrom()
	{
		$outletSelectQuery = $this->getMock('OutletSelectQueryMock', array('appendProperty'));

		$this->assertEquals($outletSelectQuery, $outletSelectQuery->from('TestTable'));
		$this->assertEquals('{TestTable}', $outletSelectQuery->table);
	}

	/**
	 * Tests OutletSelectQuery->getFromData()
	 */
	public function testGetFromData()
	{
		$outletSelectQuery = $this->getMock('OutletSelectQueryMock', array('extractEntityAndAlias'), array(), '', false);

		$data = new stdClass();
		$data->entityName = 'Test';
		$data->alias = 'TestAlias';

		$outletSelectQuery->table = '{Test TestAlias}';

		$outletSelectQuery->expects($this->once())
						  ->method('extractEntityAndAlias')
						  ->with('Test TestAlias')
						  ->will($this->returnValue($data));

		$this->assertEquals($data, $outletSelectQuery->getFromData());
	}

	/**
	 * Tests OutletSelectQuery->extractEntityAndAlias()
	 */
	public function testExtractEntityAndAlias()
	{
		$outletSelectQuery = $this->getMock('OutletSelectQueryMock', array('getFromData'), array(), '', false);

		$data = new stdClass();
		$data->entityName = 'Test';
		$data->alias = 'TestAlias';

		$this->assertEquals($data, $outletSelectQuery->extractEntityAndAlias('Test TestAlias'));
	}

	/**
	 * Tests OutletSelectQuery->with()
	 */
	public function testWith()
	{
		$outletSelectQuery = $this->getMock('OutletSelectQueryMock', array('getFromData', 'extractEntityAndAlias', 'getEntityManager', 'leftJoin'));
		$outletEntityManager = $this->getMock('OutletEntityManager', array('getEntity'));
		$outletEntity = $this->getMock('OutletEntity', array('getAssociation'), array(), '', false);
		$outletEntity2 = $this->getMock('OutletEntity', array('getName'), array(), '', false);
		$outletAssociation = $this->getMock('OutletAssociation', array('getEntity', 'getKey', 'getRefKey'));

		$entityData = new stdClass();
		$entityData->entityName = 'EntityName';
		$entityData->alias = 'Alias';

		$foreignData = new stdClass();
		$foreignData->entityName = 'ForeignEntityName';
		$foreignData->alias = 'ForeignAlias';

		$outletSelectQuery->expects($this->once())
						  ->method('getFromData')
						  ->will($this->returnValue($entityData));

		$outletSelectQuery->expects($this->once())
						  ->method('extractEntityAndAlias')
						  ->with('ForeignEntityName')
						  ->will($this->returnValue($foreignData));

		$outletSelectQuery->expects($this->once())
						  ->method('leftJoin')
						  ->will($this->returnValue($outletSelectQuery));

		$outletSelectQuery->expects($this->once())
						  ->method('getEntityManager')
						  ->will($this->returnValue($outletEntityManager));

		$outletEntityManager->expects($this->once())
							->method('getEntity')
							->with($entityData->entityName)
							->will($this->returnValue($outletEntity));

		$outletEntity->expects($this->once())
					 ->method('getAssociation')
					 ->with($foreignData->entityName)
					 ->will($this->returnValue($outletAssociation));

		$outletAssociation->expects($this->once())
						  ->method('getEntity')
						  ->will($this->returnValue($outletEntity2));

		$outletAssociation->expects($this->once())
						  ->method('getKey');

		$outletAssociation->expects($this->once())
						  ->method('getRefKey');

		$outletEntity2->expects($this->once())
					  ->method('getName');

		$this->assertEquals($outletSelectQuery, $outletSelectQuery->with('ForeignEntityName'));
	}

	/**
	 * Tests OutletSelectQuery->with()
	 *
	 * @expectedException OutletException
	 */
	public function testWithNoAssociation()
	{
		$outletSelectQuery = $this->getMock('OutletSelectQueryMock', array('getFromData', 'extractEntityAndAlias', 'getEntityManager', 'leftJoin'));
		$outletEntityManager = $this->getMock('OutletEntityManager', array('getEntity'));
		$outletEntity = $this->getMock('OutletEntity', array('getAssociation'), array(), '', false);
		$outletEntity2 = $this->getMock('OutletEntity', array('getName'), array(), '', false);
		$outletAssociation = $this->getMock('OutletAssociation', array('getEntity', 'getKey', 'getRefKey'));

		$entityData = new stdClass();
		$entityData->entityName = 'EntityName';
		$entityData->alias = 'Alias';

		$foreignData = new stdClass();
		$foreignData->entityName = 'ForeignEntityName';
		$foreignData->alias = 'ForeignAlias';

		$outletSelectQuery->expects($this->once())
						  ->method('getFromData')
						  ->will($this->returnValue($entityData));

		$outletSelectQuery->expects($this->once())
						  ->method('extractEntityAndAlias')
						  ->with('ForeignEntityName')
						  ->will($this->returnValue($foreignData));

		$outletSelectQuery->expects($this->never())
						  ->method('leftJoin');

		$outletSelectQuery->expects($this->once())
						  ->method('getEntityManager')
						  ->will($this->returnValue($outletEntityManager));

		$outletEntityManager->expects($this->once())
							->method('getEntity')
							->with($entityData->entityName)
							->will($this->returnValue($outletEntity));

		$outletEntity->expects($this->once())
					 ->method('getAssociation')
					 ->with($foreignData->entityName)
					 ->will($this->returnValue(null));

		$outletAssociation->expects($this->never())
						  ->method('getEntity');

		$outletAssociation->expects($this->never())
						  ->method('getKey');

		$outletAssociation->expects($this->never())
						  ->method('getRefKey');

		$outletEntity2->expects($this->never())
					  ->method('getName');

		$this->assertEquals($outletSelectQuery, $outletSelectQuery->with('ForeignEntityName'));
	}

	/**
	 * Tests OutletSelectQuery->leftJoin()
	 */
	public function testLeftJoin()
	{
		$outletSelectQuery = $this->getMock('OutletSelectQueryMock', array('toSql'));

		$this->assertEquals($outletSelectQuery, $outletSelectQuery->leftJoin('EntityName', 'EntityKey', 'ForeignEntityKey'));
		$this->assertEquals('LEFT JOIN {EntityName} ON {EntityKey} = {ForeignEntityKey}', $outletSelectQuery->joins['EntityName']);
	}

	/**
	 * Tests OutletSelectQuery->leftJoinWithExpression()
	 */
	public function testLeftJoinWithExpression()
	{
		$outletSelectQuery = $this->getMock('OutletSelectQueryMock', array('toSql'));

		$this->assertEquals($outletSelectQuery, $outletSelectQuery->leftJoinWithExpression('EntityName', '1 = 1'));
		$this->assertEquals('LEFT JOIN {EntityName} ON 1 = 1', $outletSelectQuery->joins['EntityName']);
	}

	/**
	 * Tests OutletSelectQuery->innerJoin()
	 */
	public function testInnerJoin()
	{
		$outletSelectQuery = $this->getMock('OutletSelectQueryMock', array('toSql'));

		$this->assertEquals($outletSelectQuery, $outletSelectQuery->innerJoin('EntityName', 'EntityKey', 'ForeignEntityKey'));
		$this->assertEquals('INNER JOIN {EntityName} ON {EntityKey} = {ForeignEntityKey}', $outletSelectQuery->joins['EntityName']);
	}

	/**
	 * Tests OutletSelectQuery->innerJoinWithTable()
	 */
	public function testInnerJoinWithTable()
	{
		$outletSelectQuery = $this->getMock('OutletSelectQueryMock', array('toSql'));

		$this->assertEquals($outletSelectQuery, $outletSelectQuery->innerJoinWithTable('test_table', 'test_Key', 1));
		$this->assertEquals('INNER JOIN test_table ON test_Key = 1', $outletSelectQuery->joins['test_table']);
	}

	/**
	 * Tests OutletSelectQuery->orderBy()
	 */
	public function testOrderBy()
	{
		$outletSelectQuery = $this->getMock('OutletSelectQueryMock', array('toSql'));

		$this->assertEquals($outletSelectQuery, $outletSelectQuery->orderBy('test_field', 'ASC'));
		$this->assertEquals('{test_field} ASC', $outletSelectQuery->orders['test_field']);
	}

	/**
	 * Tests OutletSelectQuery->limit()
	 */
	public function testLimit()
	{
		$outletSelectQuery = $this->getMock('OutletSelectQueryMock', array('toSql'));

		$this->assertEquals($outletSelectQuery, $outletSelectQuery->limit(1));
		$this->assertEquals(1, $outletSelectQuery->limit);
	}

	/**
	 * Tests OutletSelectQuery->offset()
	 */
	public function testOffset()
	{
		$outletSelectQuery = $this->getMock('OutletSelectQueryMock', array('toSql'));

		$this->assertEquals($outletSelectQuery, $outletSelectQuery->offset(1));
		$this->assertEquals(1, $outletSelectQuery->offset);
	}

	/**
	 * Tests OutletSelectQuery->having()
	 */
	public function testHaving()
	{
		$outletSelectQuery = $this->getMock('OutletSelectQueryMock', array('toSql'));

		$this->assertEquals($outletSelectQuery, $outletSelectQuery->having('condition = 1'));
		$this->assertTrue(in_array('condition = 1', $outletSelectQuery->having));
	}

	/**
	 * Tests OutletSelectQuery->groupBy()
	 */
	public function testGroupBy()
	{
		$outletSelectQuery = $this->getMock('OutletSelectQueryMock', array('toSql'));

		$this->assertEquals($outletSelectQuery, $outletSelectQuery->groupBy('property'));
		$this->assertEquals('{property}', $outletSelectQuery->groups['property']);
	}

	/**
	 * Tests OutletSelectQuery->find()
	 */
	public function testFind()
	{
		$outletSelectQuery = $this->getMock('OutletSelectQueryMock', array('execute', 'getEntityManager', 'getFromData'), array(), '', false);
		$outletEntityManager = $this->getMock('OutletEntityManager', array('getEntityForRow'), array(), '', false);
		$outletProxy = $this->getMock('OutletProxy');
		$pdo = $this->getMock('PDOStatement', array('fetch'));

		$entityData = new stdClass();
		$entityData->entityName = 'EntityName';
		$entityData->alias = 'Alias';

		$outletSelectQuery->params = array();

		$outletSelectQuery->expects($this->once())
						  ->method('execute')
						  ->with(array())
						  ->will($this->returnValue($pdo));

		$outletSelectQuery->expects($this->once())
						  ->method('getEntityManager')
						  ->will($this->returnValue($outletEntityManager));

		$outletSelectQuery->expects($this->once())
						  ->method('getFromData')
						  ->will($this->returnValue($entityData));

		$outletEntityManager->expects($this->once())
							->method('getEntityForRow')
							->with($entityData->entityName, array('test'))
							->will($this->returnValue($outletProxy));

		$pdo->expects($this->exactly(2))
			->method('fetch')
			->with(PDO::FETCH_ASSOC)
			->will($this->onConsecutiveCalls(array('test'), array()));

		$this->assertEquals(array($outletProxy), $outletSelectQuery->find());
	}

	/**
	 * Tests OutletSelectQuery->findOne()
	 */
	public function testFindOne()
	{
		$outletSelectQuery = $this->getMock('OutletSelectQueryMock', array('limit', 'find'), array(), '', false);
		$outletProxy = $this->getMock('OutletProxy');

		$outletSelectQuery->expects($this->once())
						  ->method('limit')
						  ->with(1)
						  ->will($this->returnValue($outletSelectQuery));

		$outletSelectQuery->expects($this->once())
						  ->method('find')
						  ->will($this->returnValue(array('teste')));

		$this->assertEquals('teste', $outletSelectQuery->findOne());
	}

	/**
	 * Tests OutletSelectQuery->count()
	 */
	public function testCount()
	{
		$outletSelectQuery = $this->getMock('OutletSelectQueryMock', array('appendField', 'execute'), array(), '', false);
		$pdo = $this->getMock('PDOStatement', array('fetch'));

		$outletSelectQuery->params = array();

		$outletSelectQuery->expects($this->once())
						  ->method('appendField')
						  ->with('COUNT(*)', 'total')
						  ->will($this->returnValue($outletSelectQuery));

		$outletSelectQuery->expects($this->once())
						  ->method('execute')
						  ->with(array())
						  ->will($this->returnValue($pdo));

		$pdo->expects($this->once())
			->method('fetch')
			->with(PDO::FETCH_ASSOC)
			->will($this->returnValue(array('total' => 3)));

		$this->assertEquals(3, $outletSelectQuery->count());
	}

	/**
	 * Tests OutletSelectQuery->getGroupByClause()
	 */
	public function testGetGroupByClause()
	{
		$outletSelectQuery = $this->getMock('OutletSelectQueryMock', array('appendField'), array(), '', false);

		$outletSelectQuery->groups = array('test_column');

		$this->assertEquals('GROUP BY test_column', $outletSelectQuery->getGroupByClause());
	}

	/**
	 * Tests OutletSelectQuery->getOrderByClause()
	 */
	public function testGetOrderByClause()
	{
		$outletSelectQuery = $this->getMock('OutletSelectQueryMock', array('appendField'), array(), '', false);

		$outletSelectQuery->orders = array('test_column ASC', 'test_column DESC');
		$this->assertEquals('ORDER BY test_column ASC, test_column DESC', $outletSelectQuery->getOrderByClause());

		$outletSelectQuery->orders = array();
		$this->assertEquals(null, $outletSelectQuery->getOrderByClause());
	}

	/**
	 * Tests OutletSelectQuery->getLimitClause()
	 */
	public function testGetLimitClause()
	{
		$outletSelectQuery = $this->getMock('OutletSelectQueryMock', array('appendField'), array(), '', false);

		$outletSelectQuery->limit = 1;
		$outletSelectQuery->offset = 1;

		$this->assertEquals('LIMIT 1 OFFSET 1', $outletSelectQuery->getLimitClause());

		$outletSelectQuery->limit = 1;
		$outletSelectQuery->offset = null;

		$this->assertEquals('LIMIT 1', $outletSelectQuery->getLimitClause());

		$outletSelectQuery->limit = null;
		$outletSelectQuery->offset = null;

		$this->assertEquals(null, $outletSelectQuery->getLimitClause());
	}

	/**
	 * Tests OutletSelectQuery->fillSelectedFields()
	 */
	public function testFillSelectedFields()
	{
		$outletSelectQuery = $this->getMock('OutletSelectQueryMock', array('getFromData', 'getEntityManager', 'appendEntityFields'), array(), '', false);
		$outletEntityManager = $this->getMock('OutletEntityManager', array('getEntity'), array(), '', false);
		$outletEntity = $this->getMock('OutletEntity', array(), array(), '', false);

		$entityData = new stdClass();
		$entityData->entityName = 'Test';
		$entityData->alias = 'TestAlias';

		$outletSelectQuery->expects($this->once())
						  ->method('getFromData')
						  ->will($this->returnValue($entityData));

		$outletSelectQuery->expects($this->once())
						  ->method('getEntityManager')
						  ->will($this->returnValue($outletEntityManager));

		$outletSelectQuery->expects($this->once())
						  ->method('appendEntityFields')
						  ->with($outletEntity, $entityData->alias)
						  ->will($this->returnValue($outletEntityManager));

		$outletEntityManager->expects($this->once())
							->method('getEntity')
							->with($entityData->entityName)
							->will($this->returnValue($outletEntity));

		$this->assertEquals(null, $outletSelectQuery->fillSelectedFields());
	}

	/**
	 * Tests OutletSelectQuery->appendEntityFields()
	 */
	public function testAppendEntityFieldsWithoutEmbeddedEntity()
	{
		$outletSelectQuery = $this->getMock('OutletSelectQueryMock', array('toSql'), array(), '', false);
		$outletEntity = $this->getMock('OutletEntity', array('getProperties'), array(), '', false);
		$outletEntityProperty = $this->getMock('OutletEntityProperty', array('isEmbedded', 'getEmbeddedEntity'), array(), '', false);
		$outletEntityProperty2 = $this->getMock('OutletEntityProperty', array('isEmbedded', 'getEmbeddedEntity'), array(), '', false);

		$outletEntity->expects($this->once())
					 ->method('getProperties')
					 ->will($this->returnValue(array('name' => $outletEntityProperty, 'age' => $outletEntityProperty2)));

		$outletEntityProperty->expects($this->once())
							 ->method('isEmbedded')
							 ->will($this->returnValue(false));

		$outletEntityProperty->expects($this->never())
							 ->method('getEmbeddedEntity');

		$outletEntityProperty2->expects($this->once())
							  ->method('isEmbedded')
							  ->will($this->returnValue(false));

		$outletEntityProperty2->expects($this->never())
							  ->method('getEmbeddedEntity');

		$outletSelectQuery->appendEntityFields($outletEntity, 'TestAlias');
		$this->assertEquals(array('{TestAlias.name}' => '{TestAlias.name}', '{TestAlias.age}' => '{TestAlias.age}'), $outletSelectQuery->fields);
	}

	/**
	 * Tests OutletSelectQuery->appendEntityFields()
	 */
	public function testAppendEntityFieldsWithEmbeddedEntity()
	{
		$outletSelectQuery = $this->getMock('OutletSelectQueryMock', array('toSql'), array(), '', false);
		$outletEntity = $this->getMock('OutletEntity', array('getProperties'), array(), '', false);
		$outletEmbeddableEntity = $this->getMock('OutletEmbeddableEntity', array('getProperties'), array(), '', false);
		$outletEntityProperty = $this->getMock('OutletEntityProperty', array('isEmbedded', 'getEmbeddedEntity'), array(), '', false);
		$outletEntityProperty2 = $this->getMock('OutletEntityProperty', array('isEmbedded', 'getEmbeddedEntity'), array(), '', false);
		$outletEntityProperty3 = $this->getMock('OutletEntityProperty', array('isEmbedded', 'getEmbeddedEntity'), array(), '', false);
		$outletEntityProperty4 = $this->getMock('OutletEntityProperty', array('isEmbedded', 'getEmbeddedEntity'), array(), '', false);

		$outletEntity->expects($this->once())
					 ->method('getProperties')
					 ->will($this->returnValue(array('address' => $outletEntityProperty, 'name' => $outletEntityProperty2)));

		$outletEmbeddableEntity->expects($this->once())
							   ->method('getProperties')
							   ->will($this->returnValue(array('street' => $outletEntityProperty3, 'number' => $outletEntityProperty4)));

		$outletEntityProperty->expects($this->once())
							 ->method('isEmbedded')
							 ->will($this->returnValue(true));

		$outletEntityProperty->expects($this->once())
							 ->method('getEmbeddedEntity')
							 ->will($this->returnValue($outletEmbeddableEntity));

		$outletEntityProperty2->expects($this->once())
							  ->method('isEmbedded')
							  ->will($this->returnValue(false));

		$outletEntityProperty2->expects($this->never())
							  ->method('getEmbeddedEntity');

		$outletEntityProperty3->expects($this->once())
							  ->method('isEmbedded')
							  ->will($this->returnValue(false));

		$outletEntityProperty3->expects($this->never())
							  ->method('getEmbeddedEntity');

		$outletEntityProperty4->expects($this->once())
							  ->method('isEmbedded')
							  ->will($this->returnValue(false));

		$outletEntityProperty4->expects($this->never())
							  ->method('getEmbeddedEntity');

		$outletSelectQuery->appendEntityFields($outletEntity, 'TestAlias');
		$this->assertEquals(array('{TestAlias.street}' => '{TestAlias.street}', '{TestAlias.number}' => '{TestAlias.number}', '{TestAlias.name}' => '{TestAlias.name}'), $outletSelectQuery->fields);
	}

	/**
	 * Tests OutletSelectQuery->toSql()
	 */
	public function testToSql()
	{
		$outletSelectQuery = $this->getMock('OutletSelectQueryMock', array('appendField'), array(), '', false);

		$outletSelectQuery->fields = array('COUNT(*)' => 'test_field');
		$outletSelectQuery->table = 'test_table';
		$outletSelectQuery->criteria = array('1 = 1');
		$outletSelectQuery->having = array('2 = 2');
		$outletSelectQuery->joins = array('LEFT JOIN test_table2 ON 1 = 1');

		$sql = 'SELECT test_field FROM test_table LEFT JOIN test_table2 ON 1 = 1 WHERE 1 = 1 HAVING 2 = 2';

		$this->assertEquals($sql, preg_replace('/\s+/', ' ', $outletSelectQuery->toSql()));
	}

	/**
	 * Tests OutletSelectQuery->toSql()
	 */
	public function testToSql2()
	{
		$outletSelectQuery = $this->getMock('OutletSelectQueryMock', array('getGroupByClause', 'getOrderByClause', 'getLimitClause', 'fillSelectedFields'), array(), '', false);

		$outletSelectQuery->expects($this->once())
						  ->method('fillSelectedFields');

		$outletSelectQuery->expects($this->once())
						  ->method('getGroupByClause')
						  ->will($this->returnValue('GROUP BY test_column'));

		$outletSelectQuery->expects($this->once())
						  ->method('getOrderByClause')
						  ->will($this->returnValue('ORDER BY test_column ASC'));

		$outletSelectQuery->expects($this->once())
						  ->method('getLimitClause')
						  ->will($this->returnValue('LIMIT 10 OFFSET 1'));

		$outletSelectQuery->fields = array('test_field');
		$outletSelectQuery->table = 'test_table';
		$outletSelectQuery->criteria = array('1 = 1');
		$outletSelectQuery->having = array('2 = 2');
		$outletSelectQuery->joins = array('LEFT JOIN test_table2 ON 1 = 1');

		$sql = 'SELECT test_field FROM test_table LEFT JOIN test_table2 ON 1 = 1 WHERE 1 = 1 GROUP BY test_column ORDER BY test_column ASC HAVING 2 = 2 LIMIT 10 OFFSET 1';

		$this->assertEquals($sql, preg_replace('/\s+/', ' ', $outletSelectQuery->toSql()));
	}
}