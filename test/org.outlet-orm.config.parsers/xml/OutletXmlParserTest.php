<?php
require_once 'application/org.outlet-orm/core/OutletException.php';
require_once 'application/org.outlet-orm/config/OutletConfig.php';
require_once 'application/org.outlet-orm/config/OutletConnectionConfig.php';
require_once 'application/org.outlet-orm/config/OutletConfigException.php';
require_once 'application/org.outlet-orm/config/OutletConfigParser.php';
require_once 'application/org.outlet-orm.config.parsers/xml/OutletXmlParser.php';
require_once 'application/org.outlet-orm.config.parsers/xml/OutletXmlParserException.php';
require_once 'application/org.outlet-orm/entity/OutletEmbeddableEntity.php';
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

	public function testCreateEntityConfigWithoutMocks()
	{
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
			<outlet-config xmlns="http://www.outlet-orm.org" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.outlet-orm.org outlet-config.xsd ">
				<connection>
					<dialect>mysql</dialect>
					<dsn>sqlite::memory:</dsn>
				</connection>
				<classes>
					<class name="Address" table="addresses" plural="Addresses">
						<property name="AddressId" column="id" type="int" pk="true" autoIncrement="true"/>
						<property name="UserID" column="user_id" type="int"/>
						<property name="Street" column="street" type="varchar"/>
					</class>
					<class name="Bug" table="bugs" sequenceName="bugsIdSeq">
						<property name="ID" column="id" type="int" pk="true" autoIncrement="true"/>
						<property name="Title" column="title" type="varchar"/>
						<property name="ProjectID" column="project_id" type="int"/>
						<property name="TimeToFix" column="time_to_fix" type="float" default="2000.000001"/>
						<property name="Test_One" column="test_one" type="int"/>
						<association type="many-to-one" classReference="Project" key="ProjectID"/>
					</class>
					<class name="Machine" table="machines">
						<property name="Name" column="name" type="varchar" pk="true"/>
						<property name="Description" column="description" type="varchar"/>
					</class>
					<class name="Project" table="projects">
						<property name="ProjectID" column="id" type="int" pk="true" autoIncrement="true"/>
						<property name="Name" column="name" type="varchar"/>
						<property name="CreatedDate" column="created_date" type="datetime" defaultExpr="NOW()"/>
						<property name="StatusID" column="status_id" type="int" default="1"/>
						<property name="Description" column="description" type="varchar" default="Default Description"/>
						<association type="one-to-many" classReference="Bug" key="ProjectID"/>
					</class>
					<class name="User" table="users">
						<property name="UserID" column="id" type="int" pk="true" autoIncrement="true"/>
						<property name="FirstName" column="first_name" type="varchar"/>
						<property name="LastName" column="last_name" type="varchar"/>
						<association type="one-to-many" classReference="Address" key="UserID" name="WorkAddress" plural="WorkAddresses"/>
						<association type="many-to-many" classReference="Bug" table="watchers" tableKeyLocal="user_id" tableKeyForeign="bug_id"/>
					</class>
					<class name="Profile" table="profiles">
						<property name="ProfileID" column="id" type="int" pk="true" autoIncrement="true"/>
						<property name="UserID" column="user_id" type="int"/>
						<association type="one-to-one" classReference="User" key="UserID" optional="true"/>
					</class>
				</classes>
			</outlet-config>';

		$parser = new OutletXmlParser($xml);

		$address = new OutletEntity('Address', 'addresses', 'Addresses');
		$address->addProperty(new OutletEntityProperty('AddressId', 'id', 'int', true, true));
		$address->addProperty(new OutletEntityProperty('UserID', 'user_id', 'int'));
		$address->addProperty(new OutletEntityProperty('Street', 'street', 'varchar'));

		$machine = new OutletEntity('Machine', 'machines');
		$machine->addProperty(new OutletEntityProperty('Name', 'name', 'varchar', true));
		$machine->addProperty(new OutletEntityProperty('Description', 'description', 'varchar'));

		$project = new OutletEntity('Project', 'projects');
		$project->addProperty(new OutletEntityProperty('ProjectID', 'id', 'int', true, true));
		$project->addProperty(new OutletEntityProperty('Name', 'name', 'varchar'));
		$project->addProperty(new OutletEntityProperty('CreatedDate', 'created_date', 'datetime', false, false, null, 'NOW()'));
		$project->addProperty(new OutletEntityProperty('StatusID', 'status_id', 'int', false, false, '1'));
		$project->addProperty(new OutletEntityProperty('Description', 'description', 'varchar', false, false, 'Default Description'));

		$bug = new OutletEntity('Bug', 'bugs', null, 'bugsIdSeq');
		$bug->addProperty(new OutletEntityProperty('ID', 'id', 'int', true, true));
		$bug->addProperty(new OutletEntityProperty('Title', 'title', 'varchar'));
		$bug->addProperty(new OutletEntityProperty('ProjectID', 'project_id', 'int'));
		$bug->addProperty(new OutletEntityProperty('TimeToFix', 'time_to_fix', 'float', false, false, '2000.000001'));
		$bug->addProperty(new OutletEntityProperty('Test_One', 'test_one', 'int'));
		$bug->addAssociation(new OutletManyToOneAssociation($project, 'ProjectID', 'ProjectID'));

		$project->addAssociation(new OutletOneToManyAssociation($bug, 'ProjectID', 'ProjectID'));

		$user = new OutletEntity('User', 'users');
		$user->addProperty(new OutletEntityProperty('UserID', 'id', 'int', true, true));
		$user->addProperty(new OutletEntityProperty('FirstName', 'first_name', 'varchar'));
		$user->addProperty(new OutletEntityProperty('LastName', 'last_name', 'varchar'));
		$user->addAssociation(new OutletOneToManyAssociation($address, 'UserID', 'UserID', 'WorkAddresses'));
		$user->addAssociation(new OutletManyToManyAssociation($bug, 'ID', 'UserID', 'watchers', 'user_id', 'bug_id'));

		$profile = new OutletEntity('Profile', 'profiles');
		$profile->addProperty(new OutletEntityProperty('ProfileID', 'id', 'int', true, true));
		$profile->addProperty(new OutletEntityProperty('UserID', 'user_id', 'int'));
		$profile->addAssociation(new OutletOneToOneAssociation($user, 'UserID', 'UserID', null, true));

		$this->assertEquals($address, $parser->getConfig()->getEntity('Address'));
		$this->assertEquals($machine, $parser->getConfig()->getEntity('Machine'));
		$this->assertEquals($project, $parser->getConfig()->getEntity('Project'));
		$this->assertEquals($bug, $parser->getConfig()->getEntity('Bug'));
		$this->assertEquals($user, $parser->getConfig()->getEntity('User'));
		$this->assertEquals($profile, $parser->getConfig()->getEntity('Profile'));
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

		$parser = $this->getParserMock();
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

		$parser = $this->getParserMock();
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

		$parser = $this->getParserMock();
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

		$parser = $this->getParserMock();
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

		$parser = $this->getParserMock();
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

		$parser = $this->getParserMock();
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

		$parser = $this->getParserMock();
		$parser->validateAssociationConfig($xml);
	}

	/**
	 * @return OutletXmlParser
	 */
	protected function getParserMock()
	{
		$parser = null;
		eval('if (!class_exists("OutletXmlParser_Mock")) { class OutletXmlParser_Mock extends OutletXmlParser { public function __construct() {} public function validateAssociationConfig(SimpleXMLElement $assoc) { return parent::validateAssociationConfig($assoc); } } }');
		eval('$parser = new OutletXmlParser_Mock();');

		return $parser;
	}
}