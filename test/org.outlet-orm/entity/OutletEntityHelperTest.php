<?php
require_once 'application/org.outlet-orm/entity/OutletEmbeddableEntity.php';
require_once 'application/org.outlet-orm/entity/OutletEntity.php';
require_once 'application/org.outlet-orm/entity/OutletEntityProperty.php';
require_once 'application/org.outlet-orm/entity/OutletEntityHelper.php';
require_once 'application/org.outlet-orm/database/OutletQuery.php';
require_once 'application/org.outlet-orm/database/OutletWriteQuery.php';
require_once 'application/org.outlet-orm/database/OutletInsertQuery.php';
require_once 'application/org.outlet-orm/database/OutletUpdateQuery.php';
require_once 'PHPUnit/Framework/TestCase.php';

eval(
	'class EntityHelperTestUser
	{
		public $id;

		public $name;

		public $address;

		public function __construct($id = null, $name = null, EntityHelperTestAddress $address = null)
		{
			$this->id = $id;
			$this->name = $name;
			$this->address = $address;
		}
	}

	class EntityHelperTestAddress
	{
		public $street;

		public $number;

		public function __construct($street = null, $number = null)
		{
			$this->street = $street;
			$this->number = $number;
		}
	}

	class OutletInsertQueryMock_123 extends OutletInsertQuery
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

	class OutletUpdateQueryMock_456 extends OutletUpdateQuery
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
	}'
);

/**
 * OutletEntityHelper test case.
 */
class OutletEntityHelperTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests OutletEntityHelper->populateObject()
	 */
	public function testPopulateObjectWithoutEmbedded()
	{
		$entity = new OutletEntity('EntityHelperTestUser', 'user');
		$entity->addProperty(new OutletEntityProperty('id', 'id', 'int', true, true));
		$entity->addProperty(new OutletEntityProperty('name', 'full_name', 'varchar'));

		$obj = null;
		eval('$obj = new EntityHelperTestUser();');
		$properties = array('id' => '1', 'full_name' => 'Test user');

		$expected = null;
		eval('$expected = new EntityHelperTestUser(1, \'Test user\');');

		$helper = new OutletEntityHelper();
		$helper->populateObject($entity, $obj, $properties);

		$this->assertEquals($expected, $obj);
	}

	/**
	 * Tests OutletEntityHelper->populateObject()
	 */
	public function testPopulateObjectWithEmbedded()
	{
		$entity = new OutletEntity('EntityHelperTestUser', 'user');
		$entity->addProperty(new OutletEntityProperty('id', 'id', 'int', true, true));
		$entity->addProperty(new OutletEntityProperty('name', 'full_name', 'varchar'));

		$entity2 = new OutletEmbeddableEntity('EntityHelperTestAddress');
		$entity2->addProperty(new OutletEntityProperty('street', 'street_name', 'varchar'));
		$entity2->addProperty(new OutletEntityProperty('number', 'number', 'int'));

		$mockedProperty = $this->getMock('OutletEntityProperty', array('getEmbeddedEntity'), array('address', null, 'embedded', false, false, null, null, 'EntityHelperTestAddress'));
		$entity->addProperty($mockedProperty);

		$mockedProperty->expects($this->once())
					   ->method('getEmbeddedEntity')
					   ->will($this->returnValue($entity2));

		$obj = null;
		eval('$obj = new EntityHelperTestUser();');
		$properties = array('id' => '1', 'full_name' => 'Test user', 'street_name' => '22 Acacia Avenue', 'number' => 666);

		$expected = null;
		eval('$expected = new EntityHelperTestUser(1, \'Test user\', new EntityHelperTestAddress(\'22 Acacia Avenue\', 666));');

		$helper = new OutletEntityHelper();
		$helper->populateObject($entity, $obj, $properties);

		$this->assertEquals($expected, $obj);
	}

	public function testExtractValuesWithoutEmbedded()
	{
		$entity = new OutletEntity('EntityHelperTestUser', 'user');
		$entity->addProperty(new OutletEntityProperty('id', 'id', 'int', true, true));
		$entity->addProperty(new OutletEntityProperty('name', 'full_name', 'varchar'));

		$obj = null;
		eval('$obj = new EntityHelperTestUser(1, \'Test user\');');

		$helper = new OutletEntityHelper();
		$this->assertEquals(array('id' => 1, 'name' => 'Test user'), $helper->extractValues($entity, $obj));
	}

	public function testExtractValuesWithEmbedded()
	{
		$entity = new OutletEntity('EntityHelperTestUser', 'user');
		$entity->addProperty(new OutletEntityProperty('id', 'id', 'int', true, true));
		$entity->addProperty(new OutletEntityProperty('name', 'full_name', 'varchar'));

		$entity2 = new OutletEmbeddableEntity('EntityHelperTestAddress');
		$entity2->addProperty(new OutletEntityProperty('street', 'street_name', 'varchar'));
		$entity2->addProperty(new OutletEntityProperty('number', 'number', 'int'));

		$mockedProperty = $this->getMock('OutletEntityProperty', array('getEmbeddedEntity'), array('address', null, 'embedded', false, false, null, null, 'EntityHelperTestAddress'));
		$entity->addProperty($mockedProperty);

		$mockedProperty->expects($this->once())
					   ->method('getEmbeddedEntity')
					   ->will($this->returnValue($entity2));

		$obj = null;
		eval('$obj = new EntityHelperTestUser(1, \'Test user\', new EntityHelperTestAddress(\'22 Acacia Avenue\', 666));');

		$helper = new OutletEntityHelper();
		$this->assertEquals(array('id' => 1, 'name' => 'Test user', 'street' => '22 Acacia Avenue', 'number' => 666), $helper->extractValues($entity, $obj));
	}

	public function testExtractValuesToSqlWithoutEmbedded()
	{
		$entity = new OutletEntity('EntityHelperTestUser', 'user');
		$entity->addProperty(new OutletEntityProperty('id', 'id', 'int', true, true));
		$entity->addProperty(new OutletEntityProperty('name', 'full_name', 'varchar'));

		$obj = null;
		eval('$obj = new EntityHelperTestUser(1, \'Test user\');');

		$helper = new OutletEntityHelper();
		$this->assertEquals(array('id' => 1, 'full_name' => 'Test user'), $helper->extractValuesToSql($entity, $obj));
	}

	public function testExtractValuesToSqlWithEmbedded()
	{
		$entity = new OutletEntity('EntityHelperTestUser', 'user');
		$entity->addProperty(new OutletEntityProperty('id', 'id', 'int', true, true));
		$entity->addProperty(new OutletEntityProperty('name', 'full_name', 'varchar'));

		$entity2 = new OutletEmbeddableEntity('EntityHelperTestAddress');
		$entity2->addProperty(new OutletEntityProperty('street', 'street_name', 'varchar'));
		$entity2->addProperty(new OutletEntityProperty('number', 'number', 'int'));

		$mockedProperty = $this->getMock('OutletEntityProperty', array('getEmbeddedEntity'), array('address', null, 'embedded', false, false, null, null, 'EntityHelperTestAddress'));
		$entity->addProperty($mockedProperty);

		$mockedProperty->expects($this->once())
					   ->method('getEmbeddedEntity')
					   ->will($this->returnValue($entity2));

		$obj = null;
		eval('$obj = new EntityHelperTestUser(1, \'Test user\', new EntityHelperTestAddress(\'22 Acacia Avenue\', 666));');

		$helper = new OutletEntityHelper();
		$this->assertEquals(array('id' => 1, 'full_name' => 'Test user', 'street_name' => '22 Acacia Avenue', 'number' => 666), $helper->extractValuesToSql($entity, $obj));
	}

	public function testFillInsertQueryWithoutEmbedded()
	{
		$entity = new OutletEntity('EntityHelperTestUser', 'user');
		$entity->addProperty(new OutletEntityProperty('id', 'id', 'int', true, true));
		$entity->addProperty(new OutletEntityProperty('name', 'full_name', 'varchar'));

		$obj = null;
		eval('$obj = new EntityHelperTestUser(1, \'Test user\');');

		$query = null;
		eval('$query = new OutletInsertQueryMock_123();');

		$helper = new OutletEntityHelper();
		$helper->fillInsertQuery($entity, $query, $obj);
		$this->assertEquals(array('{EntityHelperTestUser.name}' => 'Test user'), $query->fields);
	}

	public function testFillInsertQueryWithEmbedded()
	{
		$entity = new OutletEntity('EntityHelperTestUser', 'user');
		$entity->addProperty(new OutletEntityProperty('id', 'id', 'int', true, true));
		$entity->addProperty(new OutletEntityProperty('name', 'full_name', 'varchar'));

		$entity2 = new OutletEmbeddableEntity('EntityHelperTestAddress');
		$entity2->addProperty(new OutletEntityProperty('street', 'street_name', 'varchar'));
		$entity2->addProperty(new OutletEntityProperty('number', 'number', 'int'));

		$mockedProperty = $this->getMock('OutletEntityProperty', array('getEmbeddedEntity'), array('address', null, 'embedded', false, false, null, null, 'EntityHelperTestAddress'));
		$entity->addProperty($mockedProperty);

		$mockedProperty->expects($this->once())
					   ->method('getEmbeddedEntity')
					   ->will($this->returnValue($entity2));

		$obj = null;
		eval('$obj = new EntityHelperTestUser(1, \'Test user\', new EntityHelperTestAddress(\'22 Acacia Avenue\', 666));');

		$query = null;
		eval('$query = new OutletInsertQueryMock_123();');

		$helper = new OutletEntityHelper();
		$helper->fillInsertQuery($entity, $query, $obj);
		$this->assertEquals(array('{EntityHelperTestUser.name}' => 'Test user', '{EntityHelperTestUser.street}' => '22 Acacia Avenue', '{EntityHelperTestUser.number}' => 666), $query->fields);
	}

	public function testFillUpdateQueryWithoutEmbedded()
	{
		$entity = new OutletEntity('EntityHelperTestUser', 'user');
		$entity->addProperty(new OutletEntityProperty('id', 'id', 'int', true, true));
		$entity->addProperty(new OutletEntityProperty('name', 'full_name', 'varchar'));

		$obj = null;
		eval('$obj = new EntityHelperTestUser(1, \'Test user\');');

		$query = null;
		eval('$query = new OutletUpdateQueryMock_456(\'EntityHelperTestUser\');');

		$helper = new OutletEntityHelper();
		$helper->fillUpdateQuery($entity, $query, $obj, array('full_name'));
		$this->assertEquals(array('{EntityHelperTestUser.name}' => 'Test user'), $query->fields);
	}

	public function testFillUpdateQueryWithEmbedded()
	{
		$entity = new OutletEntity('EntityHelperTestUser', 'user');
		$entity->addProperty(new OutletEntityProperty('id', 'id', 'int', true, true));
		$entity->addProperty(new OutletEntityProperty('name', 'full_name', 'varchar'));

		$entity2 = new OutletEmbeddableEntity('EntityHelperTestAddress');
		$entity2->addProperty(new OutletEntityProperty('street', 'street_name', 'varchar'));
		$entity2->addProperty(new OutletEntityProperty('number', 'number', 'int'));

		$mockedProperty = $this->getMock('OutletEntityProperty', array('getEmbeddedEntity'), array('address', null, 'embedded', false, false, null, null, 'EntityHelperTestAddress'));
		$entity->addProperty($mockedProperty);

		$mockedProperty->expects($this->once())
					   ->method('getEmbeddedEntity')
					   ->will($this->returnValue($entity2));

		$obj = null;
		eval('$obj = new EntityHelperTestUser(1, \'Test user\', new EntityHelperTestAddress(\'22 Acacia Avenue\', 666));');

		$query = null;
		eval('$query = new OutletUpdateQueryMock_456(\'EntityHelperTestUser\');');

		$helper = new OutletEntityHelper();
		$helper->fillUpdateQuery($entity, $query, $obj, array('street_name', 'number'));
		$this->assertEquals(array('{EntityHelperTestUser.street}' => '22 Acacia Avenue', '{EntityHelperTestUser.number}' => 666), $query->fields);
	}
}