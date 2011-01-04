<?php
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/../../');

require_once 'PHPUnit/Framework/TestSuite.php';

require_once 'test/org.outlet-orm/autoloader/OutletAutoloaderTest.php';
require_once 'test/org.outlet-orm/database/OutletWriteQueryTest.php';
require_once 'test/org.outlet-orm/database/OutletQueryTest.php';
require_once 'test/org.outlet-orm/database/OutletDeleteQueryTest.php';
require_once 'test/org.outlet-orm/database/OutletInsertQueryTest.php';
require_once 'test/org.outlet-orm/database/OutletUpdateQueryTest.php';
require_once 'test/org.outlet-orm/database/OutletSelectQueryTest.php';
require_once 'test/org.outlet-orm/database/OutletConnectionTest.php';
require_once 'test/org.outlet-orm/config/OutletConnectionConfigTest.php';
require_once 'test/org.outlet-orm/config/OutletConfigTest.php';
require_once 'test/org.outlet-orm/proxy/OutletProxyFactoryTest.php';
require_once 'test/org.outlet-orm/core/OutletCacheTest.php';
require_once 'test/org.outlet-orm/entity/OutletEntityHelperTest.php';

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
		$this->addTestSuite('OutletConnectionConfigTest');
		$this->addTestSuite('OutletConfigTest');
		$this->addTestSuite('OutletProxyFactoryTest');
		$this->addTestSuite('OutletCacheTest');
		$this->addTestSuite('OutletWriteQueryTest');
		$this->addTestSuite('OutletQueryTest');
		$this->addTestSuite('OutletDeleteQueryTest');
		$this->addTestSuite('OutletInsertQueryTest');
		$this->addTestSuite('OutletUpdateQueryTest');
		$this->addTestSuite('OutletSelectQueryTest');
		$this->addTestSuite('OutletEntityHelperTest');
	}
	/**
	 * Creates the suite.
	 */
	public static function suite()
	{
		return new self();
	}
}