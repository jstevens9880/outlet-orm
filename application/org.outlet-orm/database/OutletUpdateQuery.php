<?php
/**
 * Classe responsável pelos UPDATES
 *
 * @package org.outlet-orm
 * @subpackage database
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */

/**
 * Classe responsável pelos UPDATES
 *
 * @package org.outlet-orm
 * @subpackage database
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */
class OutletUpdateQuery extends OutletWriteQuery
{
	/**
	 * Class constructor
	 *
	 * @param string $entityName
	 */
	public function __construct($entityName)
	{
		parent::__construct();

		$this->table = '{' . $entityName . '}';
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

		if (count($this->fields) == 0) {
			throw new BadMethodCallException('You must set the fields to update');
		}

		if (count($this->criteria) == 0) {
			throw new BadMethodCallException('You must set the WHERE clause');
		}

		$fields = array();

		foreach (array_keys($this->fields) as $field) {
			$fields[] = $field . ' = ?';
		}

		return 'UPDATE ' . $this->table . ' SET '
				. implode(', ', $fields)
				. ' WHERE ' . implode(' AND ', $this->criteria);
	}
}