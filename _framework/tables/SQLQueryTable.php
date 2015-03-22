<?php

class SQLQueryTable extends AbstractTablePlus {
	
	protected $columns;
	protected $sql;
	protected $sort_clause;
	protected $sort_dir;
	protected $footRows = array();
	protected $page_size = null;
	protected $sql_row_count = 0;
	protected $has_row_labels = false;

	/**
	 *
	 * @param string $id
	 * @param array $columns
	 * @param string $sql
	 * @param string $default_sort_clause
	 * @param string $default_sort_dir
	 * @param int $page_size
	 */
	public function __construct($id,$columns,$sql,$default_sort_clause='Name',$default_sort_dir='ASC',$page_size=null){
		$this->id = $id;
		$this->columns = $columns;
		$this->sql = $sql;
		$this->sort_clause = (isset($_GET["{$id}_sort_clause"])?$_GET["{$id}_sort_clause"]:$default_sort_clause);
		$this->sort_dir = (isset($_GET["{$id}_sort_dir"])?$_GET["{$id}_sort_dir"]:$default_sort_dir);
		$this->page_size = $page_size;
		$this->data = null;
	}

	public function hasRowLabels(){return $this->has_row_labels;}
	public function setHasRowLabels($has_row_labels){$this->has_row_labels = $has_row_labels;}

	public function setQuery($query){$this->query = $query; $this->data = null;}
	public function getQuery(){return $query;}
	public function setPageSize($page_size){$this->page_size = $page_size; $this->data = null;}
	public function getPageSize(){return $this->page_size;}
	public function setSortClause($sort_clause){$this->sort_clause = $sort_clause; $this->data = null;}
	public function getSortClause(){return $sort_clause;}
	public function setSortDir($sort_dir){$this->sort_dir = $sort_dir; $this->data = null;}
	public function getSortDir(){return $sort_dir;}

	public function rowCount(){
		$this->getSQLResult();
		return parent::rowCount();
	}

	public function getPage(){return ( isset($_GET[$this->id.'_page']) ? $_GET[$this->id.'_page'] : 0);}
	public function fullDataRowCount() {return $this->sql_row_count;}
	public function getTableClass(){return parent::getTableClass().' resulttable';}
		
	public function addRow($cells){
		$this->footRows[] = $cells;
	}
	
	public function getSQLResult(){
		if (is_null($this->data)){
			$sql = "$this->sql ORDER BY ".( $this->sort_dir=='ASC' ? $this->sort_clause : SQLUtils::invertOrderClause($this->sort_clause) );
			
			$this->page_count = 1;
			if (!is_null($this->page_size)){
				$page = $this->getPage();
				$start = $page*$this->page_size;
				$sql.= " LIMIT $start, $this->page_size";
			}
			
			if (strpos('SQL_CALC_FOUND_ROWS',$sql)===false){
				$sql = preg_replace('/^\\s*SELECT/', 'SELECT SQL_CALC_FOUND_ROWS', $sql);
			}
			
			$res = SQL::query($sql);
			if ($res->success()){
				$this->data = array();
				$this->sql_row_count = SQL::query("SELECT FOUND_ROWS() AS `total_rows`")->getOnly()->total_rows;
				foreach ($res as $row){
					$this->data[] = $row;
				}
			} 	
		}
		return $this->data;
	}
	
	public function display($attrs=array()){
		$this->getSQLResult();
		return parent::display($attrs);
	}
	
	public function saveAsCSV($filename){
		$this->setPageSize(null);
		$this->getSQLResult();
		parent::saveAsCSV($filename);
	}	

}

?>