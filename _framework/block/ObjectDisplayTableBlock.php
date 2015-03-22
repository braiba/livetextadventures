<?php

class ObjectDisplayTableBlock extends ObjectDisplayBlock {
	
	public function start(){ 
		$this->buffer.= '<table cellspacing="0" cellpadding="0"><tbody>';
	}
	public function text($text){
		$this->buffer.= '<tr class="text"><td colspan="2">'.$text.'</td></tr>'; 
	}
	public function displayValue($name,$value,$opts=array()){
		$this->buffer.= '<tr><td class="label">'.$name.':</td><td class="value">'.$value.'</td></tr>';		
	}
	public function end($return=false){		
		$this->buffer.= '</tbody></table>'; 
		if ($return){ return $this->buffer; } else { echo $this->buffer; }
	}
	
}

?>