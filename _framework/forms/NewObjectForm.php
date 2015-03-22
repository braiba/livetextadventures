<?php

class NewObjectForm extends ObjectForm {

	protected $object;
	
	public function __construct($object,$title=null){
		parent::__construct($object->getTableName(),$title);
		$this->object = $object;
	}
	
	public function getValue($name){
		if (isset($_POST[$name])) return $_POST[$name];
		if (isset($this->object)) return $this->object->$name;
		return null;
	}
	
}

?>