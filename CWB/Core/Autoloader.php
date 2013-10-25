<?php

namespace CWB\Core;

/**
 * class to autoloader classes
 *
 */
class Autoloader
{
	/**
	 * all names of class
	 * @var array 
	 */
	private static $pathsController = null;

	/**
	 * path to Controller folder
	 * @var string
	 */
	private static $beforeDir = '';

	/**
	 * mapper the Controller classes into app directory
	 * this build the array with the paths of 
	 * all classes in the app\Controller
	 * @param string $dir > [optional]path to the directory
	 */
	private static function setPathsController($dir = null)
	{
		if (self::$pathsController == null)
				self::$pathsController = array();

		if (empty($dir))
				$dir = realpath(__DIR__ . '../../Controller');

		if (empty(self::$beforeDir))
				self::$beforeDir = dirname($dir) . DIRECTORY_SEPARATOR;

		$files = scandir($dir);
		foreach ($files as $file) {
			if ($file == '.' || $file == '..')
					continue;

			if (is_file($dir . DIRECTORY_SEPARATOR . $file))
					self::$pathsController[] = str_replace(self::$beforeDir, '', $dir . DIRECTORY_SEPARATOR . $file);
			elseif (is_dir($dir . DIRECTORY_SEPARATOR . $file))
					self::setPathsController($dir . DIRECTORY_SEPARATOR . $file);
		}
	}

	/**
	 * autoload class
	 * @param string $className > name of class
	 */
	public static function autoload($className)
	{
		$className = str_replace('CWB', '', $className);
		$className = trim($className, '\\');

		$file = str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';

		foreach (self::$pathsController as $value) {
			if (preg_match('#' . preg_quote($file) . '#i', $value))
					$file = $value;
		}

		$appDir = realpath(__DIR__ . '../..') . DIRECTORY_SEPARATOR;

		if (is_file($appDir . $file))
				include_once $appDir . $file;
	}

	/**
	 * register the autoload function
	 */
	public static function register()
	{
		if (self::$pathsController == null)
				self::setPathsController();

		spl_autoload_register(array(__CLASS__, 'autoload'));
	}

	/**
	 * register the autoload function
	 */
	public static function unregister()
	{
		spl_autoload_unregister(array(__CLASS__, 'autoload'));
	}
}

// register the autoload
Autoloader::register();
