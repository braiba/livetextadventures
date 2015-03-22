<?php

class DisplayMessageHandler extends MessageHandler {

	protected $name;

	public static $all = array();

	public function __construct($filter,$name){
		parent::__construct($filter);
		$this->name = $name;
		self::$all[] = $this;
	}
	
	public function getName(){
		return $this->name;
	}

	public function msg($msg,$msg_level){
		if ($msg_level==Messages::M_WARNING) {
			$msg = '<i>WARNING:</i> '.$msg;
		}
		Session::addValue(array('messages',$this->name), $msg);
	}

	public function clear(){
		Session::setValue(array('messages',$this->name), array());
	}
	public function isEmpty(){
		$msgs = Session::getValue(array('messages',$this->name));
		return empty($msgs);
	}

	public function display($excludeWrapper=false){
		if ($this->isEmpty()) return;
		if (!$excludeWrapper) echo '<div class="messages">';
		$msgs = Session::getValue(array('messages',$this->name));
		echo HTMLUtils::ul($msgs,array('class'=>$this->name));
		if (!$excludeWrapper) echo '</div>';
		$this->clear();
	}

	public static function displayAll(){
		$messages = false;
		foreach (self::$all as $handler){
			if (!$handler->isEmpty()){
				$messages = true;
				break;
			}
		}
		if (!$messages && !Messages::forceDisplay()) return;
		echo '<div class="messages">';
		foreach (self::$all as $handler){
			$handler->display(true);
		}
		echo '</div>';
	}

}

?>