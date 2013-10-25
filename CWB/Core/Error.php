<?php

namespace CWB\Core;

use CWB\Config\App;

/**
 * class with function statcs for handler of errors
 * like error_get_last, set_error_handler, set_exception_handler
 *
 * @package CWB
 */
final class Error extends \Exception
{

	/**
	 * get the page of error in App::getAppDir().ErrorPage/
	 * 
	 * @param string $page > page of error (must be have your sufix type: .php || .html)
	 * @param array $params > parameters repassados for this page
	 */
	public static function getErrorPage($page, $params = array())
	{
		if (is_array($params) || is_object($params)) {
			foreach ($params as $key => $value) {
				${$key} = $value;
			}
		}

		include App::getAppDir() . 'ErrorPage' . DIRECTORY_SEPARATOR . $page;
		die;
	}

	/**
	 * get the last mensagem of error.
	 * @return array as informações com a mensagem de erro
	 */
	public static function lastError()
	{
		return @error_get_last();
	}
}
