<?php

/**
 * Description of ArrayTable
 *
 * @author Thomas
 */
class ArrayTablePlus extends AbstractTablePlus {
	
	/**
	 *
	 * @param array $res
	 * @param array $columns
	 * @param string $id
	 */
	public function __construct($data,$columns,$id=null){
		$this->id = $id;
		$this->data = array_values($data);
		$this->columns = $columns;
	}
	
	protected function sortLink($field,$desc=false){return '';}
	
	public function getPage(){return 0;}
	public function getPageSize(){return sizeof($this->data);}
	public function fullDataRowCount(){return sizeof($this->data);}
	public function rowCount(){return sizeof($this->data)+1;}
	
	public function getTableClass(){return parent::getTableClass().' arraytableplus';}
	
	public function addRow($row){
		$this->data[] = SQLQueryResultRow::wrap($row);
	}
	
}
?>
