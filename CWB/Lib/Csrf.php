<?php

namespace CWB\Lib;

/**
 * Cross Site Request Forgery protection class.
 * @package CWB
 */
class Csrf
{

	/**
	 * Builds a 'hidden' form type which is populated with the generated token.
	 * @return string The HTML form tag.
	 */
	public static function inputTag()
	{
		$token = self::getToken();
		return "<input type=\"hidden\" name=\"cwbCsrf\" value=\"{$token}\">";
	}

	/**
	 * get a new generated token.
	 * @return string The Token.
	 */
	public static function getToken()
	{
		Session::start(); // because is a static
		if(!isset($_SESSION['cwbCsrf'])) {
			self::tokeniser();
		}
		return Session::get('cwbCsrf');
	}

	/**
	 * Generates a new CSFR token.
	 * @return bool 
	 */
	protected static function tokeniser()
	{
		$alphabet = "abcdefghijklmnopqrstuwxyz0123456789ABCDEFGHIJKLMNOPQRSTUWXYZ!@#$%¨&*{]}^`[)(-+:?/|";
		$pass = array();
		$alphaLength = strlen($alphabet) - 1;
		for($i = 0; $i < 40; $i++){
			$n = rand(0, $alphaLength);
			$pass[] = $alphabet[$n];
		}

		Session::start();
		Session::add(implode($pass), 'cwbCsrf');
		Session::add((time() + 1800), 'cwbCsrf-time');
	}

	/**
	 * Verfies that the submitted form has a valid CSFR token.
	 * verify REQUEST method 
	 * @return bool true on sucess or false on failure
	 */
	public static function verify()
	{
		if(isset($_REQUEST['cwbCsrf']) && $_REQUEST['cwbCsrf'] === self::getToken()) {
			//generate new token if expirated time of 30 min
			if(Session::get('cwbCsrf-time') <= time()) self::tokeniser();
			return true;
		}
		return false;
	}

}
