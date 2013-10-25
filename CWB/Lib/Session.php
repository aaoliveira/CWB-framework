<?php

namespace CWB\Lib;

/**
 * class for handle session
 * 
 * @package CWB
 */
class Session
{

	/**
	 * inicia uma sessão
	 * @return boolean true
	 */
	public static function start()
	{
		if(!self::active()) {
			session_start();
		}
		return true;
	}

	/**
	 * reton the status of session
	 * @return boolean status session
	 */
	public static function active()
	{
		return isset($_SESSION);
	}

	/**
	 * destrói todos os dados associados com a sessão atual. 
	 * Ela não desregistra nenhuma das variáveis globais associadas a sessão atual,
	 * nem desregistra o cookie de sessão. 
	 */
	public static function destroy()
	{
		if(self::active()) {
			session_destroy();
		} else {
			self::start();
			session_destroy();
		}
	}

	//-----------------------------------

	/**
	 * Regenerate the PHPSID  
	 * @return boolean.
	 */
	public static function regenerateId()
	{
		if(session_regenerate_id()) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * get the value in session array
	 * @param mixed $var > [opitional] index where encounter a values
	 * @return mixed value of the session or session[$var] or false on failure 
	 */
	public static function get($var = null)
	{
		if(!self::active()) return null;

		if($var == null)
			return $_SESSION;
		else
			return isset($_SESSION[$var]) ? $_SESSION[$var] : null;
	}

	/**
	 * add a variable in the session array
	 * @param mixed $data > data to register in session array
	 * @param mixed $var > index where find for value
	 */
	public static function add($data, $var = null)
	{
		if(!self::active())	return;

		if($var === null)
			$_SESSION[] = $data;
		else
			$_SESSION[$var] = $data;
	}

	/**
	 * remove a index var in session array
	 * @param mixed $var > index to remove in session array
	 */
	public static function remove($var)
	{
		if(isset($_SESSION[$var]))
			unset($_SESSION[$var]);
	}

	/**
	 * Get the id this current session
	 * @return string the id of session for this current session 
	 * or a empty string if hasn't current session.
	 */
	public static function getId()
	{
		return session_id();
	}

	/**
	 * define the id of current session
	 * @param string $id > string to id session
	 */
	public static function setId($id)
	{
		session_id($id);
	}

	/**
	 * Get and/or define the name of current session
	 * @param string $name 
	 * @return type
	 */
	public static function name($name = '')
	{
		return session_name($name);
	}

	/**
	 * codify the data of current session as string
	 * @return string data of current session
	 */
	public static function encode()
	{
		return session_encode();
	}

	/**
	 * decify data of session of a string
	 * @param string $data > data to decify for session
	 * @return boolean true on sucess or false on failure
	 */
	public static function decode($data)
	{
		return session_decode($data);
	}

}
