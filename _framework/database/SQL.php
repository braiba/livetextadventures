<?php

/**
 * Class for querying the SQL database. Currently this is mostly a wrapper for mysql_query that also outputs to the error log automatically if the query fails.
 * @author Thomas
 */
class SQL {

	/**
	 *
	 * @var SQLConnection
	 */
	private static $dflt_conn;
	private static $info_conn;
	
	private function __construct(){

	}

	public static function initialise(){		
		self::$dflt_conn = new SQLConnection();
		self::$dflt_conn->connect(DB_HOST,DB_USER,DB_PASS,DB_NAME, (defined('DB_PORT')?DB_PORT:3306) );

		self::$info_conn = new SQLConnection();
		self::$info_conn->connect(DB_HOST,DB_USER,DB_PASS,'information_schema', (defined('DB_PORT')?DB_PORT:3306) );
	}
	
	
	/**
	 * Get instance
	 * @return SQLConnection
	 */
	public static function getDefaultConnection(){
		return self::$dflt_conn;
	}
	/**
	 * Get instance
	 * @return SQLConnection
	 */
	public static function getInfoConnection(){
		return self::$info_conn;
	}

	/**
	 * Executes a query on a specified connection. Because different types of connection return results in different formats it is
	 *   recommended only to use mysqli or mysql instances for the $conn parameter when a function or method needs to be able
	 *   create a temporary table on a specified connection.
	 * @param string $sql The query to be executed
	 * @param SQLConnection|mysqli|resource $conn The connection to execute the query on (the default SQL class connection will be
	 *		used if not specified, or null).
	 * @return SQLQueryResult|resource|boolean An appropriate query result for the connection used
	 */
	public static function query($sql,$conn=null){
		if (!is_null($conn)){
			if ($conn instanceof SQLConnection){
				return $conn->query($sql);
			}
			if ($conn instanceof mysqli){
				if ($res = $conn->query($sql)){
					return $res;
				}
				$error = $conn->error;
			} else {
				if ($res = mysql_query($sql,$conn)){
					return $res;
				}
				$error = mysql_error($conn);
			}
			Messages::msg('MySQL ERROR: '.$error."\r\n".'QUERY: '.SQLUtils::makeQueryReadable($sql),Messages::M_SQL_ERROR);
			Messages::msg('A database query failed with the following error message: '.$error,Messages::M_USER_ERROR);
			return false;
		}
		return self::getDefaultConnection()->query($sql);
	}

	/**
	 * Executes a query on a given connection, as per SQL::query(), but terminates gracefully if the query fails for some reason.
	 * @param string $sql The query to be executed
	 * @param SQLConnection|mysqli|resource $conn The connection to execute the query on (the default SQL class connection will be
	 *		used if not specified, or null).
	 * @return SQLQueryResult|resource|boolean An appropriate query result for the connection used
	 */
	public static function queryOrDie($sql,$conn=null){
		if (!is_null($conn)){
			if ($conn instanceof SQLConnection){
				return $conn->query($sql,SQLConnection::ON_ERROR_DIE);
			}
			if ($conn instanceof mysqli){
				if ($res = $conn->query($sql)){
					return $res;
				}
				$error = $conn->error;
			} else {
				if ($res = mysql_query($sql,$conn)){
					return $res;
				}
				$error = mysql_error($conn);
			}
			Messages::msg('MySQL ERROR: '.$error."\r\n".'QUERY: '.$sql,Messages::M_SQL_ERROR);
			Messages::msg('A database query failed with the following error message: '.$error,Messages::M_USER_ERROR);
			Messages::display();
			exit;
		}
		return self::getDefaultConnection()->query($sql,SQLConnection::ON_ERROR_DIE);
	}


	/**
	 * Executes a query on a given connection, as per SQL::query(), but throws an SQLQueryException if the query fails for some reason.
	 * @param string $sql The query to be executed
	 * @param SQLConnection|mysqli|resource $conn The connection to execute the query on (the default SQL class connection will be
	 *		used if not specified, or null).
	 * @return SQLQueryResult|resource|boolean An appropriate query result for the connection used
	 * @throws SQLQueryException
	 */
	public static function queryOrException($sql,$conn=null){
		if (!is_null($conn)){
			if ($conn instanceof SQLConnection){
				return $conn->query($sql,SQLConnection::ON_ERROR_EXCEPTION);
			}
			if ($conn instanceof mysqli){
				if ($res = $conn->query($sql)){
					return $res;
				}
				$error = $conn->error;
			} else {
				if ($res = mysql_query($sql,$conn)){
					return $res;
				}
				$error = mysql_error($conn);
			}
			throw new SQLQueryException($sql,$error);
		}
		return self::getDefaultConnection()->query($sql,SQLConnection::ON_ERROR_EXCEPTION);
	}

	/**
	 * Executes an update statement intended to affect only a single row.
	 * @param string $sql The statement to be executed.
	 * @return SQLQueryResult The result of the update.
	 * @throws MySQLException if the statement does not update one and only one row.
	 */
	public static function updateSingle($sql){
		$conn = self::getConnection();
		$conn->startTransaction();
		$res = $conn->query($sql);
		if ($res->affectedRows()==1){
			$conn->commit();
		} else {
			$conn->rollback();
			throw new MySQLException($sql,'Query was exected to update 1 row, but '.$res->affectedRows().' rows were updated. Update has been rolled back');
		}
		return $res;
	}
	
	/**
	 * PHP Magic Method. Relays static calls to the mySQLi instance
	 * @param string $method
	 * @param array $args
	 */
	public static function __callStatic($method,$args){
		return call_user_func_array(array(self::getDefaultConnection(),$method),$args);
	}

}

SQL::initialise();

?>