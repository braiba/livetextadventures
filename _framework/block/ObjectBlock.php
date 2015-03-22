<?php

abstract class ObjectBlock extends Block {
	
	protected $object;
	
	public function __construct($object){
		$this->object = $object;
	}
	
	
	
}
?>