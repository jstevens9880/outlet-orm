<?php
require_once 'application/org.outlet-orm/core/OutletException.php';
require_once 'application/org.outlet-orm/config/OutletConfig.php';
require_once 'application/org.outlet-orm/config/OutletConnectionConfig.php';
require_once 'application/org.outlet-orm/config/OutletConfigException.php';
require_once 'application/org.outlet-orm/config/OutletConfigParser.php';
require_once 'application/org.outlet-orm.config.parsers/array/OutletArrayParser.php';
require_once 'application/org.outlet-orm.config.parsers/array/OutletArrayParserException.php';
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
 * OutletArrayParser test case.
 */
class OutletArrayParserTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests OutletArrayParser->__construct()
	 */
	public function test__construct()
	{
		$source = array(
			'connection' => array(
				'dsn' => 'mysql:host=myserver.com;dbname=mydb',
				'username' => 'user',
				'password' => 'pass',
				'dialect'  => 'mysql'
			),
			'classes' => array(
				'TestClass' => array()
			)
		);

		$parser = new OutletArrayParser($source);

		$this->assertEquals($source, $parser->getSource());
	}

	/**
	 * Tests OutletArrayParser->__construct()
	 *
	 * @expectedException OutletArrayParserException
	 */
	public function test__constructWithoutDialect()
	{
		$source = array(
			'connection' => array(
				'dsn' => 'mysql:host=myserver.com;dbname=mydb',
				'username' => 'user',
				'password' => 'pass',
			),
			'classes' => array(
				'TestClass' => array()
			)
		);

		$parser = new OutletArrayParser($source);

		$this->assertEquals($source, $parser->getSource());
	}

	/**
	 * Tests OutletArrayParser->__construct()
	 *
	 * @expectedException OutletArrayParserException
	 */
	public function test__constructWithEmptyClasses()
	{
		$source = array(
			'connection' => array(
				'dsn' => 'mysql:host=myserver.com;dbname=mydb',
				'username' => 'user',
				'password' => 'pass',
				'dialect'  => 'mysql'
			),
			'classes' => array(
			)
		);

		$parser = new OutletArrayParser($source);

		$this->assertEquals($source, $parser->getSource());
	}

	/**
	 * Tests OutletArrayParser->__construct()
	 *
	 * @expectedException OutletArrayParserException
	 */
	public function test__constructWithoutClasses()
	{
		$source = array(
			'connection' => array(
				'dsn' => 'mysql:host=myserver.com;dbname=mydb',
				'username' => 'user',
				'password' => 'pass',
				'dialect'  => 'mysql'
			)
		);

		$parser = new OutletArrayParser($source);

		$this->assertEquals($source, $parser->getSource());
	}

	/**
	 * Tests OutletArrayParser->__construct()
	 *
	 * @expectedException OutletArrayParserException
	 */
	public function test__constructWithEmptyConnection()
	{
		$source = array(
			'connection' => array(
			)
		);

		$parser = new OutletArrayParser($source);

		$this->assertEquals($source, $parser->getSource());
	}

	/**
	 * Tests OutletArrayParser->__construct()
	 *
	 * @expectedException OutletArrayParserException
	 */
	public function test__constructWithNonPdoInstance()
	{
		$source = array(
			'connection' => array(
				'pdo' => 'sdfhjkas'
			)
		);

		$parser = new OutletArrayParser($source);

		$this->assertEquals($source, $parser->getSource());
	}

	/**
	 * Tests OutletArrayParser->__construct()
	 *
	 * @expectedException OutletArrayParserException
	 */
	public function test__constructWithEmptySource()
	{
		$source = array();

		$parser = new OutletArrayParser($source);

		$this->assertEquals($source, $parser->getSource());
	}

	/**
	 * Tests OutletArrayParser->getConfig()
	 */
	public function testGetConfig()
	{
		$source = array(
			'connection' => array(
				'dsn' => 'mysql:host=myserver.com;dbname=mydb',
				'username' => 'user',
				'password' => 'pass',
				'dialect'  => 'mysql'
			),
			'classes' => array(
				'TestClass' => array()
			)
		);

		$parser = new OutletArrayParser($source);
		$config = $parser->getConfig();

		$this->assertTrue($config === $parser->getConfig());
	}

	/**
	 * Tests OutletArrayParser->createConnectionConfig()
	 */
	public function testCreateConnectionConfig()
	{
		$source = array(
			'connection' => array(
				'dsn' => 'mysql:host=myserver.com;dbname=mydb',
				'username' => 'user',
				'password' => 'pass',
				'dialect'  => 'mysql'
			),
			'classes' => array(
				'TestClass' => array()
			)
		);

		$expectedConfig = new OutletConnectionConfig('mysql', 'mysql:host=myserver.com;dbname=mydb', 'user', 'pass');

		$parser = new OutletArrayParser($source);
		$parser->createConnectionConfig();

		$this->assertEquals($expectedConfig, $parser->getConfig()->getConnectionConfig());
	}

	/**
	 * Tests OutletArrayParser->createConnectionConfig()
	 */
	public function testCreateConnectionConfigWithPdo()
	{
		$pdo = $this->getMock('PDO', array(), array('sqlite::memory:'));
		$source = array(
			'connection' => array(
				'dialect'  => 'mysql',
				'pdo' => $pdo
			),
			'classes' => array(
				'TestClass' => array()
			)
		);

		$expectedConfig = new OutletConnectionConfig('mysql', null, null, null, $pdo);

		$parser = new OutletArrayParser($source);
		$parser->createConnectionConfig();

		$this->assertEquals($expectedConfig, $parser->getConfig()->getConnectionConfig());
	}

	/**
	 * Tests OutletArrayParser->entityExists()
	 */
	public function testEntityExists()
	{
		$source = array(
			'connection' => array(
				'dsn' => 'mysql:host=myserver.com;dbname=mydb',
				'username' => 'user',
				'password' => 'pass',
				'dialect'  => 'mysql'
			),
			'classes' => array(
				'TestClass' => array()
			)
		);

		$parser = new OutletArrayParser($source);

		$this->assertTrue($parser->entityExists('TestClass'));
	}

	/**
	 * Tests OutletArrayParser->entityExists()
	 */
	public function testEntityExistsWithEmbedded()
	{
		$source = array(
			'connection' => array(
				'dsn' => 'mysql:host=myserver.com;dbname=mydb',
				'username' => 'user',
				'password' => 'pass',
				'dialect'  => 'mysql'
			),
			'classes' => array(
				'TestClass' => array()
			),
			'embeddables' => array(
				'TestClass3' => array()
			)
		);

		$parser = new OutletArrayParser($source);

		$this->assertTrue($parser->entityExists('TestClass3'));
	}

	/**
	 * Tests OutletArrayParser->entityExists()
	 */
	public function testEntityExistsNoExistingClass()
	{
		$source = array(
			'connection' => array(
				'dsn' => 'mysql:host=myserver.com;dbname=mydb',
				'username' => 'user',
				'password' => 'pass',
				'dialect'  => 'mysql'
			),
			'classes' => array(
				'TestClass' => array()
			)
		);

		$parser = new OutletArrayParser($source);

		$this->assertFalse($parser->entityExists('TestClass2'));
	}

	/**
	 * Tests OutletArrayParser->createEntityConfig
	 *
	 * @expectedException OutletArrayParserException
	 */
	public function testCreateEntityConfigNonExistantEntity()
	{
		$source = array(
			'connection' => array(
				'dsn' => 'mysql:host=myserver.com;dbname=mydb',
				'username' => 'user',
				'password' => 'pass',
				'dialect'  => 'mysql'
			),
			'classes' => array(
				'TestClass' => array()
			)
		);

		$parser = new OutletArrayParser($source);

		$parser->createEntityConfig('TestClass2');
	}

	/**
	 * Tests OutletArrayParser->createEntityConfig
	 */
	public function testCreateEntityConfig()
	{
		$source = array(
			'connection' => array(
				'dsn' => 'mysql:host=myserver.com;dbname=mydb',
				'username' => 'user',
				'password' => 'pass',
				'dialect'  => 'mysql'
			),
			'classes' => array(
				'Project' => array(
					'table' => 'projects',
					'props' => array(
						'ID' => array('id', 'int', array('pk' => true, 'autoIncrement' => true)),
						'Name' => array('name', 'varchar')
					)
				),
				'Bug' => array(
					'table' => 'bugs',
					'props' => array(
						'ID' => array('id', 'int', array('pk' => true, 'autoIncrement' => true)),
						'Title' => array('title', 'varchar'),
						'ProjectID' => array('project_id', 'int'),
						'Description' => array('description', 'varchar')
					),
					'associations' => array(
						array('many-to-one', 'Project', array('key' => 'ProjectID'))
					)
				)
			)
		);

		$parser = new OutletArrayParser($source);

		$this->assertEquals(0, count($parser->getConfig()->getEntities()));

		$parser->createEntityConfig('Project');
		$this->assertEquals(1, count($parser->getConfig()->getEntities()));

		$parser->createEntityConfig('Bug');
		$this->assertEquals(2, count($parser->getConfig()->getEntities()));
	}

	/**
	 * Tests OutletArrayParser->createEntityConfig
	 */
	public function testCreateEntityConfigWithEmbedded()
	{
		$source = array(
			'connection' => array(
				'dsn' => 'mysql:host=myserver.com;dbname=mydb',
				'username' => 'user',
				'password' => 'pass',
				'dialect'  => 'mysql'
			),
			'classes' => array(
				'Project' => array(
					'table' => 'projects',
					'props' => array(
						'ID' => array('id', 'int', array('pk' => true, 'autoIncrement' => true)),
						'Name' => array('name', 'varchar')
					)
				),
				'Bug' => array(
					'table' => 'bugs',
					'props' => array(
						'ID' => array('id', 'int', array('pk' => true, 'autoIncrement' => true)),
						'Title' => array('title', 'varchar'),
						'ProjectID' => array('project_id', 'int'),
						'Description' => array('description', 'varchar')
					),
					'associations' => array(
						array('many-to-one', 'Project', array('key' => 'ProjectID'))
					)
				),
				'User' => array(
					'table' => 'users',
					'props' => array(
						'ID' => array('id', 'int', array('pk' => true, 'autoIncrement' => true)),
						'Name' => array('title', 'varchar'),
						'Address' => array(null, 'embedded', array('ref' => 'Address'))
					)
				)
			),
			'embeddables' => array(
				'Address' => array(
					'props' => array(
						'Street' => array('street', 'varchar'),
						'Number' => array('number', 'varchar'),
					)
				)
			)
		);

		$parser = new OutletArrayParser($source);

		$this->assertEquals(0, count($parser->getConfig()->getEntities()));

		$parser->createEntityConfig('Project');
		$this->assertEquals(1, count($parser->getConfig()->getEntities()));

		$parser->createEntityConfig('Bug');
		$this->assertEquals(2, count($parser->getConfig()->getEntities()));

		$parser->createEntityConfig('User');
		$this->assertEquals(3, count($parser->getConfig()->getEntities()));

		$parser->createEntityConfig('Address');
		$this->assertEquals(4, count($parser->getConfig()->getEntities()));
	}
}