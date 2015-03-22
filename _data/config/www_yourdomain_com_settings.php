<?php

	define('DB_HOST', 'localhost');
	define('DB_USER', '');
	define('DB_PASS', '');
	define('DB_NAME', '');
		
	SQL::getDefaultConnection()->setErrorAction(SQLConnection::ON_ERROR_EXCEPTION);
	
	Messages::enablePHPErrorHandling();
	
	Messages::addHandler(
		new ErrorLogMessageHandler(
			Messages::M_ERROR|
			Messages::M_CODE_WARNING|
			Messages::M_CODE_ERROR|
			Messages::M_SQL_WARNING|
			Messages::M_SQL_ERROR|
			Messages::M_DEBUG|
			Messages::M_PHP_INFO|
			Messages::M_PHP_ERROR|
			Messages::M_BACKGROUND_WARNING|
			Messages::M_BACKGROUND_ERROR
		)
	);
			
?>