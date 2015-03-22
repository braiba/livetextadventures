<?php

abstract class FormField {

	protected $name;
	protected $label;

	public function __construct($name,$label=null){
		$this->name = $name;
		$this->label = (isset($label)?$label:ucfirst($name));
	}
	
	public function getName(){return $this->name;}
	public function getLabel(){return $this->label;}
	
	protected function getValue($form){
		return $form->getValue($this->name);
	}
	
	public abstract function getHTML($form);
		
}

?>