<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author Thomas
 */
class CrossReferenceTablePlus extends AbstractTablePlus {

	protected $title = null;
	protected $labels = array();
	protected $foot_size = 0;

	/**
	 *
	 * @param array $columns
	 * @param array $data
	 * @param string $id
	 */
	public function __construct($columns,$data=array(),$id=null){
		$this->id = $id;
		$this->labels = array_keys($data);
		$this->data = array_values($data);
		$this->columns = $columns;
	}

	public function isSortable(){return false;}

	public function getPage(){return 0;}
	public function getPageSize(){return sizeof($this->data);}
	public function getFootSize(){return $this->foot_size;}
	public function setFootSize($foot_size){$this->foot_size = $foot_size;}
	public function hasRowLabels(){return true;}
	public function fullDataRowCount(){return sizeof($this->data);}
	public function rowCount(){return sizeof($this->data)+$this->getHeadSize();}
	public function colCount(){return sizeof($this->columns)+1;}

	public function getTableClass(){return parent::getTableClass().' crossreferencetableplus';}

	protected function cellOpen($x, $y, $attrs=array()) {
		if ($x == 0 && $y == 0) {			
			HTMLUtils::addClass($attrs, 'title_cell');
		}
		return parent::cellOpen($x, $y, $attrs);
	}

	public function getCell($x,$y){
		if ($x==0){
			if ($y<$this->getHeadSize()){
				return (is_null($this->title)?'&nbsp;':$this->title);
			}
			return $this->labels[$y-$this->getHeadSize()];
		}
		return parent::getCell($x-1,$y);
	}

	public function getTitle(){return $this->title;}
	public function setTitle($title){$this->title = $title;}

	public function addRow($label,$row){
		$this->labels[] = $label;
		$this->data[] = SQLQueryResultRow::wrap($row);
	}

}
?>
