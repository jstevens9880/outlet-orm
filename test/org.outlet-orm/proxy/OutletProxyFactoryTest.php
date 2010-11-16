<?php
require_once 'application/org.outlet-orm/proxy/OutletProxyFactory.php';
require_once 'application/org.outlet-orm/proxy/OutletProxyGenerator.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * OutletProxyFactory test case.
 */
class OutletProxyFactoryTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests OutletProxyFactory->getProxy()
	 */
	public function testGetProxyExistingClass()
	{
		$factory = $this->getMock('OutletProxyFactory', array('getProxyName', 'createProxyClass', 'createInstance', 'checkClass'));
		
		$factory->expects($this->once())
				->method('getProxyName')
				->with('Project')
				->will($this->returnValue('Project_OutletProxy'));
		
		$factory->expects($this->once())
				->method('checkClass')
				->with('Project_OutletProxy')
				->will($this->returnValue(true));
		
		$factory->expects($this->never())
				->method('createProxyClass');
		
		$factory->expects($this->once())
				->method('createInstance')
				->with('Project_OutletProxy')
				->will($this->returnValue('Just testing...'));
				
		$this->assertEquals('Just testing...', $factory->getProxy('Project'));
	}
	
	/**
	 * Tests OutletProxyFactory->getProxy()
	 */
	public function testGetProxyExistingNonClass()
	{
		$factory = $this->getMock('OutletProxyFactory', array('getProxyName', 'createProxyClass', 'createInstance', 'checkClass'));
		
		$factory->expects($this->once())
				->method('getProxyName')
				->with('Project')
				->will($this->returnValue('Project_OutletProxy'));
		
		$factory->expects($this->once())
				->method('checkClass')
				->with('Project_OutletProxy')
				->will($this->returnValue(false));
		
		$factory->expects($this->once())
				->method('createProxyClass')
				->with('Project_OutletProxy');
		
		$factory->expects($this->once())
				->method('createInstance')
				->with('Project_OutletProxy')
				->will($this->returnValue('Just testing...'));
				
		$this->assertEquals('Just testing...', $factory->getProxy('Project'));
	}
	
	/**
	 * Tests OutletProxyFactory->getProxyName()
	 */
	public function testGetProxyName()
	{
		$this->assertEquals('Aaa_OutletProxy', OutletProxyFactory::getInstance()->getProxyName('Aaa'));
		$this->assertEquals('Abc_OutletProxy', OutletProxyFactory::getInstance()->getProxyName('Abc'));
		$this->assertEquals('Project_OutletProxy', OutletProxyFactory::getInstance()->getProxyName('Project'));
		$this->assertEquals('Bugs_OutletProxy', OutletProxyFactory::getInstance()->getProxyName('Bugs'));
	}
}