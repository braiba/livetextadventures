<?php
	
class SQLQuery {

	const RESULT_UNKNOWN = 0;
	const RESULT_SMALL = 1;
	const RESULT_BIG = 2;
	
	const CACHE_ALLOW = 0;
	const CACHE_FORCE = 1;
	const CACHE_PREVENT = 2;
	
	const LOCK_NONE = 0;
	const LOCK_FOR_UPDATE = 1;
	const LOCK_SHARE_MODE = 2;
	
	/** @var bool specifies whether to remove duplicate rows from the result set */
	protected $distinct = false;	
	/** @var bool gives the SELECT higher priority than a statement that updates a table. Use with care! */
	protected $high_priority = false;
	/** @var bool forces the optimizer to join the tables in the order in which they are listed */
	protected $straight_join = false;
	/** @var int a RESULT_* const used to tell the optimiser if it should expect the result set to be particularly big or small */
	protected $result_size = self::RESULT_UNKNOWN;
	/** @var bool forces the result to be put into a temporary table (top-level only).*/
	protected $result_buffer = false;
	/** @var bool tells MySQL to calculate how many rows there would be in the result set without the LIMIT clause (top-level only). */
	protected $calc_rows = false;
	/** @var int specifies how to handle caching. */
	protected $cache = self::CACHE_ALLOW;
	/** @var bool specifies the rollup option for grouping (see {@link http://dev.mysql.com/doc/refman/5.5/en/group-by-modifiers.html}). */
	protected $group_rollup = false;
	/** @var int specifies the options for locking examined rows (see {@link http://dev.mysql.com/doc/refman/5.5/en/select.html})  */
	protected $lock_mode = self::LOCK_NONE;
	
	/** @var SQLSelectReference[] */
	protected $select_exprs = array();	
	/** @var SQLTableReference */
	protected $base_table = null;
	/** @var SQLJoin[] */
	protected $joins = array();
	/** @var SQLCondition[] */
	protected $where_conditions = array();
	/** @var SQLAggregateExpression[] */
	protected $group_exprs = array();
	/** @var SQLCondition[] */
	protected $having_conditions = array();
	/** @var SQLAggregateExpression[] */
	protected $order_by_exprs = array();	
	/** @var int */
	protected $limit_count = null;
	/** @var int */
	protected $limit_offset = null;
	// FRAMEWORK: PROCEDURE
	// FRAMEWORK: INTO OUTFILE
	
	protected $tablei = 0;
	
	protected $userAliases = array();
			
	
	
	public function getBaseTable(){
		return $this->base_table;
	}
	
	public function hasOrderBy(){
		return !empty($this->order_by_exprs);
	}
	
	public function isGrouped(){
		return !empty($this->group_by_exprs);
	}
	
	public function clearSelect(){
		$this->select_exprs = array();
	}
	
	public function addSelect($select,$alias=null){
		$this->select_exprs[] = new SQLSelectReference(new SQLExpression($select),$alias);
	}
	
	public function setBaseTable($table,$alias){
		if (is_string($table)){$table = SQLTable::getByTable($table);}
		$this->base_table = new SQLDatabaseTableReference($table, $alias);
	}
	
	public function joinUsing($table,$alias,$columns){
		if (is_string($table)){$table = SQLTable::getByTable($table);}
		if (is_string($columns)){$columns = array($columns);}
		$this->joins[] = new SQLUsingJoin(new SQLDatabaseTableReference($table, $alias), SQLJoin::JOIN_RIGHT, $columns);
	}
	
	public function joinOn($table,$alias,$condition){	
		if (is_string($table)){$table = SQLTable::getByTable($table);}	
		if (is_string($condition)){$condition = new SQLCondition($condition);}
		$this->joins[] = new SQLOnJoin(new SQLDatabaseTableReference($table, $alias), SQLJoin::JOIN_RIGHT, $condition);
	}
	
	public function leftJoinUsing($table,$alias,$columns){
		if (is_string($table)){$table = SQLTable::getByTable($table);}
		$this->joins[] = new SQLUsingJoin(new SQLDatabaseTableReference($table, $alias), SQLJoin::JOIN_LEFT, $columns);
	}
	
	public function leftJoinOn($table,$alias,$condition){
		if (is_string($table)){$table = SQLTable::getByTable($table);}
		if (is_string($condition)){$condition = new SQLCondition($condition);}
		$this->joins[] = new SQLOnJoin(new SQLDatabaseTableReference($table, $alias), SQLJoin::JOIN_LEFT, $condition);		
	}
	
	public function joinQueryUsing(SQLQuery $query, $alias, $columns){
		if (is_string($columns)){$columns = array($columns);}
		$this->joins[] = new SQLUsingJoin(new SQLQueryTableReference($query, $alias), SQLJoin::JOIN_RIGHT, $columns);
	}
	
	public function joinQueryOn(SQLQuery $query, $alias, SQLCondition $condition){		
		if (is_string($condition)){$condition = new SQLCondition($condition);}
		$this->joins[] = new SQLOnJoin(new SQLQueryTableReference($query, $alias), SQLJoin::JOIN_RIGHT, $condition);
	}
	
	public function leftJoinQueryUsing(SQLQuery $query, $alias, $columns){
		$this->joins[] = new SQLUsingJoin(new SQLQueryTableReference($query, $alias), SQLJoin::JOIN_LEFT, $columns);
	}
	
	public function leftJoinQueryOn(SQLQuery $query, $alias, SQLCondition $condition){
		if (is_string($condition)){$condition = new SQLCondition($condition);}
		$this->joins[] = new SQLOnJoin(new SQLQueryTableReference($query, $alias), SQLJoin::JOIN_LEFT, $condition);		
	}
	
	public function addWhere($condition){
		if (is_string($condition)){$condition = new SQLCondition($condition);}
		$this->where_conditions[] = $condition;
	}
	public function clearWhere(){
		$this->where_conditions = array();
	}
	public function setWhere($condition){
		$this->clearWhere();
		$this->addWhere($condition);
	}
	
	public function addOrderBy($expression,$desc=false){	
		if (is_string($expression)){
			if (preg_match('/^(.+)\\s+(ASC|DESC)$/i',$expression,$match)){
				$expression = $match[1];
				$desc = strtoupper($match[2])=='DESC';
			}
			$expression = new SQLExpression($expression);		
		}
		if ($expression){
			$this->order_by_exprs[] = new SQLAggregateExpression($expression, $desc);
		}
	}
	public function clearOrderBy(){	
		$this->order_by_exprs = array();
	}
	public function setOrderBy($expression,$desc=false){	
		$this->clearOrderBy();
		$this->addOrderBy($expression, $desc);
	}
	
	public function addGroupBy($expression,$desc=false){		
		if (is_string($expression)){
			if (preg_match('/^(.+)\\s+(ASC|DESC)$/i',$expression,$match)){
				$expression = $match[1];
				$desc = strtoupper($match[2])=='DESC';
			}
			$expression = new SQLExpression($expression);		
		}
		if ($expression){
			$this->group_exprs[] = new SQLAggregateExpression($expression, $desc);
		}
	}
	public function clearGroupBy(){	
		$this->group_exprs = array();
	}
	public function setGroupBy($expression,$desc=false){	
		$this->clearGroupBy();
		$this->addGroupBy($expression, $desc);
	}
	
	public function addHaving($condition){
		if (is_string($condition)){$condition = new SQLCondition($condition);}
		$this->having_conditions[] = $condition;
	}
	public function clearHaving(){
		$this->having_conditions = array();
	}
	public function setHaving($condition){
		$this->clearHaving();
		$this->addHaving($condition);
	}
	
	public function setLimit($count,$offset=null){
		$this->limit_count = $count;
		$this->limit_offset = $offset;
	}
	
	public function applyPagination($page_size=10,$prefix=''){
		$page = isset($_GET[$prefix.'page']) ? $_GET[$prefix.'page'] : 0;
		
		$this->setLimit($page_size,$page*$page_size);
		$this->calc_rows = true;
	}
	
	public function __toString(){
		$sql = 'SELECT';
		if ($this->distinct){
			$sql.= ' DISTINCT';
		}
		if ($this->straight_join){
			$sql.= ' STRAIGHT JOIN';
		}
		switch ($this->result_size){
			case self::RESULT_UNKNOWN: break;
			case self::RESULT_SMALL  : $sql.= ' SQL_SMALL_RESULT'; break;
			case self::RESULT_BIG    : $sql.= ' SQL_BIG_RESULT'; break;			
		}
		switch ($this->cache){
			case self::CACHE_ALLOW  : break;
			case self::CACHE_FORCE  : $sql.= ' SQL_CACHE'; break;
			case self::CACHE_PREVENT: $sql.= ' SQL_NO_CACHE'; break;			
		}
		if ($this->result_buffer){
			$sql.= ' SQL_BUFFER_RESULT';
		}
		if ($this->calc_rows){
			$sql.= ' SQL_CALC_FOUND_ROWS';
		}
		if (!empty($this->select_exprs)){
			$sql.= ' '.implode(', ',$this->select_exprs);
		} else {
			$sql.= ' *';
		}
		if (!empty($this->base_table)){
			$sql.= ' FROM '.$this->base_table;
			if (!empty($this->joins)){
				$sql.= ' '.implode(' ',$this->joins);
			}
			if (!empty($this->where_conditions)){
				$sql.= ' WHERE ('.implode(') AND (',$this->where_conditions).')';
			}
			if (!empty($this->group_exprs)){
				$sql.= ' GROUP BY '.implode(', ',$this->group_exprs);
			}
			if (!empty($this->having_conditions)){
				$sql.= ' HAVING ('.implode(') AND (',$this->having_conditions).')';
			}
			if (!empty($this->order_by_exprs)){
				$sql.= ' ORDER BY '.implode(', ',$this->order_by_exprs);
			}
			if ($this->limit_count){
				$sql.= ' LIMIT '.(isset($this->limit_offset)?$this->limit_offset.', ':'').$this->limit_count;
			}			
			switch ($this->lock_mode){
				case self::LOCK_NONE      : break;
				case self::LOCK_FOR_UPDATE: $sql.= ' FOR UPDATE'; break;
				case self::LOCK_SHARE_MODE: $sql.= ' LOCK IN SHARE MODE'; break;			
			}
		}
		
		return $sql;
	}
	
		
}

?>