<?php

namespace CWB\Lib\Db;

/**
 * Query
 *
 * @author Sasa
 */
class Query
{
	/**
	 * call a query to select
	 */
	const QUERY_SELECT = 1;

	/**
	 * call a query to insert
	 */
	const QUERY_INSERT = 2;

	/**
	 * call a query to update
	 */
	const QUERY_UPDATE = 3;

	/**
	 * call a query to delete
	 */
	const QUERY_DELETE = 4;

	protected $select = array();
	protected $insert = array();
	protected $update = array();
	protected $delete = array();
	protected $from = array();
	protected $where = array();
	protected $having = array();
	protected $join = array();
	protected $params = array();
	protected $orderBy = array();
	protected $groupBy = array();
	protected $limit = "";
	private $type;
	private $operator = 'AND';

	/**
	 * instance of this class
	 * @var \CWB\Lib\Db\Query
	 */
	public static $instance = null;

	/**
	 * Database SQL Builder
	 */
	private function __construct(){}

	/**
	 * get the instance of class
	 * @return \CWB\Lib\Db\Query
	 */
	public static function &getInstance()
	{
		if(self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * define o tipo de query que é esta
	 * @param int $type
	 */
	private function setType($type)
	{
		$this->type = $type;
	}

	/**
	 * define o tipo de operador lógico
	 * @param string $type > tipo de operador lógico como: OR , AND , && , || , etc.
	 */
	public function setOperator($type)
	{
		$this->operator = $type;
	}
	
	/**
	 * Add statement for select - SELECT [?] FROM ...
	 *
	 * Examples:
	 * $sql->select("u.*")
	 * 		->select("b.*, COUNT(*) as total")
	 * 		->select(array(
	 * 					"MAX(id) as maxId",
	 * 					"collum"
	 * 				)
	 * 		);
	 *
	 * @param string $statement
	 * @return \CWB\Lib\Db\Query
	 */
	public function select($statement = '*')
	{
		$this->setType(self::QUERY_SELECT);

		if(is_array($statement)) {
			foreach($statement as $val){
				$this->select[] = $val;
			}
		} else {
			$this->select[] = $statement;
		}

		return $this;
	}

	/**
	 * Add a sql statment to insert
	 * @param array $fields > fields => values to insert
	 * @param array $params > parameters of statment
	 * @return \CWB\Lib\Db\Query
	 */
	public function insert($fields, $params = null)
	{
		$this->setType(self::QUERY_INSERT);

		foreach($fields as $field => $val){
			if($val == null)
				$this->insert[$field] = 'NULL';
			elseif($val != '?' && $val{0} != ':')
				$this->insert[$field] = "'" . addslashes($val) . "'";
			else
				$this->insert[$field] = $val;
		}
		$this->addParams($params);

		return $this;
	}

	/**
	 * Add a sql statment to update
	 * <code>
	 * Exemples:
	 * $sql->update(array("field"=>"?",),'value' );
	 * </code>
	 * @param array $fields > fields => values to insert
	 * @param array $params > parameters of statment
	 * @return \CWB\Lib\Db\Query
	 */
	public function update($fields, $params = null)
	{
		$this->setType(self::QUERY_UPDATE);

		foreach($fields as $field => $val){
			if($val == null)
				$this->update[$field] = 'NULL';
			elseif($val != '?' && $val{0} != ':')
				$this->update[$field] = "'" . addslashes($val) . "'";
			else
				$this->update[$field] = $val;
		}
		$this->addParams($params);

		return $this;
	}

	/**
	 * Add a sql statment to update
	 * <code>
	 * Exemples:
	 * $sql->delete();
	 * </code>
	 * @param array $fields > fields => values to insert
	 * @param array $params > parameters of statment
	 * @return \CWB\Lib\Db\Query
	 */
	public function delete()
	{
		$this->setType(self::QUERY_DELETE);
		return $this;
	}

	/**
	 * Add statement for from - SELECT * FROM [?] ...
	 *
	 * Examples:
	 * $sql->from("users");
	 * $sql->from("users u, posts p");
	 * $sql->from(array("users u","posts p"));
	 *
	 * @param string $statement
	 * @return \CWB\Lib\Db\Query
	 */
	public function from($statement)
	{
		if(is_array($statement)) {
			foreach($statement as $val){
				$this->from[] = $val;
			}
		} else {
			$this->from[] = $statement;
		}

		return $this;
	}

	/**
	 * sinonimous of from
	 * @param type $statement
	 */
	public function into($statement)
	{
		return $this->from($statement);
	}
	
	/**
	 * Add statement for where - ... WHERE [?] ...
	 *
	 * Examples:
	 * <code>
	 * $sql->where("user_id = ?", $user_id);
	 * $sql->where("u.registered > ? AND (u.is_active = ? OR u.column IS NOT NULL)", array($registered, 1));
	 *</code>
	 * @param string $statement
	 * @param mixed $params
	 * @return \CWB\Lib\Db\Query
	 */
	public function where($statement, $params = null)
	{
		$this->where[] = $statement;
		$this->addParams($params);

		return $this;
	}

	/**
	 * Add where in statement
	 *
	 * @param string $column
	 * @param array $params
	 *
	 * @return \CWB\Lib\Db\Query
	 */
	public function whereIn($column, $params)
	{
		$this->prepareWhereInStatement($column, $params, false);
		$this->addParams($params);

		return $this;
	}

	/**
	 * Add where not in statement
	 *
	 * @param $column
	 * @param $params
	 * @return \CWB\Lib\Db\Query
	 */
	public function whereNotIn($column, $params)
	{
		$this->prepareWhereInStatement($column, $params, true);

		return $this;
	}

	/**
	 * Add statement for HAVING ...
	 * @param string $statement
	 * @param mixed $params
	 * @return \CWB\Lib\Db\Query
	 */
	public function having($statement, $params = null)
	{
		$this->having[] = $statement;
		$this->addParams($params);

		return $this;
	}

	/**
	 * Add statement for join
	 *
	 * Examples:
	 * <code>
	 * $sql->join("posts p","p.user_id = u.user_id", "INNER JOIN");
	 * </code>
	 * @param string $table > table to join
	 * @param string $on > connection of table
	 * @param string $type > type of joining
	 * @return \CWB\Lib\Db\Query
	 */
	public function join($table, $on = '', $type = 'JOIN')
	{
		$this->join[] = $type . ' ' . $table . ($on == '' ? '' : ' ON ' . $on);

		return $this;
	}

	/**
	 * Add statement for group - GROUP BY [...]
	 *
	 * Examples:
	 * $sql->groupBy("user_id");
	 * $sql->groupBy("u.is_active, p.post_id");
	 * $sql->groupBy(array('u.is_active', 'p.post_id'));
	 *
	 * @param string $statement
	 * @return \CWB\Lib\Db\Query
	 */
	public function groupBy($statement)
	{
		if(is_array($statement)) {
			foreach($statement as $val){
				$this->groupBy[] = $val;
			}
		} else {
			$this->groupBy[] = $statement;
		}

		return $this;
	}

	/**
	 * Add statement for order - ORDER BY [...]
	 *
	 * Examples:
	 * <code>
	 * $sql->orderBy("registered");
	 * $sql->orderBy("is_active, registered DESC");
	 * </code>
	 * @param string $statement
	 * @return \CWB\Lib\Db\Query
	 */
	public function orderBy($statement)
	{
		if(is_array($statement)) {
			foreach($statement as $val){
				$this->orderBy[] = $val;
			}
		} else {
			$this->orderBy[] = $statement;
		}

		return $this;
	}

	/**
	 * Add statement for limit - LIMIT [...]
	 *
	 * Examples:
	 * $sql->limit(30);
	 * $sql->limit(30,30);
	 *
	 * @param int $limit
	 * @param int $offset
	 * @return \CWB\Lib\Db\Query
	 */
	public function limit($limit, $offset = null)
	{
		$this->limit = '';

		if(!is_null($offset)) {
			$this->limit = $offset . ', ';
		}

		$this->limit .= $limit;

		return $this;
	}

	/**
	 * print the sql builder
	 * @return string
	 */
	public function __toString()
	{
		switch($this->type){
			case self::QUERY_SELECT:
				return $this->getSelect();
				break;

			case self::QUERY_INSERT:
				return $this->getInsert();
				break;

			case self::QUERY_UPDATE:
				return $this->getUpdate();
				break;

			case self::QUERY_DELETE:
				return $this->getDelete();
				break;
		}
		return false;
	}

	/**
	 * Returns generated SQL query
	 *
	 * @return string
	 */
	private function getSelect()
	{
		$sql = $this->prepareSelectString();
		$sql .= $this->prepareJoinString();
		$sql .= $this->prepareWhereString();
		$sql .= $this->prepareGroupByString();
		$sql .= $this->prepareHavingString();
		$sql .= $this->prepareOrderByString();
		$sql .= $this->prepareLimitString();

		$this->clearAll();

		return $sql;
	}

	/**
	 * get sql statment to insert
	 * @return string
	 */
	private function getInsert()
	{
		$fields = array_keys($this->insert);
		$values = array_values($this->insert);

		if(count($values) == 1)
			$sql = "INSERT INTO {$this->from[0]}({$fields[0]}) VALUES ({$values[0]});";
		else
			$sql = "INSERT INTO {$this->from[0]}(" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ");";

		$this->clearAll();

		return $sql;
	}

	/**
	 * get sql statment to update
	 * @return string
	 */
	private function getUpdate()
	{
		$from = count($this->from) > 1 ? implode(", ", $this->from) : $this->from[0];
		$sql = "UPDATE {$from} SET ";
		$temp = array();
		foreach($this->update as $field => $value){
			$temp[] = $field . ' = ' . $value;
		}

		if(count($temp) > 1)
			$sql .= implode(', ', $temp) . ' ' . $this->prepareWhereString();
		else
			$sql .= @$temp[0] . ' ' . $this->prepareWhereString();

		$this->clearAll();

		return $sql;
	}

	/**
	 * get sql statment to delete
	 * @return string
	 */
	private function getDelete()
	{
		$from = count($this->from) > 1 ? implode(", ", $this->from) : $this->from[0];
		$sql = "DELETE FROM {$from} " . $this->prepareWhereString();
		$this->clearAll();
		return $sql;
	}

	/**
	 * reset all fields in this class
	 */
	private function clearAll()
	{
		$this->type = null;
		$this->insert = array();
		$this->update = array();
		$this->delete = array();
		$this->select = array();
		$this->from = array();
		$this->where = array();
		$this->having = array();
		$this->join = array();
		$this->orderBy = array();
		$this->groupBy = array();
		$this->limit = "";
	}

	/**
	 * Returns prepared select string
	 *
	 * @return string
	 */
	private function prepareSelectString()
	{
		if(empty($this->select)) {
			$this->select("*");
		}

		$select = count($this->select) > 1 ? implode(", ", $this->select) : $this->select[0];
		$from = count($this->from) > 1 ? implode(", ", $this->from) : $this->from[0];

		return "SELECT " . $select . " FROM " . $from . " ";
	}

	/**
	 * Add param(s) to stack
	 *
	 * @param array $params
	 *
	 * @return void
	 */
	public function addParams($params)
	{
		if(is_null($params)) {
			return;
		}

		if(!is_array($params)) {
			$params = array($params);
		}

		$this->params = array_merge($this->params, $params);

		return $this;
	}

	/**
	 * get all param(s) to execute query
	 * and clean the params
	 *
	 * @return array
	 */
	public function getParams()
	{
		$p = $this->params;
		$this->params = array();
		return $p;
	}

	/**
	 * Prepares where in statement
	 *
	 * @param string $column
	 * @param array $params
	 * @param bool $not_in Use NOT IN statement
	 *
	 * @return void
	 */
	private function prepareWhereInStatement($column, $params, $not_in = false)
	{
		$qm = array_fill(0, count($params), "?");
		$qm = count($qm) > 1 ? implode(", ", $qm) : $qm[0];
		$in = ($not_in) ? "NOT IN" : "IN";
		$this->where[] = $column . " " . $in . " (" . $qm . ")";
	}

	/**
	 * Returns prepared join string
	 *
	 * @return string
	 */
	private function prepareJoinString()
	{
		if(!empty($this->join)) {
			return implode(" ", $this->join) . " ";
		}

		return '';
	}

	/**
	 * Returns prepared where string
	 *
	 * @return string
	 */
	private function prepareWhereString()
	{
		if(!empty($this->where)) {
			$this->where = count($this->where) > 1 ? implode(" {$this->operator} ", $this->where) : $this->where[0];
			return "WHERE " . $this->where . " ";
		}

		return '';
	}

	/**
	 * Returns prepared group by string
	 *
	 * @return string
	 */
	private function prepareGroupByString()
	{
		if(!empty($this->groupBy)) {
			$this->groupBy = count($this->groupBy) > 1 ? implode(", ", $this->groupBy) : $this->groupBy[0];
			return "GROUP BY " . $this->groupBy . " ";
		}

		return '';
	}

	/**
	 * Returns prepared having string
	 *
	 * @return string
	 */
	private function prepareHavingString()
	{
		if(!empty($this->having)) {
			$this->having = count($this->having) > 1 ? implode(" {$this->operator} ", $this->having) : $this->having[0];
			return "HAVING " . $this->having . " ";
		}

		return '';
	}

	/**
	 * Returns prepared order by string
	 *
	 * @return string
	 */
	private function prepareOrderByString()
	{
		if(!empty($this->orderBy)) {
			$this->orderBy = count($this->orderBy) > 1 ? implode(", ", $this->orderBy) : $this->orderBy[0];
			return "ORDER BY " . $this->orderBy . " ";
		}

		return '';
	}

	/**
	 * Returns prepared limit string
	 *
	 * @return string
	 */
	private function prepareLimitString()
	{
		if(!empty($this->limit)) {
			return "LIMIT " . $this->limit;
		}

		return '';
	}

}
