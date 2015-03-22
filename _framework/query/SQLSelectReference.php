<?php

	/**
	 * Description of SQLSelectReference
	 *
	 * @author thomas
	 */
	class SQLSelectReference {
		
		/** @var SQLExpression */
		protected $expr;
		/** @var string */
		protected $alias;
		
		public function __construct(SQLExpression $expr, $alias=null){
			$this->expr = $expr;
			$this->alias = $alias;
		}
		
		public function __toString(){
			return $this->expr.($this->alias?' AS `'.$this->alias.'`':'');
		}
		
	}

?>
