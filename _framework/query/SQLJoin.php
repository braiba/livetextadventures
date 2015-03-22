<?php

	/**
	 * Description of SQLJoin
	 *
	 * @author thomas
	 */
	abstract class SQLJoin {
		
		const JOIN_RIGHT = 0;		
		const JOIN_LEFT = 1;
		
		protected $table_ref;
		protected $join;
		
		public function __construct(SQLTableReference $table_ref,$join){
			$this->table_ref = $table_ref;
			$this->join = $join;
		}		
		
		public function getJoinString(){
			switch ($this->join){
				case self::JOIN_RIGHT: return 'JOIN';
				case self::JOIN_LEFT: return 'LEFT JOIN';
			}
			return 'JOIN';
		}
	}

?>
