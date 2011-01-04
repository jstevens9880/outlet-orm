<?php
/**
 * Class responsable for INSERTS
 *
 * @package org.outlet-orm
 * @subpackage database
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */

/**
 * Class responsable for INSERTS
 *
 * @package org.outlet-orm
 * @subpackage database
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */
class OutletInsertQuery extends OutletWriteQuery
{
	/**
	 * Sets the entity
	 *
	 * @param string $entityName
	 * @return OutletInsertQuery
	 */
	public function into($entityName)
	{
		return $this->intoTable('{' . $entityName . '}');
	}

	/**
	 * Sets the table
	 *
	 * @param string $tableName
	 * @return OutletInsertQuery
	 */
	public function intoTable($tableName)
	{
		$this->table = $tableName;

		return $this;
	}

	/**
	 * @see OutletQuery::toSql()
	 */
	public function toSql()
	{
		if (is_null($this->table)) {
			throw new BadMethodCallException('You must set the FROM clause');
		}

		if (count($this->fields) == 0) {
			throw new BadMethodCallException('You must set the fields to insert');
		}

		$values = array();

		foreach (array_keys($this->fields) as $field) {
			$values[] = $this->isExpression($field) ? $this->fields[$field] : '?';
		}

		return 'INSERT INTO ' . $this->table . ' (' . implode(', ', array_keys($this->fields)) . ')'
				. ' VALUES (' . implode(', ', $values) . ')';
	}
}