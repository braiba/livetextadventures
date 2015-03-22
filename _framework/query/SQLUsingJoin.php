<?php

	/**
	 * Description of SQLRightJoin
	 *
	 * @author thomas
	 */
	class SQLUsingJoin extends SQLJoin {
		
		protected $columns = array();
		
		public function __construct(SQLTableReference $table_ref,$join,$columns=array()){
			parent::__construct($table_ref,$join);
			$this->columns = $columns;
		}
		
		public function __toString(){
			return $this->getJoinString().' '.$this->table_ref.(!empty($this->columns)?' USING ('.implode(',',$this->columns).')':'');
		}
		
	}

?>
