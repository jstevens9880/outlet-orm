<?php
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/../../');

require_once 'PHPUnit/Framework/TestSuite.php';

require_once 'test/org.outlet-orm/autoloader/OutletAutoloaderTest.php';
require_once 'test/org.outlet-orm/database/OutletConnectionTest.php';
require_once 'test/org.outlet-orm/config/OutletXmlParserTest.php';
require_once 'test/org.outlet-orm/config/OutletConnectionConfigTest.php';
require_once 'test/org.outlet-orm/config/OutletConfigTest.php';
require_once 'test/org.outlet-orm/proxy/OutletProxyFactoryTest.php';
/**
 * Static test suite.
 */
class OutletTestSuite extends PHPUnit_Framework_TestSuite
{
	/**
	 * Constructs the test suite handler.
	 */
	public function __construct()
	{
		$this->setName('OutletTestSuite');
		
		$this->addTestSuite('OutletAutoloaderTest');
		$this->addTestSuite('OutletConnectionTest');
		$this->addTestSuite('OutletXmlParserTest');
		$this->addTestSuite('OutletConnectionConfigTest');
		$this->addTestSuite('OutletConfigTest');
		$this->addTestSuite('OutletProxyFactoryTest');
	}
	/**
	 * Creates the suite.
	 */
	public static function suite()
	{
		return new self();
	}
}