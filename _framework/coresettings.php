<?php

	date_default_timezone_set('Europe/London');

	spl_autoload_register(
		function ($class){
			if (!include_once("$class.php")){
				while (ob_get_level()) ob_end_clean();
				$ex = new Exception('Could not find class: ' . $class);
				Messages::msg($ex->getTraceAsString(),Messages::M_CODE_ERROR);
				die($ex);
			}
		}
	);
	
	function addToIncludePath($dir){
		set_include_path($dir.PATH_SEPARATOR.get_include_path());
	}
	
	error_reporting(E_ALL);
		
	require_once('./_framework/constants.php');
	
	$file = $_SERVER['PHP_SELF'];
	$dir = preg_replace('#[\\\\/][^\\\\/]+$#','',$file);
	
	define('SITE_DIR',$dir);
	define('SITE_PATH',$dir);
	define('FRAMEWORK_PATH',$dir.'/_framework');
	
	define('SERVER_ROOT',$_SERVER['DOCUMENT_ROOT']);
	define('SITE_ROOT',SERVER_ROOT.(defined('SITE_DIR')?SITE_DIR:SITE_PATH));
	define('FRAMEWORK_ROOT',SERVER_ROOT.FRAMEWORK_PATH);
	
	$framework_dirs = array('core','database','database/constraints','database/exceptions','messages','messages/handlers','block','objects','query','forms','tables','images','utils');
	foreach ($framework_dirs as $dir){
		addToIncludePath(FRAMEWORK_ROOT."/$dir/");
	}
		
	$site_dirs = array('controllers','models','user');
	foreach ($site_dirs as $dir){
		addToIncludePath(SITE_ROOT."/_data/$dir/");
		addToIncludePath(SITE_ROOT."/_data/$dir/abstract/");
		addToIncludePath(SITE_ROOT."/_data/$dir/interface/");
	}
	
?>