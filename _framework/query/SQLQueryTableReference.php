<?php

	/**
	 * Description of SQLQueryTableReference
	 *
	 * @author thomas
	 */
	class SQLQueryTableReference extends SQLTableReference {
		
		protected $query = null;
		
		public function __construct(SQLQuery $query, $alias=null){
			parent::__construct($alias);
			$this->query = $query;
		}
		
		public function __toString(){
			return '('.$this->query.') AS `'.$this->alias.'`';
		}
		
	}

?>
