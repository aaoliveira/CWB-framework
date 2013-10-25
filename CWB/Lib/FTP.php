<?php

namespace CWB\Lib;

/**
 * FTP Class
 *
 * @package CWB
 * @subpackage Lib
 * @category Libraries
 * @author Felipe
 */
class FTP
{

	/**
	 * FTP Server hostname
	 *
	 * @var string
	 */
	public $hostname = '';

	/**
	 * FTP Username
	 *
	 * @var string
	 */
	public $username = '';

	/**
	 * FTP Password
	 *
	 * @var string
	 */
	public $password = '';

	/**
	 * FTP Server port
	 *
	 * @var int
	 */
	public $port = 21;

	/**
	 * Passive mode flag
	 *
	 * @var bool
	 */
	public $passive = TRUE;

	/**
	 * Debug flag
	 *
	 * Especifica se deve exibir mensagens de erro.
	 *
	 * @var bool
	 */
	public $debug = FALSE;

	/**
	 * onde se quarda todas as mensagem de erro e debug
	 *
	 */
	protected $errors = array();

	/**
	 * Connection
	 *
	 * @var resource
	 */
	public $conn_id = FALSE;

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @param array $config
	 * @return void
	 */
	public function __construct($config = array())
	{
		if( count($config) > 0 ) {
			$this->initialize($config);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * inicializar preferências
	 *
	 * @param array $config
	 * @return void
	 */
	public function initialize($config = array())
	{
		foreach( $config as $key => $val ){
			if( isset($this->$key) ) {
				$this->$key = $val;
			}
		}
		// Prep the hostname
		$this->hostname = preg_replace('|.+?://|', '', $this->hostname);
	}

	// --------------------------------------------------------------------

	/**
	 * FTP Connect
	 *
	 * @param array $config Connection values
	 * @return bool
	 */
	public function connect($config = array())
	{
		if( count($config) > 0 ) {
			$this->initialize($config);
		}

		if( FALSE === ($this->conn_id = @ftp_connect($this->hostname, $this->port)) ) {
			if( $this->debug === TRUE ) {
				$this->_error('ftp_unable_to_connect');
			}
			return FALSE;
		}

		if( !$this->_login() ) {
			if( $this->debug === TRUE ) {
				$this->_error('ftp_unable_to_login');
			}
			return FALSE;
		}

		// Set passive mode if needed
		if( $this->passive === TRUE ) {
			ftp_pasv($this->conn_id, TRUE);
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * FTP Login
	 *
	 * @return bool
	 */
	protected function _login()
	{
		return @ftp_login($this->conn_id, $this->username, $this->password);
	}

	// --------------------------------------------------------------------

	/**
	 * Valida o ID de conexão
	 *
	 * @return bool
	 */
	protected function _is_conn()
	{
		if( !is_resource($this->conn_id) ) {
			if( $this->debug === TRUE ) {
				$this->_error('ftp_no_connection');
			}
			return FALSE;
		}
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Muda o diretório
	 *
	 * O segundo parâmetro permite-nos momentaneamente desligar a depuração de modo que
	 * Esta função pode ser usada para testar a existência de uma pasta
	 * Sem jogar um erro. Não há equivalente FTP para is_dir ()
	 * Para que fazê-lo por tentar mudar para um diretório específico.
	 * Internamente, este parâmetro é utilizado apenas pela função de "espelho" abaixo.
	 *
	 * @param string $path
	 * @param bool $supress_debug
	 * @return bool
	 */
	public function changedir($path = '', $supress_debug = FALSE)
	{
		if( $path === '' OR !$this->_is_conn() ) {
			return FALSE;
		}

		$result = @ftp_chdir($this->conn_id, $path);

		if( $result === FALSE ) {
			if( $this->debug === TRUE && $supress_debug === FALSE ) {
				$this->_error('ftp_unable_to_changedir');
			}
			return FALSE;
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Make a diretory
	 *
	 * @param string $path
	 * @param int $permissions
	 * @return bool
	 */
	public function mkdir($path = '', $permissions = NULL)
	{
		if( $path === '' OR !$this->_is_conn() ) {
			return FALSE;
		}

		$result = @ftp_mkdir($this->conn_id, $path);

		if( $result === FALSE ) {
			if( $this->debug === TRUE ) {
				$this->_error('ftp_unable_to_makdir');
			}
			return FALSE;
		}

		// Set file permissions if needed
		if( $permissions !== NULL ) {
			$this->chmod($path, (int)$permissions);
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * send a file to the server
	 *
	 * @param string $locpath
	 * @param string $rempath
	 * @param string $mode
	 * @param int $permissions
	 * @return bool
	 */
	public function upload($locpath, $rempath, $mode = 'auto', $permissions = NULL)
	{
		if( !$this->_is_conn() ) {
			return FALSE;
		}

		if( !file_exists($locpath) ) {
			$this->_error('ftp_no_source_file');
			return FALSE;
		}

		// Set the mode if not specified
		if( $mode === 'auto' ) {
			// Get the file extension so we can set the upload type
			$ext = $this->_getext($locpath);
			$mode = $this->_settype($ext);
		}

		$mode = ($mode === 'ascii') ? FTP_ASCII : FTP_BINARY;

		$result = @ftp_put($this->conn_id, $rempath, $locpath, $mode);

		if( $result === FALSE ) {
			if( $this->debug === TRUE ) {
				$this->_error('ftp_unable_to_upload');
			}
			return FALSE;
		}

		// Set file permissions if needed
		if( $permissions !== NULL ) {
			$this->chmod($rempath, (int)$permissions);
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Baixar um arquivo de um servidor remoto para o servidor local
	 *
	 * @param string $rempath
	 * @param string $locpath
	 * @param string $mode
	 * @return bool
	 */
	public function download($rempath, $locpath, $mode = 'auto')
	{
		if( !$this->_is_conn() ) {
			return FALSE;
		}

		// Set the mode if not specified
		if( $mode === 'auto' ) {
			// Get the file extension so we can set the upload type
			$ext = $this->_getext($rempath);
			$mode = $this->_settype($ext);
		}

		$mode = ($mode === 'ascii') ? FTP_ASCII : FTP_BINARY;

		$result = @ftp_get($this->conn_id, $locpath, $rempath, $mode);

		if( $result === FALSE ) {
			if( $this->debug === TRUE ) {
				$this->_error('ftp_unable_to_download');
			}
			return FALSE;
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Renomeia (ou move) um arquivo
	 *
	 * @param string $old_file
	 * @param string $new_file
	 * @param bool $move
	 * @return bool
	 */
	public function rename($old_file, $new_file, $move = FALSE)
	{
		if( !$this->_is_conn() ) {
			return FALSE;
		}

		$result = @ftp_rename($this->conn_id, $old_file, $new_file);

		if( $result === FALSE ) {
			if( $this->debug === TRUE ) {
				$this->_error('ftp_unable_to_' . ($move === FALSE ? 'rename' : 'move'));
			}
			return FALSE;
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Move um arquivo
	 *
	 * @param string $old_file
	 * @param string $new_file
	 * @return bool
	 */
	public function move($old_file, $new_file)
	{
		return $this->rename($old_file, $new_file, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Delete um arquivo
	 *
	 * @param string $filepath
	 * @return bool
	 */
	public function delete_file($filepath)
	{
		if( !$this->_is_conn() ) {
			return FALSE;
		}

		$result = @ftp_delete($this->conn_id, $filepath);

		if( $result === FALSE ) {
			if( $this->debug === TRUE ) {
				$this->_error('ftp_unable_to_delete');
			}
			return FALSE;
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Excluir uma pasta e de forma recursiva apagar tudo (incluindo sub-pastas)
	 * contido dentro dele.
	 *
	 * @param string $filepath
	 * @return bool
	 */
	public function delete_dir($filepath)
	{
		if( !$this->_is_conn() ) {
			return FALSE;
		}

		// Add a trailing slash to the file path if needed
		$filepath = preg_replace('/(.+?)\/*$/', '\\1/', $filepath);

		$list = $this->list_files($filepath);

		if( $list !== FALSE && count($list) > 0 ) {
			foreach( $list as $item ){
				// If we can't delete the item it's probaly a folder so
				// we'll recursively call delete_dir()
				if( !@ftp_delete($this->conn_id, $item) ) {
					$this->delete_dir($item);
				}
			}
		}

		$result = @ftp_rmdir($this->conn_id, $filepath);

		if( $result === FALSE ) {
			if( $this->debug === TRUE ) {
				$this->_error('ftp_unable_to_delete');
			}
			return FALSE;
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Definir permissões de arquivo
	 *
	 * @param string $path File path
	 * @param int $perm Permissions
	 * @return bool
	 */
	public function chmod($path, $perm)
	{
		if( !$this->_is_conn() ) {
			return FALSE;
		}

		$result = @ftp_chmod($this->conn_id, $perm, $path);

		if( $result === FALSE ) {
			if( $this->debug === TRUE ) {
				$this->_error('ftp_unable_to_chmod');
			}
			return FALSE;
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Lista os arquivos FTP no diretório especificado
	 *
	 * @param string $path
	 * @return array
	 */
	public function list_files($path = '.')
	{
		if( !$this->_is_conn() ) {
			return FALSE;
		}

		return ftp_nlist($this->conn_id, $path);
	}

	// ------------------------------------------------------------------------

	/**
	 * Leia um diretório e recriá-lo remotamente
	 *
	 * Esta função recursiva lê uma pasta e tudo o que ela contém
	 * (Incluindo sub-pastas) e cria um espelho via FTP com base nele.
	 * Qualquer que seja a estrutura de diretórios do caminho do arquivo original será
	 * Recriado no servidor.
	 *
	 * @param string $locpath Caminho para fonte com barra invertida
	 * @param string $rempath Caminho para o destino - incluir a pasta base com barra invertida
	 * @return bool
	 */
	public function mirror($locpath, $rempath)
	{
		if( !$this->_is_conn() ) {
			return FALSE;
		}

		// Open the local file path
		if( $fp = @opendir($locpath) ) {
			// Attempt to open the remote file path and try to create it, if it doesn't exist
			if( !$this->changedir($rempath, TRUE) && (!$this->mkdir($rempath) OR !$this->changedir($rempath)) ) {
				return FALSE;
			}

			// Recursively read the local directory
			while(FALSE !== ($file = readdir($fp))){
				if( @is_dir($locpath . $file) && $file[0] !== '.' ) {
					$this->mirror($locpath . $file . '/', $rempath . $file . '/');
				} elseif( $file[0] !== '.' ) {
					// Get the file extension so we can se the upload type
					$ext = $this->_getext($file);
					$mode = $this->_settype($ext);

					$this->upload($locpath . $file, $rempath . $file, $mode);
				}
			}
			return TRUE;
		}

		return FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Extract the file extension
	 *
	 * @param string $filename
	 * @return string
	 */
	protected function _getext($filename)
	{
		if( FALSE === strpos($filename, '.') ) {
			return 'txt';
		}

		$x = explode('.', $filename);
		return end($x);
	}

	// --------------------------------------------------------------------

	/**
	 * Set the upload type
	 *
	 * @param string $ext Filename extension
	 * @return string
	 */
	protected function _settype($ext)
	{
		$text_types = array(
			  'txt',
			  'text',
			  'php',
			  'phps',
			  'php4',
			  'php5',
			  'js',
			  'css',
			  'htm',
			  'html',
			  'phtml',
			  'shtml',
			  'log',
			  'xml'
		);

		return in_array($ext, $text_types) ? 'ascii' : 'binary';
	}

	// ------------------------------------------------------------------------

	/**
	 * fecha a conexão
	 *
	 * @return bool
	 */
	public function close()
	{
		if( !$this->_is_conn() ) {
			return FALSE;
		}
		return @ftp_close($this->conn_id);
	}

	// ------------------------------------------------------------------------

	/**
	 * Display error message
	 * 
	 * @throw new Exception
	 * @param string $line
	 * @return void
	 */
	protected function _error($line)
	{
		$this->errors[] = $line;
	}

	/**
	 * pega a ultima mensagem de debug
	 * @return string a ultima mensagen de erros FALSE em caso de não tiver nada
	 */
	public function lastError()
	{
		if( count($this->errors) == 0 ) {
			return false;
		}
		return $this->errors[(count($this->errors) - 1)];
	}

	public function __destruct()
	{
		$this->close();
	}

}
