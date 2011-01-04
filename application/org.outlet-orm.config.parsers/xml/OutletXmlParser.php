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
		return !is_null($this->getEntityConfig($name)) ||  !is_null($this->getEmbeddableEntityConfig($name));
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
			} elseif ($classConfig = $this->getEmbeddableEntityConfig($name)) {
				$entity = $this->parseEmbeddable($classConfig);
				$config->addEntity($entity);
			} else {
				throw new OutletXmlParserException('There\'s no entity with name "' . $name . '" on the config source.');
			}
		}
	}

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
	 * Returns the embeddable entity configuration
	 *
	 * @param string $name
	 * @return SimpleXMLElement
	 */
	protected function getEmbeddableEntityConfig($name)
	{
		$result = $this->getSource()->xpath('//outlet:embeddable[@name="' . $name . '"]');

		if (count($result) == 1) {
			return $result[0];
		}

		return null;
	}

	/**
	 * @param SimpleXMLElement $class
	 * @return OutletEntity
	 */
	protected function parseClass(SimpleXMLElement $class)
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
	 * @param SimpleXMLElement $class
	 * @return OutletEmbeddableEntity
	 */
	protected function parseEmbeddable(SimpleXMLElement $class)
	{
		$entity = new OutletEmbeddableEntity($this->getNodeAttrValue($class, 'name'));

		foreach ($class->property as $property) {
			$entity->addProperty($this->parseProperty($property));
		}

		return $entity;
	}

	/**
	 * @param SimpleXMLElement $prop
	 * @return OutletEntityProperty
	 */
	protected function parseProperty(SimpleXMLElement $prop)
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

		if ($ref = $this->getNodeAttrValue($prop, 'ref')) {
			$property->setRef($ref);
		}

		return $property;
	}

	/**
	 * Enter description here ...
	 * @param OutletEntity $entity
	 * @param SimpleXMLElement $class
	 */
	protected function parseAssociations(OutletEntity $entity, SimpleXMLElement $class)
	{
		foreach ($class->association as $association) {
			$relatedEntity = $this->getConfig()->getEntity($this->getNodeAttrValue($association, 'classReference'));

			$entity->addAssociation($this->parseAssociation($entity, $relatedEntity, $association));
		}
	}

	/**
	 * @param OutletEntity $entity
	 * @param OutletEntity $relatedEntity
	 * @param SimpleXMLElement $assoc
	 * @return OutletAssociation
	 */
	protected function parseAssociation(OutletEntity $entity, OutletEntity $relatedEntity, SimpleXMLElement $assoc)
	{
		$this->validateAssociationConfig($assoc);
		$association = $this->getAssociation($entity, $relatedEntity, $assoc);

		if (!$association instanceof OutletManyToManyAssociation) {
			if ($name = $this->getNodeAttrValue($assoc, 'name')) {
				$association->setName($name);
			}
		}

		if ($association instanceof OutletManyToManyAssociation || $association instanceof OutletOneToManyAssociation) {
			if ($plural = $this->getNodeAttrValue($assoc, 'plural')) {
				$association->setName($plural);
			}
		} else {
			if ($optional = $this->getNodeAttrValue($assoc, 'optional')) {
				$association->setOptional($optional == 'true');
			}
		}

		return $association;
	}

	/**
	 * Returns the association
	 *
	 * @param OutletEntity $entity
	 * @param OutletEntity $relatedEntity
	 * @param SimpleXMLElement $assoc
	 * @return OutletManyToOneAssociation|OutletManyToManyAssociation|OutletOneToOneAssociation|OutletOneToManyAssociation
	 */
	protected function getAssociation(OutletEntity $entity, OutletEntity $relatedEntity, SimpleXMLElement $assoc)
	{
		$type = $this->getNodeAttrValue($assoc, 'type');

		switch ($type) {
			case 'many-to-one':
				return new OutletManyToOneAssociation($relatedEntity, $this->getNodeAttrValue($assoc, 'key'), $relatedEntity->getMainPrimaryKey());
			case 'many-to-many':
				return new OutletManyToManyAssociation(
					$relatedEntity, $relatedEntity->getMainPrimaryKey(),
					$entity->getMainPrimaryKey(), $this->getNodeAttrValue($assoc, 'table'),
					$this->getNodeAttrValue($assoc, 'tableKeyLocal'), $this->getNodeAttrValue($assoc, 'tableKeyForeign')
				);
			case 'one-to-one':
				return new OutletOneToOneAssociation($relatedEntity, $this->getNodeAttrValue($assoc, 'key'), $relatedEntity->getMainPrimaryKey());
			case 'one-to-many':
				return new OutletOneToManyAssociation($relatedEntity, $this->getNodeAttrValue($assoc, 'key'), $entity->getMainPrimaryKey());
		}
	}

	/**
	 * Validates the association options
	 *
	 * @param SimpleXMLElement $assoc
	 * @throws OutletXmlParserException
	 */
	protected function validateAssociationConfig(SimpleXMLElement $assoc)
	{
		$type = $this->getNodeAttrValue($assoc, 'type');
		$key = $this->getNodeAttrValue($assoc, 'key');
		$name = $this->getNodeAttrValue($assoc, 'name');

		if ($type != 'many-to-many') {
			if ($this->hasAnyManyToManyParams($assoc)) {
				throw new OutletXmlParserException('The "table", "tableKeyLocal" or "tableKeyForeign" attributes are prohibited in the ' . $type . ' association');
			}

			if (!$key) {
				throw new OutletXmlParserException('You must define the association\'s key');
			}

			if ($type != 'one-to-many') {
				if ($this->getNodeAttrValue($assoc, 'plural')) {
					throw new OutletXmlParserException('The "plural" attribute is prohibited in the ' . $type . ' association');
				}
			} elseif ($this->getNodeAttrValue($assoc, 'optional')) {
				throw new OutletXmlParserException('The "optional" attribute is prohibited in the ' . $type . ' association');
			}
		} elseif ($key || $name || !$this->hasAllManyToManyParams($assoc)) {
			throw new OutletXmlParserException('The ' . $type . ' association must have only "table", "tableKeyLocal", "tableKeyForeign" or "plural" attributes');
		}
	}

	/**
	 * Returs if the association has all of the many to many params
	 *
	 * @return boolean
	 */
	protected function hasAllManyToManyParams(SimpleXMLElement $assoc)
	{
		$table = $this->getNodeAttrValue($assoc, 'table');
		$tableKeyLocal = $this->getNodeAttrValue($assoc, 'tableKeyLocal');
		$tableKeyForeign = $this->getNodeAttrValue($assoc, 'tableKeyForeign');

		return $table && $tableKeyLocal && $tableKeyForeign;
	}

	/**
	 * Returs if the association has one of the many to many params
	 *
	 * @return boolean
	 */
	protected function hasAnyManyToManyParams(SimpleXMLElement $assoc)
	{
		$table = $this->getNodeAttrValue($assoc, 'table');
		$tableKeyLocal = $this->getNodeAttrValue($assoc, 'tableKeyLocal');
		$tableKeyForeign = $this->getNodeAttrValue($assoc, 'tableKeyForeign');

		return $table || $tableKeyLocal || $tableKeyForeign;
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