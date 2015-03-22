<?php

abstract class Block {

	protected $buffer;
	public function addToBuffer($str){$this->buffer.=$str;}
	
	public abstract function start();
	public abstract function text($text);
	public abstract function display($name,$reference=null,$opts=array());
	public abstract function displayValue($name,$value,$opts=array());
	public abstract function end($return=false);
	
}
?>