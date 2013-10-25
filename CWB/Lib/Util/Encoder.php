<?php

namespace CWB\Lib\Util;

use CWB\Config\App;

/**
 * Cryptography class
 *
 * This file is under GNU General Public License
 * 
 * @package CWB
 */
class Encoder
{
	// The key
	private $key;

	// Generates the key
	public function __construct()
	{
		$key = App::SECRET_KEY;
		$key = str_split(sha1(md5($key)), 1);
		$signal = false;
		$sum = 0;

		foreach( $key as $char ){
			if( $signal ) {
				$sum -= ord($char);
				$signal = false;
			} else {
				$sum += ord($char);
				$signal = true;
			}
		}

		$this->_key = abs($sum);
	}

	// Encrypt
	public function encode($text)
	{
		$text = str_split($text, 1);
		$final = '';

		foreach( $text as $char ){
			$final .= sprintf("%03x", ord($char) + $this->_key);
		}

		return $final;
	}

	// Decrypt
	public function decode($text)
	{
		$final = '';
		$text = str_split($text, 3);

		foreach( $text as $char ){
			$final .= chr(hexdec($char) - $this->_key);
		}

		return $final;
	}

}