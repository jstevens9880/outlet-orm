<?php
/**
 * Contains the interface for the configuration parsers
 *
 * @package org.outlet-orm
 * @subpackage config
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */

/**
 * Default methods that every connection parser must have
 *
 * @package org.outlet-orm
 * @subpackage config
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */
interface OutletConfigParser
{
	/**
	 * Creates the connection config
	 */
	public function createConnectionConfig();

	/**
	 * Creates the entity config
	 *
	 * @param string $name
	 */
	public function createEntityConfig($name);

	/**
	 * Creates the embeddable entity config
	 *
	 * @param string $name
	 */
	/*public function createEmbeddableEntityConfig($name);*/

	/**
	 * Returns if the entity exists on config source
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function entityExists($name);

	/**
	 * Returns an instance of OutletConfig
	 *
	 * @return OutletConfig
	 */
	public function getConfig();
}