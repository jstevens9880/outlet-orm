<?php
require_once 'application/org.outlet-orm/config/OutletConnectionConfig.php';
require_once 'application/org.outlet-orm/config/OutletConfig.php';
require_once 'application/org.outlet-orm/config/OutletConfigParser.php';
require_once 'application/org.outlet-orm/entity/OutletEntity.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * OutletConfig test case.
 */
class OutletConfigTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @return object
	 */
	protected function getParserMock()
	{
		return $this->getMock(
			'OutletConfigParser',
			array('createConnectionConfig', 'createEntityConfig', 'createEmbeddableEntityConfig', 'entityExists', 'getConfig')
		);
	}

	/**
	 * Tests OutletConfig->setConnectionConfig()
	 */
	public function testSetConnectionConfig()
	{
		$con = $this->getMock('OutletConnectionConfig', array(), array(), '', false);
		$parser = $this->getParserMock();
		$config = new OutletConfig($parser);

		$config->setConnectionConfig($con);

		$this->assertEquals($con, $config->getConnectionConfig());
	}

	/**
	 * Tests OutletConfig->createConnection()
	 */
	public function testCreateConnection()
	{
		$con = $this->getMock('OutletConnectionConfig', array('createOutletConnection'), array(), '', false);
		$parser = $this->getParserMock();
		$connection = $this->getMock('OutletConnection', array(), array(), '', false);
		$config = $this->getMock('OutletConfig', array('getConnectionConfig'), array($parser));

		$config->expects($this->once())
			   ->method('getConnectionConfig')
			   ->will($this->returnValue($con));

		$con->expects($this->once())
			->method('createOutletConnection')
			->will($this->returnValue($connection));

		$this->assertEquals($connection, $config->createConnection());
	}

	/**
	 * Tests OutletConfig->setEntities()
	 */
	public function testSetEntities()
	{
		$parser = $this->getParserMock();
		$config = new OutletConfig($parser);
		$config->setEntities(array());

		$this->assertEquals(array(), $config->getEntities());
	}

	/**
	 * Tests OutletConfig->addEntity()
	 */
	public function testAddEntity()
	{
		$parser = $this->getParserMock();
		$config = new OutletConfig($parser);
		$entity = new OutletEntity('Test', 'test');

		$config->addEntity($entity);

		$this->assertEquals(array('Test' => $entity), $config->getEntities());
	}

	/**
	 * Tests OutletConfig->getEntity()
	 */
	public function testGetEntity()
	{
		$parser = $this->getParserMock();
		$config = new OutletConfig($parser);
		$entity = new OutletEntity('Test', 'test');

		$config->addEntity($entity);

		$this->assertEquals($entity, $config->getEntity('Test'));
		$this->assertEquals(null, $config->getEntity('Test2'));
	}
}