<?php

namespace CWB\Config;

/**
 * classe App 
 * onde se guarda todas as informações 
 * estaticas de configurações da aplicação.
 *
 * @package CWB
 */
class App
{
	/**
	 * base da URL para ser usada pelos helpers
	 * @var string
	 */
	const BASE_URL = 'http://localhost/';

	/**
	 * key para codifacação/criptografia de senha e arquivos
	 * @var string
	 */
	const SECRET_KEY = 'YoUr SuPeR sEcReT kEy HeRe';

	/**
	 * pasta onde é guardado os arquivos de cache
	 * @var string
	 */
	const CACHE_PATH = '/CWB/Cache';
	
	/**
	 * classes to autoload
	 * use 'nameToCall' => '\CWB\NameSpace\To\Class',
	 * to call use the \CWB\Lib\Registry class
	 * @var array
	 */
	public static $autoload = array(
		//'View' => '\CWB\Lib\View',
	);

	/**
	 * type of request
	 * you can use 
	 * 'PATH_INFO' -> Uses the PATH_INFO
	 * 'REQUEST' -> -Uses the global REQUEST variable
	 *			  |-- use index: 'controller' to controller without \CWB\Controller\
	 *			  |-- use index: 'method' to action
	 *			  |-- use index: 'args' to arguments of function. Can be a array or string separated by '/'
	 * 'ORIG_PATH_INFO' -> Uses the ORIG_PATH_INFO
	 * 
	 * @var string type of request
	 */
	const TYPE_REQUEST = 'PATH_INFO';

	/**
	 * definições com o header
	 */
	/**
	 * change the headers
	 * @var boolean
	 */
	const CHANGE_HEADERS = true;

	/**
	 * array com as informações do cabeçalho<br>
	 * não é necessário inserir o ';' no final de cada informação,
	 * pois cada informação será separada com ";\n"<br>
	 * é necessario que CHANGE_HEADERS seja <b>true</b> para que as informações sejam alteradas
	 * @var array $headers
	 */
	public static $headers = array(
		'Content-type: text/html; charset=UTF-8',
		'X-Powered-By: CWB',
		'Content-Language: pt_br, pt-BR',
		'Server: CWB SERVER 1.1',
		'Host: CWB_HOST',
	);
	/**
	 * END header
	 */

	/**
	 * configurações com ini_set()
	 */
	/**
	 * valida as proximas configurações com ini_set()<br>
	 * quando <b>true</b> ativa as alternancias.<br>
	 * quando <b>false</b> não faz alterações do phpini 
	 * @var boolean
	 */
	const CHANGE_INI_SET = false;

	/**
	 * transforma as diretivas do phpini.<br>
	 * É necessária que Config\App::IniSetActive seja <b>true</b>.<br>
	 * *key* = nome da variavel<br>
	 * *value* = valor da variavel<br>
	 * caso o *value* seja '' (empty) não será transformado
	 * @return array all configs to change php-ini
	 */
	public static function iniConfig()
	{
		return array(
			// expose_php is Off por segurança
			'expose_php'=>'Off',
			//limite de merória
			'memory_limit' => '128M',
			// tempo maximo de execução
			'max_execution_time'=> 360,
			// nivel de reporte de error
			'error_reporting' => E_ALL,
			//arquivo de log para error
			'error_log'=> '',
			// date_default_timezone_set
			'date.timezone'=>'America/Sao_Paulo'
		);
	}
	/**
	 * END ini_set();
	 */

	public static function getAppDir()
	{
		return realpath(__DIR__ . '../../') . DIRECTORY_SEPARATOR;
	}

}
