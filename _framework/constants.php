<?php

	define('RESULT_VALIDATION_FAILURE', null); // evaluates as false
	define('RESULT_FAILURE', 0); // evaluates as false
	define('RESULT_SUCCESS', 1); // evaluates as true
	define('RESULT_IGNORED', 2); // evaluates as true
	
	define('MINUTE_LENGTH', 60);
	define('HOUR_LENGTH', 60 * MINUTE_LENGTH);
	define('DAY_LENGTH', 24 * HOUR_LENGTH);
	define('WEEK_LENGTH', 7 * DAY_LENGTH);

	define('HTTP_STATUS_MOVED_PERMANENTLY', 301);
	define('HTTP_STATUS_FOUND', 302);
	define('HTTP_STATUS_SEE_OTHER', 303);
	define('HTTP_STATUS_NOT_MODIFIED', 304);
	define('HTTP_STATUS_BAD_REQUEST', 400);
	define('HTTP_STATUS_FORBIDDEN', 403);
	define('HTTP_STATUS_NOT_FOUND', 404);
	define('HTTP_STATUS_INTERNAL_SERVER_ERROR', 500);
	define('HTTP_STATUS_NOT_IMPLEMENTED', 501);
	
	define('SQL_ER_LOCK_WAIT_TIMEOUT',1205); // "Lock wait timeout exceeded; try restarting transaction."
	define('SQL_ER_LOCK_DEADLOCK',1213);     // "Deadlock found when trying to get lock; try restarting transaction."
	define('SQL_ER_SERVER_GONE',2006);       // "MySQL server has gone away." NOTE: This is the error that occurs when the database dies, or we kill a connection
	define('SQL_ER_QUERY_INTERRUPTED',1317); // "Query execution was interrupted." NOTE: This is the error that occurs when we kill a query

?>