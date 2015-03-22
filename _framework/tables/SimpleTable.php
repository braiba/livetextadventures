<?php

class SimpleTable extends AbstractTable {

	protected $data = array();
	protected $head_size = 0;
	protected $foot_size = 0;
	protected $hasHeaderRow = false;
	protected $colCount = 0;
	
	public function getHeadSize(){return $this->head_size;}
	public function setHeadSize($head_size){$this->head_size = $head_size;}
	public function getFootSize(){return $this->foot_size;}
	public function setFootSize($foot_size){$this->foot_size = $foot_size;}
	public function hasRowLabels(){return false;}
	public function rowCount(){return sizeof($this->data);}
	public function colCount(){return $this->colCount;}
	public function getCell($x,$y){ return ( array_key_exists($x,$this->data[$y]) ? $this->data[$y][$x] : '');}
	public function addRow($row){ $this->data[] = $row; if (sizeof($row)>$this->colCount) $this->colCount = sizeof($row); }
	
	protected function cellOpen($x,$y){
		parent::cellOpen($x,$y); // sets $this->currCellType		
		$attrs = array();
		if ($x==sizeof($this->data[$y])-1){
			$attrs = array('colspan'=>$this->colCount-$x);
		} else if ($x>=sizeof($this->data[$y])){
			$this->currCellType = null;
			return '';
		}
		return HTMLUtils::openTag($this->currCellType,$attrs);
		
	}
}

?>