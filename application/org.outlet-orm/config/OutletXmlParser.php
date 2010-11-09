<?php
/**
 * Contains the class to use XML config
 * 
 * @package org.outlet-orm
 * @subpackage config
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */

/**
 * Class to create an OutletConfig from a XML file 
 * 
 * @package org.outlet-orm
 * @subpackage config
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */
class OutletXmlParser
{
	/**
	 * @var SimpleXmlElement
	 */
	private $xmlObj;
	
	/**
	 * @var bool
	 */
	private $validate;
	
	/**
	 * Constructor 
	 */
	public function __construct()
	{
		libxml_use_internal_errors(true);
		$this->validate = true;
	}
	
	/**
	 * @param bool $validate
	 */
	public function setValidate($validate)
	{
		$this->validate = $validate;
	}
	
	/**
	 * @param SimpleXMLElement $xmlObj
	 */
	public function setXmlObj(SimpleXMLElement $xmlObj)
	{
		$this->xmlObj = $xmlObj;
	}
	
	/**
	 * @return SimpleXmlElement
	 */
	protected function getXmlObj()
	{
		return $this->xmlObj;
	}
	
	/**
	 * Create the config object from a XML file
	 * 
	 * @param string $file File name
	 * @throws OutletXmlParserException
	 * @return OutletConfig
	 */
	public function parseFromFile($file)
	{
		try {
			$this->setXmlObj(new SimpleXMLElement($file, null, true));
			$this->getXmlErrors();
		} catch (Exception $e) {
			throw new OutletXmlParserException('Xml parse error.', null, $e);
		}
		
		return $this->parse();
	}
	
	/**
	 * Create the config object from a XML string
	 * 
	 * @param string $xmlString
	 * @throws OutletXmlParserException
	 * @return OutletConfig
	 */
	public function parseFromString($xmlString)
	{
		try {
			$this->setXmlObj(new SimpleXMLElement($xmlString));
			$this->getXmlErrors();
		} catch (Exception $e) {
			throw new OutletXmlParserException('Xml parse error.', null, $e);
		}
		
		return $this->parse();
	}
	
	/**
	 * Creates a config object from XML
	 * 
	 * @return OutletConfig
	 */
	public function parse()
	{
		$this->validate();
		
		$config = $this->getConfigClass();
		$config->setConnectionConfig($this->parseConnection());
		$this->parseClasses($config);
		
		return $config;
	}
	
	/**
	 * Retorns an instance of config class (testing)
	 * 
	 * @return OutletConfig
	 */
	protected function getConfigClass()
	{
		return new OutletConfig();
	}
	
	/**
	 * @return string
	 */
	protected function getXsdPath()
	{
		return dirname(__FILE__) . '/../../../resources/outlet-config.xsd';
	}
	
	/**
	 * Validate the XML source 
	 */
	public function validate()
	{
		$this->verifyInstance();
		
		if ($this->validate) {
			$dom = new DOMDocument();
			$dom->loadXML($this->getXmlObj()->saveXML());
			
			if (!$dom->schemaValidate($this->getXsdPath())) {
				throw new OutletXmlParserException('This XML is not compatible with the Outlet\'s standard config', $this->getXmlErrors());
			}
		}
	}
	
	/**
	 * Verify if the instance is right
	 */
	protected function verifyInstance()
	{
		if (!$this->getXmlObj() instanceof SimpleXMLElement) {
			throw new OutletXmlParserException('The $this->xmlObj attribute must be an instance of SimpleXMLElement');
		}
	}
	
	/**
	 * Get Xml errors
	 * 
	 * @throws Exception
	 */
	protected function getXmlErrors()
	{
		$errors = '';
		
		foreach(libxml_get_errors() as $error) {
	        $errors .= $error->message . '; ';
	    }
	    
	    libxml_clear_errors();
	    
	    if (strlen($errors) > 0) {
	    	throw new Exception($errors);
	    }
	}
	
	/**
	 * @return OutletConnectionConfig
	 * @throws OutletXmlParserException
	 */
	public function parseConnection()
	{
		try {
			$config = new OutletConnectionConfig(
				strval($this->getXmlObj()->connection->dialect),
				strval($this->getXmlObj()->connection->dsn),
				$this->getXmlObj()->connection->username ? strval($this->getXmlObj()->connection->username) : null,
				$this->getXmlObj()->connection->password ? strval($this->getXmlObj()->connection->password) : null
			);
			
			return $config;
		} catch (OutletConfigException $e) {
			throw new OutletXmlParserException('There were some errors during the connection parse.', null, $e);
		}
	}
	
	/**
	 * @param OutletConfig $config
	 */
	public function parseClasses(OutletConfig $config)
	{
		foreach ($this->getXmlObj()->classes->class as $class) {
			$config->addEntity($this->parseClass($class));
		}
		
		foreach ($this->getXmlObj()->classes->class as $class) {
			if ($class->association) {
				$this->parseAssociations($config, $class);
			}
		}
	}
	
	/**
	 * Returns the attribute vale
	 * 
	 * @param SimpleXMLElement $obj
	 * @param string $attributeName
	 * @return mixed
	 */
	protected function getNodeAttrValue(SimpleXMLElement $obj, $attributeName)
	{
		foreach($obj->attributes() as $name => $value) {
			if ($name == $attributeName) {
				return strval($value);
			}
		}
		
		return null;
	}
	
	/**
	 * @param SimpleXMLElement $class
	 * @return OutletEntity
	 */
	public function parseClass(SimpleXMLElement $class)
	{
		$entity = new OutletEntity(
			$this->getNodeAttrValue($class, 'name'),
			$this->getNodeAttrValue($class, 'table')
		);
		
		if ($plural = $this->getNodeAttrValue($class, 'plural')) {
			$entity->setPlural($plural);
		}
		
		if ($sequenceName = $this->getNodeAttrValue($class, 'sequenceName')) {
			$entity->setSequenceName($sequenceName);
		}
		
		foreach ($class->property as $property) {
			$entity->addProperty($this->parseProperty($property));
		}
		
		return $entity;
	}
	
	/**
	 * @param SimpleXMLElement $prop
	 * @return OutletEntityProperty
	 */
	public function parseProperty(SimpleXMLElement $prop)
	{
		$property = new OutletEntityProperty(
			$this->getNodeAttrValue($prop, 'name'),
			$this->getNodeAttrValue($prop, 'column'),
			$this->getNodeAttrValue($prop, 'type')
		);
		
		if ($pk = $this->getNodeAttrValue($prop, 'pk')) {
			$property->setPrimaryKey($pk == 'true');
		}
		
		if ($autoIncrement = $this->getNodeAttrValue($prop, 'autoIncrement')) {
			$property->setAutoIncrement($autoIncrement == 'true');
		}
		
		if ($default = $this->getNodeAttrValue($prop, 'default')) {
			$property->setDefaultValue($default);
		}
		
		if ($defaultExpr = $this->getNodeAttrValue($prop, 'defaultExpr')) {
			$property->setDefaultExpression($defaultExpr);
		}
		
		return $property;
	}
	
	/**
	 * Enter description here ...
	 * @param OutletConfig $config
	 * @param SimpleXMLElement $class
	 */
	public function parseAssociations(OutletConfig $config, SimpleXMLElement $class)
	{
		foreach ($class->association as $association) {
			$entity = $config->getEntity($this->getNodeAttrValue($class, 'name'));
			$entity->addAssociation($this->parseAssociation($config, $entity, $association));
		}
	}
	
	/**
	 * @param OutletConfig $config
	 * @param OutletEntity $entity
	 * @param SimpleXMLElement $association
	 * @return OutletAssociation
	 * @throws OutletXmlParserException
	 */
	public function parseAssociation(OutletConfig $config, OutletEntity $entity, SimpleXMLElement $association)
	{
		switch ($this->getNodeAttrValue($association, 'type')) {
			case 'many-to-one':
				return $this->createManyToOneAssociation($config, $association);
			case 'many-to-many':
				return $this->createManyToManyAssociation($config, $entity, $association);
			case 'one-to-one':
				return $this->createOneToOneAssociation($config, $association);
			case 'one-to-many':
				return $this->createOneToManyAssociation($config, $entity, $association);
		}
	}
	
	/**
	 * Validates the association options
	 * 
	 * @param SimpleXMLElement $assoc
	 * @throws OutletXmlParserException
	 */
	public function validateAssociationConfig(SimpleXMLElement $assoc)
	{
		if ($this->getNodeAttrValue($assoc, 'type') == 'many-to-many') {
			if ($this->getNodeAttrValue($assoc, 'key') || $this->getNodeAttrValue($assoc, 'name') || !$this->getNodeAttrValue($assoc, 'table') || !$this->getNodeAttrValue($assoc, 'tableKeyLocal') || !$this->getNodeAttrValue($assoc, 'tableKeyForeign')) {
				throw new OutletXmlParserException('The ' . $this->getNodeAttrValue($assoc, 'type') . ' association must have only "table", "tableKeyLocal", "tableKeyForeign" or "plural" attributes');
			}
		} else {
			if ($this->getNodeAttrValue($assoc, 'table') || $this->getNodeAttrValue($assoc, 'tableKeyLocal') || $this->getNodeAttrValue($assoc, 'tableKeyForeign')) {
				throw new OutletXmlParserException('The "table", "tableKeyLocal" or "tableKeyForeign" attributes are prohibited in the ' . $this->getNodeAttrValue($assoc, 'type') . ' association');
			}
			
			if (!$this->getNodeAttrValue($assoc, 'key')) {
				throw new OutletXmlParserException('You must define the association\'s key');
			}
			
			if ($this->getNodeAttrValue($assoc, 'type') != 'one-to-many') {
				if ($this->getNodeAttrValue($assoc, 'plural')) {
					throw new OutletXmlParserException('The "plural" attribute is prohibited in the ' . $this->getNodeAttrValue($assoc, 'type') . ' association');
				}
			} else {
				if ($this->getNodeAttrValue($assoc, 'optional')) {
					throw new OutletXmlParserException('The "optional" attribute is prohibited in the ' . $this->getNodeAttrValue($assoc, 'type') . ' association');
				}
			}
		}
	} 
	
	/**
	 * Create a many-to-one association based on entity config
	 * 
	 * @param OutletConfig $config
	 * @param SimpleXMLElement $association
	 * @return OutletManyToOneAssociation
	 * @throws OutletXmlParserException
	 */
	public function createManyToOneAssociation(OutletConfig $config, SimpleXMLElement $assoc)
	{
		$this->validateAssociationConfig($assoc);
		
		$relatedEntity = $config->getEntity($this->getNodeAttrValue($assoc, 'classReference'));

		$association = new OutletManyToOneAssociation($relatedEntity, $this->getNodeAttrValue($assoc, 'key'), $relatedEntity->getMainPrimaryKey());
		
		if ($name = $this->getNodeAttrValue($assoc, 'name')) {
			$association->setName($name);
		}
		
		if ($optional = $this->getNodeAttrValue($assoc, 'optional')) {
			$association->setOptional($optional == 'true');
		}
		
		return $association;
	}
	
	/**
	 * Create a many-to-many association based on entity config
	 * 
	 * @param OutletConfig $config
	 * @param OutletEntity $entity
	 * @param SimpleXMLElement $association
	 * @return OutletManyToManyAssociation
	 * @throws OutletXmlParserException
	 */
	public function createManyToManyAssociation(OutletConfig $config, OutletEntity $entity, SimpleXMLElement $assoc)
	{
		$this->validateAssociationConfig($assoc);
		
		$relatedEntity = $config->getEntity($this->getNodeAttrValue($assoc, 'classReference'));
		
		$association = new OutletManyToManyAssociation(
			$relatedEntity,
			$relatedEntity->getMainPrimaryKey(),
			$entity->getMainPrimaryKey(),
			$this->getNodeAttrValue($assoc, 'table'),
			$this->getNodeAttrValue($assoc, 'tableKeyLocal'),
			$this->getNodeAttrValue($assoc, 'tableKeyForeign')
		);
		
		if ($plural = $this->getNodeAttrValue($assoc, 'plural')) {
			$association->setName($plural);
		}
		
		return $association;
	}
	
	/**
	 * Create a one-to-one association based on entity config
	 * 
	 * @param OutletConfig $config
	 * @param SimpleXMLElement $association
	 * @return OutletOneToOneAssociation
	 * @throws OutletXmlParserException
	 */
	public function createOneToOneAssociation(OutletConfig $config, SimpleXMLElement $assoc)
	{
		$this->validateAssociationConfig($assoc);
		
		$relatedEntity = $config->getEntity($this->getNodeAttrValue($assoc, 'classReference'));
		
		$association = new OutletOneToOneAssociation(
			$relatedEntity,
			$this->getNodeAttrValue($assoc, 'key'),
			$relatedEntity->getMainPrimaryKey()
		);
		
		if ($name = $this->getNodeAttrValue($assoc, 'name')) {
			$association->setName($name);
		}
		
		if ($optional = $this->getNodeAttrValue($assoc, 'optional')) {
			$association->setOptional($optional == 'true');
		}
		
		return $association;
	}
	
	/**
	 * Create a one-to-many association based on entity config
	 * 
	 * @param OutletConfig $config
	 * @param OutletEntity $entity
	 * @param SimpleXMLElement $association
	 * @return OutletOneToManyAssociation
	 * @throws OutletXmlParserException
	 */
	public function createOneToManyAssociation(OutletConfig $config, OutletEntity $entity, SimpleXMLElement $assoc)
	{
		$this->validateAssociationConfig($assoc);
		
		$relatedEntity = $config->getEntity($this->getNodeAttrValue($assoc, 'classReference'));
		
		$association = new OutletOneToManyAssociation(
			$relatedEntity,
			$this->getNodeAttrValue($assoc, 'key'),
			$entity->getMainPrimaryKey()
		);
		
		if ($name = $this->getNodeAttrValue($assoc, 'name')) {
			$association->setName($name);
		}
		
		if ($plural = $this->getNodeAttrValue($assoc, 'plural')) {
			$association->setName($plural);
		}
		
		return $association;
	}
}