<?php

class Cookie {

	public static function setValue($name,$value,$lifespan=null){
		return setcookie($name,$value,(is_null($lifespan)?0:time()+$lifespan));
	}

	public static function getValue($name,$default=null){
		if (isset($_COOKIE[$name])){
			return $_COOKIE[$name];
		}
		return $default;
	}

	public static function unsetValue($name){
		return setcookie($name,null,time()-24*60*60);
	}

	public static function valueExists($name){
		return isset($_COOKIE[$name]);
	}

}

?>