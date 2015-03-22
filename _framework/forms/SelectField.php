<?php

class SelectField extends FormField {

	protected $required = false;
	
	public function __construct($name,$required=false,$label=null){
		parent::__construct($name,$label);
		$this->required = $required;
	}
	
	public function isRequired(){return $this->required;}
	public function setRequired($required){$this->required = $required;}
	
	
	protected function getValue($form){
		$value = parent::getValue($form);
		if ($value instanceof Object) return $value->id;
		return $value;
	}
	
	public function getHTML($form){
		$html = '<select name="'.$this->name.'">';
		$selected = $this->getValue($form);
		if (!$this->required)
			$html.= '<option></option>';
		$options = $form->getOptions($this->name); 
		foreach ($options as $value=>$text)
			$html.= "<option value=\"$value\"".($value==$selected?' selected="selected"':'').">$text</option>";
		$html.= '</select>';
		return "<p><b>{$this->label}:</b> $html</p>";
	}

}

?>