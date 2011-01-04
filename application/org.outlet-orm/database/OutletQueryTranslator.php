<?php
/**
 * Class for query consersion
 *
 * @package org.outlet-orm
 * @subpackage database
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */

/**
 * Class for query consersion
 *
 * @package org.outlet-orm
 * @subpackage database
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */
class OutletQueryTranslator
{
	/**
	 * Entities identifier pattern
	 *
	 * @var string
	 */
	const IDENTIFIER_PATTERN = '/\{[a-zA-Z0-9_\\\]+(( |\.)[a-zA-Z0-9_]+)*\}/';

	/**
	 * Translate a query interpolating properties
	 *
	 * @param OutletQuery $query
	 * @return string
	 */
	public function translate(OutletQuery $queryObj)
	{
		$query = $queryObj->toSql();
		$tables = array();
		$matches = array();
		preg_match_all(self::IDENTIFIER_PATTERN, $query, $matches, PREG_SET_ORDER);

		foreach ($matches as $match) {
			$match = $match[0];
			$str = substr($match, 1, -1);

			if (strpos($str, '.') === false) {
				if (strpos($str, ' ') !== false) {
					list($entityName, $alias) = explode(' ', $str);

					$tables[$alias] = array('table' => $this->getEntityManager()->getEntity($entityName)->getTable(), 'entityName' => $entityName);
					$table = $tables[$alias]['table'] . ' ' . $alias;
				} else {
					$table = $this->getEntityManager()->getEntity($str)->getTable();
					$tables[$str] = array('table' => $table, 'entityName' => $str);
				}

				$query = str_replace($match, $table, $query);
			}
		}

		foreach ($matches as $match) {
			$match = $match[0];
			$str = substr($match, 1, -1);

			if (strpos($str, '.') !== false) {
				list($alias, $prop) = explode('.', $str);
				$entity = $this->getEntityManager()->getEntity($tables[$alias]['entityName']);

				if ($property = $entity->getProperty($prop)) {
					$column = $property->getColumn();
				} else {
					foreach ($entity->getEmbeddedProperties() as $property) {
						if ($embeddedProp = $property->getEmbeddedEntity()->getProperty($prop)) {
							$column = $embeddedProp->getColumn();
						}
					}
				}

				if (!$queryObj instanceof OutletWriteQuery) {
					if ($alias == $tables[$alias]['entityName']) {
						$alias = $tables[$alias]['table'];
					}

					$column = $alias . '.' . $column;
				}

				$query = str_replace($match, $column, $query);
			}
		}

		return $query;
	}

	/**
	 * @return OutletEntityManager
	 */
	protected function getEntityManager()
	{
		return OutletEntityManager::getInstance();
	}
}