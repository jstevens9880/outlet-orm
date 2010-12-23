<?php
/**
 * Contains the class to use XML config
 *
 * @package org.outlet-orm.config.parsers
 * @subpackage xml
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */

/**
 * Class to create an OutletConfig from a XML file
 *
 * @package org.outlet-orm.config.parsers
 * @subpackage xml
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */
class OutletXmlParser implements OutletConfigParser
{
	/**
	 * @var SimpleXmlElement
	 */
	private $source;

	/**
	 * Config instance
	 *
	 * @var OutletConfig
	 */
	private $config;

	/**
	 * Constructor
	 *
	 * @param string $xml FileName or XML string
	 */
	public function __construct($xml)
	{
		libxml_use_internal_errors(true);

		try {
			$this->createXmlObj($xml);
			$this->getXmlErrors();
		} catch (Exception $e) {
			throw new OutletXmlParserException('Xml parse error.', null, $e);
		}

		$this->validate();
	}

	/**
	 * @return SimpleXmlElement
	 */
	public function getSource()
	{
		return $this->source;
	}

	/**
	 * Creates the XML source
	 *
	 * @param string $xml FileName or XML string
	 */
	protected function createXmlObj($xml)
	{
		if (file_exists($xml)) {
			$this->source = new SimpleXMLElement($xml, null, true);
		} else {
			$this->source = new SimpleXMLElement($xml);
		}

		$this->source->registerXPathNamespace('outlet', 'http://www.outlet-orm.org');
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
	 * Returns an OutletConfig instance
	 *
	 * @return OutletConfig
	 */
	public function getConfig()
	{
		if (is_null($this->config)) {
			$this->config = new OutletConfig($this);
		}

		return $this->config;
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
		$dom = new DOMDocument();
		$dom->loadXML($this->getSource()->saveXML());

		if (!$dom->schemaValidate($this->getXsdPath())) {
			throw new OutletXmlParserException('This XML is not compatible with the Outlet\'s standard config', $this->getXmlErrors());
		}
	}

	/**
	 * Creates the connection config
	 *
	 * @throws OutletXmlParserException
	 */
	public function createConnectionConfig()
	{
		try {
			$config = new OutletConnectionConfig(
				strval($this->getSource()->connection->dialect),
				strval($this->getSource()->connection->dsn),
				$this->getSource()->connection->username ? strval($this->getSource()->connection->username) : null,
				$this->getSource()->connection->password ? strval($this->getSource()->connection->password) : null
			);

			$this->getConfig()->setConnectionConfig($config);
		} catch (OutletConfigException $e) {
			throw new OutletXmlParserException('There were some errors during the connection parse.', null, $e);
		}
	}

	/**
	 * Returns if the entity exists on config source
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function entityExists($name)
	{
		return !is_null($this->getEntityConfig($name));
	}

	/**
	 * Creates the entity config
	 *
	 * @param string $name
	 */
	public function createEntityConfig($name)
	{
		$config = $this->getConfig();

		if (!$config->entityExists($name)) {
			if ($classConfig = $this->getEntityConfig($name)) {
				$entity = $this->parseClass($classConfig);
				$config->addEntity($entity);

				if ($classConfig->association) {
					$this->parseAssociations($entity, $classConfig);
				}
			} else {
				throw new OutletXmlParserException('There\'s no entity with name "' . $name . '" on the config source.');
			}
		}
	}

	/**
	 * Creates the embeddable entity config
	 *
	 * @param string $name
	 */
	/*public function createEmbeddableEntityConfig($name)
	{
		//TODO implement...
	}*/

	/**
	 * Returns the entity configuration
	 *
	 * @param string $name
	 * @return SimpleXMLElement
	 */
	protected function getEntityConfig($name)
	{
		$result = $this->getSource()->xpath('//outlet:class[@name="' . $name . '"]');

		if (count($result) == 1) {
			return $result[0];
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
	 * @param OutletEntity $entity
	 * @param SimpleXMLElement $class
	 */
	public function parseAssociations(OutletEntity $entity, SimpleXMLElement $class)
	{
		foreach ($class->association as $association) {
			$relatedEntity = $this->getConfig()->getEntity($this->getNodeAttrValue($association, 'classReference'));

			$entity->addAssociation($this->parseAssociation($entity, $relatedEntity, $association));
		}
	}

	/**
	 * @param OutletEntity $entity
	 * @param OutletEntity $relatedEntity
	 * @param SimpleXMLElement $association
	 * @return OutletAssociation
	 * @throws OutletXmlParserException
	 */
	public function parseAssociation(OutletEntity $entity, OutletEntity $relatedEntity, SimpleXMLElement $association)
	{
		$this->validateAssociationConfig($association);

		switch ($this->getNodeAttrValue($association, 'type')) {
			case 'many-to-one':
				return $this->createManyToOneAssociation($relatedEntity, $association);
			case 'many-to-many':
				return $this->createManyToManyAssociation($entity, $relatedEntity, $association);
			case 'one-to-one':
				return $this->createOneToOneAssociation($relatedEntity, $association);
			case 'one-to-many':
				return $this->createOneToManyAssociation($entity, $relatedEntity, $association);
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
		$type = $this->getNodeAttrValue($assoc, 'type');
		$key = $this->getNodeAttrValue($assoc, 'key');
		$table = $this->getNodeAttrValue($assoc, 'table');
		$name = $this->getNodeAttrValue($assoc, 'name');
		$tableKeyLocal = $this->getNodeAttrValue($assoc, 'tableKeyLocal');
		$tableKeyForeign = $this->getNodeAttrValue($assoc, 'tableKeyForeign');

		if ($type == 'many-to-many') {
			if ($key || $name || !$table || !$tableKeyLocal || !$tableKeyForeign) {
				throw new OutletXmlParserException('The ' . $type . ' association must have only "table", "tableKeyLocal", "tableKeyForeign" or "plural" attributes');
			}
		} else {
			if ($table || $tableKeyLocal || $tableKeyForeign) {
				throw new OutletXmlParserException('The "table", "tableKeyLocal" or "tableKeyForeign" attributes are prohibited in the ' . $type . ' association');
			}

			if (!$key) {
				throw new OutletXmlParserException('You must define the association\'s key');
			}

			if ($type != 'one-to-many') {
				if ($this->getNodeAttrValue($assoc, 'plural')) {
					throw new OutletXmlParserException('The "plural" attribute is prohibited in the ' . $type . ' association');
				}
			} else {
				if ($this->getNodeAttrValue($assoc, 'optional')) {
					throw new OutletXmlParserException('The "optional" attribute is prohibited in the ' . $type . ' association');
				}
			}
		}
	}

	/**
	 * Create a many-to-one association based on entity config
	 *
	 * @param OutletEntity $relatedEntity
	 * @param SimpleXMLElement $association
	 * @return OutletManyToOneAssociation
	 * @throws OutletXmlParserException
	 */
	public function createManyToOneAssociation(OutletEntity $relatedEntity, SimpleXMLElement $assoc)
	{
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
	 * @param OutletEntity $entity
	 * @param OutletEntity $relatedEntity
	 * @param SimpleXMLElement $association
	 * @return OutletManyToManyAssociation
	 * @throws OutletXmlParserException
	 */
	public function createManyToManyAssociation(OutletEntity $entity, OutletEntity $relatedEntity, SimpleXMLElement $assoc)
	{
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
	 * @param OutletEntity $relatedEntity
	 * @param SimpleXMLElement $association
	 * @return OutletOneToOneAssociation
	 * @throws OutletXmlParserException
	 */
	public function createOneToOneAssociation(OutletEntity $relatedEntity, SimpleXMLElement $assoc)
	{
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
	 * @param OutletEntity $entity
	 * @param OutletEntity $relatedEntity
	 * @param SimpleXMLElement $association
	 * @return OutletOneToManyAssociation
	 * @throws OutletXmlParserException
	 */
	public function createOneToManyAssociation(OutletEntity $entity, OutletEntity $relatedEntity, SimpleXMLElement $assoc)
	{
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
}