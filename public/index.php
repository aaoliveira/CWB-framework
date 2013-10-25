<?php

/**
 * path to base directory
 */
define('PUBLIC_DIR',  __DIR__. DIRECTORY_SEPARATOR);

require PUBLIC_DIR.'../CWB/Core/Autoloader.php';

/**
 * autoloader do composer
 */
/*
if(file_exists(dirname(PUBLIC_DIR).'/vendor/autoloader.php')){
	include dirname(PUBLIC_DIR).'/vendor/autoloader.php';
}
*/

CWB\Core\Start::Main();
