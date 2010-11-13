<?php
/**
 * Contains the class to create proxies
 * 
 * @package org.outlet-orm
 * @subpackage proxy
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */

/**
 * Class that creates the proxies instances
 * 
 * @package org.outlet-orm
 * @subpackage proxy
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */
class OutletProxyFactory
{
	/**
	 * Singleton instance
	 * 
	 * @var OutletProxyFactory
	 */
	private static $instance;
	
	/**
	 * Proxies definitions generator
	 * 
	 * @var OutletProxyGenerator
	 */
	private $proxyGenerator;
	
	/**
	 * Singleton usage of class
	 * 
	 * @return OutletProxyFactory
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	/**
	 * Class constructor (is public just for tests)
	 */
	public function __construct()
	{
		$this->setProxyGenerator(new OutletProxyGenerator());
	}
	
	/**
	 * Returns the proxies generator
	 * 
	 * @return OutletProxyGenerator
	 */
	protected function getProxyGenerator()
	{
		return $this->proxyGenerator;
	}

	/**
	 * Configures the proxies gererator
	 * 
	 * @param OutletProxyGenerator $proxyGenerator
	 */
	protected function setProxyGenerator(OutletProxyGenerator $proxyGenerator)
	{
		$this->proxyGenerator = $proxyGenerator;
	}
	
	/**
	 * Returns a new instance of a proxy class
	 * 
	 * @param string $class
	 * @return OutletProxy
	 */
	public function getProxy($class)
	{
		$proxyName = $this->getProxyName($class);
		
		if (!$this->checkClass($proxyName)) {
			$this->createProxyClass($proxyName);
		}
		
		return $this->createInstance($proxyName);
	}
	
	/**
	 * Returns the proxies's naming conventions
	 * 
	 * @param string $class
	 * @return string
	 */
	public function getProxyName($class)
	{
		return $class . '_OutletProxy';
	}
	
	/**
	 * Creates the proxy class
	 * 
	 * @param string $class
	 */
	protected function createProxyClass($class)
	{
		$entity = Outlet::getInstance()->getConfig()->getEntity($class);
			
		eval($this->getProxyGenerator()->generateProxy($entity));
	}
	
	/**
	 * Create a new instance of the class (just for tests)
	 * 
	 * @param string $class
	 * @return OutletProxy
	 */
	protected function createInstance($class)
	{
		return new $class();
	}
	
	/**
	 * Verify if the class exists (just for tests)
	 * 
	 * @param string $class
	 * @return boolean
	 */
	protected function checkClass($class)
	{
		return class_exists($class);
	}
}