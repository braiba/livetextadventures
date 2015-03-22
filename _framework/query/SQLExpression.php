<?php

	/**
	 * Description of SQLExpression
	 *
	 * @author thomas
	 */
	class SQLExpression {
		
		protected $sql;
		
		public function __construct($sql){
			$this->sql = $sql;
		}
		
		public function __toString(){
			return $this->sql;
		}
		
	}

?>
