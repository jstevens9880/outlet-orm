<?php
/**
 * Classe responsável pelos DELETS
 *
 * @package org.outlet-orm
 * @subpackage database
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */

/**
 * Classe responsável pelos DELETS
 *
 * @package org.outlet-orm
 * @subpackage database
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */
class OutletDeleteQuery extends OutletQuery
{
	/**
	 * Sets the entity
	 *
	 * @param string $entityName
	 * @return OutletDeleteQuery
	 */
	public function from($entityName)
	{
		return $this->fromTable('{' . $entityName . '}');
	}

	/**
	 * Sets the table
	 *
	 * @param string $tableName
	 * @return OutletDeleteQuery
	 */
	public function fromTable($tableName)
	{
		$this->table = $tableName;

		return $this;
	}

	/**
	 * @see OutletQuery::toSql()
	 * @return string
	 */
	public function toSql()
	{
		if (is_null($this->table)) {
			throw new BadMethodCallException('You must set the FROM clause');
		}

		if (count($this->criteria) == 0) {
			throw new BadMethodCallException('You must set the WHERE clause');
		}

		return 'DELETE FROM ' . $this->table . ' WHERE ' . implode(' AND ', $this->criteria);
	}
}