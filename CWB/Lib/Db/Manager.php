<?php

namespace CWB\Lib\Db;

use CWB\Core\Error;
use CWB\Lib\Cache;
use PDOStatement;

/**
 * Classe para interação com o banco de dados
 *
 * @author Felipe
 */
class Manager
{
	/**
	 * connection name
	 * @var string
	 */
	private $connName = 'default';

	/**
	 * instancia da classe PDO
	 * @var object
	 */
	private $conn = null;

	/**
	 * instancia da classe PDOStatment
	 * @var object 
	 */
	private $SMTM;
	
	/**
	 * instanace of default connection
	 * @var \CWB\Lib\Db\Manager
	 */
	private static $defaultInstance = null;
	
	/**
	 * classe para manipulação do banco de dados.
	 * utliza a classe PDO da plataforma PHP.
	 * 
	 * @param string $connName > name of connection
	 * @param array $config > dados para a conexão:
	 * <ul>
	 * 	<li>string <b>dsn</b>: dsn para a conexão;</li>
	 * 	<li>string <b>username</b>: usuario do banco;</li>
	 * 	<li>string <b>password</b>: senha do usuario;</li>
	 * 	<li>array <b>options</b>: opções da conexão;</li>
	 * </ul>
	 */
	public function __construct($connName = 'default', $config = array())
	{
		if($this->connName != $connName) {
			$this->connName = $connName;
			if(count($config)) {
				Connection::add($this->connName, $config);
			}
		}

		try {
			$this->conn =& Connection::get($this->connName);
		} catch(Exception $e) {
			Error::getErrorPage('Db.php', array('msg' => $e->getMessage()));
		}
	}
	
	/**
	 * get de instance of default connection
	 * @return \CWB\Lib\Db\Manager
	 */
	public static function &getDefaultInstance()
	{
		if(self::$defaultInstance == null) {
			self::$defaultInstance = new self();
		}
		return self::$defaultInstance;
	}
	
	/**
	 * Inicia uma transação
	 * Desliga o modo de confirmação automática.
	 * Enquanto o modo autocommit é desligado, as alterações feitas no banco de dados através do objeto PDO
	 * Instância não estão comprometidos até terminar a transação chamando PDO :: commit ().
	 * Chamada PDO :: rollback () irá reverter todas as alterações para o banco de dados
	 * E retornar a ligação para o modo de confirmação automática.
	 * 
	 * @return TRUE em caso de sucesso ou FALSE em caso de falha.
	 */
	public function beginTransaction()
	{
		return $this->conn->beginTransaction();
	}

	/**
	 * Confirma uma transação.
	 * Confirma uma transação, voltando a conexão com o banco para o modo
	 * autocommit até a próxima chamada para PDO::beginTransaction() inicia uma nova transação.
	 * @return TRUE em caso de sucesso ou FALSE em caso de falha.
	 */
	public function commit()
	{
		return $this->conn->commit();
	}

	/**
	 * Reverte uma transação
	 * Desfaz a transação atual, iniciada por PDO :: beginTransaction().
	 * A PDOException será lançada se nenhuma transação estiver ativa.
	 * Se o banco de dados foi definido para o modo autocommit,
	 * Esta função irá restaurar o modo autocommit depois de ter revertida a transação.
	 * 
	 * @return TRUE em caso de sucesso ou FALSE em caso de falha.
	 */
	public function rollBack()
	{
		return $this->conn->rollBack();
	}

	/**
	 * Verifica se dentro de uma transação
	 * Verifica se a transação está ativa dentro do driver.
	 * Este método só funciona para os motoristas de banco de dados que suportam transações.
	 *
	 * @return TRUE se uma transação esta ativa, and FALSE se não.
	 */
	public function inTransaction()
	{
		return $this->conn->inTransaction();
	}

	/**
	 * Retorna o ID da última linha inserida ou valor de sequência.
	 * Retorna o ID da última linha inserida,
	 * Ou o último valor de um objeto de seqüência,
	 * Dependendo do driver subjacente.
	 * Por exemplo, pdo_pgsql requer que você especifique
	 * O nome de um objeto de seqüência para o parâmetro nome.
	 *
	 * @param string $name > Nome do objeto de seqüência a partir do qual o ID deve ser devolvido.
	 *
	 * @return mixed Se um nome de seqüência não foi especificado para o parâmetro de nome,
	 * PDO :: lastInsertId () retorna uma string que representa o ID da linha da última linha que foi inserida no banco de dados.
	 * Se um nome de seqüência foi especificado para o parâmetro de nome,
	 * PDO :: lastInsertId () retorna uma string representando o último valor recuperado do objeto de seqüência especificada.
	 * Se o driver PDO não suporta esta capacidade, PDO :: lastInsertId () desencadeia uma IM001 SQLSTATE.
	 */
	public function lastInsertId($name = null)
	{
		return $this->conn->lastInsertId($name);
	}

	/**
	 * Retorna o número de linhas afetadas pela última declaração SQL.
	 * DELETE,INSERT ou UPDATE executada pelo objeto PDOStatement correspondente.
	 * Se a última declaração SQL executado pelo PDOStatement associada foi uma instrução SELECT,
	 * alguns bancos de dados pode retornar o número de linhas retornadas por essa afirmação.
	 * No entanto, este comportamento não é garantida para todos os bancos de dados e não deve ser 
	 * invocado para aplicações portáteis.
	 * @return int o número de linhas ou FALSE em caso de falhas.
	 */
	public function rowCount()
	{
		if($this->SMTM instanceof \PDOStatement) {
			return $this->SMTM->rowCount();
		} else {
			return false;
		}
	}

	/**
	 * executa uma SQL statement em uma unica chamada, 
	 * retornando o resultado (if any) returned by the statement as a PDOStatement object. 
	 * @param string $SQL > SQL statement
	 * @return mixed PDOStatement object, or FALSE em caso de falha. 
	 */
	public function query($SQL)
	{
		$this->SMTM = $this->conn->query((string)$SQL);
		if($this->SMTM instanceof \PDOStatement) {
			return $this;
		}
		return false;
	}

	/**
	 * execute a Query class
	 * @param \CWB\Lib\Db\Query $Query
	 * @return \CWB\Lib\Db\Manager
	 */
	public function execQuery(Query $Query)
	{
		return $this->prepare($Query->__toString())
				->execute($Query->getParams());
	}

	/**
	 * Prepara um SQL statement para ser executado pelo method $this->exec().
	 * O SQL statement pode conter zero or mais nomeados (:name) ou marca de questão (?)
	 * parametros marcados serão substituidos por valores reais quando o statement é executado.
	 * Você não pode usar os ambos nomeados (:name) ou marca de questão (?) no mesmo SQL statement;
	 * Use apenas um ou outro marcador de parametros.
	 * 
	 * @param string $SQL
	 * @return \CWB\Lib\Db\Manager
	 */
	public function prepare($SQL)
	{
		$this->SMTM = $this->conn->prepare((string)$SQL);
		return $this;
	}

	/**
	 * Executa o statement preparado.
	 * @param array $input_parameters > array com a formatação dos parametros.
	 * @return \CWB\Lib\Db\Manager ou FALSE em caso de não haver instancia do PDOStatement
	 * @see PDOStatement -> Execute
	 */
	public function execute($input_parameters = null)
	{
		if($this->SMTM instanceof \PDOStatement) {
			$this->SMTM->execute($input_parameters);
			return $this;
		} else {
			return false;
		}
	}

	/**
	 * Passa uma variável PHP para um correspondente chamado ou ponto de interrogação
	 * Espaço reservado na instrução SQL que foi usado para preparar a declaração.
	 * Ao contrário PDOStatement :: bindValue (), a variável está vinculado como uma referência e só será
	 * Avaliado na época em que PDOStatement :: execute () é chamado.
	 * 
	 * @param mixed $parameter >Identificador de parâmetro. Para uma declaração preparada com espaços reservados nomeados,
	 * Este será um nome de parâmetro do formulário: nome.
	 * Para uma declaração preparada com ponto de interrogação placeholders,
	 * Esta será a posição 1 indexada do parâmetro. 
	 * @param mixed $value >Nome da variável PHP para ligar o parâmetro da instrução SQL.
	 *
	 * @param int $data_type >Tipo de dados explícito para o parâmetro usando o PDO :: PARAM_ * constantes.
	 * Para retornar um parâmetro INOUT de um procedimento armazenado,
	 * Usar o operador bitwise OR para definir os bits PDO :: PARAM_INPUT_OUTPUT para o parâmetro data_type.
	 *
	 * @param int $length > Comprimento do tipo de dados. Para indicar que um parâmetro é um parâmetro a partir
	 * de um procedimento armazenado, você deve definir explicitamente o comprimento.
	 *
	 * @param mixed $drive_options >
	 *
	 * @return \CWB\Lib\Db\Manager ou FALSE em caso de não haver instancia do PDOStatement
	 */
	public function bindParam($parameter, &$value, $data_type = \PDO::PARAM_STR, $length = null, $drive_options = null)
	{
		if($this->SMTM instanceof \PDOStatement) {
			$this->SMTM->bindParam($parameter, $value, (int)$data_type, $length, $drive_options);
			return $this;
		} else {
			return false;
		}
	}

	/**
	 * Vincula-se um valor correspondente a um nome ou ponto de interrogação
	 * Espaço reservado na instrução SQL que foi usado para preparar a declaração.
	 * 
	 * @param mixed $parameter >Identificador de parâmetro. Para uma declaração preparada com espaços reservados nomeados,
	 * Este será um nome de parâmetro do formulário: nome.
	 * Para uma declaração preparada com ponto de interrogação placeholders,
	 * Esta será a posição 1 indexada do parâmetro.
	 *
	 * @param mixed $value > Nome da variável PHP para ligar o parâmetro da instrução SQL.
	 * @param int $data_type >Tipo de dados explícito para o parâmetro usando o PDO :: PARAM_* constantes.
	 * Para retornar um parâmetro INOUT de um procedimento armazenado,
	 * usar o operador bitwise OR para definir os bits PDO :: PARAM_INPUT_OUTPUT para o parâmetro data_type.
	 *
	 * @return \CWB\Lib\Db\Manager ou FALSE em caso de não haver instancia do PDOStatement
	 */
	public function bindValue($parameter, $value, $data_type = \PDO::PARAM_STR)
	{
		if($this->SMTM instanceof \PDOStatement) {
			$this->SMTM->bindValue($parameter, $value, $data_type);
			return $this;
		} else {
			return false;
		}
	}

	/**
	 * Obtém uma linha de um conjunto de resultados associado a um objeto PDOStatement.
	 * O parâmetro fetch_style determina como PDO retorna a linha.
	 * 
	 * @return mixed valor desta função em caso de sucesso depende do tipo fetch.
	 * Em todos os casos, é retornado FALSE em caso de falha.
	 */
	public function fetch($style = null)
	{
		if($this->SMTM instanceof \PDOStatement) {
			return $this->SMTM->fetch($style);
		} else {
			return false;
		}
	}

	/**
	 * Retorna um array contendo todas as linhas do conjunto de resultados
	 * 
	 * @return array array contendo todas as linhas restantes no conjunto de resultados.
	 * A matriz representa cada linha ou como uma matriz de valores de coluna
	 * Ou um objeto com propriedades correspondentes a cada nome da coluna.
	 */
	public function fetchAll($style = null)
	{
		if($this->SMTM instanceof \PDOStatement) {
			return $this->SMTM->fetchAll($style);
		} else {
			return false;
		}
	}

	/**
	 * tipo var_dump em todos os parametros do PDOStatement
	 * Despejar um comando SQL preparado.
	 * Dumps as informações contidas em uma declaração preparada diretamente na saída.
	 * Ele irá fornecer a consulta SQL em uso, o número de parâmetros utilizados (Params)
	 * A lista de parâmetros, com o seu nome, tipo (ParamType) como um inteiro,
	 * Seu nome da chave ou a posição, o valor ea posição na consulta
	 * (Se for suportado pelo condutor DOP, caso contrário, será -1).
	 * Esta é uma função de depuração, que despejar directamente os dados na saída normal.
	 * @return void
	 */
	public function debugDumpParams()
	{
		if($this->SMTM instanceof \PDOStatement) {
			$this->SMTM->debugDumpParams();
		}
	}

	/**
	 * Fecha o cursor, permitindo que a instrução seja executado novamente.
	 * libera a conexão com o servidor para que outras instruções SQL podem ser emitidos,
	 * mas deixa a declaração em um estado que permite que ele seja executado novamente.
	 * Este método é útil para os motoristas de banco de dados que não suportam a execução
	 * de um objeto PDOStatement quando um objeto PDOStatement anteriormente executado ainda tem linhas não pegas.
	 * Se o driver de banco de dados sofre com esta limitação, o problema pode se manifestar em um erro de seqüência.
	 *
	 * @return TRUE em caso de sucesso ou FALSE em caso de falha.
	 */
	public function free()
	{
		if($this->SMTM instanceof \PDOStatement) {
			return $this->SMTM->closeCursor();
		}
	}

	/**
	 * adciona '\' antes de caracteres como (%,_) para as clausuras LIKE
	 * e insere % antes e depois da string
	 * @param string $str > string para ser escapada
	 * @param string $side > lado para colocar o coringa. pode ser colocado
	 * 'left', 'right', 'both' or ''(default)
	 * @return string string escapada
	 */
	public function toLike($str, $side = '')
	{
		$str = strtr((string)$str, array('%' => '\%', '_' => '\_'));

		if($side == 'left') {
			$str = '%' . $str;
		} elseif($side == 'right') {
			$str = $str . '%';
		} elseif($side == 'both') {
			$str = '%' . $str . '%';
		}

		return $str;
	}

	/**
	 * Add new attribute for this class
	 * 
	 * @param mixed $name
	 * @param mixed $value
	 * @return \CWB\Lib\Db\Manager class
	 */
	public function setAttribute($name, $value)
	{
		if($this->SMTM instanceof \PDOStatement) {
			return $this->SMTM->setAttribute($name, $value);
		} else {
			return $this->conn->setAttribute($name, $value);
		}
	}

	/**
	 * Fetch a error information associated with the last operation on the database handle
	 * 
	 * @param int $type > tipo da mensagem de erro:
	 * <ul>
	 * <li><b>0:</b> SQLSTATE error code (a five characters alphanumeric identifier defined in the ANSI SQL standard).</li>
	 * <li><b>1:</b> Driver-specific error code.</li>
	 * <li><b>2:</b> Driver-specific error message.</li>
	 * </u>
	 * @return mixed >error information about the last operation performed by this database.
	 */
	public function error($type = 2)
	{
		$type = in_array((int)$type, array(0, 1, 2)) ? (int)$type : 2;
		if($this->SMTM instanceof \PDOStatement) {
			$ret = $this->SMTM->errorInfo();
		} else {
			$ret = $this->conn->errorInfo();
		}
		return $ret[$type];
	}

}