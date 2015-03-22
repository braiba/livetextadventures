<?php

class VerticalTable extends AbstractTable {

	protected $rowLabels;
	protected $values;

	public function __construct($rowLabels=array(),$values=array()){
		$this->rowLabels = $rowLabels;
		$this->values = $values;
	}

	public function getTableClass(){ return parent::getTableClass().' calculation'; }
	public function getHeadSize(){ return 0; }
	public function getFootSize(){ return 0; }
	public function hasRowLabels(){ return true; }
	public function rowCount(){ return sizeof($this->rowLabels); }
	public function colCount(){ return 2; }
	public function getCell($x,$y){
		if ($x==0){
			return $this->rowLabels[$y];
		}
		return $this->values[$y];
	}

	public function setFootSize($footSize){
		$this->footSize = $footSize;
	}
	
	public function addRow($label,$value){
		$this->rowLabels[] = $label;
		$this->values[] = $value;
		return true;
	}

}
?>