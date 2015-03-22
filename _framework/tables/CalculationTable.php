<?php

class CalculationTable extends VerticalTable {

	protected $footSize = 1;

	public function getFootSize(){ return $this->footSize; }
	public function setFootSize($footSize){
		$this->footSize = $footSize;
	}
	
}
?>