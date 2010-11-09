<?php
require_once 'application/org.outlet-orm/core/OutletException.php';
require_once 'application/org.outlet-orm/config/OutletConfig.php';
require_once 'application/org.outlet-orm/config/OutletConnectionConfig.php';
require_once 'application/org.outlet-orm/config/OutletConfigException.php';
require_once 'application/org.outlet-orm/config/OutletXmlParserException.php';
require_once 'application/org.outlet-orm/config/OutletXmlParser.php';
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
	/**
	 * Tests OutletXmlParser->parseFromFile()
	 */
	public function testParseFromFile()
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
		
		$obj = $this->getMock('OutletXmlParser', array('parse'));
		$obj->setValidate(false);

		$obj->expects($this->once())
			->method('parse');
			
		$obj->parseFromFile($file);
		
		unlink($file);
	}

	/**
	 * Tests OutletXmlParser->parseFromString()
	 */
	public function testParseFromString()
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
		
		$obj = $this->getMock('OutletXmlParser', array('parse'));
		$obj->setValidate(false);

		$obj->expects($this->once())
			->method('parse');
			
		$obj->parseFromString($xmlString);
	}
	
	/**
	 * Tests OutletXmlParser->parseFromFile()
	 * 
	 * @expectedException OutletXmlParserException
	 */
	public function testParseFromFileException()
	{
		$xmlString = 'aa';
		
		$file = dirname(__FILE__) . '/tmp.xml';
		
		file_put_contents($file, $xmlString);
			
		$obj = $this->getMock('OutletXmlParser', array('parse'));
		$obj->setValidate(false);

		$obj->expects($this->never())
			->method('parse');

		try {
			$obj->parseFromFile($file);
			unlink($file);
		} catch (OutletXmlParserException $e) {
			unlink($file);
			throw $e;
		}
	}
	
	/**
	 * Tests OutletXmlParser->parseFromString()
	 * 
	 * @expectedException OutletXmlParserException
	 */
	public function testParseFromStringException()
	{
		$xmlString = 'aa';
		
		$obj = $this->getMock('OutletXmlParser', array('parse'));
		$obj->setValidate(false);

		$obj->expects($this->never())
			->method('parse');
			
		$obj->parseFromString($xmlString);
	}
	
	/**
	 *  Tests OutletXmlParser->validate()
	 *  
	 *  @dataProvider validateProvider
	 */
	public function testValidate($xmlString, $hasErrors)
	{
		$obj = new OutletXmlParser();
		
		try {
			$obj->setXmlObj(new SimpleXMLElement($xmlString));
			$obj->validate();
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
	 * Tests OutletXmlParser->parse()
	 */
	public function testParse()
	{
		$obj = $this->getMock('OutletXmlParser', array('validate', 'getConfigClass', 'parseConnection', 'parseClasses'));
		$con = $this->getMock('OutletConnectionConfig', array(), array(), '', false);
		$config = $this->getMock('OutletConfig', array('setConnectionConfig'));
		
		$obj->expects($this->once())
			->method('validate');
		
		$obj->expects($this->once())
			->method('getConfigClass')
			->will($this->returnValue($config));
		
		$obj->expects($this->once())
			->method('parseConnection')
			->will($this->returnValue($con));
		
		$obj->expects($this->once())
			->method('parseClasses')
			->with($config);
			
		$config->expects($this->once())
			   ->method('setConnectionConfig')
			   ->with($con);
			   
		$this->assertEquals($config, $obj->parse());
	}
	
	/**
	 * Tests OutletXmlParser->parse() when an object is not configured
	 * 
	 * @expectedException OutletXmlParserException
	 */
	public function testParseNoXmlConfigured()
	{
		$obj = new OutletXmlParser();
		$obj->parse();
	}
	
	/**
	 * Tests OutletXmlParser->parse()
	 * 
	 * @expectedException OutletXmlParserException
	 */
	public function testParseConnectionConfigErrors()
	{
		$obj = $this->getMock('OutletXmlParser', array('validate', 'getConfigClass', 'parseConnection', 'parseClasses'));
		$con = $this->getMock('OutletConnectionConfig', array(), array(), '', false);
		$config = $this->getMock('OutletConfig', array('setConnectionConfig'));
		
		$obj->expects($this->once())
			->method('validate');
		
		$obj->expects($this->once())
			->method('getConfigClass')
			->will($this->returnValue($config));
		
		$obj->expects($this->once())
			->method('parseConnection')
			->will($this->throwException(new OutletXmlParserException('sdfasdf')));
		
		$obj->expects($this->never())
			->method('parseClasses');
			
		$config->expects($this->never())
			   ->method('setConnectionConfig');
			   
		$obj->parse();
	}
	
	/**
	 * Tests OutletXmlParser->parse()
	 * 
	 * @expectedException OutletXmlParserException
	 */
	public function testParseClassParseErrors()
	{
		$obj = $this->getMock('OutletXmlParser', array('validate', 'getConfigClass', 'parseConnection', 'parseClasses'));
		$con = $this->getMock('OutletConnectionConfig', array(), array(), '', false);
		$config = $this->getMock('OutletConfig', array('setConnectionConfig'));
		
		$obj->expects($this->once())
			->method('validate');
		
		$obj->expects($this->once())
			->method('getConfigClass')
			->will($this->returnValue($config));
		
		$obj->expects($this->once())
			->method('parseConnection')
			->will($this->returnValue($con));
		
		$obj->expects($this->once())
			->method('parseClasses')
			->with($config)
			->will($this->throwException(new OutletXmlParserException('sdfasdf')));
			
		$config->expects($this->once())
			   ->method('setConnectionConfig')
			   ->with($con);
			   
		$this->assertEquals($config, $obj->parse());
	}
	
	/**
	 * Tests OutletXmlParser->parseConnection()
	 */
	public function testParseConnection()
	{
		$xml = new SimpleXMLElement(
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
			</outlet-config>'
		);
		
		$parser = new OutletXmlParser();
		$parser->setXmlObj($xml);
		
		$config = new OutletConnectionConfig('sqlite', 'sqlite::memory:');
		
		$this->assertEquals($config, $parser->parseConnection());
	}
	
	/**
	 * Tests OutletXmlParser->parseConnection()
	 * 
	 * @expectedException OutletXmlParserException
	 */
	public function testParseConnectionErrors()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
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
			</outlet-config>'
		);
		
		$parser = new OutletXmlParser();
		$parser->setXmlObj($xml);
		
		$parser->parseConnection();
	}
	
	/**
	 * Tests OutletXmlParser->parseClasses()
	 */
	public function testParseClassesNoAssociation()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
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
			</outlet-config>'
		);
		
		$parser = $this->getMock('OutletXmlParser', array('parseClass', 'parseAssociations'));
		$config = $this->getMock('OutletConfig', array('addEntity'));
		$entity = $this->getMock('OutletEntity', array(), array(), '', false);
		
		$parser->expects($this->once())
			   ->method('parseClass')
			   ->will($this->returnValue($entity));
		
		$parser->expects($this->never())
			   ->method('parseAssociations');
		
		$config->expects($this->once())
			   ->method('addEntity')
			   ->with($entity);
		
		$parser->setXmlObj($xml);
		$parser->parseClasses($config);
	}
	
	/**
	 * Tests OutletXmlParser->parseClasses()
	 */
	public function testParseClassesWithAssociation()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<outlet-config xmlns="http://www.outlet-orm.org" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.outlet-orm.org outlet-config.xsd ">
				<connection>
					<dialect>mysql</dialect>
					<dsn>mysql:host=myhost.com;dbname=testdb</dsn>
					<username>root</username>
					<password>admin</password>
				</connection>
				<classes>
					<class name="Address" table="address">
						<property name="AddressId" column="id" type="int" pk="true" autoIncrement="true" />
						<property name="Street" column="street" type="varchar" />
					</class>
					<class name="User" table="user">
						<property name="UserId" column="id" type="int" pk="true" autoIncrement="true" />
						<property name="FirstName" column="first_name" type="varchar" />
						<property name="LastName" column="last_name" type="varchar" />
						<property name="AddressId" column="address_id" type="int" />
						<association type="many-to-one" classReference="Address" key="AddressId"/>
					</class>
				</classes>
			</outlet-config>'
		);
		
		$parser = $this->getMock('OutletXmlParser', array('parseClass', 'parseAssociations'));
		$config = $this->getMock('OutletConfig', array('addEntity'));
		$entity = $this->getMock('OutletEntity', array(), array(), '', false);
		
		$parser->expects($this->exactly(2))
			   ->method('parseClass')
			   ->will($this->returnValue($entity));
		
		$parser->expects($this->once())
			   ->method('parseAssociations');
		
		$config->expects($this->exactly(2))
			   ->method('addEntity')
			   ->with($entity);
		
		$parser->setXmlObj($xml);
		$parser->parseClasses($config);
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
		
		$parser = $this->getMock('OutletXmlParser', array('parseProperty'));
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
		
		$parser = $this->getMock('OutletXmlParser', array('parseProperty'));
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
		
		$parser = new OutletXmlParser();

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
		
		$parser = new OutletXmlParser();

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
		
		$parser = new OutletXmlParser();

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
		
		$parser = new OutletXmlParser();

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
		
		$parser = $this->getMock('OutletXmlParser', array('parseAssociation'));
		$config = $this->getMock('OutletConfig', array('getEntity'));
		$entity = $this->getMock('OutletEntity', array('addAssociation'), array(), '', false);
		$assoc = $this->getMock('OutletAssociation');
		
		$parser->expects($this->exactly(2))
			   ->method('parseAssociation')
			   ->will($this->returnValue($assoc));
			   
		$config->expects($this->exactly(2))
			   ->method('getEntity')
			   ->with('User')
			   ->will($this->returnValue($entity));
			   
		$entity->expects($this->exactly(2))
			   ->method('addAssociation')
			   ->with($assoc);
			   
		$parser->parseAssociations($config, $xml);
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
		
		$parser = $this->getMock('OutletXmlParser', array('createManyToOneAssociation', 'createManyToManyAssociation', 'createOneToOneAssociation', 'createOneToManyAssociation'));
		$config = $this->getMock('OutletConfig');
		$entity = $this->getMock('OutletEntity', array(), array(), '', false);
		$assoc = $this->getMock('OutletAssociation');
		
		$parser->expects($this->once())
			   ->method('createManyToOneAssociation')
			   ->with($config, $xml)
			   ->will($this->returnValue($assoc));
		
		$parser->expects($this->never())
			   ->method('createManyToManyAssociation');
		
		$parser->expects($this->never())
			   ->method('createOneToOneAssociation');
		
		$parser->expects($this->never())
			   ->method('createOneToManyAssociation');
			   
		$this->assertEquals($assoc, $parser->parseAssociation($config, $entity, $xml));
	}
	
	/**
	 * Tests OutletXmlParser->parseAssociation()
	 * 
	 * @expectedException OutletXmlParserException
	 */
	public function testParseAssociationManyToOneError()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<association type="many-to-one" classReference="Address" key="AddressId"/>'
		);
		
		$parser = $this->getMock('OutletXmlParser', array('createManyToOneAssociation', 'createManyToManyAssociation', 'createOneToOneAssociation', 'createOneToManyAssociation'));
		$config = $this->getMock('OutletConfig');
		$entity = $this->getMock('OutletEntity', array(), array(), '', false);
		$assoc = $this->getMock('OutletAssociation');
		
		$parser->expects($this->once())
			   ->method('createManyToOneAssociation')
			   ->with($config, $xml)
			   ->will($this->throwException(new OutletXmlParserException('blablabla')));
		
		$parser->expects($this->never())
			   ->method('createManyToManyAssociation');
		
		$parser->expects($this->never())
			   ->method('createOneToOneAssociation');
		
		$parser->expects($this->never())
			   ->method('createOneToManyAssociation');
			   
		$parser->parseAssociation($config, $entity, $xml);
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
		
		$parser = $this->getMock('OutletXmlParser', array('createManyToOneAssociation', 'createManyToManyAssociation', 'createOneToOneAssociation', 'createOneToManyAssociation'));
		$config = $this->getMock('OutletConfig');
		$entity = $this->getMock('OutletEntity', array(), array(), '', false);
		$assoc = $this->getMock('OutletAssociation');
		
		$parser->expects($this->never())
			   ->method('createManyToOneAssociation');
		
		$parser->expects($this->once())
			   ->method('createManyToManyAssociation')
			   ->with($config, $entity, $xml)
			   ->will($this->returnValue($assoc));
		
		$parser->expects($this->never())
			   ->method('createOneToOneAssociation');
		
		$parser->expects($this->never())
			   ->method('createOneToManyAssociation');
			   
		$this->assertEquals($assoc, $parser->parseAssociation($config, $entity, $xml));
	}
	
	/**
	 * Tests OutletXmlParser->parseAssociation()
	 * 
	 * @expectedException OutletXmlParserException
	 */
	public function testParseAssociationManyToManyError()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<association type="many-to-many" classReference="Address" key="AddressId"/>'
		);
		
		$parser = $this->getMock('OutletXmlParser', array('createManyToOneAssociation', 'createManyToManyAssociation', 'createOneToOneAssociation', 'createOneToManyAssociation'));
		$config = $this->getMock('OutletConfig');
		$entity = $this->getMock('OutletEntity', array(), array(), '', false);
		$assoc = $this->getMock('OutletAssociation');
		
		$parser->expects($this->never())
			   ->method('createManyToOneAssociation');
		
		$parser->expects($this->once())
			   ->method('createManyToManyAssociation')
			   ->with($config, $entity, $xml)
			   ->will($this->throwException(new OutletXmlParserException('blablabla')));
		
		$parser->expects($this->never())
			   ->method('createOneToOneAssociation');
		
		$parser->expects($this->never())
			   ->method('createOneToManyAssociation');
			   
		$parser->parseAssociation($config, $entity, $xml);
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
		
		$parser = $this->getMock('OutletXmlParser', array('createManyToOneAssociation', 'createManyToManyAssociation', 'createOneToOneAssociation', 'createOneToManyAssociation'));
		$config = $this->getMock('OutletConfig');
		$entity = $this->getMock('OutletEntity', array(), array(), '', false);
		$assoc = $this->getMock('OutletAssociation');
		
		$parser->expects($this->never())
			   ->method('createManyToOneAssociation');
		
		$parser->expects($this->never())
			   ->method('createManyToManyAssociation');
		
		$parser->expects($this->once())
			   ->method('createOneToOneAssociation')
			   ->with($config, $xml)
			   ->will($this->returnValue($assoc));
		
		$parser->expects($this->never())
			   ->method('createOneToManyAssociation');
			   
		$this->assertEquals($assoc, $parser->parseAssociation($config, $entity, $xml));
	}
	
	/**
	 * Tests OutletXmlParser->parseAssociation()
	 * 
	 * @expectedException OutletXmlParserException
	 */
	public function testParseAssociationOneToOneError()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<association type="one-to-one" classReference="Address" key="AddressId"/>'
		);
		
		$parser = $this->getMock('OutletXmlParser', array('createManyToOneAssociation', 'createManyToManyAssociation', 'createOneToOneAssociation', 'createOneToManyAssociation'));
		$config = $this->getMock('OutletConfig');
		$entity = $this->getMock('OutletEntity', array(), array(), '', false);
		$assoc = $this->getMock('OutletAssociation');
		
		$parser->expects($this->never())
			   ->method('createManyToOneAssociation');
		
		$parser->expects($this->never())
			   ->method('createManyToManyAssociation');
		
		$parser->expects($this->once())
			   ->method('createOneToOneAssociation')
			   ->with($config, $xml)
			   ->will($this->throwException(new OutletXmlParserException('blablabla')));
		
		$parser->expects($this->never())
			   ->method('createOneToManyAssociation');
			   
		$parser->parseAssociation($config, $entity, $xml);
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
		
		$parser = $this->getMock('OutletXmlParser', array('createManyToOneAssociation', 'createManyToManyAssociation', 'createOneToOneAssociation', 'createOneToManyAssociation'));
		$config = $this->getMock('OutletConfig');
		$entity = $this->getMock('OutletEntity', array(), array(), '', false);
		$assoc = $this->getMock('OutletAssociation');
		
		$parser->expects($this->never())
			   ->method('createManyToOneAssociation');
		
		$parser->expects($this->never())
			   ->method('createManyToManyAssociation');
		
		$parser->expects($this->never())
			   ->method('createOneToOneAssociation');
		
		$parser->expects($this->once())
			   ->method('createOneToManyAssociation')
			   ->with($config, $entity, $xml)
			   ->will($this->returnValue($assoc));
			   
		$this->assertEquals($assoc, $parser->parseAssociation($config, $entity, $xml));
	}
	
	/**
	 * Tests OutletXmlParser->parseAssociation()
	 * 
	 * @expectedException OutletXmlParserException
	 */
	public function testParseAssociationOneToManyError()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<association type="one-to-many" classReference="Address" key="AddressId"/>'
		);
		
		$parser = $this->getMock('OutletXmlParser', array('createManyToOneAssociation', 'createManyToManyAssociation', 'createOneToOneAssociation', 'createOneToManyAssociation'));
		$config = $this->getMock('OutletConfig');
		$entity = $this->getMock('OutletEntity', array(), array(), '', false);
		$assoc = $this->getMock('OutletAssociation');
		
		$parser->expects($this->never())
			   ->method('createManyToOneAssociation');
		
		$parser->expects($this->never())
			   ->method('createManyToManyAssociation');
		
		$parser->expects($this->never())
			   ->method('createOneToOneAssociation');
		
		$parser->expects($this->once())
			   ->method('createOneToManyAssociation')
			   ->with($config, $entity, $xml)
			   ->will($this->throwException(new OutletXmlParserException('blablabla')));
			   
		$parser->parseAssociation($config, $entity, $xml);
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
		
		$parser = new OutletXmlParser();
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
		
		$parser = new OutletXmlParser();
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
		
		$parser = new OutletXmlParser();
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
		
		$parser = new OutletXmlParser();
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
		
		$parser = new OutletXmlParser();
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
		
		$parser = new OutletXmlParser();
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
		
		$parser = new OutletXmlParser();
		$parser->validateAssociationConfig($xml);
	}
	
	/**
	 * Tests OutletXmlParser->createManyToOneAssociation()
	 * 
	 * @expectedException OutletXmlParserException
	 */
	public function testCreateManyToOneAssociationValidationError()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<association type="many-to-one" classReference="Address" key="AddressId"/>'
		);
		
		$parser = $this->getMock('OutletXmlParser', array('validateAssociationConfig'));
		$config = $this->getMock('OutletConfig');
		
		$parser->expects($this->once())
			   ->method('validateAssociationConfig')
			   ->with($xml)
			   ->will($this->throwException(new OutletXmlParserException('blablabla')));
			   
		$assoc = $parser->createManyToOneAssociation($config, $xml);
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
		
		$parser = $this->getMock('OutletXmlParser', array('validateAssociationConfig'));
		$config = $this->getMock('OutletConfig', array('getEntity'));
		$entity = $this->getMock('OutletEntity', array('getMainPrimaryKey', 'getName'), array(), '', false);
		
		$config->expects($this->once())
			   ->method('getEntity')
			   ->with('Address')
			   ->will($this->returnValue($entity));
		
		$entity->expects($this->once())
			   ->method('getMainPrimaryKey')
			   ->will($this->returnValue('id'));
		
		$entity->expects($this->once())
			   ->method('getName')
			   ->will($this->returnValue('Address'));
		
		$parser->expects($this->once())
			   ->method('validateAssociationConfig')
			   ->with($xml);
			   
		$assoc = $parser->createManyToOneAssociation($config, $xml);
		
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
		
		$parser = $this->getMock('OutletXmlParser', array('validateAssociationConfig'));
		$config = $this->getMock('OutletConfig', array('getEntity'));
		$entity = $this->getMock('OutletEntity', array('getMainPrimaryKey', 'getName'), array(), '', false);
		
		$config->expects($this->once())
			   ->method('getEntity')
			   ->with('Address')
			   ->will($this->returnValue($entity));
		
		$entity->expects($this->once())
			   ->method('getMainPrimaryKey')
			   ->will($this->returnValue('id'));
		
		$entity->expects($this->once())
			   ->method('getName')
			   ->will($this->returnValue('Address'));
		
		$parser->expects($this->once())
			   ->method('validateAssociationConfig')
			   ->with($xml);
			   
		$assoc = $parser->createManyToOneAssociation($config, $xml);
		
		$this->assertEquals($entity, $assoc->getEntity());
		$this->assertEquals('AddressId', $assoc->getKey());
		$this->assertEquals('id', $assoc->getRefKey());
		$this->assertEquals('Blablabla', $assoc->getName());
		$this->assertEquals(true, $assoc->isOptional());
	}
	
	/**
	 * Tests OutletXmlParser->createManyToManyAssociation()
	 * 
	 * @expectedException OutletXmlParserException
	 */
	public function testCreateManyToManyAssociationValidationError()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<association type="many-to-many" classReference="Address"/>'
		);
		
		$parser = $this->getMock('OutletXmlParser', array('validateAssociationConfig'));
		$config = $this->getMock('OutletConfig');
		$entity = $this->getMock('OutletEntity', array('getMainPrimaryKey', 'getName'), array(), '', false);
		
		$parser->expects($this->once())
			   ->method('validateAssociationConfig')
			   ->with($xml)
			   ->will($this->throwException(new OutletXmlParserException('blablabla')));
			   
		$assoc = $parser->createManyToManyAssociation($config, $entity, $xml);
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
		
		$parser = $this->getMock('OutletXmlParser', array('validateAssociationConfig'));
		$config = $this->getMock('OutletConfig');
		$entity = $this->getMock('OutletEntity', array('getMainPrimaryKey', 'getPlural'), array(), '', false);
		
		$parser->expects($this->once())
			   ->method('validateAssociationConfig')
			   ->with($xml);
		
		$config->expects($this->once())
			   ->method('getEntity')
			   ->with('Address')
			   ->will($this->returnValue($entity));
		
		$entity->expects($this->exactly(2))
			   ->method('getMainPrimaryKey')
			   ->will($this->returnValue('AddressId'));
		
		$entity->expects($this->once())
			   ->method('getPlural')
			   ->will($this->returnValue('Addresses'));
			   
		$assoc = $parser->createManyToManyAssociation($config, $entity, $xml);
		
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
		
		$parser = $this->getMock('OutletXmlParser', array('validateAssociationConfig'));
		$config = $this->getMock('OutletConfig');
		$entity = $this->getMock('OutletEntity', array('getMainPrimaryKey', 'getPlural'), array(), '', false);
		
		$parser->expects($this->once())
			   ->method('validateAssociationConfig')
			   ->with($xml);
		
		$config->expects($this->once())
			   ->method('getEntity')
			   ->with('Address')
			   ->will($this->returnValue($entity));
		
		$entity->expects($this->exactly(2))
			   ->method('getMainPrimaryKey')
			   ->will($this->returnValue('AddressId'));
		
		$entity->expects($this->once())
			   ->method('getPlural')
			   ->will($this->returnValue('Addresses'));
			   
		$assoc = $parser->createManyToManyAssociation($config, $entity, $xml);
		
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
	 * 
	 * @expectedException OutletXmlParserException
	 */
	public function testCreateOneToOneAssociationValidationError()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<association type="one-to-one" classReference="Address" key="AddressId"/>'
		);
		
		$parser = $this->getMock('OutletXmlParser', array('validateAssociationConfig'));
		$config = $this->getMock('OutletConfig');
		
		$parser->expects($this->once())
			   ->method('validateAssociationConfig')
			   ->with($xml)
			   ->will($this->throwException(new OutletXmlParserException('blablabla')));
			   
		$assoc = $parser->createOneToOneAssociation($config, $xml);
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
		
		$parser = $this->getMock('OutletXmlParser', array('validateAssociationConfig'));
		$config = $this->getMock('OutletConfig', array('getEntity'));
		$entity = $this->getMock('OutletEntity', array('getMainPrimaryKey', 'getName'), array(), '', false);
		
		$config->expects($this->once())
			   ->method('getEntity')
			   ->with('Address')
			   ->will($this->returnValue($entity));
		
		$entity->expects($this->once())
			   ->method('getMainPrimaryKey')
			   ->will($this->returnValue('id'));
		
		$entity->expects($this->once())
			   ->method('getName')
			   ->will($this->returnValue('Address'));
		
		$parser->expects($this->once())
			   ->method('validateAssociationConfig')
			   ->with($xml);
			   
		$assoc = $parser->createOneToOneAssociation($config, $xml);
		
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
		
		$parser = $this->getMock('OutletXmlParser', array('validateAssociationConfig'));
		$config = $this->getMock('OutletConfig', array('getEntity'));
		$entity = $this->getMock('OutletEntity', array('getMainPrimaryKey', 'getName'), array(), '', false);
		
		$config->expects($this->once())
			   ->method('getEntity')
			   ->with('Address')
			   ->will($this->returnValue($entity));
		
		$entity->expects($this->once())
			   ->method('getMainPrimaryKey')
			   ->will($this->returnValue('id'));
		
		$entity->expects($this->once())
			   ->method('getName')
			   ->will($this->returnValue('Address'));
		
		$parser->expects($this->once())
			   ->method('validateAssociationConfig')
			   ->with($xml);
			   
		$assoc = $parser->createOneToOneAssociation($config, $xml);
		
		$this->assertEquals($entity, $assoc->getEntity());
		$this->assertEquals('AddressId', $assoc->getKey());
		$this->assertEquals('id', $assoc->getRefKey());
		$this->assertEquals('Blablabla', $assoc->getName());
		$this->assertEquals(true, $assoc->isOptional());
	}
	
	/**
	 * Tests OutletXmlParser->createOneToManyAssociation()
	 * 
	 * @expectedException OutletXmlParserException
	 */
	public function testCreateOneToManyAssociationValidationError()
	{
		$xml = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>
			<association type="one-to-many" classReference="Address" key="AddressId"/>'
		);
		
		$parser = $this->getMock('OutletXmlParser', array('validateAssociationConfig'));
		$config = $this->getMock('OutletConfig');
		$entity = $this->getMock('OutletEntity', array('getMainPrimaryKey', 'getName'), array(), '', false);
		
		$parser->expects($this->once())
			   ->method('validateAssociationConfig')
			   ->with($xml)
			   ->will($this->throwException(new OutletXmlParserException('blablabla')));
			   
		$assoc = $parser->createOneToManyAssociation($config, $entity, $xml);
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
		
		$parser = $this->getMock('OutletXmlParser', array('validateAssociationConfig'));
		$config = $this->getMock('OutletConfig');
		$entity = $this->getMock('OutletEntity', array('getMainPrimaryKey', 'getName'), array(), '', false);
		
		$parser->expects($this->once())
			   ->method('validateAssociationConfig')
			   ->with($xml);
		
		$config->expects($this->once())
			   ->method('getEntity')
			   ->with('Address')
			   ->will($this->returnValue($entity));
		
		$entity->expects($this->once())
			   ->method('getMainPrimaryKey')
			   ->will($this->returnValue('AddressId'));
		
		$entity->expects($this->once())
			   ->method('getName')
			   ->will($this->returnValue('Address'));
			   
		$assoc = $parser->createOneToManyAssociation($config, $entity, $xml);
		
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
		
		$parser = $this->getMock('OutletXmlParser', array('validateAssociationConfig'));
		$config = $this->getMock('OutletConfig');
		$entity = $this->getMock('OutletEntity', array('getMainPrimaryKey', 'getName'), array(), '', false);
		
		$parser->expects($this->once())
			   ->method('validateAssociationConfig')
			   ->with($xml);
		
		$config->expects($this->once())
			   ->method('getEntity')
			   ->with('Address')
			   ->will($this->returnValue($entity));
		
		$entity->expects($this->once())
			   ->method('getMainPrimaryKey')
			   ->will($this->returnValue('AddressId'));
		
		$entity->expects($this->once())
			   ->method('getName')
			   ->will($this->returnValue('Address'));
			   
		$assoc = $parser->createOneToManyAssociation($config, $entity, $xml);
		
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
		
		$parser = $this->getMock('OutletXmlParser', array('validateAssociationConfig'));
		$config = $this->getMock('OutletConfig');
		$entity = $this->getMock('OutletEntity', array('getMainPrimaryKey', 'getName'), array(), '', false);
		
		$parser->expects($this->once())
			   ->method('validateAssociationConfig')
			   ->with($xml);
		
		$config->expects($this->once())
			   ->method('getEntity')
			   ->with('Address')
			   ->will($this->returnValue($entity));
		
		$entity->expects($this->once())
			   ->method('getMainPrimaryKey')
			   ->will($this->returnValue('AddressId'));
		
		$entity->expects($this->once())
			   ->method('getName')
			   ->will($this->returnValue('Address'));
			   
		$assoc = $parser->createOneToManyAssociation($config, $entity, $xml);
		
		$this->assertEquals($entity, $assoc->getEntity());
		$this->assertEquals('AddressId', $assoc->getKey());
		$this->assertEquals('AddressId', $assoc->getRefKey());
		$this->assertEquals('Tesssstes', $assoc->getName());
	}
}