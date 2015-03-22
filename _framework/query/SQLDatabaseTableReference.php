<?php

	/**
	 * Description of SQLDatabaseTableReference
	 *
	 * @author thomas
	 */
	class SQLDatabaseTableReference extends SQLTableReference {
		
		protected $table = null;
		
		public function __construct(SQLTable $table, $alias=null){
			parent::__construct($alias);
			$this->table = $table;
		}
		
		public function __toString(){
			return $this->table->getFullName().' AS `'.$this->alias.'`';
		}
		
	}

?>
