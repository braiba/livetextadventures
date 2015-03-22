<?php
/**
 *
 * @author Thomas
 */
class SQLQueryException extends SQLException {

	protected $query;
	
	public function __construct($query, $message, $code=0, $previous=null){
		parent::__construct($message, $code, $previous);
		$this->query = $query;
	}
	
	public function getQuery(){
		return $this->query;
	}

	public function __toString(){
		$query = SQLUtils::makeQueryReadable($this->getQuery());
		return get_class($this).':'.$this->getMessage()."\r\n---QUERY---\r\n".$query."\r\n".$this->getTraceAsString();
	}
}
?>
