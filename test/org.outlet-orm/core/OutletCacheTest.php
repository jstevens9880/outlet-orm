<?php
require_once 'application/org.outlet-orm/core/OutletCache.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * OutletCache test case.
 */
class OutletCacheTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests OutletCache->__construct()
	 */
	public function test__construct()
	{
		$cache = new OutletCache();

		$this->assertEquals(array(), $cache->getCache());
	}

	/**
	 * Tests OutletCache->clearCache()
	 */
	public function testClearCache()
	{
		$cache = new OutletCache();

		$cache->persist('Teste', array(1), array());
		$this->assertEquals(array('Teste' => array(1 => array())), $cache->getCache());

		$cache->clearCache();
		$this->assertEquals(array(), $cache->getCache());
	}

	/**
	 * Tests OutletCache->persist()
	 */
	public function testPersist()
	{
		$cache = new OutletCache();

		$cache->persist('Teste', array(1), array('data' => new stdClass(), 'obj' => new stdClass()));
		$this->assertEquals(
			array('Teste' => array(1 => array('data' => new stdClass(), 'obj' => new stdClass()))),
			$cache->getCache()
		);
	}

	/**
	 * Tests OutletCache->persist()
	 */
	public function testPersistUpdateCache()
	{
		$cache = new OutletCache();

		$cache->persist('Teste', array(1), array('data' => new stdClass(), 'obj' => new stdClass()));
		$cache->persist('Teste', array(1), array());

		$this->assertEquals(array('Teste' => array(1 => array())), $cache->getCache());
	}

	/**
	 * Tests OutletCache->persist()
	 */
	public function testPersistMultiPrimaryKey()
	{
		$cache = new OutletCache();

		$cache->persist('Teste', array(1), array());
		$cache->persist('Teste', array(1, 3, 5), array('data' => new stdClass(), 'obj' => new stdClass()));

		$this->assertEquals(
			array(
				'Teste' => array(
					1 => array(),
					'1;3;5' => array('data' => new stdClass(), 'obj' => new stdClass())
				)
			),
			$cache->getCache()
		);
	}

	/**
	 * Tests OutletCache->retrieve()
	 */
	public function testRetrieve()
	{
		$cache = new OutletCache();

		$cache->persist('Teste', array(1), array());
		$cache->persist('Teste', array(1, 3, 5), array('data' => new stdClass(), 'obj' => new stdClass()));

		$this->assertEquals(array('data' => new stdClass(), 'obj' => new stdClass()), $cache->retrieve('Teste', array(1, 3, 5)));
	}

	/**
	 * Tests OutletCache->retrieve()
	 */
	public function testRetrieveNoDataFound()
	{
		$cache = new OutletCache();

		$cache->persist('Teste', array(1), array());
		$cache->persist('Teste', array(1, 3, 5), array('data' => new stdClass(), 'obj' => new stdClass()));

		$this->assertNull($cache->retrieve('Teste', array(2)));
	}

	/**
	 * Tests OutletCache->remove()
	 */
	public function testRemove()
	{
		$cache = new OutletCache();

		$cache->persist('Teste', array(1), array());
		$cache->persist('Teste', array(1, 3, 5), array('data' => new stdClass(), 'obj' => new stdClass()));

		$cache->remove('Teste', array(1, 3, 5));

		$this->assertNull($cache->retrieve('Teste', array(1, 3, 5)));
	}

	/**
	 * Tests OutletCache->remove()
	 */
	public function testRemoveNoDataToRemove()
	{
		$cache = new OutletCache();

		$cache->persist('Teste', array(1), array());
		$cache->persist('Teste', array(1, 3, 5), array('data' => new stdClass(), 'obj' => new stdClass()));

		$cache->remove('Teste', array(2));

		$this->assertEquals(
			array(
				'Teste' => array(
					1 => array(),
					'1;3;5' => array('data' => new stdClass(), 'obj' => new stdClass())
				)
			),
			$cache->getCache()
		);
	}

	/**
	 * Tests OutletCache->clearEntityCache()
	 */
	public function testClearEntityCache()
	{
		$cache = new OutletCache();

		$cache->persist('Teste2', array(1), array());
		$cache->persist('Teste', array(1), array());
		$cache->persist('Teste', array(1, 3, 5), array('data' => new stdClass(), 'obj' => new stdClass()));

		$cache->clearEntityCache('Teste');

		$this->assertEquals(
			array(
				'Teste2' => array(
					1 => array()
				)
			),
			$cache->getCache()
		);
	}

	/**
	 * Tests OutletCache->clearEntityCache()
	 */
	public function testClearEntityCacheEntityNotFound()
	{
		$cache = new OutletCache();

		$cache->persist('Teste2', array(1), array());
		$cache->persist('Teste', array(1), array());
		$cache->persist('Teste', array(1, 3, 5), array('data' => new stdClass(), 'obj' => new stdClass()));

		$cache->clearEntityCache('Teste3');

		$this->assertEquals(
			array(
				'Teste' => array(
					1 => array(),
					'1;3;5' => array('data' => new stdClass(), 'obj' => new stdClass())
				),
				'Teste2' => array(
					1 => array()
				)
			),
			$cache->getCache()
		);
	}
}