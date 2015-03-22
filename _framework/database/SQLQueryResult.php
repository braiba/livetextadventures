<?php
class SQLQueryResult implements Iterator, ArrayAccess {

	protected $res;
	protected $affected_rows;
	protected $curr = null;
	protected $position = 0;
	protected $freed;

	public function __construct($res,$affected_rows=0){
		$this->res = $res;
		$this->affected_rows = $affected_rows;
	}
	public function __destruct(){
		$this->free();
	}
	
	public function success(){return true;}
	
	public function affectedRows(){
		return $this->affected_rows;
	}

	/*
	 *	- Iterator interface - 
	 */
	
	public function rewind() {
		if ($this->res->num_rows==0) return;
		$this->res->data_seek(0);
		$this->curr = $this->res->fetch_assoc();
		$this->position = 0;
	}

	/**
	 *
	 * @return SQLQueryResultRow
	 */
	public function current() {
		return (isset($this->curr)?SQLQueryResultRow::wrap($this->curr):null);
	}

	/**
	 *
	 * @return int
	 */
	public function key() {
		return $this->position;
	}

	/**
	 * 
	 */
	public function next() {
		$this->curr = $this->res->fetch_assoc();
		$this->position++;
	}

	/**
	 *
	 * @return boolean
	 */
	public function valid() {
		return ($this->curr!==null);
	}

	/**
	 *
	 * @return int
	 */
	public function size(){
		return $this->res->num_rows;
	}
	
	/*
	 * ArrayAccess
	 */

	public function offsetExists($offset){
		return (is_numeric($offset) && $offset >=0 && $offset < $this->res->num_rows);
	}
	public function offsetGet($offset){
		if (!$this->offsetExists($offset)) return null;
		if (!$this->res->data_seek($offset)) return null;
		return SQLQueryResultRow::wrap($this->res->fetch_assoc());
	}
	public function offsetSet($offset,$value){
		return false;
	}
	public function offsetUnset($offset){
		return false;
	}
	
	 
	/*
	 * Other methods
	 */
	/**
	 * Get the first row of the result. <br />
	 * <b>IMPORTANT:</b> Unless you explicitly *need* the query to return more than one result, always use
	 *   {@link getOnly()} instead, which does the same thing, but throws an SQLQueryException and a 
	 *   M_BACKGROUND_ERROR message if there is more than one row.
	 * @return SQLQueryResultRow
	 */
	public function getFirst(){
		$this->rewind();
		if ($this->valid()) {
			return $this->current();
		}
		return null;
	}
	
	/**
	 * Get the row from a single-row result. If there is more than one row, an SQLQueryException will be thrown. If the result is empty,
	 *   null will be returned.
	 * @return SQLQueryResultRow
	 * @throws SQLQueryException
	 */
	public function getOnly() {
		if ($this->size() > 1){
			$msg = 'Single result expected, but '.$this->size().' results found.';
			Messages::msg($msg,Messages::M_BACKGROUND_ERROR);
			throw new SQLQueryException("<Query Unknown>",$msg);
		}
		$this->rewind();
		if ($this->valid()) {
			return $this->current();
		}
		return null;
	}

	/**
	 *
	 * @return boolean
	 */
	public function isEmpty(){
		return ($this->res->num_rows==0);
	}
	
	public function free(){
		if ($this->freed){
			return;
		}
		$this->res->free();
		$this->freed = true;
	}

	/**
	 *
	 * @return array
	 */
	public function getColumns(){
		return $this->res->fetch_fields();
	}

	public function getColumnNames(){
		if (is_null($this->res)) return array();
		return array_map(
			function($item){return $item->name;},
			$this->res->fetch_fields()
		);
	}
	
	/*
	 *
	 */
	public function toArray(){
		$array = array();
		foreach ($this as $row){
			$row_array = array();
			foreach ($row as $col=>$val){
				$row_array[$col] = $val;
			}
			$array[] = $row_array;
		}
		return $array;
	}
}
?>