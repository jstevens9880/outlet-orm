<?php
require_once 'application/org.outlet-orm/core/OutletException.php';
require_once 'application/org.outlet-orm/config/OutletConfig.php';
require_once 'application/org.outlet-orm/config/OutletConnectionConfig.php';
require_once 'application/org.outlet-orm/config/OutletConfigException.php';
require_once 'application/org.outlet-orm/config/OutletConfigParser.php';
require_once 'application/org.outlet-orm.config.parsers/xml/OutletXmlParser.php';
require_once 'application/org.outlet-orm.config.parsers/xml/OutletXmlParserException.php';
require_once 'application/org.outlet-orm/entity/OutletEntity.php';
require_once 'application/org.outlet-orm/entity/OutletEntityProperty.php';
require_once 'application/org.outlet-orm/association/OutletAssociation.php';
require_once 'application/org.outlet-orm/association/OutletManyToManyAssociation.php';
require_once 'application/org.outlet-orm/association/OutletManyToOneAssociation.php';
require_once 'application/org.outlet-orm/association/OutletOneToManyAssociation.php';
require_once 'application/org.outlet-orm/association/OutletOneToOneAssociation.php';
require_once 'application/org.outlet-orm/database/OutletConnection.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * OutletXmlParser test case.
 */
class OutletXmlParserTest extends PHPUnit_Framework_TestCase
{
	public function test__constructFromFile()
	{
		$xmlString =
			'<?xml version="1.0" encoding="UTF-8"?>
			<outlet-config xmlns="http://www.outlet-orm.org" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.outlet-orm.org outlet-config.xsd ">
				<connection>
					<dialect>sqlite</dialect>
					<dsn>sqlite::memory:</dsn>
				</connection>
				<classes>
					<class name="Machine" table="machine">
						<property name="Name" column="name" type="varchar" />
						<property name="Description" column="description" type="varchar" />
					</class>
				</classes>
			</outlet-config>';

		$file = dirname(__FILE__) . '/tmp.xml';
		file_put_contents($file, $xmlString);

		$obj = new OutletXmlParser($file);

		$this->assertNotNull($obj->getSource());

		unlink($file);
	}

	public function test__constructFromString()
	{
		$xmlString =
			'<?xml version="1.0" encoding="UTF-8"?>
			<outlet-config xmlns="http://www.outlet-orm.org" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.outlet-orm.org outlet-config.xsd ">
				<connection>
					<dialect>sqlite</dialect>
					<dsn>sqlite::memory:</dsn>
				</connection>
				<classes>
					<class name="Machine" table="machine">
						<property name="Name" column="name" type="varchar" />
						<property name="Description" column="description" type="varchar" />
					</class>
				</classes>
			</outlet-config>';

		$obj = new OutletXmlParser($xmlString);

		$this->assertNotNull($obj->getSource());
	}

	/**
	 * Tests OutletXmlParser->parseFromFile()
	 *
	 * @expectedException OutletXmlParserException
	 */
	public function test__constructFromFileInvalidXml()
	{
		$xmlString = 'aa';

		try {
			$file = dirname(__FILE__) . '/tmp.xml';
			file_put_contents($file, $xmlString);

			$obj = new OutletXmlParser($file);

			$this->assertNotNull($obj->getSource());

			unlink($file);
		} catch (OutletXmlParserException $e) {
			unlink($file);
			throw $e;
		}
	}

	/**
	 * @expectedException OutletXmlParserException
	 */
	public function test__constructFromStringInvalidXml()
	{
		$obj = new OutletXmlParser('aa');

		$this->assertNotNull($obj->getSource());
	}

	/**
	 *  Tests OutletXmlParser->validate()
	 *
	 *  @dataProvider validateProvider
	 */
	public function testValidate($xmlString, $hasErrors)
	{
		try {
			$obj = new OutletXmlParser($xmlString);
			$this->assertFalse($hasErrors);
		} catch (Exception $e) {
			$this->assertTrue($hasErrors, $e->getMessage());
		}
	}

	public function validateProvider()
	{
		return array(
			array('', true),
			array(
				'<?xml version="1.0" encoding="UTF-8"?>
				<root></root>',
				true
			),
			array(
				'<?xml version="1.0" encoding="UTF-8"?>
				<outlet-config xmlns="http://www.outlet-orm.org" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.outlet-orm.org outlet-config.xsd ">
					<connection>
						<dialect>sqlite</dialect>
						<dsn>sqlite::memory:</dsn>
					</connection>
					<classes>
						<class name="Machine" table="machine">
							<property name="Name" column="name" type="varchar" />
							<property name="Description" column="description" type="varchar" />
						</class>
					</classes>
				</outlet-config>',
				false
			),
			array(
				'<?xml version="1.0" encoding="UTF-8"?>
				<outlet-config xmlns="http://www.outlet-orm.org" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.outlet-orm.org outlet-config.xsd ">
					<connection>
						<dialect>oracle</dialect>
						<dsn>sqlite::memory:</dsn>
					</connection>
					<classes>
						<class name="Machine" table="machine">
							<property name="Name" column="name" type="varchar" />
							<property name="Description" column="description" type="varchar" />
						</class>
					</classes>
				</outlet-config>',
				true
			)
		);
	}

	/**
	 * Tests OutletArrayParser->getConfig()
	 */
	public function testGetConfig()
	{
		$source = '<?xml version="1.0" encoding="UTF-8"?>
				<outlet-config xmlns="http://www.outlet-orm.org" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.outlet-orm.org outlet-config.xsd ">
					<connection>
						<dialect>sqlite</dialect>
						<dsn>sqlite::memory:</dsn>
					</connection>
					<classes>
						<class name="Machine" table="machine">
							<property name="Name" column="name" type="varchar" />
							<property name="Description" column="description" type="varchar" />
						</class>
					</classes>
				</outlet-config>';

		$parser = new OutletXmlParser($source);
		$config = $parser->getConfig();

		$this->assertTrue($config === $parser->getConfig());
	}

	/**
	 * Tests OutletArrayParser->createConnectionConfig()
	 */
	public function testCreateConnectionConfig()
	{
		$source = '<?xml version="1.0" encoding="UTF-8"?>
				<outlet-config xmlns="http://www.outlet-orm.org" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.outlet-orm.org outlet-config.xsd ">
					<connection>
						<dialect>sqlite</dialect>
						<dsn>sqlite::memory:</dsn>
					</connection>
					<classes>
						<class name="Machine" table="machine">
							<property name="Name" column="name" type="varchar" />
							<property name="Description" column="description" type="varchar" />
						</class>
					</classes>
				</outlet-config>';

		$expectedConfig = new OutletConnectionConfig('sqlite', 'sqlite::memory:');
		$parser = new OutletXmlParser($source);
		$parser->createConnectionConfig();

		$this->assertEquals($expectedConfig, $parser->getConfig()->getConnectionConfig());
	}

	/**
	 * Tests OutletXmlParser->createConnectionConfig()
	 *
	 * @expectedException OutletXmlParserException
	 */
	public function testCreateConnectionConfigErrors()
	{
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
			<outlet-config xmlns="http://www.outlet-orm.org" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.outlet-orm.org outlet-config.xsd ">
				<connection>
					<dialect>mysql</dialect>
					<dsn>sqlite::memory:</dsn>
				</connection>
				<classes>
					<class name="Machine" table="machine">
						<property name="Name" column="name" type="varchar" />
						<property name="Description" column="description" type="varchar" />
					</class>
				</classes>
			</outlet-config>';

		$parser = new OutletXmlParser($xml);
		$parser->createConnectionConfig();
	}

	/**
	 * Tests OutletArrayParser->entityExists()
	 */
	public function testEntityExists()
	{
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
			<outlet-config xmlns="http://www.outlet-orm.org" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.outlet-orm.org outlet-config.xsd ">
				<connection>
					<dialect>mysql</dialect>
					<dsn>sqlite::memory:</dsn>
				</connection>
				<classes>
					<class name="Machine" table="machine">
						<property name="Name" column="name" type="varchar" />
						<property name="Description" column="description" type="varchar" />
					</class>
				</classes>
			</outlet-config>';

		$parser = new OutletXmlParser($xml);

		$this->assertTrue($parser->entityExists('Machine'));
	}

	/**
	 * Tests OutletArrayParser->entityExists()
	 */
	public function testEntityExistsNoExistingClass()
	{
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
			<outlet-config xmlns="http://www.outlet-orm.org" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.outlet-orm.org outlet-config.xsd ">
				<connection>
					<dialect>mysql</dialect>
					<dsn>sqlite::memory:</dsn>
				</connection>
				<classes>
					<class name="Machine" table="machine">
						<property name="Name" column="name" type="varchar" />
						<property name="Description" column="description" type="varchar" />
					</class>
				</classes>
			</outlet-config>';

		$parser = new OutletXmlParser($xml);

		$this->assertFalse($parser->entityExists('Bug'));
	}

	/**
	 * Tests OutletXmlParser->createEntityConfig()
	 */
	public function testCreateEntityConfigEntityAlreadyParsed()
	{
		$parser = $this->getMock('OutletXmlParser', array('getConfig', 'getEntityConfig'), array(), '', false);
		$config = $this->getMock('OutletConfig', array('entityExists'), array(), '', false);

		$parser->expects($this->once())
			   ->method('getConfig')
			   ->will($this->returnValue($config));

		$parser->expects($this->never())
			   ->method('getEntityConfig');

		$config->expects($this->once())
			   ->method('entityExists')
			   ->with('Address')
			   ->will($this->returnValue(true));

		$parser->createEntityConfig('Address');
	}

	/**
	 * Tests OutletXmlParser->createEntityConfig()
	 *
	 * @expectedException OutletXmlParserException
	 */
	public function testCreateEntityConfigNonExistingClass()
	{
		$parser = $this->getMock('OutletXmlParser', array('getConfig', 'getEntityConfig'), array(), '', false);
		$config = $this->getMock('OutletConfig', array('entityExists'), array(), '', false);

		$parser->expects($this->once())
			   ->method('getConfig')
			   ->will($this->returnValue($config));

		$parser->expects($this->once())
			   ->method('getEntityConfig')
			   ->with('Address2')
			   ->will($this->returnValue(null));

		$config->expects($this->once())
			   ->method('entityExists')
			   ->with('Address2')
			   ->will($this->returnValue(false));

		$parser->createEntityConfig('Address2');
	}

	/**
	 * Tests OutletXmlParser->createEntityConfig()
	 */
	public function testCreateEntityConfig()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<class name="Address" table="address">
				<property name="AddressId" column="id" type="int" pk="true" autoIncrement="true" />
				<property name="Street" column="street" type="varchar" />
			</class>'
		);

		$parser = $this->getMock('OutletXmlParser', array('getConfig', 'getEntityConfig', 'parseClass', 'parseAssociations'), array(), '', false);
		$config = $this->getMock('OutletConfig', array('entityExists', 'addEntity'), array(), '', false);
		$entity = $this->getMock('OutletEntity', array('addAssociation'), array(), '', false);

		$parser->expects($this->once())
			   ->method('getConfig')
			   ->will($this->returnValue($config));

		$parser->expects($this->once())
			   ->method('getEntityConfig')
			   ->with('Address')
			   ->will($this->returnValue($xml));

		$parser->expects($this->once())
			   ->method('parseClass')
			   ->with($xml)
			   ->will($this->returnValue($entity));

		$parser->expects($this->never())
			   ->method('parseAssociations');

		$config->expects($this->once())
			   ->method('entityExists')
			   ->with('Address')
			   ->will($this->returnValue(false));

		$parser->createEntityConfig('Address');
	}

	/**
	 * Tests OutletXmlParser->createEntityConfig()
	 */
	public function testCreateEntityConfigWithAssociation()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<class name="Address" table="address">
				<property name="AddressId" column="id" type="int" pk="true" autoIncrement="true" />
				<property name="Street" column="street" type="varchar" />
				<association type="many-to-one" classReference="Address" key="AddressId"/>
			</class>'
		);

		$parser = $this->getMock('OutletXmlParser', array('getConfig', 'getEntityConfig', 'parseClass', 'parseAssociations'), array(), '', false);
		$config = $this->getMock('OutletConfig', array('entityExists', 'addEntity'), array(), '', false);
		$entity = $this->getMock('OutletEntity', array('addAssociation'), array(), '', false);

		$parser->expects($this->once())
			   ->method('getConfig')
			   ->will($this->returnValue($config));

		$parser->expects($this->once())
			   ->method('getEntityConfig')
			   ->with('Address')
			   ->will($this->returnValue($xml));

		$parser->expects($this->once())
			   ->method('parseClass')
			   ->with($xml)
			   ->will($this->returnValue($entity));

		$parser->expects($this->once())
			   ->method('parseAssociations')
			   ->with($entity, $xml);

		$config->expects($this->once())
			   ->method('entityExists')
			   ->with('Address')
			   ->will($this->returnValue(false));

		$parser->createEntityConfig('Address');
	}

	/**
	 * Tests OutletXmlParser->parseClass()
	 */
	public function testParseClassNoOptionalParams()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<class name="Address" table="address">
				<property name="AddressId" column="id" type="int" pk="true" autoIncrement="true" />
				<property name="Street" column="street" type="varchar" />
			</class>'
		);

		$parser = $this->getMock('OutletXmlParser', array('parseProperty'), array(), '', false);
		$property = $this->getMock('OutletEntityProperty', array(), array(), '', false);

		$parser->expects($this->exactly(2))
			   ->method('parseProperty')
			   ->will($this->returnValue($property));

		$entity = $parser->parseClass($xml);

		$this->assertEquals('Address', $entity->getName());
		$this->assertEquals('address', $entity->getTable());
		$this->assertEquals('Addresss', $entity->getPlural());
		$this->assertEquals(null, $entity->getSequenceName());
		$this->assertEquals(2, $entity->getProperties()->count());
		$this->assertEquals(0, $entity->getAssociations()->count());
	}

	/**
	 * Tests OutletXmlParser->parseClass()
	 */
	public function testParseClassWithOptionalParams()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<class name="Address" table="address" plural="Addresses" sequenceName="teste">
				<property name="AddressId" column="id" type="int" pk="true" autoIncrement="true" />
				<property name="Street" column="street" type="varchar" />
			</class>'
		);

		$parser = $this->getMock('OutletXmlParser', array('parseProperty'), array(), '', false);
		$property = $this->getMock('OutletEntityProperty', array(), array(), '', false);

		$parser->expects($this->exactly(2))
			   ->method('parseProperty')
			   ->will($this->returnValue($property));

		$entity = $parser->parseClass($xml);

		$this->assertEquals('Address', $entity->getName());
		$this->assertEquals('address', $entity->getTable());
		$this->assertEquals('Addresses', $entity->getPlural());
		$this->assertEquals('teste', $entity->getSequenceName());
		$this->assertEquals(2, $entity->getProperties()->count());
		$this->assertEquals(0, $entity->getAssociations()->count());
	}

	/**
	 * Tests OutletXmlParser->parseProperty()
	 */
	public function testParsePropertyNoOptionalParams()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<property name="AddressId" column="id" type="int" />'
		);

		$parser = $this->getMock('OutletXmlParser', array('parseClass'), array(), '', false);
		$property = $parser->parseProperty($xml);

		$this->assertEquals('AddressId', $property->getName());
		$this->assertEquals('id', $property->getColumn());
		$this->assertEquals('int', $property->getType());
		$this->assertEquals(false, $property->getPrimaryKey());
		$this->assertEquals(false, $property->getAutoIncrement());
		$this->assertEquals(null, $property->getDefaultValue());
		$this->assertEquals(null, $property->getDefaultExpression());
	}

	/**
	 * Tests OutletXmlParser->parseProperty()
	 */
	public function testParsePropertyPkAutoInc()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<property name="AddressId" column="id" type="int" pk="true" autoIncrement="true" />'
		);

		$parser = $this->getMock('OutletXmlParser', array('parseClass'), array(), '', false);
		$property = $parser->parseProperty($xml);

		$this->assertEquals('AddressId', $property->getName());
		$this->assertEquals('id', $property->getColumn());
		$this->assertEquals('int', $property->getType());
		$this->assertEquals(true, $property->getPrimaryKey());
		$this->assertEquals(true, $property->getAutoIncrement());
		$this->assertEquals(null, $property->getDefaultValue());
		$this->assertEquals(null, $property->getDefaultExpression());
	}

	/**
	 * Tests OutletXmlParser->parseProperty()
	 */
	public function testParsePropertyDefaultValue()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<property name="AddressId" column="id" type="int" default="1" />'
		);

		$parser = $this->getMock('OutletXmlParser', array('parseClass'), array(), '', false);
		$property = $parser->parseProperty($xml);

		$this->assertEquals('AddressId', $property->getName());
		$this->assertEquals('id', $property->getColumn());
		$this->assertEquals('int', $property->getType());
		$this->assertEquals(false, $property->getPrimaryKey());
		$this->assertEquals(false, $property->getAutoIncrement());
		$this->assertEquals(1, $property->getDefaultValue());
		$this->assertEquals(null, $property->getDefaultExpression());
	}

	/**
	 * Tests OutletXmlParser->parseProperty()
	 */
	public function testParsePropertyDefaultExpression()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<property name="AddressId" column="id" type="int" defaultExpr="NOW()" />'
		);

		$parser = $this->getMock('OutletXmlParser', array('parseClass'), array(), '', false);
		$property = $parser->parseProperty($xml);

		$this->assertEquals('AddressId', $property->getName());
		$this->assertEquals('id', $property->getColumn());
		$this->assertEquals('int', $property->getType());
		$this->assertEquals(false, $property->getPrimaryKey());
		$this->assertEquals(false, $property->getAutoIncrement());
		$this->assertEquals(null, $property->getDefaultValue());
		$this->assertEquals('NOW()', $property->getDefaultExpression());
	}

	/**
	 * Tests OutletXmlParser->parseAssociations()
	 */
	public function testParseAssociations()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<class name="User" table="user">
				<property name="UserId" column="id" type="int" pk="true" autoIncrement="true" />
				<property name="FirstName" column="first_name" type="varchar" />
				<property name="LastName" column="last_name" type="varchar" />
				<property name="AddressId" column="address_id" type="int" />
				<association type="many-to-one" classReference="Address" key="AddressId"/>
				<association type="one-to-many" classReference="Test" key="TestId"/>
			</class>'
		);

		$parser = $this->getMock('OutletXmlParser', array('parseAssociation', 'getConfig'), array(), '', false);
		$config = $this->getMock('OutletConfig', array('getEntity'), array(), '', false);
		$entity = $this->getMock('OutletEntity', array('addAssociation'), array(), '', false);
		$assoc = $this->getMock('OutletAssociation');

		$parser->expects($this->exactly(2))
			   ->method('parseAssociation')
			   ->will($this->returnValue($assoc));

		$parser->expects($this->exactly(2))
			   ->method('getConfig')
			   ->will($this->returnValue($config));

		$config->expects($this->exactly(2))
			   ->method('getEntity')
			   ->will($this->returnValue($entity));

		$entity->expects($this->exactly(2))
			   ->method('addAssociation')
			   ->with($assoc);

		$parser->parseAssociations($entity, $xml);
	}

	/**
	 * Tests OutletXmlParser->validateAssociationConfig()
	 */
	public function testValidateAssociationConfigManyToMany()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<association type="many-to-many" classReference="Address" table="aa" tableKeyLocal="id" tableKeyForeign="id"/>'
		);

		$parser = $this->getMock('OutletXmlParser', array('parseClass'), array(), '', false);
		$parser->validateAssociationConfig($xml);
	}

	/**
	 * Tests OutletXmlParser->validateAssociationConfig()
	 *
	 * @expectedException OutletXmlParserException
	 */
	public function testValidateAssociationConfigManyToManyIncomplete()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<association type="many-to-many" classReference="Address"/>'
		);

		$parser = $this->getMock('OutletXmlParser', array('parseClass'), array(), '', false);
		$parser->validateAssociationConfig($xml);
	}

	/**
	 * Tests OutletXmlParser->validateAssociationConfig()
	 */
	public function testValidateAssociationConfigOneToMany()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<association type="one-to-many" classReference="Address" key="aa"/>'
		);

		$parser = $this->getMock('OutletXmlParser', array('parseClass'), array(), '', false);
		$parser->validateAssociationConfig($xml);
	}

	/**
	 * Tests OutletXmlParser->validateAssociationConfig()
	 *
	 * @expectedException OutletXmlParserException
	 */
	public function testValidateAssociationConfigOneToManyWithoutKey()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<association type="one-to-many" classReference="Address"/>'
		);

		$parser = $this->getMock('OutletXmlParser', array('parseClass'), array(), '', false);
		$parser->validateAssociationConfig($xml);
	}

	/**
	 * Tests OutletXmlParser->validateAssociationConfig()
	 *
	 * @expectedException OutletXmlParserException
	 */
	public function testValidateAssociationConfigOneToManyWithManyToManyConfig()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<association type="one-to-many" classReference="Address" table="aa" tableKeyLocal="id" tableKeyForeign="id"/>'
		);

		$parser = $this->getMock('OutletXmlParser', array('parseClass'), array(), '', false);
		$parser->validateAssociationConfig($xml);
	}

	/**
	 * Tests OutletXmlParser->validateAssociationConfig()
	 *
	 * @expectedException OutletXmlParserException
	 */
	public function testValidateAssociationConfigOneToManyWithOptional()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<association type="one-to-many" classReference="Address" key="aa" optional="true"/>'
		);

		$parser = $this->getMock('OutletXmlParser', array('parseClass'), array(), '', false);
		$parser->validateAssociationConfig($xml);
	}

	/**
	 * Tests OutletXmlParser->validateAssociationConfig()
	 *
	 * @expectedException OutletXmlParserException
	 */
	public function testValidateAssociationConfigOneToOneWithPlural()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<association type="one-to-one" classReference="Address" key="aa" plural="Addresses"/>'
		);

		$parser = $this->getMock('OutletXmlParser', array('parseClass'), array(), '', false);
		$parser->validateAssociationConfig($xml);
	}

	/**
	 * Tests OutletXmlParser->parseAssociation()
	 *
	 * @expectedException OutletXmlParserException
	 */
	public function testParseAssociationError()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<association type="many-to-one" classReference="Address" key="AddressId"/>'
		);

		$parser = $this->getMock('OutletXmlParser', array('createManyToOneAssociation', 'createManyToManyAssociation', 'createOneToOneAssociation', 'createOneToManyAssociation', 'validateAssociationConfig'), array(), '', false);
		$entity = $this->getMock('OutletEntity', array(), array(), '', false);
		$entity2 = $this->getMock('OutletEntity', array(), array(), '', false);
		$assoc = $this->getMock('OutletAssociation');

		$parser->expects($this->once())
			   ->method('validateAssociationConfig')
			   ->will($this->throwException(new OutletXmlParserException('blablabla')));

		$parser->expects($this->never())
			   ->method('createManyToOneAssociation');

		$parser->expects($this->never())
			   ->method('createManyToManyAssociation');

		$parser->expects($this->never())
			   ->method('createOneToOneAssociation');

		$parser->expects($this->never())
			   ->method('createOneToManyAssociation');

		$parser->parseAssociation($entity, $entity2, $xml);
	}

	/**
	 * Tests OutletXmlParser->parseAssociation()
	 */
	public function testParseAssociationManyToOne()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<association type="many-to-one" classReference="Address" key="AddressId"/>'
		);

		$parser = $this->getMock('OutletXmlParser', array('validateAssociationConfig', 'createManyToOneAssociation', 'createManyToManyAssociation', 'createOneToOneAssociation', 'createOneToManyAssociation'), array(), '', false);
		$entity = $this->getMock('OutletEntity', array(), array(), '', false);
		$entity2 = $this->getMock('OutletEntity', array(), array(), '', false);
		$assoc = $this->getMock('OutletAssociation');

		$parser->expects($this->once())
			   ->method('validateAssociationConfig');

		$parser->expects($this->once())
			   ->method('createManyToOneAssociation')
			   ->with($entity2, $xml)
			   ->will($this->returnValue($assoc));

		$parser->expects($this->never())
			   ->method('createManyToManyAssociation');

		$parser->expects($this->never())
			   ->method('createOneToOneAssociation');

		$parser->expects($this->never())
			   ->method('createOneToManyAssociation');

		$this->assertEquals($assoc, $parser->parseAssociation($entity, $entity2, $xml));
	}

	/**
	 * Tests OutletXmlParser->parseAssociation()
	 */
	public function testParseAssociationManyToMany()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<association type="many-to-many" classReference="Address" key="AddressId"/>'
		);

		$parser = $this->getMock('OutletXmlParser', array('validateAssociationConfig', 'createManyToOneAssociation', 'createManyToManyAssociation', 'createOneToOneAssociation', 'createOneToManyAssociation'), array(), '', false);
		$entity = $this->getMock('OutletEntity', array(), array(), '', false);
		$entity2 = $this->getMock('OutletEntity', array(), array(), '', false);
		$assoc = $this->getMock('OutletAssociation');

		$parser->expects($this->once())
			   ->method('validateAssociationConfig');

		$parser->expects($this->never())
			   ->method('createManyToOneAssociation');

		$parser->expects($this->once())
			   ->method('createManyToManyAssociation')
			   ->with($entity, $entity2, $xml)
			   ->will($this->returnValue($assoc));

		$parser->expects($this->never())
			   ->method('createOneToOneAssociation');

		$parser->expects($this->never())
			   ->method('createOneToManyAssociation');

		$this->assertEquals($assoc, $parser->parseAssociation($entity, $entity2, $xml));
	}

	/**
	 * Tests OutletXmlParser->parseAssociation()
	 */
	public function testParseAssociationOneToOne()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<association type="one-to-one" classReference="Address" key="AddressId"/>'
		);

		$parser = $this->getMock('OutletXmlParser', array('validateAssociationConfig', 'createManyToOneAssociation', 'createManyToManyAssociation', 'createOneToOneAssociation', 'createOneToManyAssociation'), array(), '', false);
		$entity = $this->getMock('OutletEntity', array(), array(), '', false);
		$entity2 = $this->getMock('OutletEntity', array(), array(), '', false);
		$assoc = $this->getMock('OutletAssociation');

		$parser->expects($this->once())
			   ->method('validateAssociationConfig');

		$parser->expects($this->never())
			   ->method('createManyToOneAssociation');

		$parser->expects($this->never())
			   ->method('createManyToManyAssociation');

		$parser->expects($this->once())
			   ->method('createOneToOneAssociation')
			   ->with($entity2,  $xml)
			   ->will($this->returnValue($assoc));

		$parser->expects($this->never())
			   ->method('createOneToManyAssociation');

		$this->assertEquals($assoc, $parser->parseAssociation($entity, $entity2, $xml));
	}

	/**
	 * Tests OutletXmlParser->parseAssociation()
	 */
	public function testParseAssociationOneToMany()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<association type="one-to-many" classReference="Address" key="AddressId"/>'
		);

		$parser = $this->getMock('OutletXmlParser', array('validateAssociationConfig', 'createManyToOneAssociation', 'createManyToManyAssociation', 'createOneToOneAssociation', 'createOneToManyAssociation'), array(), '', false);
		$entity = $this->getMock('OutletEntity', array(), array(), '', false);
		$entity2 = $this->getMock('OutletEntity', array(), array(), '', false);
		$assoc = $this->getMock('OutletAssociation');

		$parser->expects($this->once())
			   ->method('validateAssociationConfig');

		$parser->expects($this->never())
			   ->method('createManyToOneAssociation');

		$parser->expects($this->never())
			   ->method('createManyToManyAssociation');

		$parser->expects($this->never())
			   ->method('createOneToOneAssociation');

		$parser->expects($this->once())
			   ->method('createOneToManyAssociation')
			   ->with($entity, $entity2, $xml)
			   ->will($this->returnValue($assoc));

		$this->assertEquals($assoc, $parser->parseAssociation($entity, $entity2, $xml));
	}

	/**
	 * Tests OutletXmlParser->createManyToOneAssociation()
	 */
	public function testCreateManyToOneAssociation()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<association type="many-to-one" classReference="Address" key="AddressId"/>'
		);

		$parser = $this->getMock('OutletXmlParser', array('validateAssociationConfig'), array(), '', false);
		$entity = $this->getMock('OutletEntity', array('getMainPrimaryKey', 'getName'), array(), '', false);

		$entity->expects($this->once())
			   ->method('getMainPrimaryKey')
			   ->will($this->returnValue('id'));

		$entity->expects($this->once())
			   ->method('getName')
			   ->will($this->returnValue('Address'));

		$assoc = $parser->createManyToOneAssociation($entity, $xml);

		$this->assertEquals($entity, $assoc->getEntity());
		$this->assertEquals('AddressId', $assoc->getKey());
		$this->assertEquals('id', $assoc->getRefKey());
		$this->assertEquals('Address', $assoc->getName());
		$this->assertEquals(false, $assoc->isOptional());
	}

	/**
	 * Tests OutletXmlParser->createManyToOneAssociation()
	 */
	public function testCreateManyToOneAssociationWithOptionalSettings()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<association type="many-to-one" classReference="Address" key="AddressId" name="Blablabla" optional="true"/>'
		);

		$parser = $this->getMock('OutletXmlParser', array('validateAssociationConfig'), array(), '', false);
		$entity = $this->getMock('OutletEntity', array('getMainPrimaryKey', 'getName'), array(), '', false);

		$entity->expects($this->once())
			   ->method('getMainPrimaryKey')
			   ->will($this->returnValue('id'));

		$entity->expects($this->once())
			   ->method('getName')
			   ->will($this->returnValue('Address'));

		$assoc = $parser->createManyToOneAssociation($entity, $xml);

		$this->assertEquals($entity, $assoc->getEntity());
		$this->assertEquals('AddressId', $assoc->getKey());
		$this->assertEquals('id', $assoc->getRefKey());
		$this->assertEquals('Blablabla', $assoc->getName());
		$this->assertEquals(true, $assoc->isOptional());
	}

	/**
	 * Tests OutletXmlParser->createManyToManyAssociation()
	 */
	public function testCreateManyToManyAssociation()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<association type="many-to-many" classReference="Address" table="teste" tableKeyLocal="teste_id" tableKeyForeign="teste_id2"/>'
		);

		$parser = $this->getMock('OutletXmlParser', array('validateAssociationConfig'), array(), '', false);
		$entity = $this->getMock('OutletEntity', array('getMainPrimaryKey', 'getPlural'), array(), '', false);

		$entity->expects($this->exactly(2))
			   ->method('getMainPrimaryKey')
			   ->will($this->returnValue('AddressId'));

		$entity->expects($this->once())
			   ->method('getPlural')
			   ->will($this->returnValue('Addresses'));

		$assoc = $parser->createManyToManyAssociation($entity, $entity, $xml);

		$this->assertEquals($entity, $assoc->getEntity());
		$this->assertEquals('AddressId', $assoc->getKey());
		$this->assertEquals('AddressId', $assoc->getRefKey());
		$this->assertEquals('Addresses', $assoc->getName());
		$this->assertEquals('teste', $assoc->getLinkingTable());
		$this->assertEquals('teste_id', $assoc->getTableKeyLocal());
		$this->assertEquals('teste_id2', $assoc->getTableKeyForeign());
	}

	/**
	 * Tests OutletXmlParser->createManyToManyAssociation()
	 */
	public function testCreateManyToManyAssociationWithPlural()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<association type="many-to-many" classReference="Address" table="teste" tableKeyLocal="teste_id" tableKeyForeign="teste_id2" plural="Tesssstes"/>'
		);

		$parser = $this->getMock('OutletXmlParser', array('validateAssociationConfig'), array(), '', false);
		$entity = $this->getMock('OutletEntity', array('getMainPrimaryKey', 'getPlural'), array(), '', false);

		$entity->expects($this->exactly(2))
			   ->method('getMainPrimaryKey')
			   ->will($this->returnValue('AddressId'));

		$entity->expects($this->once())
			   ->method('getPlural')
			   ->will($this->returnValue('Addresses'));

		$assoc = $parser->createManyToManyAssociation($entity, $entity, $xml);

		$this->assertEquals($entity, $assoc->getEntity());
		$this->assertEquals('AddressId', $assoc->getKey());
		$this->assertEquals('AddressId', $assoc->getRefKey());
		$this->assertEquals('Tesssstes', $assoc->getName());
		$this->assertEquals('teste', $assoc->getLinkingTable());
		$this->assertEquals('teste_id', $assoc->getTableKeyLocal());
		$this->assertEquals('teste_id2', $assoc->getTableKeyForeign());
	}

	/**
	 * Tests OutletXmlParser->createOneToOneAssociation()
	 */
	public function testCreateOneToOneAssociation()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<association type="one-to-one" classReference="Address" key="AddressId"/>'
		);

		$parser = $this->getMock('OutletXmlParser', array('validateAssociationConfig'), array(), '', false);
		$entity = $this->getMock('OutletEntity', array('getMainPrimaryKey', 'getName'), array(), '', false);

		$entity->expects($this->once())
			   ->method('getMainPrimaryKey')
			   ->will($this->returnValue('id'));

		$entity->expects($this->once())
			   ->method('getName')
			   ->will($this->returnValue('Address'));

		$assoc = $parser->createOneToOneAssociation($entity, $xml);

		$this->assertEquals($entity, $assoc->getEntity());
		$this->assertEquals('AddressId', $assoc->getKey());
		$this->assertEquals('id', $assoc->getRefKey());
		$this->assertEquals('Address', $assoc->getName());
		$this->assertEquals(false, $assoc->isOptional());
	}

	/**
	 * Tests OutletXmlParser->createOneToOneAssociation()
	 */
	public function testCreateOneToOneAssociationWithOptionalSettings()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<association type="one-to-one" classReference="Address" key="AddressId" name="Blablabla" optional="true"/>'
		);

		$parser = $this->getMock('OutletXmlParser', array('validateAssociationConfig'), array(), '', false);
		$entity = $this->getMock('OutletEntity', array('getMainPrimaryKey', 'getName'), array(), '', false);

		$entity->expects($this->once())
			   ->method('getMainPrimaryKey')
			   ->will($this->returnValue('id'));

		$entity->expects($this->once())
			   ->method('getName')
			   ->will($this->returnValue('Address'));

		$assoc = $parser->createOneToOneAssociation($entity, $xml);

		$this->assertEquals($entity, $assoc->getEntity());
		$this->assertEquals('AddressId', $assoc->getKey());
		$this->assertEquals('id', $assoc->getRefKey());
		$this->assertEquals('Blablabla', $assoc->getName());
		$this->assertEquals(true, $assoc->isOptional());
	}

	/**
	 * Tests OutletXmlParser->createOneToManyAssociation()
	 */
	public function testCreateOneToManyAssociation()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<association type="one-to-many" classReference="Address" key="AddressId"/>'
		);

		$parser = $this->getMock('OutletXmlParser', array('validateAssociationConfig'), array(), '', false);
		$entity = $this->getMock('OutletEntity', array('getMainPrimaryKey', 'getName'), array(), '', false);

		$entity->expects($this->once())
			   ->method('getMainPrimaryKey')
			   ->will($this->returnValue('AddressId'));

		$entity->expects($this->once())
			   ->method('getName')
			   ->will($this->returnValue('Address'));

		$assoc = $parser->createOneToManyAssociation($entity, $entity, $xml);

		$this->assertEquals($entity, $assoc->getEntity());
		$this->assertEquals('AddressId', $assoc->getKey());
		$this->assertEquals('AddressId', $assoc->getRefKey());
		$this->assertEquals('Address', $assoc->getName());
	}

	/**
	 * Tests OutletXmlParser->createOneToManyAssociation()
	 */
	public function testCreateOneToManyAssociationWithPlural()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<association type="one-to-many" classReference="Address" key="AddressId" plural="Tesssstes"/>'
		);

		$parser = $this->getMock('OutletXmlParser', array('validateAssociationConfig'), array(), '', false);
		$entity = $this->getMock('OutletEntity', array('getMainPrimaryKey', 'getName'), array(), '', false);

		$entity->expects($this->once())
			   ->method('getMainPrimaryKey')
			   ->will($this->returnValue('AddressId'));

		$entity->expects($this->once())
			   ->method('getName')
			   ->will($this->returnValue('Address'));

		$assoc = $parser->createOneToManyAssociation($entity, $entity, $xml);

		$this->assertEquals($entity, $assoc->getEntity());
		$this->assertEquals('AddressId', $assoc->getKey());
		$this->assertEquals('AddressId', $assoc->getRefKey());
		$this->assertEquals('Tesssstes', $assoc->getName());
	}

	/**
	 * Tests OutletXmlParser->createOneToManyAssociation()
	 */
	public function testCreateOneToManyAssociationWithName()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<association type="one-to-many" classReference="Address" key="AddressId" name="Tesssstes"/>'
		);

		$parser = $this->getMock('OutletXmlParser', array('validateAssociationConfig'), array(), '', false);
		$entity = $this->getMock('OutletEntity', array('getMainPrimaryKey', 'getName'), array(), '', false);

		$entity->expects($this->once())
			   ->method('getMainPrimaryKey')
			   ->will($this->returnValue('AddressId'));

		$entity->expects($this->once())
			   ->method('getName')
			   ->will($this->returnValue('Address'));

		$assoc = $parser->createOneToManyAssociation($entity, $entity, $xml);

		$this->assertEquals($entity, $assoc->getEntity());
		$this->assertEquals('AddressId', $assoc->getKey());
		$this->assertEquals('AddressId', $assoc->getRefKey());
		$this->assertEquals('Tesssstes', $assoc->getName());
	}
}