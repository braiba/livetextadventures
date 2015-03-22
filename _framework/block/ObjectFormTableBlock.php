<?php

class ObjectFormTableBlock extends ObjectFormBlock {
	
	public function start(){ 
		$this->buffer = HTMLUtils::openForm(); 
		$this->buffer.= '<table cellspacing="0" cellpadding="0" class="formtable"><tbody>';
	}
	public function text($text){
		$this->buffer.= '<tr><th class="empty">&nbsp;</th><td>'.$text.'</td></tr>'; 
	}
	public function displayValue($name,$value,$opts=array()){
		$this->buffer.= '<tr><th>'.$name.':</th><td>'.$value.'</td></tr>';		
	}
	public function end($return=false){	 
		$this->buffer.= '<tr class="submit"><th class="empty">&nbsp;</th><td>'.HTMLUtils::submitInput('submit','Submit').'</td></tr>'; 
		$this->buffer.= '</tbody></table>'; 
		$this->buffer.= HTMLUtils::closeForm();
		if ($return){ return $this->buffer; } else { echo $this->buffer; } 
	}
	
}
?>