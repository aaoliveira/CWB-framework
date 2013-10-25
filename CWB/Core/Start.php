<?php

namespace CWB\Core;

use CWB\Config\App;
use CWB\Lib\Registry;

/**
 * Where init the framework
 * with the static method Run()
 * 
 * @package CWB
 */
final class Start
{
	/**
	 * Set the configs defaults for the framework
	 * the configs in the \Config\App
	 */
	final private static function setDefaults()
	{
		// if change headers
		if(App::CHANGE_HEADERS) {
			foreach(App::$headers as $header){
				header($header);
			}
		}

		//replace configs with ini_set();
		if(App::CHANGE_INI_SET) {
			foreach(App::iniConfig() as $var => $value){
				if($value !== '') {
					ini_set((string)$var, (string)$value);
				}
			}
		}

		foreach(App::$autoload as $key => $value){
			Registry::set(new $value, $key);
		}
		// insert the others methods of the App here
		// END App methods
	}

	/**
	 * init the app
	 * load the routes and configs App, DB, etc
	 * into framework. 
	 * Search for Controller\Method called on URL,
	 * case has't called the Controller\Method call the defaults
	 */
	final public static function Main()
	{
		//default configs
		self::setDefaults();
		// load the router
		$w = new Router;
		//call the method and exit;
		call_user_func_array(array(new $w->controller, (string)$w->method), (array)$w->args);
	}

}
