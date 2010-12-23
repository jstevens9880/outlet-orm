<?php
/**
 * Contains the necessary class to use spl_autoload_register
 *
 * @package org.outlet-orm
 * @subpackage autoloader
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */

/**
 * Class for autoloading with SPL
 *
 * @package org.outlet-orm
 * @subpackage autoloader
 * @author Luís Otávio Cobucci Oblonczyk <luis@softnex.com.br>
 */
class OutletAutoloader
{
	/**
	 * @var array
	 */
	private $directories;

	/**
	 * Search and include the class
	 *
	 * @param string $class
	 * @uses Softnex_AutoLoader::getDirectories()
	 * @uses Softnex_AutoLoader::getFileName()
	 * @uses Softnex_AutoLoader::fileExists()
	 * @uses Softnex_AutoLoader::includeClass()
	 */
	public function autoLoad($class)
	{
		foreach ($this->getDirectories() as $dir) {
			$file = $this->getFileName($dir, $class);

			if ($this->fileExists($file)) {
				$this->includeClass($file);
				break;
			}
		}
	}

	/**
	 * Include the requested file
	 *
	 * @param string $file
	 */
	protected function includeClass($file)
	{
		include $file;
	}

	/**
	 * Get the file path based on class name
	 *
	 * @param string $dir
	 * @param string $class
	 * @return string
	 */
	protected function getFileName($dir, $class)
	{
		return $dir . $class . '.php';
	}

	/**
	 * Checks if the file exists and if it's readable
	 *
	 * @param string $file
	 * @return boolean
	 */
	protected function fileExists($file)
	{
		return is_readable($file);
	}

	/**
	 * Get the list of directories to search
	 *
	 * @return ArrayObject
	 */
	protected function getDirectories()
	{
		if (is_null($this->directories)) {
			$mainDir = realpath(dirname(__FILE__) . '/../') . '/';
			$utilsDir = realpath(dirname(__FILE__) . '/../../org.outlet-orm.utils/') . '/';
			$parsersDir = realpath(dirname(__FILE__) . '/../../org.outlet-orm.config.parsers/') . '/';

			$this->directories = array();
			$this->directories[] = $mainDir . 'association/';
			$this->directories[] = $mainDir . 'config/';
			$this->directories[] = $mainDir . 'core/';
			$this->directories[] = $mainDir . 'database/';
			$this->directories[] = $mainDir . 'entity/';
			$this->directories[] = $mainDir . 'map/';
			$this->directories[] = $mainDir . 'proxy/';
			$this->directories[] = $parsersDir . 'xml/';
			$this->directories[] = $parsersDir . 'array/';
			$this->directories[] = $utilsDir . 'nestedset/';
			$this->directories[] = $utilsDir . 'pagination/';
		}

		return $this->directories;
	}
}