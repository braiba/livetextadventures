<?php

class Random {
	
	private function __construct(){}
	
	private static function nonassocArrayElement($array){
		return $array[rand(0,sizeof($array)-1)];
	}

	public static function arrayKey($array){
		return self::nonassocArrayElement(array_keys($array));		
	}
	public static function arrayKeys($arrays){
		$values = array();
		foreach ($arrays as $array) {
			$values[] = self::arrayKey($array);		
		}
		return $values;
	}
	public static function arrayValue($array){
		return self::nonassocArrayElement(array_values($array));		
	}
	public static function arrayValues($arrays){
		$values = array();
		foreach ($arrays as $array) {
			$values[] = self::arrayValue($array);
		}
		return $values;
	}
	
}
?>