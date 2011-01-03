<?php
require_once 'test/integration-tests/resources/OutletTestCase.php';

class FluentInterfaceQueryAPITest extends OutletTestCase
{
	function testSimpleSelect()
	{
		$outlet = Outlet::getInstance();

		$p = new OutletTest_Project();
		$p->setName('Project 1');

		$outlet->save($p);

		$p = new OutletTest_Project();
		$p->setName('Project 2');

		$outlet->save($p);

		$this->assertEquals(2, count($outlet->from('OutletTest_Project')->find()));
	}

	function testFindOne()
	{
		$outlet = Outlet::getInstance();

		$p = new OutletTest_Project();
		$p->setName('Project 1');

		$outlet->save($p);

		$p2 = new OutletTest_Project();
		$p2->setName('Project 2');

		$outlet->save($p2);

		$this->assertEquals($p, $outlet->from('OutletTest_Project')->findOne());
	}

	function testAliasedQuery()
	{
		$outlet = Outlet::getInstance();

		$p = new OutletTest_Project();
		$p->setName('1');

		$outlet->save($p);

		$this->assertEquals($p, $outlet->from('OutletTest_Project p')->where('{p.Name} = ?', array('1'))->findOne());
	}

	function testEagerFetchingOneToOne()
	{
		$outlet = Outlet::getInstance();

		$user = new OutletTest_User();
		$user->FirstName = 'Alvaro';
		$user->LastName = 'Carrasco';

		$outlet->save($user);

		$profile = new OutletTest_Profile();
		$profile->setUserID($user->UserID);
		$outlet->save($profile);

		$outlet->clearCache();

		$profile = $outlet->from('OutletTest_Profile')->with('User Users')->findOne();

		$this->assertEquals($user->UserID, $profile->getUser()->UserID);
		$this->assertEquals($user->FirstName, $profile->getUser()->FirstName);
		$this->assertEquals($user->LastName, $profile->getUser()->LastName);
	}

	function testEagerFetchingManyToOne()
	{
		$outlet = Outlet::getInstance();

		$bug = new OutletTest_Bug();
		$bug->Title = 'Test Bug';

		$project = new OutletTest_Project();
		$project->setName('Test Project');

		$bug->setProject($project);

		$outlet->save($bug);

		$outlet->clearCache();

		$bug = $outlet->from('OutletTest_Bug')->with('Project')->findOne();

		$this->assertEquals($project->getName(), $bug->getProject()->getName());
	}

	function testPagination()
	{
		$outlet = Outlet::getInstance();
		$totalRecords = 15;

		for ($i = 0; $i < $totalRecords; $i++) {
			$project = new OutletTest_Project();
			$project->setName('Test Project ' . $i);
			$outlet->save($project);
		}

		$this->assertEquals(10, count($outlet->from('OutletTest_Project')->limit(10)->find()));
		$this->assertEquals(5, count($outlet->from('OutletTest_Project')->limit(10)->offset(10)->find()));
	}

	function testCount()
	{
		$outlet = Outlet::getInstance();
		$totalRecords = 15;

		for ($i = 0; $i < $totalRecords; $i++) {
			$project = new OutletTest_Project();
			$project->setName('Test Project ' . $i);
			$outlet->save($project);
		}

		$this->assertEquals($totalRecords, $outlet->from('OutletTest_Project')->count());
		$this->assertEquals($totalRecords, $outlet->from('OutletTest_Project')->limit(10)->count());
	}
}