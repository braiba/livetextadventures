<?php

class ObjectDisplayBlock extends ObjectBlock {
	
	public function start(){ $this->buffer = ''; }
	public function text($text){
		$this->buffer.= HTMLUtils::nl2p($text);
	}
	public function display($name,$reference=null,$opts=array()){
		if (is_null($reference)){
			$reference = $name;
		}
		$value = $this->object->getFieldByReference($reference);
	
		if (isset($opts['processAsHTML']) && $opts['processAsHTML']){
			$value = Utils::processAsHTML($value);
		} else if (isset($opts['multiline']) && $opts['multiline']){
			$value = TextUtils::nl2p($value);
		}
		$this->displayValue($name,$value,$opts);
	}
	public function displayValue($name,$value,$opts=array()){
		$this->buffer.= '<p><b>'.$name.':</b>'.(isset($opts['multiline']) && $opts['multiline']?'</p><p>':' ').$value.'</p>';		
	}
	public function end($return=false){	
		if ($return){ return $this->buffer; } else { echo $this->buffer; }
	}
	
}
?>