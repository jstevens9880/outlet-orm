<?php
/**
 * Classe responsável pelos SELECTS
 *
 * @package org.outlet-orm
 * @subpackage database
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */

/**
 * Classe responsável pelos SELECTS
 *
 * @package org.outlet-orm
 * @subpackage database
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */
class OutletSelectQuery extends OutletQuery
{
	/**
	 * Select clause
	 *
	 * @var array
	 */
	protected $fields;

	/**
	 * Joins
	 *
	 * @var array
	 */
	protected $joins;

	/**
	 * Orders
	 *
	 * @var array
	 */
	protected $orders;

	/**
	 * Group by
	 *
	 * @var array
	 */
	protected $groups;

	/**
	 * Having
	 *
	 * @var array
	 */
	protected $having;

	/**
	 * Limit
	 *
	 * @var int
	 */
	protected $limit;

	/**
	 * Offset
	 *
	 * @var int
	 */
	protected $offset;

	/**
	 * @var array
	 */
	protected $params;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		$this->fields = array();
		$this->joins = array();
		$this->orders = array();
		$this->groups = array();
		$this->having = array();
		$this->params = array();
	}

	/**
	 * Sets the criteria
	 *
	 * @param string $criteria
	 * @param array $params
	 * @return OutletSelectQuery
	 */
	public function where($criteria, array $params = array())
	{
		$this->params = array_merge($this->params, $params);

		return parent::where($criteria);
	}

	/**
	 * Add a new property to select
	 *
	 * @param string $field
	 * @param string $alias
	 * @return OutletSelectQuery
	 */
	public function appendProperty($property, $alias = null)
	{
		return $this->appendField('{' . $property . '}', $alias);
	}

	/**
	 * Add a new field to select
	 *
	 * @param string $field
	 * @param string $alias
	 * @return OutletSelectQuery
	 */
	public function appendField($field, $alias = null)
	{
		$this->fields[$field] = $field . (!is_null($alias) ? ' as ' . $alias : '');

		return $this;
	}

	/**
	 * Sets entity
	 *
	 * @param string $entityName
	 * @return OutletSelectQuery
	 */
	public function from($entityName)
	{
		$this->table = '{' . $entityName . '}';

		return $this;
	}

	/**
	 * Gets the from data
	 *
	 * @return stdClass
	 */
	protected function getFromData()
	{
		return $this->extractEntityAndAlias(substr($this->table, 1, -1));
	}

	/**
	 * Extracts entityName and alias
	 *
	 * @param string $from
	 * @return stdClass
	 */
	protected function extractEntityAndAlias($from)
	{
		$data = new stdClass();
		$from = explode(' ', $from);

		$data->entityName = $from[0];
		$data->alias = isset($from[1]) ? $from[1] : $from[0];

		return $data;
	}

	/**
	 * Include associated entity tables
	 *
	 * @param string $foreignEntityName
	 * @return OutletSelectQuery
	 */
	public function with($foreignEntityName)
	{
		$entityData = $this->getFromData();
		$foreignData = $this->extractEntityAndAlias($foreignEntityName);

		$entity = $this->getEntityManager()->getEntity($entityData->entityName);

		if ($assoc = $entity->getAssociation($foreignData->entityName)) {
			$foreignEntity = $assoc->getEntity();

			return $this->leftJoin($foreignEntity->getName() . ' ' . $foreignData->alias, $entityData->alias . '.' . $assoc->getKey(), $foreignData->alias . '.' . $assoc->getRefKey());
		}

		throw new OutletException('No association found with entity or alias [' . $foreignData->entityName . ']');
	}

	/**
	 * Adds a new left join
	 *
	 * @param string $foreignEntityName
	 * @param string $entityKey
	 * @param string $foreignEntityKey
	 * @return OutletSelectQuery
	 */
	public function leftJoin($foreignEntityName, $entityKey, $foreignEntityKey)
	{
		$this->joins[$foreignEntityName] = 'LEFT JOIN {' . $foreignEntityName . '} ON {' . $entityKey . '} = {' . $foreignEntityKey . '}';

		return $this;
	}

	/**
	 * Adds a new left join with an expression
	 *
	 * @param string $foreignEntityName
	 * @param string $expression
	 * @return OutletSelectQuery
	 */
	public function leftJoinWithExpression($foreignEntityName, $expression)
	{
		$this->joins[$foreignEntityName] = 'LEFT JOIN {' . $foreignEntityName . '} ON ' . $expression;

		return $this;
	}

	/**
	 * Adds a new inner join
	 *
	 * @param string $foreignEntityName
	 * @param string $entityKey
	 * @param string $foreignEntityKey
	 * @return OutletSelectQuery
	 */
	public function innerJoin($foreignEntityName, $entityKey, $foreignEntityKey)
	{
		$this->joins[$foreignEntityName] = 'INNER JOIN {' . $foreignEntityName . '} ON {' . $entityKey . '} = {' . $foreignEntityKey . '}';

		return $this;
	}

	/**
	 * Adds a new inner join
	 *
	 * @param string $table
	 * @param string $key
	 * @param string $foreignKey
	 * @return OutletSelectQuery
	 */
	public function innerJoinWithTable($table, $key, $foreignKey)
	{
		$this->joins[$table] = 'INNER JOIN ' . $table . ' ON ' . $key . ' = ' . $foreignKey;

		return $this;
	}

	/**
	 * Adds a new sort element
	 *
	 * @param string $property
	 * @param string $sortOrder
	 * @return OutletSelectQuery
	 */
	public function orderBy($property, $sortOrder = 'ASC')
	{
		$this->orders[$property] = '{' . $property . '} ' . $sortOrder;

		return $this;
	}

	/**
	 * Sets the limit
	 *
	 * @param int $limit
	 * @return OutletSelectQuery
	 */
	public function limit($limit)
	{
		$this->limit = $limit;

		return $this;
	}

	/**
	 * Sets the offset
	 *
	 * @param int $offset
	 * @return OutletSelectQuery
	 */
	public function offset($offset)
	{
		$this->offset = $offset;

		return $this;
	}

	/**
	 * Adds a new having condition
	 *
	 * @param string $condition
	 * @return OutletSelectQuery
	 */
	public function having($condition)
	{
		$this->having[] = $condition;

		return $this;
	}

	/**
	 * Adds a new group by condition
	 *
	 * @param unknown_type $property
	 * @return OutletSelectQuery
	 */
	public function groupBy($property)
	{
		$this->groups[$property] = '{' . $property . '}';

		return $this;
	}

	/**
	 * Returns the dataset
	 *
	 * @return array
	 */
	public function find()
	{
		$result = array();
		$stm = $this->execute($this->params);

		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			$result[] = $this->getEntityManager()->getEntityForRow($this->getFromData()->entityName, $row);
		}

		return $result;
	}

	/**
	 * Executes query returning the first result out of the result set
	 *
	 * @return object first result out of the result set
	 */
	public function findOne()
	{
		$result = $this->limit(1)->find();

		return count($result) > 0 ? $result[0] : null;
	}

	/**
	 * Number of rows
	 *
	 * @return int
	 */
	public function count()
	{
		$query = clone $this;
		$query->appendField('COUNT(*)', 'total');

		$stm = $query->execute($this->params);
		$res = $stm->fetch(PDO::FETCH_ASSOC);

		return (int) $res['total'];
	}

	/**
	 * Returns the group by clause
	 *
	 * @return string
	 */
	protected function getGroupByClause()
	{
		if (isset($this->groups[0])) {
			return 'GROUP BY ' . implode(', ',	$this->groups);
		}
	}

	/**
	 * Returns the group by clause
	 *
	 * @return string
	 */
	protected function getOrderByClause()
	{
		if (isset($this->orders[0])) {
			return 'ORDER BY ' . implode(', ',	$this->orders);
		}
	}

	/**
	 * Returns the limit clause
	 *
	 * @return string
	 */
	protected function getLimitClause()
	{
		if ($this->limit) {
			$query = 'LIMIT ' . $this->limit;

			if ($this->offset) {
				$query .= ' OFFSET ' . $this->offset;
			}

			return $query;
		}
	}

	/**
	 * Fill the $this->fields with the entity properties
	 */
	protected function fillSelectedFields()
	{
		$entityData = $this->getFromData();
		$entity = $this->getEntityManager()->getEntity($entityData->entityName);

		foreach (array_keys($entity->getProperties()) as $property) {
			$this->appendProperty($entityData->alias . '.' . $property);
		}
	}

	/**
	 *
	 * @return string
	 * @see OutletQuery::toSql()
	 */
	public function toSql()
	{
		$where = isset($this->criteria[0]) ? 'WHERE ' . implode('AND ',	$this->criteria) : '';
		$having = isset($this->having[0]) ? 'HAVING ' . implode('AND ',	$this->having) : '';

		if (isset($this->fields['COUNT(*)'])) {
			return
				'SELECT
					' . $this->fields['COUNT(*)'] . '
				FROM
					' . $this->table . '
				' . implode("\n", $this->joins) . '
				' . $where . '
				' . $having;
		} else {
			$this->fillSelectedFields();

			return
				'SELECT
					' . implode(', ', $this->fields) . '
				FROM
					' . $this->table . '
				' . implode("\n", $this->joins) . '
				' . $where . '
				' . $this->getGroupByClause() . '
				' . $this->getOrderByClause() . '
				' . $having . '
				' . $this->getLimitClause();
		}
	}
}