<?php

/**
 * FRAMEWORK: Currently if you wrap an array that contains an array, the inner array will be passed by value, not by reference
 * when its value is retrieved. This means that you can't do, for example, 
 *  $a = ArrayWrapper($array);
 *  $a['Hello']['World'] = true;
 * Unfortunately ArrayAccess->offsetExists is not defined as passing by reference, so we may need to work around this by wrapping the inner arrays.
 * Currently the best way around this is: * 
 *  $b = $a['Hello'];
 *  $b['World'] = true;
 *  $a['Hello'] = $b;
 *
 * @author Thomas
 */
abstract class ArrayWrapper implements ArrayAccess, Iterator {

  protected $index;
  protected $array;
  protected $position = 0;

  /**
   * SQLQueryResultRow constructor.
   * Child classes should implement a static wrap method that extracts the array and wraps that instead if it is passes an instance of ArrayWrapper
   * @param array $array
   * @return SQLQueryResultRow
   */
  protected function __construct($array){
    $this->array = $array;
    $this->index = $this->buildIndex($array);
  }

	public static function unwrap($el){
		if (is_array($el)){
			return $el;
		}
		if ($el instanceof ArrayWrapper){
			return $el->getWrappedArray();
		}
		if ($el instanceof Iterator){
			$array = array();
			foreach ($el as $key=>$value){
				$array[$key] = $value;
			}
			return $array;
		}
		Messages::msg('Failed to unwrap element: Not a type of array.',Messages::M_CODE_ERROR);
		return array();
	}

  public abstract function makeQuickName($name);
  
  protected function buildIndex($array){
    $index = array();
    foreach ($array as $key=>$value){
      $index[$this->makeQuickName($key)] = $key;
    }
    return $index;
  }

  /*
   * ArrayAccess interface
   */
  public function offsetExists($offset){
    $quickname = $this->makeQuickName($offset);
    return isset($this->index[$quickname]);
  }
  public function offsetGet($offset){
    $quickname = $this->makeQuickName($offset);
    return ( array_key_exists($quickname,$this->index) ? $this->array[$this->index[$quickname]] : null );
  }
  public function offsetSet($offset,$value){
    $quickname = $this->makeQuickName($offset);
    // If this quickname already maps to another value in the array, remove that value
    if (array_key_exists($quickname,$this->index) && $this->index[$quickname]!=$offset){
      unset($this->array[$this->index[$quickname]]);
    }
    $this->index[$quickname] = $offset;
    $this->array[$offset] = $value;
  }
  public function offsetUnset($offset){
    $quickname = $this->makeQuickName($offset);
    unset($this->index[$quickname]);
    unset($this->array[$offset]);
  }

  /*
   * Iterator interface
   */
  function rewind(){
    reset($this->array);
  }
  function current(){
    return current($this->array);
  }
  function key(){
    return key($this->array);
  }
  function next(){
    next($this->array);
  }
  function valid(){
    return (key($this->array)!==null);
  }
  
  /*
   * Magic methods
   */
  
  function __get($name){
    return $this->offsetGet($name);
  }
  function __set($name,$value){
    return $this->offsetSet($name,$value);
  }
  function __isset($name){
    return $this->offsetExists($name);
  }
  function __unset($name){
    return $this->offsetUnset($name);
  }
  
  /*
   * array_* methods 
   */
  
  function key_exists($key){return $this->offsetExists($key);}
  function keys($search_value=null,$strict=false){return array_keys($this->array);}
  function values(){return array_values($this->array);}
  function count_values(){return array_count_values($this->array);}
  function rand($num_req = 1){return array_rand($this->array,$num_req);}
  
  /*
   * Misc functions 
   */
  function lookup($name){
    $quickname = $this->makeQuickName($offset);
    return $this->index[$quickname];
  }
  function getWrappedArray(){
    return $this->array;
  }
}


?>