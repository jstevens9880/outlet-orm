<?php
/**
 * File level comment
 *
 * @package org.outlet-orm
 * @subpackage database
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */

/**
 * Base classe for queries that writes on database
 *
 * @package org.outlet-orm
 * @subpackage database
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */
abstract class OutletWriteQuery extends OutletQuery
{
	/**
	 * Params to query (associative array)
	 *
	 * @var array
	 */
	protected $fields;

	/**
	 * Fields that are an expression
	 *
	 * @var array
	 */
	protected $expressions;

	/**
	 * Class constructor
	 */
	public function __construct()
	{
		parent::__construct();

		$this->fields = array();
		$this->expressions = array();
	}

	/**
	 * Configures the values that will be inserted
	 *
	 * @param string $prop
	 * @param string $value
	 * @param string $type
	 * @return OutletInsertQuery
	 */
	public function set($prop, $value, $type)
	{
		$this->fields[$prop] = self::toSqlValue($type, $value);

		return $this;
	}

	/**
	 * Configures the values that will be inserted
	 *
	 * @param string $prop
	 * @param string $value
	 * @return OutletInsertQuery
	 */
	public function setExpression($prop, $value)
	{
		$this->fields[$prop] = $value;
		$this->expressions[] = $prop;

		return $this;
	}

	/**
	 * Translates a PHP value to a SQL Value
	 *
	 * @param string $type
	 * @param mixed $value
	 * @return mixed
	 */
	public static function toSqlValue($type, $value)
	{
		if (is_null($value)) {
			return null;
		}

		switch ($type) {
			case 'date':
				return $value->format('Y-m-d');
			case 'datetime':
				return $value->format('Y-m-d H:i:s');
			case 'int':
			case 'bool':
				return (int) $value;
			case 'float':
				return (float) $value;
			default:
				return $value;
		}
	}

	/**
	 * Executes the query using the parameters
	 * that were setted using OutletUpdateQuery::set().
	 *
	 * @param array $params This will be merged on the setted values
	 * @return PDOStatement
	 */
	public function execute(array $params = array())
	{
		return parent::execute(
			array_merge(
				$this->getNonExpressionValues(),
				$params
			)
		);
	}

	/**
	 * Returns if the field is an expression
	 *
	 * @param string $field
	 * @return boolean
	 */
	protected function isExpression($field)
	{
		return in_array($field, $this->expressions);
	}

	/**
	 * Return the non expression values
	 *
	 * @return array
	 */
	protected function getNonExpressionValues()
	{
		$values = array();

		foreach ($this->fields as $field => $value) {
			if (!$this->isExpression($field)) {
				$values[] = $value;
			}
		}

		return $values;
	}
}