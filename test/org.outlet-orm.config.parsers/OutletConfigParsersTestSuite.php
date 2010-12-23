<?php
require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'test/org.outlet-orm.config.parsers/array/OutletArrayParserTest.php';
require_once 'test/org.outlet-orm.config.parsers/xml/OutletXmlParserTest.php';

/**
 * Static test suite.
 */
class OutletConfigParsersTestSuite extends PHPUnit_Framework_TestSuite
{
	/**
	 * Constructs the test suite handler.
	 */
	public function __construct()
	{
		$this->setName('OutletConfigParsersTestSuite');

		$this->addTestSuite('OutletXmlParserTest');
		$this->addTestSuite('OutletArrayParserTest');
	}

	/**
	 * Creates the suite.
	 */
	public static function suite()
	{
		return new self();
	}
}