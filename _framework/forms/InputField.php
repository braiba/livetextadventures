<?php

class InputField extends FormField {
		
	protected $type;	
		
	public function __construct($name,$type,$label=null){
		parent::__construct($name,$label);
		$this->type = $type;
	}
	
	public function getType(){return $this-type;}
	
	public function getHTML($form){
		return "<p><b>{$this->label}:</b> <input type=\"{$this->type}\" name=\"{$this->name}\" id=\"{$this->name}\" class=\"{$this->type}\" value=\"".$this->getValue($form)."\" /></p>";
	}

}

?>