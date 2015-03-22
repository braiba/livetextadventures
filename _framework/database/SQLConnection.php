<?php

/**
 * Class for querying the SQL database. 
 * @author Thomas
 * 
 * @property-read int $affected_rows Gets the number of affected rows in a previous MySQL operation
 * @method bool autocommit() autocommit(bool $mode) Turns on or off auto-commiting database modifications
 * @method bool change_user() change_user(string $user , string $password , string $database) Changes the user of the specified database connection
 * @method string character_set_name() character_set_name() Returns the default character set for the database connection
 * @property-read string $client_info Returns the MySQL client version as a string
 * @property-read int $client_version Get MySQL client info
 * @method bool close() close() Closes a previously opened database connection
 * @method bool commit() commit() Commits the current transaction
 * @property-read int $connect_errno Returns the error code from last connect call
 * @property-read string $connect_error Returns a string description of the last connect error
 * @method bool debug() debug(string $message) Performs debugging operations
 * @method bool dump_debug_info() dump_debug_info() Dump debugging information into the log
 * @property-read int $errno Returns the error code for the most recent function call
 * @property-read string $error Returns a string description of the last error
 * @method int field_count() field_count() Returns the number of columns for the most recent query
 * @method object get_charset() get_charset() Returns a character set object
 * @method string get_client_info() get_client_info() Returns the MySQL client version as a string
 * @property-read int $client_version Get MySQL client info
 * @property-read string $host_info Returns a string representing the type of connection used
 * @property-read string $protocol_version Returns the version of the MySQL protocol used
 * @property-read string $server_info Returns the version of the MySQL server
 * @property-read int $server_version Returns the version of the MySQL server as an integer
 * @property-read string $info Retrieves information about the most recently executed query
 * @property-read mixed $insert_id Returns the auto generated id used in the last query
 * @method bool kill() kill(int $Processid) Asks the server to kill a MySQL thread
 * @method bool more_results() more_results() Check if there are any more query results from a multi query
 * @method bool multi_query() multi_query(string $query) Performs a query on the database
 * @method bool next_result() next_result() Prepare next result from multi_query
 * @method bool options() options(int $option , mixed $value) Set options
 * @method bool ping() ping() Pings a server connection, or tries to reconnect if the connection has gone down
 * @method mixed query() query(string $query, $resultmode = MYSQLI_STORE_RESULT) Performs a query on the database
 * @method string real_escape_string() real_escape_string() Escapes special characters in a string for use in an SQL statement, taking into account the current charset of the connection
 * @method bool real_query() real_query(string $query) Execute an SQL query
 * @method bool refresh() refresh() Refreshes
 * @method bool rollback() rollback() Rolls back current transaction
 * @method bool select_db() select_db(string $dbname) Selects the default database for database queries
 * @method bool set_charset() set_charset(string $charset) Sets the default client character set
 * @property-read string sqlstate Returns the SQLSTATE error from previous MySQL operation
 * @method bool ssl_set() ssl_set(string $key, string $cert, string $ca, string $capath, string $cipher) Used for establishing secure connections using SSL
 * @method string stat() stat() Gets the current system status
 * @method mysqli_result store_result() store_result() Transfers a result set from the last query
 * @property-read int thread_id Returns the thread ID for the current connection
 * @method mysqli_result use_result() use_result() Initiate a result set retrieval
 * @property-read int warning_count Returns the number of warnings from the last query for the given link
 */
class SQLConnection {

	private $host;
	private $user;
	private $pass;
	private $db;
	private $port;
	private $flags;
	
	private $con;
	private $autocommit = true;

	/**
	 *
	 * @var boolean Determines whether to reconnect if the connection is lost. Ignored during a transaction.
	 */
	private $autoreconnect = true;

	/**
	 * @var array $retry_error_codes An array of error codes for which the query should be retried. 
	 */
	private $retry_error_codes = array(
		SQL_ER_LOCK_WAIT_TIMEOUT,
		SQL_ER_LOCK_DEADLOCK,
	);
	/**
	 * @var int $retry_count The maximum number of times that a query should be retried if it fails with an expected error code.
	 */
	private $retry_count = 3;
	/**
	 * @var int $retry_sleep_min The minimum period of time, in seconds, that we should wait before retrying a query. The actual
	 *   time will be a random period between this and retry_sleep_max.
	 */
	private $retry_sleep_min = 3;
	/**
	 * @var int $retry_sleep_min The maximum period of time, in seconds, that we should wait before retrying a query. The actual
	 *   time will be a random period between this and retry_sleep_min.
	 */
	private $retry_sleep_max = 10;
	
	const ON_ERROR_CONTINUE = 0;
	const ON_ERROR_DIE = 1;
	const ON_ERROR_EXCEPTION = 2;
	/**
	 * @var int If true, PHP will exit in the event that an SQL query fails
	 */
	private $error_action = self::ON_ERROR_CONTINUE;

	public function __construct(){

	}

	public function getDatabase(){
		return $this->db;
	}
	
	/**
	 * Executes the given query and returns the resulting mysql reource
	 * @param string $sql The query to be executed
	 * @param int $error_action IF specified, will override the default error_action value of the SQLConnection
	 * @return SQLQueryResult
	 * @throws SQLQueryException
	 */
	public function query($sql,$error_action=null){
		if (is_null($this->con)){
			Messages::msg('Failed to execute query: No connection found.',Messages::M_CODE_ERROR);
			die('Failed to execute query: No connection found ');
		}

		if (is_null($error_action)){
			$error_action = $this->error_action;
		}
		Messages::msg($sql,Messages::M_SQL_QUERY);

		$affected_rows = 0;
		$success = false;
		for ($i=0; $i<=$this->retry_count; $i++){
			$res = @$this->con->query($sql);
			$affected_rows = $this->con->affected_rows; // Store this immediately, since Messages::msg etc may make futher DB calls
			if ($res!==false){
				// Query successful - break retry loop
				$success = true;
				break;
			}

			if ($i==$this->retry_count){
				break;
			}

			$error_no = $this->con->errno;
			if ($error_no == SQL_ER_SERVER_GONE){
				if ($this->autoreconnect){
					if ($this->autocommit){						
						$this->reconnect();
					} else {
						// Transaction is active; don't attempt reconnect
						break;
					}
				} else {
					// Server has gone away (connection killed or mysql has died)
					break;
				}
			} else if (!in_array($error_no,$this->retry_error_codes)){
				// Unexpected error code; do not retry query
				break;
			}

			$sleep_time = rand($this->retry_sleep_min,$this->retry_sleep_max);
			Messages::msg(TextUtils::plurality("Query failed with error {$error_no}: '{$this->con->error}'. Will retry in # second(s).",$sleep_time),Messages::M_SQL_WARNING);
			sleep($sleep_time);			
		}
		
		if (!$success){
			switch ($error_no){
				case SQL_ER_QUERY_INTERRUPTED : throw new QueryInterruptedSQLException($sql);
				case SQL_ER_SERVER_GONE : throw new ServerGoneSQLException($sql);
			}

			$error_msg = $this->con->error;

			Messages::msg('MySQL ERROR: '.$error_msg."\r\n".'QUERY:'."\r\n".SQLUtils::makeQueryReadable($sql),Messages::M_SQL_ERROR);
			
			switch ($error_action){
				case self::ON_ERROR_CONTINUE:
					break;

				case self::ON_ERROR_DIE:
					Messages::msg('SQLConnection is set to die on fail. PHP will now terminate.');
					Messages::display();
					exit;

				case self::ON_ERROR_EXCEPTION:
					throw new SQLQueryException($sql, $error_msg);

				default:
					Messages::msg('Unknown error action code in SQLConnection: '.$error_action,Messages::M_CODE_ERROR);
					Messages::display();
					exit;
			}
		}

		if ($res instanceof mysqli_result){
			return new SQLQueryResult($res,$affected_rows);
		} else {
			return new SQLQueryBooleanResult($res,$affected_rows);
		}
	}

	public function connect($host,$user,$pass,$db,$port=3306,$flags=65536){
		if ($this->isConnected()){
			$this->host = null;
			$this->user = null;
			$this->pass = null;
			$this->db = null;
			$this->flags = null;
			$this->con->close();
		}

		for ($i=0; $i<=$this->retry_count; $i++){
			$this->con = @(new mysqli($host,$user,$pass,$db,$port));
			if ($this->con->connect_errno===0){
				// Connection successful - break retry loop
				$this->host = $host;
				$this->user = $user;
				$this->pass = $pass;
				$this->db = $db;
				$this->port = $port;
				$this->flags = $flags;
				$this->con->set_charset('utf8_general_ci');
				return true;
			}	
			if ($i==$this->retry_count){
				break;
			} else {
				die($this->con->connect_error);
				$sleep_time = rand($this->retry_sleep_min,$this->retry_sleep_max);
				Messages::msg(TextUtils::plurality('Connection failed with error message "'.$this->con->connect_error.'". Will retry in # second(s).',$sleep_time),Messages::M_SQL_WARNING);
				sleep($sleep_time);
			}
		}

		Messages::msg($this->con->connect_errno.': '.$this->con->connect_error,Messages::M_SQL_ERROR);
		$this->con = null;
		return false;		
	}

	/**
	 * Set wheteher to attempt to reconnect if the server disappears. Ignored during a transaction.
	 * @param boolean $autoreconnect 
	 */
	public function setAutoReconnect($autoreconnect){
		$this->autoreconnect = $autoreconnect;
	}

	public function reconnect(){
		// This is a bit of a hack. There's probably a better way to do this, but it'll do for now.
		$reconnect = ini_get('mysqli.reconnect');
		ini_set('mysqli.reconnect',true);
		$result = $this->con->ping();
		ini_set('mysqli.reconnect',$reconnect);
		if (!$result){
			$result = $this->connect($this->host, $this->user, $this->pass, $this->db, $this->flags);
		}
		return $result;
	}

	public function isConnected(){
		return ($this->con instanceof mysqli) && ($this->con->ping());
	}

	/**
	 * Searches the given table for records matching the criteria and returns them
	 * @param string|SQLTable $table The table being searched
	 * @param array $query The query in an array of the format $column_name=>$target_value
	 * @param string $additional_where
	 * @return array Array of the resulting {@link SQLRecord} objects.
	 */
	public function search($table,$query,$additional_where=null){
		if (is_string($table)){
			$table = SQLTable::get($table);
		}
		$wheres = array();
		foreach ($query as $name=>$value){
			$column = $table->getColumn($name);
			$wheres[] = "`{$column->getName()}` = {$column->valueAsSQL($value)}";
		}
		$sql = "SELECT * FROM {$table->getFullName()} WHERE ".implode(' AND ',$wheres).(isset($additional_where)?" AND $additional_where":'');
		return $this->query($sql);
	}

	/**
	 * Searches the given table for a single record matching the criteria and returns it. If more than one record is found, none are returned.
	 * @param string|SQLTable $table The table being searched
	 * @param array $query The query in an array of the format $column_name=>$target_value
	 * @param string $additional_where An optional string to be made part of the where clause. Use this for querying with >=, <=, functions, etc.
	 * @return array Array of the resulting {@link SQLRecord} objects.
	 */
	public function searchSingle($table,$query,$additional_where=null){
		$result = $this->search($table,$query,$additional_where);
		if ($result->size()==1){
			return $result->getOnly();
		}
		return null;
	}

	/**
	 * Returns the auto generated id used in the last query
	 * @return int The value of the AUTO_INCREMENT field that was updated by the previous query. Returns zero if there was no previous query on the connection or if the query did not update an AUTO_INCREMENT value.
	 */
	public function getInsertId(){
		return $this->con->insert_id;
	}

	public function __call($method,$args){
		if (!method_exists($this->con,$method)){
			throw new Exception("mysqli method does not exist: $method");
		}
		return call_user_func_array(array($this->con,$method), $args);
	}

	public function startTransaction(){
		if (!$this->autocommit){
			Messages::msg('Attempted to start a transaction without calling commit or rollback on previous transaction.',Messages::M_CODE_ERROR);
			return false;
		} else {
			return ($this->autocommit = !$this->con->autocommit(false));
		}
	}

	public function commit(){
		if ($this->autocommit){
			Messages::msg('Commit called without transaction.',Messages::M_CODE_ERROR);
			return false;
		} else {
			return ($this->autocommit = $this->con->autocommit(true));
		}
	}

	public function rollback(){
		if ($this->autocommit){
			Messages::msg('Rollback called without transaction.',Messages::M_CODE_ERROR);
		} else {
			if ($this->con->rollback()){
				return ($this->autocommit = $this->con->autocommit(true));
			}
		}
		return false;
	}

	public function disconnect() {
		if ($this->con) {
			$this->con->close();
			$this->con = null;
		} else {
			Message::msg('Disconnect called without connection.', Message::M_CODE_ERROR);
		}
	}

	public function getAutoCommit(){
		return $this->autocommit;
	}
	public function setAutoCommit($autocommit){
		$this->autocommit = $autocommit;
	}

	public function getErrorAction(){
		return $this->error_action;
	}
	public function setErrorAction($error_action){
		$this->error_action = $error_action;
	}
	
	public function getLastCountedRows(){
		if ($row = SQL::query('SELECT FOUND_ROWS() AS `total_rows`')->getOnly()){
			return $row->total_rows;
		}
		return null;
	}
	
}

?>