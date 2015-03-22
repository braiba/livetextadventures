<?php

class AJAXMessageHandler extends MessageHandler {

	public static $all = array();
	
	protected $name;
	protected $messages = array();

	public function __construct($filter,$name){
		parent::__construct($filter);
		$this->name = $name;
		self::$all[] = $this;
	}

	public function msg($msg,$msg_level){
		if ($msg_level==Messages::M_WARNING) {
			$msg = '<i>WARNING:</i> '.$msg;
		}
		$this->messages[] = $msg;
	}

	public function clear(){
		$this->messages = array();
	}
	public function isEmpty(){
		return empty($this->messages);
	}
	
	public function getArray(){
		if ($this->isEmpty()) return;
		$data = $this->messages;
		$this->clear();
		return $data;
	}
	
	public static function getAJAXData(){
		$data = array();
		foreach (self::$all as $handler){
			if ($handler_data = $handler->getArray()){
				$data[$handler->name] = $handler_data;
			}
		}
		return $data;
	}

}

?>