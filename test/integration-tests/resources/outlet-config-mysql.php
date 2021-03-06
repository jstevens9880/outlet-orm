<?php
return array(
	'connection' => array(
                'dsn' => 'mysql:host=localhost;dbname=test_outlet;',
                'username' => 'root',
                'password' => '',
		'dialect' => 'mysql'
	),
	'classes' => array(
		'OutletTest_Address' => array(
			'table' => 'addresses',
			'plural' => 'Addresses',
			'props' => array(
				'AddressID'	=> array('id', 'int', array('pk'=>true, 'autoIncrement'=>true)),
				'UserID'	=> array('user_id', 'int'),
				'Street'	=> array('street', 'varchar')
			)
		),
		'OutletTest_Bug' => array(
			'table' => 'bugs',
			'props' => array(
				'ID' 	=> array('id', 'int', array('pk'=>true, 'autoIncrement'=>true)),
				'Title'		=> array('title', 'varchar'),
				'ProjectID' => array('project_id', 'int'),
                'TimeToFix' => array('time_to_fix', 'float', array('default' => 2000.000001)),
				'Test_One'	=> array('test_one', 'int') // test an identifier with an underscore on it
			),
			'associations' => array(
				array('many-to-one', 'OutletTest_Project', array('key'=>'ProjectID'))
			)
		),
		'OutletTest_Machine' => array(
			'table' => 'machines',
			'props' => array(
				'Name' 			=> array('name', 'varchar', array('pk'=>true)),
				'Description'	=> array('description', 'varchar')
			)
		),
		'OutletTest_Project' => array(
			'table' => 'projects',
			'props' => array(
				'ProjectID' 	=> array('id', 'int', array('pk'=>true, 'autoIncrement'=>true)),
				'Name'			=> array('name', 'varchar'),
				'CreatedDate' 	=> array('created_date', 'datetime', array('defaultExpr'=>"NOW()")),
				'StatusID'		=> array('status_id', 'int', array('default'=>1)),
				'Description'	=> array('description', 'varchar', array('default'=>'Default Description'))
			),
			'associations' => array(
				array('one-to-many', 'OutletTest_Bug', array('key'=>'ProjectID'))
			),
			'useGettersAndSetters' => true
		),
		'OutletTest_User' => array(
			'table' => 'users',
			'props' => array(
				'UserID' 	=> array('id', 'int', array('pk'=>true, 'autoIncrement'=>true)),
				'FirstName' => array('first_name', 'varchar'),
				'LastName'	=> array('last_name', 'varchar')
			),
			'associations' => array(
				array('one-to-many', 'OutletTest_Address', array('key'=>'UserID', 'name'=>'WorkAddress', 'plural'=>'WorkAddresses')),
				array('many-to-many', 'OutletTest_Bug', array('table'=>'watchers', 'tableKeyLocal'=>'user_id', 'tableKeyForeign'=>'bug_id'))
			)
		),
		'OutletTest_Profile' => array(
			'table' => 'profiles',
			'props' => array(
				'ProfileID' 	=> array('id', 'int', array('pk'=>true, 'autoIncrement'=>true)),
				'UserID' 		=> array('user_id', 'int')
			),
			'associations' => array(
				array('one-to-one', 'OutletTest_User', array('key'=>'UserID', 'refKey' => 'UserID'))
			),
			'useGettersAndSetters' => true
		)
	)
);
