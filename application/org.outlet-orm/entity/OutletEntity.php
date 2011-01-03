<?php
/**
 * Contains the class to configure Outlet's entity
 *
 * @package org.outlet-orm
 * @subpackage entity
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */

/**
 * Class that maps the class on a database table
 *
 * @package org.outlet-orm
 * @subpackage entity
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */
class OutletEntity extends OutletEmbeddableEntity
{
	/**
	 * Table name
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * The noun to use when referring to more than one entity
	 *
	 * @var string
	 */
	protected $plural;

	/**
	 * Since PostgreSQL uses sequences instead of auto increment columns, the PDO driver need the sequence name in order to get the generated new id.
	 * If not specified it will use the default: {table_name}_{column_name}_seq
	 *
	 * @var string
	 */
	protected $sequenceName;

	/**
	 * Association list
	 *
	 * @var array
	 */
	protected $associations;

	/**
	 * Primary key list
	 *
	 * @var array
	 */
	protected $primaryKeys;

	/**
	 * Constructor
	 *
	 * @param string $name
	 * @param string $table
	 * @param string $plural
	 * @param string $sequenceName
	 */
	public function __construct($name, $table, $plural = null, $sequenceName = null)
	{
		$this->setName($name);
		$this->setTable($table);
		$this->setPlural(!is_null($plural) ? $plural : $name . 's');
		$this->setSequenceName($sequenceName);
		$this->setProperties(array());
		$this->setAssociations(array());
		$this->setPrimaryKeys(array());
	}

	/**
	 * @return string
	 */
	public function getTable()
	{
		return $this->table;
	}

	/**
	 * @param string $table
	 */
	public function setTable($table)
	{
		$this->table = $table;
	}

	/**
	 * @return string
	 */
	public function getPlural()
	{
		return $this->plural;
	}

	/**
	 * @param string $plural
	 */
	public function setPlural($plural)
	{
		$this->plural = $plural;
	}

	/**
	 * @return string
	 */
	public function getSequenceName()
	{
		return $this->sequenceName;
	}

	/**
	 * @param string $sequenceName
	 */
	public function setSequenceName($sequenceName)
	{
		$this->sequenceName = $sequenceName;
	}

	/**
	 * Append a property to entity
	 *
	 * @param OutletEntityProperty $property
	 */
	public function addProperty(OutletEntityProperty $property)
	{
		$this->properties[$property->getName()] = $property;

		if ($property->getPrimaryKey()) {
			$this->primaryKeys[] = $property->getName();
		}
	}

	/**
	 * @return array
	 */
	public function getAssociations()
	{
		return $this->associations;
	}

	/**
	 * @param array $associations
	 */
	public function setAssociations(array $associations)
	{
		$this->associations = $associations;
	}

	/**
	 * Append an association to entity
	 *
	 * @param OutletAssociation $association
	 */
	public function addAssociation(OutletAssociation $association)
	{
		$this->associations[$association->getName()] = $association;
	}

	/**
	 * Returns the association with that entity
	 *
	 * @param string $entityName
	 * @return OutletAssociation
	 */
	public function getAssociation($entityName)
	{
		if (isset($this->associations[$entityName])) {
			return $this->associations[$entityName];
		}

		return null;
	}

	/**
	 * Filter associations by the especified type
	 *
	 * @param string $associationType
	 * @return array
	 */
	protected function filterAssociations($associationType)
	{
		$associations = array();

		foreach ($this->getAssociations() as $entityName => $association) {
			if ($association instanceof $associationType) {
				$associations[$entityName] = $association;
			}
		}

		return $associations;
	}

	/**
	 * Returns the one-to-one associations
	 *
	 * @return array
	 */
	public function getOneToOneAssociations()
	{
		return $this->filterAssociations('OutletOneToOneAssociation');
	}

	/**
	 * Returns the one-to-many associations
	 *
	 * @return array
	 */
	public function getOneToManyAssociations()
	{
		return $this->filterAssociations('OutletOneToManyAssociation');
	}

	/**
	 * Returns the many-to-one associations
	 *
	 * @return array
	 */
	public function getManyToOneAssociations()
	{
		return $this->filterAssociations('OutletManyToOneAssociation');
	}

	/**
	 * Returns the many-to-many associations
	 *
	 * @return array
	 */
	public function getManyToManyAssociations()
	{
		return $this->filterAssociations('OutletManyToManyAssociation');
	}

	/**
	 * @return array
	 */
	public function getPrimaryKeys()
	{
		return $this->primaryKeys;
	}

	/**
	 * @param array $primaryKeys
	 */
	public function setPrimaryKeys(array $primaryKeys)
	{
		$this->primaryKeys = $primaryKeys;
	}

	/**
	 * @return string
	 */
	public function getMainPrimaryKey()
	{
		return $this->primaryKeys[0];
	}

	/**
	 * @return array
	 */
	public function getPrimaryKeysColumns()
	{
		$columns = array();

		foreach ($this->getPrimaryKeys() as $primaryKey) {
			$property = $this->getProperty($primaryKey);
			$columns[] = $property->getColumn();
		}

		return $columns;
	}

	/**
	 * Extracts the primary keys values
	 *
	 * @param object $obj
	 * @return array
	 */
	public function extractPrimaryKeysValues($obj)
	{
		return OutletEntityHelper::getInstance()->extractPrimaryKeysValues($this, $obj);
	}
}