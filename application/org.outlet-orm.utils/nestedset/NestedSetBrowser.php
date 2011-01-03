<?php
/**
 * File level comment
 *
 * @package org.outlet-orm
 * @subpackage nestedset
 * @author Alvaro Carrasco
 */

/**
 * NestedSetBrowser....
 *
 * @package org.outlet-orm
 * @subpackage nestedset
 * @author Alvaro Carrasco
 */
class NestedSetBrowser
{
	/**
	 * @var string
	 */
	protected $cls;

	/**
	 * @var string
	 */
	protected $left;

	/**
	 * @var string
	 */
	protected $right;

	/**
	 * @var array
	 */
	protected $qualifiers;

	/**
	 * @param string $cls Entity class in which the hierarchy is stored
	 * @param array $qualifiers Properties of the entity that determine which tree to look for children
	 * @param string $left Name of class property that represents the left value
	 * @param string $right Name of class property that represents the right value
	 */
	public function __construct($cls, array $qualifiers = array(), $left = 'Left', $right = 'Right')
	{
		$this->cls = $cls;
		$this->qualifiers = $qualifiers;
		$this->left = $left;
		$this->right = $right;
	}

	/**
	 * @param unknown_type $node
	 */
	public function remove($node)
	{
		$con = OutletEntityManager::getInstance()->getConnection($this->cls);
		$con->beginTransaction();

		$leftProperty = '{' . $this->cls . '.' . $this->left . '}';
		$rightProperty = '{' . $this->cls . '.' . $this->right . '}';

		OutletQuery::delete()->from($this->cls)
							 ->where($leftProperty . ' = ?')
							 ->where($rightProperty . ' = ?')
							 ->execute(array($node->Left, $node->Right));

		$qualifiers = array();

		foreach ($this->qualifiers as $field) {
			$qualifiers[] = '{' . $field . '} = ' . $con->quote($node->$field);
		}

		OutletQuery::update($this->cls)
					 ->set($leftProperty, $leftProperty . ' - 2', true)
					 ->where($qualifiers)
					 ->where($leftProperty . ' > ?')
					 ->execute(array($node->Left));

		OutletQuery::update($this->cls)
					 ->set($rightProperty, $rightProperty . ' - 2', true)
					 ->where($qualifiers)
					 ->where($rightProperty . ' > ?')
					 ->execute(array($node->Right));

		$con->commit();
	}

	public function appendChild($parent, $node)
	{
		$con = OutletEntityManager::getInstance()->getConnection($this->cls);
		$con->beginTransaction();

		$leftProperty = '{' . $this->cls . '.' . $this->left . '}';
		$rightProperty = '{' . $this->cls . '.' . $this->right . '}';

		$qualifiers = array();

		foreach ($this->qualifiers as $field) {
			$qualifiers[] = '{' . $this->cls . '.' .$field . '} = ' . $con->quote($parent->$field);
		}

		OutletQuery::update($this->cls)
					 ->set($leftProperty, $leftProperty . ' + 2', true)
					 ->where($qualifiers)
					 ->where($leftProperty . ' > ?')
					 ->execute(array($parent->{$this->right}));

		OutletQuery::update($this->cls)
					 ->set($rightProperty, $rightProperty . ' + 2', true)
					 ->where($qualifiers)
					 ->where($rightProperty . ' > ?')
					 ->execute(array($parent->{$this->right}));

		$parent->{$this->right} += 2;
		$node->{$this->left} = $parent->{$this->right} - 2;
		$node->{$this->right} = $parent->{$this->right} - 1;

		foreach ($this->qualifiers as $field) {
			$node->$field = $parent->$field;
		}

		Outlet::getInstance()->save($node);

		$con->commit();

		return $node;
	}

	/**
	 * Enter description here ...
	 *
	 * @param object $obj
	 * @return OutletProxy[]
	 */
	public function getChildren($obj)
	{
		$query = Outlet::getInstance()->from($this->cls . ' n');

		$query->leftJoinWithExpression(
			$this->cls  . ' parent',
			'({parent.' . $this->left . '} < {n.' . $this->left . '} AND {parent.' . $this->right . '} > {n.' . $this->right . '})'
		);

		foreach ($this->qualifiers as $field) {
			$query->where('{n.' . $field . '} = ?', array($obj->$field));
		}

		$query->where('{n.' . $this->left . '} = ?', array($obj->Left))
			  ->where('{n.' . $this->right . '} = ?', array($obj->Right))
			  ->where('{parent.' . $this->left . '} = ?', array($obj->Left))
			  ->groupBy('n.' . $this->left)
			  ->groupBy('n.' . $this->right)
			  ->having('COUNT({parent.' . $this->left . '}) = 1');

		return $query->find();
	}
}