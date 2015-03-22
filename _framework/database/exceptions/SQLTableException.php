<?php
/**
 *
 * @author Thomas
 */
class SQLTableException extends SQLException {

	protected $table;
	
	public function __construct(SQLTable $table, $message, $code=0, $previous=null){
		parent::__construct($message, $code, $previous);
		$this->table = $table;
	}
	
	public function getTable(){
		return $this->table;
	}

	public function __toString(){
		$query = SQLUtils::makeQueryReadable($this->getQuery());
		return get_class($this)."\r\n".'Table : '.$this->table->getFullName()."\r\n".'Message : '.$this->getMessage()."\r\n".$this->getTraceAsString();
	}
}
?>
