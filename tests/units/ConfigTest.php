<?php

class Unit_ConfigTest extends OutletTestCase {
	public function testRequireConnectionConfig() {
		try {
			$config = new OutletConfig(array());
			$this->fail("should've raised an exception");
		} catch (OutletConfigException $ex) { $this->assertTrue(true);}
	}

	public function testRequireConnectionDsnOrPdo() {
		try {
			$config = new OutletConfig(array('connection' => 1));
			$this->fail("should've raised an exception");
		} catch (OutletConfigException $ex) { $this->assertTrue(true);}
	}

	public function testRequireDialect() {
		try {
			$config = new OutletConfig(array('connection' => array('dsn' => $this->getSQLiteInMemoryDSN())));
			$this->fail("should've raised an exception");
		} catch (OutletException $ex) { $this->assertTrue(true);}

		try {
			$config = new OutletConfig(array('connection' => array('pdo' => 1)));
			$this->fail("should've raised an exception");
		} catch (OutletConfigException $ex) { $this->assertTrue(true);}
	}

	public function testRequireClassesMapping() {
		try {
			$config = new OutletConfig(array('connection' => array('dsn' => $this->getSQLiteInMemoryDSN(), 'dialect' => 'mysql')));
			$this->fail("should've raised an exception");
		} catch (OutletConfigException $ex) { $this->assertTrue(true);}
	}

	public function testCanGetConnection() {
		$config = new OutletConfig(array('connection' => array('dsn' => $this->getSQLiteInMemoryDSN(), 'dialect' => 'sqlite'), 'classes' => array()));
		$this->assertThat($config->getConnection(), $this->isInstanceOf('OutletConnection'));

		$config = new OutletConfig(array('connection' => array('pdo' => $this->getSQLiteInMemoryPDOConnection(), 'dialect' => 'sqlite'), 'classes' => array()));
		$this->assertThat($config->getConnection(), $this->isInstanceOf('OutletConnection'));
	}

	public function testRaisesExceptionIfEntityNotFound() {
		$config = new OutletConfig(array('connection' => array('pdo' => $this->getSQLiteInMemoryPDOConnection(), 'dialect' => 'sqlite'), 'classes' => array('Testing' => array('table' => 'testing', 'props' => array('id' => array('id', 'int', array('pk' => true)))))));
		try {
			$this->assertThat($config->getEntity('Testing2'), $this->isInstanceOf('OutletEntityConfig'));
			$this->fail("should've raised an exception");
		} catch (OutletException $ex) { $this->assertTrue(true); }
	}

	public function testAllowToSuppresExceptionIfEntityNotFound() {
		$config = new OutletConfig(array('connection' => array('pdo' => $this->getSQLiteInMemoryPDOConnection(), 'dialect' => 'sqlite'), 'classes' => array('Testing' => array('table' => 'testing', 'props' => array('id' => array('id', 'int', array('pk' => true)))))));
		$this->assertNull($config->getEntity('Testing2', false));
	}

	public function testGettersAndSettersAreDisabledByDefault() {
		$config = $this->createConfig();
		$this->assertFalse($config->useGettersAndSetters());
		$this->assertFalse($config->getEntity('ConfigEntity')->useGettersAndSetters());
	}

	public function testSetGettersAndSettersUsageGlobal() {
		$config = $this->createConfig(true);
		$this->assertTrue($config->useGettersAndSetters());
		$this->assertTrue($config->getEntity('ConfigEntity')->useGettersAndSetters());
	}

	public function testSetGettersAndSettersUsagePerEntity() {
		$config = $this->createConfig(true, false);
		$this->assertFalse($config->getEntity('ConfigEntity')->useGettersAndSetters());

		$config = $this->createConfig(false, true);
		$this->assertTrue($config->getEntity('ConfigEntity')->useGettersAndSetters());
	}

	public function testCanGetEntityConfig() {
		$config = new OutletConfig(array('connection' => array('pdo' => $this->getSQLiteInMemoryPDOConnection(), 'dialect' => 'sqlite'), 'classes' => array('Testing' => array('table' => 'testing', 'props' => array('id' => array('id', 'int', array('pk' => true)))))));
		$this->assertThat($config->getEntity('Testing'), $this->isInstanceOf('OutletEntityConfig'));
	}

	public function testGettingEntityConfigByObject() {
		$config = $this->createConfig();
		$this->assertThat($config->getEntity(new ConfigEntity()), $this->isInstanceOf('OutletEntityConfig'));
	}

	public function testGettingEntityConfigByProxy() {
		$config = $this->createConfig();
		$this->assertThat($config->getEntity(new ConfigEntity_OutletProxy()), $this->isInstanceOf('OutletEntityConfig'));
	}

	protected function createConfig($globalUseGettersAndSetters = null, $entityUseGettersAndSetters = null) {
		$config = array(
			'connection' => array(
				'pdo' => $this->getSQLiteInMemoryPDOConnection(),
				'dialect' => 'sqlite'
			),
			'classes' => array(
				'ConfigEntity' => array(
					'table' => 'testing',
					'props' => array('id' => array('id', 'int', array('pk' => true)))
				)
			)
		);
		if ($globalUseGettersAndSetters !== null)
			$config['useGettersAndSetters'] = $globalUseGettersAndSetters;
		if ($entityUseGettersAndSetters !== null)
			$config['classes']['ConfigEntity']['useGettersAndSetters'] = $entityUseGettersAndSetters;
		return new OutletConfig($config);
	}
}

class ConfigEntity {
	public $id;
}

class ConfigEntity_OutletProxy extends ConfigEntity implements OutletProxy {}