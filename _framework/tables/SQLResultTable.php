<?php

/**
 * Description of SQLResultTable
 *
 * @author Thomas
 */
class SQLResultTable extends AbstractTablePlus {

	protected $has_row_labels = false;
	
	/**
	 *
	 * @param SQLArrayResult $res
	 * @param array $columns
	 * @param string $id
	 */
	public function __construct($res,$columns,$id=null){
		$this->id = $id;
		$this->data = $res;
		$this->columns = $columns;
	}
	
	protected function sortLink($field,$desc=false){return '';}
	
	public function hasRowLabels(){return $this->has_row_labels;}
	public function setHasRowLabels($has_row_labels){$this->has_row_labels = $has_row_labels;}
	
	public function getPage(){return 0;}
	public function getPageSize(){return $this->data->size();}
	public function getPageCount(){return 0;}
	public function fullDataRowCount(){return $this->data->size();}
	public function rowCount(){return $this->data->size()+1;}
	
	public function getTableClass(){return parent::getTableClass().' sqlresulttable';}
	
}
?>
