<?php
/**
 * Contains the base class to DAO support
 *
 * @package org.outlet-orm.utils
 * @subpackage dao_support
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */

/**
 * Base class for DAO's
 *
 * @package org.outlet-orm.utils
 * @subpackage dao_support
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */
abstract class OutletDaoSupport
{
	/**
	 * Returns an Outlet instance
	 *
	 * @return Outlet
	 */
	protected function getOutlet()
	{
		return Outlet::getInstance();
	}
}