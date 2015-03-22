<?php
/**
 *
 * @author Thomas
 */
abstract class SQLException extends Exception {

	protected $query;
	
	public function __construct($message, $code=0, $previous=null){
		parent::__construct($message, $code, $previous);
	}

	public function getQuery(){
		return $this->query;
	}

	public function __toString(){
		$query = SQLUtils::makeQueryReadable($this->getQuery());
		return get_class($this).":\r\n---QUERY---\r\n{$query}\r\n---\r\n".$this->getTraceAsString();
	}
}
?>
