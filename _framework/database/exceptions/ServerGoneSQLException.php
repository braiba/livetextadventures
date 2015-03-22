<?php

	/**
	 *
	 * @author Thomas
	 */
	class ServerGoneSQLException extends SQLQueryException {
		const default_message = 'MySQL Server has gone away. This probably means that either the database has fallen over, or an admin has killed the connection.';

		public function __construct($query, $message = self::default_message, $code=0, $previous=null) {
			parent::__construct($query, $message, $code, $previous);
		}

	}

?>
