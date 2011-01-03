<?php
/**
 * File level comment
 *
 * @package org.outlet-orm
 * @subpackage database
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */

/**
 * Base classe for queries
 *
 * @package org.outlet-orm
 * @subpackage database
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */
abstract class OutletQuery
{
	/**
	 * Entity name
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * Where clause
	 *
	 * @var array
	 */
	protected $criteria;

	/**
	 * Creates a new delete query
	 *
	 * @return OutletDeleteQuery
	 */
	public static function delete()
	{
		return new OutletDeleteQuery();
	}

	/**
	 * Creates a new update query
	 *
	 * @param string $entityName
	 * @return OutletUpdateQuery
	 */
	public static function update($entityName)
	{
		return new OutletUpdateQuery($entityName);
	}

	/**
	 * Creates a new insert query
	 *
	 * @return OutletInsertQuery
	 */
	public static function insert()
	{
		return new OutletInsertQuery();
	}

	/**
	 * Creates a new select query
	 *
	 * @return OutletSelectQuery
	 */
	public static function select()
	{
		return new OutletSelectQuery();
	}

	/**
	 * Class constructor
	 */
	public function __construct()
	{
		$this->criteria = array();
	}

	/**
	 * Sets the criteria
	 *
	 * @param string $criteria
	 * @return OutletQuery
	 */
	public function where($criteria)
	{
		$this->criteria[] = $criteria;

		return $this;
	}

	/**
	 * @return string
	 */
	public abstract function toSql();

	/**
	 * Executes the query using the given parameters
	 *
	 * @param array $params
	 * @return PDOStatement
	 */
	public function execute(array $params = array())
	{
		$stm = $this->getEntityManager()->getConnection()->prepare(
			$this->getTranslator()->translate($this)
		);

		$stm->execute($params);

		return $stm;
	}

	/**
	 * @return OutletQueryTranslator
	 */
	protected function getTranslator()
	{
		return new OutletQueryTranslator();
	}

	/**
	 * @return OutletEntityManager
	 */
	protected function getEntityManager()
	{
		return OutletEntityManager::getInstance();
	}
}