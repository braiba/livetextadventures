<?php

class ObjectFormBlock extends ObjectDisplayBlock {
	
	public function start(){ $this->buffer = HTMLUtils::openForm(); }
	public function display($name,$reference=null,$opts=array()){
		if (isset($opts['non-input']) && $opts['non-input']){
			parent::display($name,$reference,$opts);
			return; 
		} 
		if (is_null($reference)){
			$reference = $name;
		}
		$this->displayValue($name,$this->object->generateInputTag($reference,$opts),$opts);			
	}
	public function end($return=false){
		$this->buffer.= '<p class="submit">'.HTMLUtils::submitInput('submit','Submit').'</p>';	
		$this->buffer.= HTMLUtils::closeForm();
		if ($return){ return $this->buffer; } else { echo $this->buffer; } 
	}
	
}
?>