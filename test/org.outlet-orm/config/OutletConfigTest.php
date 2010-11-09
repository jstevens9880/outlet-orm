<?php
require_once 'application/org.outlet-orm/config/OutletConnectionConfig.php';
require_once 'application/org.outlet-orm/config/OutletConfig.php';
require_once 'application/org.outlet-orm/entity/OutletEntity.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * OutletConfig test case.
 */
class OutletConfigTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests OutletConfig->setConnectionConfig()
	 */
	public function testSetConnectionConfig()
	{
		$con = $this->getMock('OutletConnectionConfig', array(), array(), '', false);
		$config = new OutletConfig();
		
		$config->setConnectionConfig($con);
		
		$this->assertEquals($con, $config->getConnectionConfig());
	}

	/**
	 * Tests OutletConfig->createConnection()
	 */
	public function testCreateConnection()
	{
		$con = $this->getMock('OutletConnectionConfig', array('createOutletConnection'), array(), '', false);
		$connection = $this->getMock('OutletConnection', array(), array(), '', false);
		$config = $this->getMock('OutletConfig', array('getConnectionConfig'));
		
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
		$entities = new ArrayObject();
		$config = new OutletConfig();
		
		$config->setEntities($entities);
		$entities->append('teste');
		
		$this->assertEquals($entities, $config->getEntities());
	}

	/**
	 * Tests OutletConfig->addEntity()
	 */
	public function testAddEntity()
	{
		$config = new OutletConfig();
		$entities = new ArrayObject();
		$entity = new OutletEntity('Test', 'test');
		
		$entities->offsetSet('Test', $entity);
		$config->addEntity($entity);
		
		$this->assertEquals($entities, $config->getEntities());
	}

	/**
	 * Tests OutletConfig->getEntity()
	 */
	public function testGetEntity()
	{
		$config = new OutletConfig();
		$entity = new OutletEntity('Test', 'test');

		$config->addEntity($entity);
		
		$this->assertEquals($entity, $config->getEntity('Test'));
		$this->assertEquals(null, $config->getEntity('Test2'));
	}
}