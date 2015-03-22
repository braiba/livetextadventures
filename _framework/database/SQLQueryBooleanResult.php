<?php

class SQLQueryBooleanResult extends SQLQueryResult {

  protected $success;
	
  public function __construct($success,$affected_rows){
    parent::__construct(null,$affected_rows);
    $this->success = $success;
  }

  public function success(){return $this->success;}
  
  /*
   * Iterator interface
   */
  
  public function rewind() { }
  public function current() { return null; }
  public function key() { return null; }
  public function next() { }
  public function valid() { return false; }
  public function size(){ return 0; }
  
  /*
   * ArrayAccess
   */

  public function offsetExists($offset){ return false; }
  public function offsetGet($offset){ return null; }
  public function offsetSet($offset,$value){ return false; }
  public function offsetUnset($offset){ return false; }
    
  /*
   * Other methods
   */
  public function getFirst(){ return null; }
  public function isEmpty(){ return true; }
  public function free(){}
  public function getColumns(){ return array(); }
}
?>