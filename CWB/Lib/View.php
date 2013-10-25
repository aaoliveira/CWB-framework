<?php

namespace CWB\Lib;

use CWB\Config\App;

/**
 * class View
 * carrega as views para a mostra
 *
 * @package CWB;
 */
final class View
{
	/**
	 * dir where have view
	 * @var string $dirView
	 */
	protected $dirView;

	/**
	 * verify if parse it's activ
	 * @var bool $parser
	 */
	public $parser = false;

	/**
	 * left delemiter of parse views
	 * @var string
	 */
	private $l_delim = '{';

	/**
	 * right delemiter of parse views
	 * @var string
	 */
	private $r_delim = '}';

	/**
	 * when atribute of a class or index of array separator
	 * @var string $parseSubAttribute
	 */
	private $subAttrDelim = '.';

	/**
	 * verify if cache it's activ
	 * @var bool $cache
	 */
	private $cache = false;

	/**
	 * time cache
	 * @var string
	 */
	public $cacheTime = '5 minutes';

	/**
	 * name to file of the cache
	 * @var string 
	 */
	public $cacheName = '';

	/**
	 * instance of cache
	 * @var object
	 */
	private $Icache = null;

	/**
	 * names of the files
	 * @var array 
	 */
	private $filename = array();

	/**
	 * parameters for the view
	 * @var array
	 */
	private $params = array();

	/**
	 * prefix to file can be a directory or algo
	 * @var string $prefix
	 */
	public $prefix = '';

	/**
	 * content of the view
	 * @var string 
	 */
	protected $content = null;

	/**
	 * classe usada para caregar as views
	 * @param array $config > configurations of the class
	 */
	public function __construct($config = array())
	{
		$this->dirView = App::getAppDir() . 'Views' . DIRECTORY_SEPARATOR;

		foreach ($config as $var => $value) {
			$this->{$var} = $value;
		}

		if ($this->cache)
				$this->cacheStart();

		return $this;
	}

	/**
	 * add the parameters for the files
	 * @param array $param
	 * 
	 * @return CWB\Lib\View
	 */
	public function addParams($param)
	{
		if (is_array($param) || is_object($param)) {
			$this->params[] =& $param;
		}
		return $this;
	}

	/**
	 * clean all parameters
	 */
	public function cleanParams()
	{
		$this->params = array();
	}

	/**
	 * inclui a file for View
	 * @param string $file
	 * @return CWB\Lib\View
	 */
	public function setFile($file)
	{
		$this->filename[] =& $file;
		return $this;
	}

	/**
	 * start the chache
	 */
	public function cacheStart()
	{
		$this->cache = true;
		if ($this->Icache == null)
				$this->Icache =& Cache::getInstance();
	}

	/**
	 * stop the cache
	 */
	public function cacheStop()
	{
		$this->cache = false;
	}

	/**
	 * load view files the content
	 */
	private function setContent()
	{
		foreach ($this->params as $__addedVariable) {
			foreach ($__addedVariable as $__key => $__value) {
				${$__key} = $__value;
			}
		}

		$this->content = '';
		ob_start();
		foreach ($this->filename as $__file) {
			if (file_exists($this->dirView . $this->prefix . $__file))
					include $this->dirView . $this->prefix . $__file;
			elseif(file_exists($this->dirView . $__file))
					include $this->dirView . $__file;
		}
		$this->content = ob_get_contents();
		@ob_end_clean();

		if ($this->parser)
				$this->content = $this->parse($this->content);
	}

	/**
	 * verify if cache is exists and avaliable
	 * if true load the content automaticaly
	 * @return boolean
	 */
	public function cacheExists()
	{
		if (!$this->cache)
				return false;

		try {
			$this->content = $this->Icache->read($this->cacheName);
		} catch (\Exception $e) {
			//return 'ERRO: ' . $e->getMessage();
		}

		return ($this->content === null) ? false : true;
	}

	/**
	 * load the view for $this->content
	 * 
	 * @param boolean $returnContent >if return string
	 * 
	 * @return object __CLASS__ or string content
	 */
	public function &load()
	{
		if ($this->cache) {
			if (!$this->cacheExists()) {
				$this->setContent();
				$this->Icache->save($this->cacheName, $this->content, $this->cacheTime);
			}
		} else {
			$this->setContent();
		}

		return $this->content;
	}

	/**
	 * show the content
	 * loading the content and writen for the cache
	 */
	public function show()
	{
		if ($this->cache || $this->parser) {
			echo $this->load();
		} else {
			$this->onlyOutput();
		}
	}

	/**
	 * output the content calling show();
	 */
	public function __toString()
	{
		$this->show();
		return '';
	}

	/**
	 * print the views -- only include the views
	 * call the views without load content in a $var
	 * and not cache
	 */
	protected function onlyOutput()
	{
		foreach ($this->params as $__addedVariable) {
			foreach ($__addedVariable as $__key => $__value) {
				${$__key} = $__value;
			}
		}

		foreach ($this->filename as $__file) {
			if (file_exists($this->dirView . $this->prefix . $__file))
					include $this->dirView . $this->prefix . $__file;
			elseif(file_exists($this->dirView . $__file))
					include $this->dirView . $__file;
		}
	}

	// ------------------------PARSES FUNCTIONS----------------------------------------------

	/**
	 * Set the left/right variable delimiters
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	void
	 */
	public function setDelimiters($l = '{', $r = '}', $subAttr = '.')
	{
		$this->l_delim = $l;
		$this->r_delim = $r;
		$this->subAttrDelim = $subAttr;
		return $this;
	}

	/**
	 *  Parse a template
	 *
	 * Parses pseudo-variables contained in the specified template,
	 * replacing them with the data in the second param
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 */
	protected function parse(&$content)
	{
		foreach ($this->params as $__addedVariable) {
			foreach ($__addedVariable as $__key => $__value) {
				${$__key} = $__value;
			}
		}

		// add global variables
		$this->addParams(array(
			'_SERVER' => $_SERVER,
			'_GET' => $_GET,
			'_POST' => $_POST,
			'_FILES' => $_FILES,
			'_COOKIE' => $_COOKIE,
			'_SESSION' => isset($_SESSION) ? $_SESSION : null,
			'_REQUEST' => $_REQUEST,
			'_ENV' => $_ENV
		));

		$match = $this->parseMatchIncludes($content);

		foreach ($match as &$file) {
			ob_start();

			if (file_exists($this->dirView . $this->prefix . $file))
					include $this->dirView . $this->prefix . $file;
			elseif (file_exists($this->dirView . $file))
					include $this->dirView . $file;

			$content = str_replace($this->l_delim . '@include="' . $file . '"' . $this->r_delim,
									ob_get_clean(),
									$content);
			//ob_end_clean();
		}

		foreach ($this->params as $__addedVariable) {
			foreach ($__addedVariable as $__key => $__val) {
				//if value is array or object
				if (is_array($__val) || is_object($__val)) {
					// if rows
					if ($this->pIsRows($__val)) {
						$content = $this->parsePair($__key, $__val, $content);
						continue;
					} else {
						// parse to sub array or obj
						$subAttr = $this->parseSubAttr($__val, $__key . $this->subAttrDelim);

						foreach ($subAttr as $_key => $_val) {
							if (is_array($_val) || is_object($_val)) {
								if ($this->pIsRows($_val)) {
									$content = $this->parsePair($_key, $_val, $content);
								} else {
									$content = $this->parseSingle($_key, (string)$_val, $content);
								}
							} else {
								$content = $this->parseSingle($_key, (string)$_val, $content);
							}
						}
						continue;
					}
				} else {
					//for normal
					$content = $this->parseSingle($__key, (string)$__val, $content);
					continue;
				}
			}
		}

		return $content;
	}

	/**
	 * try parse include files
	 * @param string $content > conteudo a ser substituido
	 * @return array all files to load 
	 */
	private function parseMatchIncludes(&$content)
	{
		if (!preg_match_all("|" . preg_quote($this->l_delim . '@include="')
							. '(.+?)' . preg_quote('"' . $this->r_delim) . "|s",
							$content,
							$match)
		) {
			return array();
		}

		return array_unique($match[1]);
	}

	/**
	 * parse the sub class or array
	 * from: array('object'=>array('fiels'=>1)) to: {object.fields}
	 * @param array $var > array where contains every as vars
	 * @param string $sub > prefix where alocate father obj
	 * @return array with all sub attr defined in key and your valou
	 */
	private function parseSubAttr(&$var, $sub = '')
	{
		$subAttr = array();
		$attr = array();
		foreach ($var as $key => $value) {
			if ($this->pIsRows($value)) {
				$subAttr[$sub . $key] = $value;
			} elseif (is_array($value) || is_object($value)) {
				$attr = $this->parseSubAttr($value, $sub . $key . $this->subAttrDelim);
			} else {
				$subAttr[$sub . $key] = $value;
			}
		}
		return $subAttr + $attr;
	}

	/**
	 * verify if array is a row of table
	 * @param array $array >array where have rows or keys
	 * @return boolean true if is rows or false if not rows
	 */
	private function pIsRows(&$array)
	{
		if (!is_array($array))
				return false;

		foreach ($array as $key => $val) {
			if (!is_int($key) || !(is_array($val) || is_object($val)))
					return false;
		}
		return true;
	}

	/**
	 *  Parse a single key/value
	 *
	 * @access	private
	 * @param	string
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	private function parseSingle($key, $val, &$string)
	{
		return str_replace($this->l_delim . $key . $this->r_delim, $val, $string);
	}

	/**
	 * Parse a tag pair
	 *
	 * Parses tag pairs:  {some_tag} string... {/some_tag}
	 *
	 * @access	private
	 * @param	string
	 * @param	array
	 * @param	string
	 * @return	string
	 */
	private function parsePair(&$variable, &$data, &$string)
	{
		if (false === ($match = $this->matchPair($string, $variable))) {
			return $string;
		}

		$str = '';
		$match['1'] = trim($match['1']);

		foreach ($data as $__row) {
			$temp = $match['1'];

			foreach ($__row as $key => $val) {
				if (is_array($val) || is_object($val)) {

					if ($this->pIsRows($val)) {
						$temp = $this->parsePair($key, $val, $temp);
					} else {
						// parse to sub array or obj
						$subAttr = $this->parseSubAttr($val, $key . $this->subAttrDelim);

						foreach ($subAttr as $_key => $_val) {
							if (is_array($_val) || is_object($_val)) {
								if ($this->pIsRows($_val)) {
									$temp = $this->parsePair($_key, $_val, $temp);
								} else {
									$temp = $this->parseSingle($_key, (string)$_val, $temp);
								}
							} else {
								$temp = $this->parseSingle($_key, (string)$_val, $temp);
							}
						}
					}
				} else {
					// case is normal
					$temp = $this->parseSingle($key, $val, $temp);
				}
			}

			$str .= $temp;
		}

		return str_replace($match['0'], $str, $string);
	}

	/**
	 *  Matches a variable pair
	 *
	 * @access	private
	 * @param	string
	 * @param	string
	 * @return	mixed
	 */
	private function matchPair(&$string, &$variable)
	{
		if (!preg_match("|" 
						. preg_quote($this->l_delim . $variable . $this->r_delim)
						. "(.+?)"
						. preg_quote($this->l_delim . '/' . $variable . $this->r_delim) 
						. "|s", 
						$string, 
						$match)
		) {
			return false;
		}
		return $match;
	}

	// -------------------------------SIMPLE INCLUDE VIEW--------------------------------------------------------
	/**
	 * pega a view para outras views
	 * @param string $file arquivo para incluir
	 */
	public static function getView($file)
	{
		include App::getAppDir() . 'Views' . DIRECTORY_SEPARATOR . $file;
	}
}
