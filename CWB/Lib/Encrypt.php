<?php

namespace CWB\Lib;

use CWB\Config\App;

/**
 * Encryption Class
 *
 * Provides two-way keyed encoding using XOR Hashing and Mcrypt
 *
 * @package	CWB
 */
class Encrypt
{
	protected $encryptionKey = '';
	private $_hashType = 'sha1';
	protected $_mcryptExists;
	protected $_mcryptCipher = '';
	protected $_mcryptMode = '';

	/**
	 * Constructor
	 *
	 * Simply determines whether the mcrypt library exists.
	 *
	 */
	public function __construct()
	{
		$this->encryptionKey = App::SECRET_KEY;

		if(extension_loaded('mcrypt')) {
			$this->_mcryptExists = true;
		} else {
			$this->_mcryptExists = false;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch the encryption key
	 *
	 * Returns it as MD5 in order to have an exact-length 128 bit key.
	 * Mcrypt is sensitive to keys that are not the correct length
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function getKey($key = '')
	{
		if($key == '') {
			if($this->encryptionKey != '') {
				return $this->encryptionKey;
			}

			$key = App::SECRET_KEY;
		}

		return md5($key);
	}

	// --------------------------------------------------------------------

	/**
	 * Set the encryption key
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function setKey($key = '')
	{
		$this->encryptionKey = $key;
	}

	// --------------------------------------------------------------------

	/**
	 * Encode
	 *
	 * Encodes the message string using bitwise XOR encoding.
	 * The key is combined with a random hash, and then it
	 * too gets converted using XOR. The whole thing is then run
	 * through mcrypt (if supported) using the randomized key.
	 * The end result is a double-encrypted message string
	 * that is randomized with each call to this function,
	 * even if the supplied message and key are the same.
	 *
	 * @access	public
	 * @param	string	the string to encode
	 * @param	string	the key
	 * @return	string
	 */
	function encode($string, $key = '')
	{
		$key = $this->getKey($key);

		if($this->_mcryptExists === TRUE) {
			$enc = $this->mcryptEncode($string, $key);
		} else {
			$enc = $this->_xorEncode($string, $key);
		}

		return base64_encode($enc);
	}

	// --------------------------------------------------------------------

	/**
	 * Decode
	 *
	 * Reverses the above process
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	function decode($string, $key = '')
	{
		$key = $this->getKey($key);

		if(preg_match('/[^a-zA-Z0-9\/\+=]/', $string)) {
			return FALSE;
		}

		$dec = base64_decode($string);

		if($this->_mcryptExists === TRUE) {
			if(($dec = $this->mcryptDecode($dec, $key)) === FALSE) {
				return FALSE;
			}
		} else {
			$dec = $this->_xorDecode($dec, $key);
		}

		return $dec;
	}

	/**
	 * XOR Encode
	 *
	 * Takes a plain-text string and key as input and generates an
	 * encoded bit-string using XOR
	 *
	 * @access	private
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	private function _xorEncode($string, $key)
	{
		$rand = '';
		while(strlen($rand) < 32){
			$rand .= mt_rand(0, mt_getrandmax());
		}

		$rand = $this->hash($rand);

		$enc = '';
		for($i = 0; $i < strlen($string); $i++){
			$enc .= substr($rand, ($i % strlen($rand)), 1) . (substr($rand, ($i % strlen($rand)), 1) ^ substr($string, $i, 1));
		}

		return $this->_xorMerge($enc, $key);
	}

	/**
	 * XOR Decode
	 *
	 * Takes an encoded string and key as input and generates the
	 * plain-text original message
	 *
	 * @access	private
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	private function _xorDecode($string, $key)
	{
		$string = $this->_xorMerge($string, $key);

		$dec = '';
		for($i = 0; $i < strlen($string); $i++){
			$dec .= (substr($string, $i++, 1) ^ substr($string, $i, 1));
		}

		return $dec;
	}

	/**
	 * XOR key + string Combiner
	 *
	 * Takes a string and key as input and computes the difference using XOR
	 *
	 * @access	private
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	private function _xorMerge($string, $key)
	{
		$hash = $this->hash($key);
		$str = '';
		for($i = 0; $i < strlen($string); $i++){
			$str .= substr($string, $i, 1) ^ substr($hash, ($i % strlen($hash)), 1);
		}

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Encrypt using Mcrypt
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	public function mcryptEncode($data, $key)
	{
		$init_size = mcrypt_get_iv_size($this->_getCipher(), $this->_getMode());
		$init_vect = mcrypt_create_iv($init_size, MCRYPT_RAND);
		return $this->_addCipherNoise($init_vect . mcrypt_encrypt($this->_getCipher(), $key, $data, $this->_getMode(), $init_vect), $key);
	}

	// --------------------------------------------------------------------

	/**
	 * Decrypt using Mcrypt
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	public function mcryptDecode($data, $key)
	{
		$data = $this->_removeCipherNoise($data, $key);
		$init_size = mcrypt_get_iv_size($this->_getCipher(), $this->_getMode());

		if($init_size > strlen($data)) {
			return FALSE;
		}

		$init_vect = substr($data, 0, $init_size);
		$data = substr($data, $init_size);
		return rtrim(mcrypt_decrypt($this->_getCipher(), $key, $data, $this->_getMode(), $init_vect), "\0");
	}

	// --------------------------------------------------------------------

	/**
	 * Adds permuted noise to the IV + encrypted data to protect
	 * against Man-in-the-middle attacks on CBC mode ciphers
	 * http://www.ciphersbyritter.com/GLOSSARY.HTM#IV
	 *
	 * Function description
	 *
	 * @access	private
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	private function _addCipherNoise($data, $key)
	{
		$keyhash = $this->hash($key);
		$keylen = strlen($keyhash);
		$str = '';

		for($i = 0, $j = 0, $len = strlen($data); $i < $len; ++$i, ++$j){
			if($j >= $keylen) {
				$j = 0;
			}

			$str .= chr((ord($data[$i]) + ord($keyhash[$j])) % 256);
		}

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Removes permuted noise from the IV + encrypted data, reversing
	 * _addCipherNoise()
	 *
	 * Function description
	 *
	 * @access	private
	 * @param	type
	 * @return	type
	 */
	private function _removeCipherNoise($data, $key)
	{
		$keyhash = $this->hash($key);
		$keylen = strlen($keyhash);
		$str = '';

		for($i = 0, $j = 0, $len = strlen($data); $i < $len; ++$i, ++$j){
			if($j >= $keylen) {
				$j = 0;
			}

			$temp = ord($data[$i]) - ord($keyhash[$j]);

			if($temp < 0) {
				$temp = $temp + 256;
			}

			$str .= chr($temp);
		}

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Set the Mcrypt Cipher
	 *
	 * @access	public
	 * @param	constant
	 * @return	string
	 */
	public function setCipher($cipher)
	{
		$this->_mcryptCipher = $cipher;
	}

	// --------------------------------------------------------------------

	/**
	 * Set the Mcrypt Mode
	 *
	 * @access	public
	 * @param	constant
	 * @return	string
	 */
	public function setMode($mode)
	{
		$this->_mcryptMode = $mode;
	}

	// --------------------------------------------------------------------

	/**
	 * Get Mcrypt cipher Value
	 *
	 * @access	private
	 * @return	string
	 */
	private function _getCipher()
	{
		if($this->_mcryptCipher == '') {
			$this->_mcryptCipher = MCRYPT_RIJNDAEL_256;
		}

		return $this->_mcryptCipher;
	}

	// --------------------------------------------------------------------

	/**
	 * Get Mcrypt Mode Value
	 *
	 * @access	private
	 * @return	string
	 */
	private function _getMode()
	{
		if($this->_mcryptMode == '') {
			$this->_mcryptMode = MCRYPT_MODE_CBC;
		}

		return $this->_mcryptMode;
	}

	// --------------------------------------------------------------------

	/**
	 * Set the Hash type
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function setHash($type = 'sha1')
	{
		$this->_hashType = in_array($type, hash_algos()) ? $type : 'sha1';
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Hash encode a string
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function hash($str)
	{
		return hash($this->_hashType, md5($str));
	}

}