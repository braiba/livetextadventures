<?php

/**
 *
 * @author Thomas
 */
class InformationSchemaSQLException extends SQLQueryException {
	
	const default_message = 'The query was interrupted. This was probably caused by an admin killing the query.';

	public function __construct($query, $message = self::default_message, $code=0, $previous=null){
		parent::__construct($query, $message, $code, $previous);
	}

}
?>
