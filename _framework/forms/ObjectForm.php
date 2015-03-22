<?php

abstract class ObjectForm extends Form {
	
	public function getOptions($name){
		$options = parent::getOptions($name);
		$property = DBObject::getClassFieldByReference($this->getTable(),$name);
		if ($property instanceof Relationship && $property->getLinkedTable()==$this->getTable())
			unset($options[$this->object->id]);		
		return $options;
	}
	
	public function getValue($name){
		if (isset($_POST[$name])) return $_POST[$name];
		if (isset($this->object)) return $this->object->$name;
		return null;
	}

}

?>