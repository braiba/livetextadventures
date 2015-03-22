<?php

	class ArrayUtils {

		public static function isAssoc($array) {
			if (!is_array($array)) {
				return false;
			}
			foreach (array_keys($array) as $numkey => $key) {
				if ($numkey !== $key) {
					return true;
				}
			}
			return false;
		}

		public static function ensureAssoc($array) {
			if (!is_array($array)) {
				return array($array => $array);
			}
			else if (!self::isAssoc($array)) {
				return array_combine($array, $array);
			}
			return $array;
		}
		
		public static function getFirstKey($array){
			if (empty($array)){
				return null;
			}
			$keys = array_keys($array);
			return $keys[0];
		}
		
		public static function getFirst($array){
			if (empty($array)){
				return null;
			}
			$keys = array_keys($array);
			return $array[$keys[0]];
		}

	}

?>