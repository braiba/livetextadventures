<?php

class SubmitField extends InputField{

	function __construct($label='Submit'){
		parent::__construct('submit','submit',$label);
	}
	
	public function getHTML($form){
		return "<p><input type=\"submit\" name=\"{$this->name}\" class=\"submit\" value=\"{$this->label}\" /></p>";
	}

}

?>