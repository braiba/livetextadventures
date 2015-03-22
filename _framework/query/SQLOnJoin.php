<?php

	/**
	 * Description of SQLLeftJoin
	 *
	 * @author thomas
	 */
	class SQLOnJoin extends SQLJoin {
		
		protected $condition = null;
		
		public function __construct(SQLTableReference $table_ref,$join,$condition=null){
			parent::__construct($table_ref,$join);
			$this->condition = $condition;
		}
		
		public function __toString(){
			return $this->getJoinString().' '.$this->table_ref.(isset($this->condition)?' ON '.$this->condition:'');
		}
		
	}

?>
