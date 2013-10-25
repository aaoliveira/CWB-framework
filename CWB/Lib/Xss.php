<?php

namespace CWB\Lib;

/**
 * Cross Side Scripting protection class.
 *
 * @package CWB
 */
class Xss
{
	/**
	 * Fix any problems or tampering with entities
	 * @param string $data the data that needs cleaning
	 * @return string The Clean String.
	 */
	public static function fixEntitys($data = '')
	{
		$data = str_replace(array('&amp;amp;', '&amp;lt;', '&amp;gt;', '&amp;quot;'),
					array('&amp;', '&lt;', '&gt;', '&quot;'), $data);
		$data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
		$data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
		return $data;
	}

	/**
	 * Remove on and xmlns attributes
	 * @param string $data the data that needs cleaning
	 * @return string The Clean String.
	 */
	public static function removeAttribute($data = '')
	{
		$data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);
		return $data;
	}

	/**
	 * Remove javascript and VB tags
	 * @param string $data the data that needs cleaning
	 * @return string The Clean String.
	 */
	public static function removeJavaVB($data = '')
	{
		$data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]'
							. '*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r'
							. '[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu',
							'$1=$2nojavascript...',
							$data);
		$data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s'
							. '[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu',
							'$1=$2novbscript...',
							$data);
		$data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u',
							'$1=$2nomozbinding...',
							$data);
		return $data;
	}

	/**
	 * Remove a common css attack 
	 * @param string $data the data that needs cleaning
	 * @return string The Clean String.
	 */
	public static function removeCssAttack($data = '')
	{
		$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', 
							'$1>',
							$data);
		$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i',
							'$1>',
							$data);
		$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r'
							. '[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu',
							'$1>',
							$data);
		return $data;
	}

	/**
	 * Remove namespaces from strong
	 * @param string $data the data that needs cleaning
	 * @return string The Clean String.
	 */
	public static function removeNameSpace($data = '')
	{
		$data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);
		return $data;
	}

	/**
	 * Remove tags that can cause a security issue
	 * @param string $data the data that needs cleaning
	 * @return string The Clean String.
	 */
	public static function removeHarmfullStrings($data = '')
	{
		$data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)'
					. '|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i',
					'',
					$data,
					-1);
		return $data;
	}

	/**
	 * Run though selected cleaners
	 * @param Array $settings What cleans you want to run through. True And False. 
	 * @param string $data the data that needs cleaning
	 * @return string The Clean String.
	 */
	public static function clean($data = '', $settings = array(true, true, true, true, true, true, true))
	{
		if ($settings[1]) {
			$data = self::removeAttribute($data);
		}
		if ($settings[2]) {
			$data = self::removeJavaVB($data);
		}
		if ($settings[3]) {
			$data = self::removeCssAttack($data);
		}
		if ($settings[4]) {
			$data = self::removeNameSpace($data);
		}
		if ($settings[5]) {
			$data = self::removeHarmfullStrings($data);
		}
		if ($settings[6]) {
			$data = htmlentities($data, ENT_QUOTES, 'UTF-8');
		}

		//Below is enforced protection
		$data = htmlspecialchars($data);

		if ( $settings[0] ) {
			$data = self::fixEntitys($data);
		}

		// Xss Clean Data
		return $data;
	}

}