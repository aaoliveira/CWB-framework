<?php

namespace CWB\Lib;

use CWB\Lib\Db\Manager;
use CWB\Lib\Db\Query;

/**
 * cria um tipo de DAO
 *
 * @author Felipe
 */
abstract class Model
{
	/**
	 * o nome da tabela
	 * @var string
	 */
	private $tableName;

	/**
	 * instancia para manipulação do banco de dados
	 * @var CWB\Lib\Db\Manager
	 */
	protected $manager;

	/**
	 * instancia para o Query Builder
	 * @var CWB\Lib\Db\Query 
	 */
	protected static $query;

	/**
	 * store all attr
	 * @var array
	 */
	protected $data = array();

	/**
	 * classe que te permite manipular o Model do db
	 * @param string $tableName > nome da tabela
	 * @param CWB\Lib\Db\Manager $manager > instancia da classe manager
	 */
	public function __construct($tableName = null, Manager $manager = null)
	{
		if(empty($tableName)) {
			$this->tableName = substr(strrchr(get_called_class(), '\\'), 1);
		} else {
			$this->tableName = $tableName;
		}

		if(empty($manager)) {
			$manager = Manager::getDefaultInstance();
		}

		$this->manager =& $manager;

		self::$query = Query::getInstance();
	}

	/**
	 * set attr to manage Db
	 */
	public function __set($name, $value)
	{
		$this->data[(string)$name] = $value;
	}

	/**
	 * get the object of this class
	 * @return mixed
	 */
	public function __get($name)
	{
		return isset($this->data[(string)$name]) ? $this->data[(string)$name] : null;
	}

	/**
	 * get the table name
	 * @return string table Name
	 */
	protected function getTable()
	{
		return $this->tableName;
	}
	
	/**
	 * get a register on table
	 * @return array all rows of table
	 */
	public function get()
	{
		self::$query->select('*')->from($this->getTable());

		if(count($this->data)) {
			foreach($this->data as $field => $value){
				self::$query->where($field . ' = ?', $value);
			}
			$this->data = array();
		}

		return $this->manager->execQuery(self::$query)->fetch();
	}
	
	/**
	 * get all registers on table
	 * @return array all rows of table
	 */
	public function getAll()
	{
		self::$query->select('*')->from($this->getTable());

		if(count($this->data)) {
			foreach($this->data as $field => $value){
				self::$query->where($field . ' = ?', $value);
			}
			$this->data = array();
		}

		return $this->manager->execQuery(self::$query)->fetchAll();
	}

	/**
	 * execute a simple delete
	 * @return int o numero de linha deletadas ou false em caso de ero
	 */
	public function delete($where = null, $params = null)
	{
		self::$query->delete()->from($this->getTable());

		if(count($this->data)) {
			foreach($this->data as $field => $value){
				self::$query->where($field . ' = ?', $value);
			}
			$this->data = array();
		}

		if($where != null) self::$query->where($where, $params);

		return $this->manager->execQuery(self::$query)->rowCount();
	}

	/**
	 * aualiza os dados do registro
	 * @return int o numero de linha deletadas ou false em caso de ero
	 */
	public function update($where = null, $params = null)
	{
		self::$query->update($this->data)->into($this->getTable());

		$this->data = array();

		if($where != null) self::$query->where($where, $params);

		return $this->manager->execQuery(self::$query)->rowCount();
	}

	/**
	 * update os dados do registro
	 * @return int last inserted id
	 */
	public function save($params = null)
	{
		self::$query->insert($this->data)->into($this->getTable())
			->addParams($params);

		$this->data = array();

		return $this->manager->execQuery(self::$query)->lastInsertId();
	}

	/**
	 * get the last error mensage on DB
	 * @return string 
	 */
	public function error($type = 2)
	{
		return $this->manager->error($type);
	}

}
