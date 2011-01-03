<?php
/**
 * File level comment
 *
 * @package org.outlet-orm
 * @subpackage proxy
 * @author Alvaro Carrasco
 */

/**
 * Generator for all proxy classes
 *
 * @package org.outlet-orm
 * @subpackage proxy
 * @author Alvaro Carrasco
 */
class OutletProxyGenerator
{
	/**
	 * Generates the source code for the proxy classes
	 *
	 * @return string class source
	 */
	public function generate()
	{
		$c = '';

		foreach ($this->getEntityMaps() as $entity) {
			if (!class_exists($this->getProxyName($entity->getName()))) {
				$c .= $this->generateProxy($entity);
			}
		}

		return $c;
	}

	/**
	 * Generate the proxy class definition
	 *
	 * @param OutletEntity $entity
	 * @throws OutletException
	 * @return string
	 */
	public function generateProxy(OutletEntity $entity)
	{
		$c = $this->getFormattedString('class ' . $this->getProxyName($entity->getName()) . ' extends ' . $entity->getName() . ' implements OutletProxy', 0, 1);
		$c .= $this->getFormattedString('{', 0, 0);

		foreach ($entity->getAssociations() as $assoc) {
			switch (get_class($assoc)) {
				case 'OutletOneToManyAssociation':
					$c .= $this->createOneToManyFunctions($entity, $assoc);
					break;
				case 'OutletManyToOneAssociation':
					$c .= $this->createManyToOneFunctions($entity, $assoc);
					break;
				case 'OutletOneToOneAssociation':
					$c .= $this->createOneToOneFunctions($entity, $assoc);
					break;
				case 'OutletManyToManyAssociation':
					$c .= $this->createManyToManyFunctions($assoc);
					break;
				default:
					throw new OutletException('Invalid association type: ' . get_class($assoc));
			}
		}

		if (substr($c, -1, 1) == "{") {
			$c .= $this->getFormattedString('', 0, 1);
		}

		$c .= $this->getFormattedString('}', 0, 2);

		return $c;
	}

	/**
	 * @param string $className
	 * @return string
	 */
	protected function getProxyName($className)
	{
		return OutletProxyFactory::getInstance()->getProxyName($className);
	}

	/**
	 * @return ArrayObject
	 */
	protected function getEntityMaps()
	{
		return Outlet::getInstance()->getConfig()->getEntities();
	}

	/**
	 * Generates the code to support one to one associations
	 *
	 * @param OutletAssociationConfig $config configuration
	 * @return string one to one function code
	 */
	public function createOneToOneFunctions(OutletEntity $entMap, OutletOneToOneAssociation $assoc)
	{
		$foreign = $assoc->getEntity()->getName();
		$key = $assoc->getKey();
		$getter = 'get'.$assoc->getName();
		$setter = 'set'.$assoc->getName();

		if ($entMap->useGettersAndSetters('get' . $key)) {
			$key = 'get' . ucfirst($key) . '()';
		}

		$c = '';
		$c .= $this->getFormattedString('', 0, 1);
		$c .= $this->getFormattedString('public function ' . $getter . '()', 1, 1);
		$c .= $this->getFormattedString('{', 1, 1);
		$c .= $this->getFormattedString('  if (is_null($this->' . $key . ')) {', 2, 1);
		$c .= $this->getFormattedString('    return parent::' . $getter . '();', 3, 1);
		$c .= $this->getFormattedString('  }', 2, 2);

		$c .= $this->getFormattedString('  if (is_null(parent::' . $getter . '()) && $this->' . $key .') {', 2, 1);
		$c .= $this->getFormattedString('    parent::' . $setter . '(Outlet::getInstance()->load(\'' . $foreign . '\', $this->' . $key . '));', 3, 1);
		$c .= $this->getFormattedString('  }', 2, 2);

		$c .= $this->getFormattedString('  return parent::' . $getter . '();', 2, 1);
		$c .= $this->getFormattedString('}', 1, 1);

		return $c;
	}

	/**
	 * Generates the code to support one to many associations
	 *
	 * @param OutletAssociationConfig $config configuration
	 * @return string one to many functions code
	 */
	public function createOneToManyFunctions(OutletEntity $entMap, OutletOneToManyAssociation $assoc)
	{
		$foreign = $assoc->getEntity()->getName();
		$key = $assoc->getKey();
		$pk_prop = $assoc->getRefKey();

		$getter = 'get' . $assoc->getName();
		$setter = 'set' . $assoc->getName();

		if ($entMap->useGettersAndSetters('get' . $pk_prop)) {
			$pk_prop = 'get' . ucfirst($pk_prop) . '()';
		}

		$c = '';
		$c .= $this->getFormattedString('', 0, 1);
		$c .= $this->getFormattedString('public function ' . $getter . '()', 1, 1);
		$c .= $this->getFormattedString('{', 1, 1);
		$c .= $this->getFormattedString('  $args = func_get_args();', 2, 2);

		$c .= $this->getFormattedString('  if (count($args)) {', 2, 1);
		$c .= $this->getFormattedString('    if (is_null($args[0])) {', 3, 1);
		$c .= $this->getFormattedString('      return parent::' . $getter . '();', 4, 1);
		$c .= $this->getFormattedString('    }', 3, 2);

		$c .= $this->getFormattedString('    $q = $args[0];', 3, 1);
		$c .= $this->getFormattedString('  } else {', 2, 1);
		$c .= $this->getFormattedString('    $q = \'\';', 3, 1);
		$c .= $this->getFormattedString('  }', 2, 2);

		$c .= $this->getFormattedString('  if (isset($args[1])) {', 2, 1);
		$c .= $this->getFormattedString('    $params = $args[1];', 3, 1);
		$c .= $this->getFormattedString('  } else {', 2, 1);
		$c .= $this->getFormattedString('    $params = array();', 3, 1);
		$c .= $this->getFormattedString('  }', 2, 2);

		$c .= $this->getFormattedString('  $q = trim($q);', 2, 2);

		$c .= $this->getFormattedString('  if (stripos($q, \'where\') !== false) {', 2, 1);
		$c .= $this->getFormattedString('    $q = \'{' . $foreign . '.' . $key . '} = \' . $this->' . $pk_prop . ' . \' AND \' . substr($q, 5);', 3, 1);
		$c .= $this->getFormattedString('  } else {', 2, 1);
		$c .= $this->getFormattedString('    $q = \'{' . $foreign . '.' . $key . '} = \' . $this->' . $pk_prop . ' . \' \' . $q;', 3, 1);
		$c .= $this->getFormattedString('  }', 2, 2);

		$c .= $this->getFormattedString('  $query = Outlet::getInstance()->from(\'' . $foreign . '\')->where($q, $params);', 2, 1);
		$c .= $this->getFormattedString('  $cur_coll = parent::' . $getter . '();', 2, 2);

		$c .= $this->getFormattedString('  if (!$cur_coll instanceof OutletCollection || $cur_coll->getQuery() != $query) {', 2, 1);
		$c .= $this->getFormattedString('    parent::' . $setter . '(new OutletCollection($query));', 3, 1);
		$c .= $this->getFormattedString('  }', 2, 2);

		$c .= $this->getFormattedString('  return parent::' . $getter . '();', 2, 1);

		$c .= $this->getFormattedString('}', 1, 1);

		return $c;
	}

	/**
	 * Generates the code to support many to many associations
	 *
	 * @param OutletManyToManyConfig $config configuration
	 * @return string many to many function code
	 */
	public function createManyToManyFunctions(OutletManyToManyAssociation $assoc)
	{
		$foreignMap = $assoc->getEntity();
		$foreign = $foreignMap->getName();
		$tableKeyLocal = $assoc->getTableKeyLocal();
		$tableKeyForeign = $assoc->getTableKeyForeign();

		$pk_prop = $assoc->getKey();
		$ref_pk = $assoc->getRefKey();
		$getter = 'get'.$assoc->getName();
		$setter = 'set'.$assoc->getName();
		$table = $assoc->getLinkingTable();

		if ($foreignMap->useGettersAndSetters('get' . $ref_pk)) {
			$ref_pk = 'get' . ucfirst($ref_pk) . '()';
		}

		$c = '';
		$c .= $this->getFormattedString('', 0, 1);
		$c .= $this->getFormattedString('public function ' . $getter . '()', 1, 1);
		$c .= $this->getFormattedString('{', 1, 1);
		$c .= $this->getFormattedString('  if (parent::' . $getter . '() instanceof OutletCollection) {', 2, 1);
		$c .= $this->getFormattedString('    return parent::' . $getter . '();', 3, 1);
		$c .= $this->getFormattedString('  }', 2, 2);

		$c .= $this->getFormattedString('  $q = Outlet::getInstance()->from(\'' . $foreign . '\')', 2, 1);
		$c .= $this->getFormattedString('       ->innerJoinWithTable(\'' . $table . '\', \'' . $table . '.' . $tableKeyForeign . '\', \'{' . $foreign . '.' . $pk_prop . '}\')', 4, 1);
		$c .= $this->getFormattedString('       ->where(\'' . $table . '.' . $tableKeyLocal . ' = ?\', array($this->' . $ref_pk . '));', 4, 2);

		$c .= $this->getFormattedString('  parent::' . $setter . '(new OutletCollection($q));', 2, 2);
		$c .= $this->getFormattedString('  return parent::' . $getter . '();', 2, 1);
		$c .= $this->getFormattedString('}', 1, 1);

		return $c;
	}

	/**
	 * Generates the code to support many to one associations
	 *
	 * @param OutletAssociationConfig $config configuration
	 * @return string many to one function code
	 */
	public function createManyToOneFunctions(OutletEntity $entMap, OutletManyToOneAssociation $assoc)
	{
		$foreignMap = $assoc->getEntity();
		$foreign = $foreignMap->getName();
		$key = $assoc->getKey();
		$refKey = $assoc->getRefKey();

		$getter = 'get'.$assoc->getName();
		$setter = 'set'.$assoc->getName();

		if ($entMap->useGettersAndSetters('get' . $key)) {
			$keyGetter = 'get' . ucfirst($key) . '()';
		} else {
			$keyGetter = $key;
		}

		if ($foreignMap->useGettersAndSetters('get' . $refKey)) {
			$refKey = 'get' . ucfirst($refKey) . '()';
		}

		$c = '';
		$c .= $this->getFormattedString('', 0, 1);
		$c .= $this->getFormattedString('public function ' . $getter . '()', 1, 1);
		$c .= $this->getFormattedString('{', 1, 1);
		$c .= $this->getFormattedString('  if (is_null($this->' . $keyGetter . ')) {', 2, 1);
		$c .= $this->getFormattedString('    return parent::' . $getter . '();', 3, 1);
		$c .= $this->getFormattedString('  }', 2, 2);

		$c .= $this->getFormattedString('  if (is_null(parent::' . $getter . '())) {', 2, 1);
		$c .= $this->getFormattedString('    parent::' . $setter .'(Outlet::getInstance()->load(\'' . $foreign . '\', $this->' . $keyGetter . '));', 3, 1);
		$c .= $this->getFormattedString('  }', 2, 2);

		$c .= $this->getFormattedString('  return parent::' . $getter . '();', 2, 1);
		$c .= $this->getFormattedString('}', 1, 1);

		$c .= $this->getFormattedString('', 0, 1);
		$c .= $this->getFormattedString('public function ' . $setter . '(' . $foreign . ' $ref' . ($assoc->isOptional() ? ' = null' : '') . ')', 1, 1);
		$c .= $this->getFormattedString('{', 1, 1);
		$c .= $this->getFormattedString('  if (is_null($ref)) {', 2, 1);

		if ($assoc->isOptional()) {
			$c .= $this->getFormattedString('    return parent::' . $setter . '(null);', 3, 1);
		} else {
			$c .= $this->getFormattedString('    throw new OutletException(\'You can not set this to NULL since this relationship has not been marked as optional\');', 3, 1);
		}

		$c .= $this->getFormattedString('  }', 2, 2);

		if ($entMap->useGettersAndSetters('set' . $key)) {
			$c .= $this->getFormattedString('  $this->set' . $key . '($ref->' . $refKey . ');', 2, 2);
		} else {
			$c .= $this->getFormattedString('  $this->' . $key . ' = $ref->' . $refKey . ';', 2, 2);
		}

		$c .= $this->getFormattedString('  return parent::' . $setter . '($ref);', 2, 2);
		$c .= $this->getFormattedString('}', 1, 1);

		return $c;
	}

	/**
	 * Returns the formated line string
	 *
	 * @param string $string
	 * @param int $identyLevel
	 * @param int $numNewLines
	 * @return string
	 */
	protected function getFormattedString($string, $identyLevel, $numNewLines)
	{
		return str_repeat("\t", $identyLevel) . trim($string) . str_repeat("\n", $numNewLines);
	}
}