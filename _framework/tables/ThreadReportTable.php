<?php

/**
 * Like SQLQueryTable, except that ThreadReportTable is used for when the SQL queries are being
 *   run by a report thread, instead of by the table itself.
 *
 * @author tom
 */
class ThreadReportTable extends AbstractTablePlus {
	
	protected $error = false;
	protected $page_count = 1;
	protected $page_size;
	protected $total_size;
	
	public function __construct($thread_response,$columns,$id='thread_report'){
		$this->id = $id;
		if ($thread_response['result']){
			switch ($thread_response['count']){
				case 0:
					break;
				case 1:
					$this->data[] = SQLQueryResultRow::wrap($thread_response['data']);
					break;
				default:
					foreach ($thread_response['data'] as $row){
						$this->data[] = SQLQueryResultRow::wrap($row);
					}
			}
			if (isset($thread_response['page_count'])){
				$this->page_count = $thread_response['page_count'];
			}
			if (isset($thread_response['page_size'])){
				$this->page_size = $thread_response['page_size'];
			}
			if (isset($thread_response['total_size'])){
				$this->total_size = $thread_response['total_size'];
			}
		} else {
			$this->error = $thread_response['error'];
		}
		$this->columns = $columns;
	}

	public function getPage(){return ( isset($_GET[$this->id.'_page']) ? $_GET[$this->id.'_page'] : 0);}
	public function getPageSize(){return $this->page_size;}
	public function getPageCount(){return $this->page_count;}
	public function fullDataRowCount(){return $this->total_size;}
	
	public function getTableClass(){return parent::getTableClass().' threadreporttable';}
	
	public function getEmptyMessage(){return ( $this->error ? $this->error : parent::getEmptyMessage() );}
	
}

?>