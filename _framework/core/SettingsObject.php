<?php

abstract class SettingsObject {

	protected $properties = array();
	
	protected function defineProperty($name,$value=null){
		$this->properties[$name] = $value;
	}
	
	public function __get($name){
		if (strpos($name,'->')!==false){
			$names = array_reverse(explode('->',$name));	
		} else {
			$names = explode('_',$name);
		}		
		return $this->getPropertyValue($this->properties,$names);		
	}
	
	private function getPropertyValue(&$propertyarray,$namequeue){
		$name = array_pop($namequeue);
		$property = &$propertyarray[$name];
		if (empty($namequeue)){
			return $property;			
		} else {
			if (is_array($property)){
				return $this->getPropertyValue($property,$namequeue);
			} else {
				Messages::msg("'$name' does not have subproperties.",Messages::M_CODE_ERROR);
				return;				
			}
		}		
	}
		
	public function __set($name,$value){
		if (strpos($name,'->')!==false){
			$names = array_reverse(explode('->',$name));	
		} else {
			$names = explode('_',$name);
		}		
		$this->setPropertyValue($this->properties,$names,$value);
	}
	
	private function setPropertyValue(&$propertyarray,$namequeue,$value){
		$name = array_pop($namequeue);
		if (!isset($propertyarray[$name])){
			Messages::msg("The '$name' property does not exist.",Messages::M_CODE_ERROR);
			return;			
		}
		$property = &$propertyarray[$name];
		if (empty($namequeue)){
			if (is_array($property)){
				if (!is_array($value)){
					$value = array($value);
				}
				$i = 0;
				$valcount = sizeof($value);
				foreach ($property as &$subproperty){
					$subproperty = $value[$i++%$valcount];
				}
			} else {
				$property = $value;
			}
		} else {
			if (is_array($property)){
				$this->setPropertyValue($property,$namequeue,$value);
			} else {
				Messages::msg("'$name' does not have subproperties.",Messages::M_CODE_ERROR);
				return;
			}
		}
	}
	public function __toString(){
		return preg_replace("/\\s+/",' ',print_r($this->properties,true));
	}
	
}

?>