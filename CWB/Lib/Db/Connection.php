<?php

namespace CWB\Lib\Db;

use CWB\Config\Db as Dbc;
use \PDO;
use \PDOException;

/**
 * class to manager connections with database
 *
 * @author Felipe
 */
class Connection
{
	/**
	 * dados com as conexões dos bancos
	 * @var string
	 */
	private static $connections = array();

	/**
	 * instance of PDO
	 * @var PDO
	 */
	private static $pdo = array();

	/**
	 * add an other connection to this class
	 * @param string $connName > name of connection
	 * @param array $configs > array with all configs
	 * <ul>
	 *	<li>string 'dsn': to connection wrapper</li>
	 *	<li>string 'username': user to db</li>
	 *	<li>string 'password': pass to user</li>
	 *	<li>array 'options': other configs to PDO class</li>
	 * </ul>
	 */
	public static function add($connName, $configs)
	{
		self::$connections[$connName]['dsn'] = isset($configs['dsn']) ? (string)$configs['dsn'] : '';
		self::$connections[$connName]['username'] = isset($configs['username']) ? (string)$configs['user'] : '';
		self::$connections[$connName]['password'] = isset($configs['password']) ? (string)$configs['pass'] : '';
		self::$connections[$connName]['options'] = isset($configs['options']) ? (array)$configs['options'] : array();
		
		self::$pdo[$connName] = null;
	}

	/**
	 * get the connection instance
	 * @param string $connName
	 * @return object -instance of connection
	 * @throws Exception
	 */
	public static function &get($connName = 'default')
	{
		if(!isset(self::$connections['default']) && !isset(self::$pdo['default']))
			self::setDefault();
		
		if(!isset(self::$connections[$connName]))
			throw new Exception("informations of connection: '$connName' not exists...", E_USER_ERROR);

		if(!self::$pdo[$connName]) {
			self::open($connName);
		}
		return self::$pdo[$connName];
	}

	/**
	 * open a connection with DB
	 * @param string $connName > nome of connection
	 * @return null
	 * @throws Exception
	 */
	private static function open($connName)
	{
		if(empty(self::$connections[$connName]['dsn'])) {
			throw new Exception('Dsn can\'t be empty.');
		}
			
		if(self::$pdo[$connName] !== null) {
			return;
		}

		try{
			self::$pdo[$connName] = new PDO(self::$connections[$connName]['dsn'],
											self::$connections[$connName]['username'],
											self::$connections[$connName]['password'],
											self::$connections[$connName]['options']);
		} catch(PDOException $e){
			throw new Exception($e->getMessage());
		}
	}

	/**
	 * load the default DB config
	 */
	private static function setDefault()
	{
		self::$connections['default']['dsn'] = Dbc::DSN;
		self::$connections['default']['username'] = Dbc::USER;
		self::$connections['default']['password'] = Dbc::PASS;
		self::$connections['default']['options'] = Dbc::$options;

		self::$pdo['default'] = null;
	}
	
}
