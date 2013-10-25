<?php

namespace CWB\Config;

/**
 * classe onde se quarda as configurações de conexão com o banco de dados
 * esta classe utiliza o PDO
 * @package CWB
 */
class Db
{
	
	/**
	 * DSN para conexão do banco de dados
	 * o padrão é de acordo com o do PDO class
	 */
	const DSN = 'mysql:host=localhost;port=3306;dbname=autogestor;charset=utf8';
	
	/**
	 * nome de usuario do banco de dados
	 */
	const USER = 'root';
	
	/**
	 * senha do usuario no banco de dados
	 */
	const PASS = '';
	
	
	/**
	 * array com os opcionais de conexão com banco de dados
	 * @var array
	 */
	static $options = array(
		\PDO::ATTR_PERSISTENT => true,
		\PDO::ATTR_AUTOCOMMIT => true,
		\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ
	);

}