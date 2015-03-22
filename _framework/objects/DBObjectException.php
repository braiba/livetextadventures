<?php
/**
 *
 * @author Thomas
 */
class DBObjectException extends Exception {

	protected $class;
	
	public function __construct($class, $message, $code=0, $previous=null){
		parent::__construct($message, $code, $previous);
		$this->class = $class;
	}
	
	public function getClass(){
		return $this->class;
	}

	public function __toString(){
		return get_class($this).'Class : '.$this->class."\r\n".'Message: '.$this->getMessage()."\r\n".$this->getTraceAsString();
	}
}
?>
