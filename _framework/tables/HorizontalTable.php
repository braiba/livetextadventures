<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author Thomas
 */
class HorizontalTable extends AbstractTable {

	protected $data = array();

	public function getTableClass(){return 'horizontal '.parent::getTableClass();}
	
	public function getHeadSize(){return 1;}
	public function getFootSize(){return 0;}
	public function hasRowLabels(){return false;}
	public function rowCount(){return 2;}
	public function colCount(){return sizeof($this->data);}
	public function getCell($x,$y){return $this->data[$x][$y?'value':'label'];}

	public function addColumn($label,$value){
		$this->data[] = array('label'=>$label,'value'=>$value);
	}

}
?>
