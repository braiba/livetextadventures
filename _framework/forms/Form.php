<?php

abstract class Form {
	
	protected $action = 'POST';
	protected $title = null;		
	protected $fields = array();
			
	public function __construct($fields=array(),$title=null){
		$this->title = $title;
	}
	
	public function getTitle($title){return $this->title;}
	public function setTitle($title){$this->title = $title;}
		
	public function addField($field,$label=null){
		if (!isset($label)) $label = ucfirst($field->getName());
		$this->fields[$label] = $field;
	}	
	
	public function getValue($name){		
		if (isset($_POST[$name])) return $_POST[$name];
		return null;
	}	
	
	public function getOptions($name){
		$options = array();
		$property = DBObject::getClassFieldByReference($this->getTable(),$name);
		if ($property instanceof Value){
			if ($col->getDataType()=='enum'){
				if (preg_match('/^enum\((.+)\)$/',$col->getColumnType(),$match)){
					$vals = explode(',',$match[1]);
					foreach ($vals as $val){
						$option = trim($val,' \'');
						$options[$option] = $option;
					}
				} else Messages::msg('An error occured parsing the datatype.',Messages::M_ERROR);
			} else Messages::msg("$name is not a valid {$this->getTable()}.",Messages::M_ERROR);
		}	else {
			$objects = DBObject::findAll($property->getLinkedTable());
			foreach ($objects as $object)				
				$options[$object->id] = $object;				
		}	
		return $options;
	}
	
	public function getHTML(){
		$html = "<form action=\"{$this->POST}\">";
		if (isset($this->title)) $html.= "<h2>{$this->title}</h2>";
		foreach ($this->fields as $field)
			$html .= $field->getHTML($this);
		$html.= '</form>';
		return $html;	
	}
	
}

?>