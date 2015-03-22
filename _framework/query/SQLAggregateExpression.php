<?php

	/**
	 * Description of SQLSelectReference
	 *
	 * @author thomas
	 */
	class SQLAggregateExpression {
		
		/** @var SQLExpression */
		protected $expr;
		/** @var bool */
		protected $desc;
		
		public function __construct(SQLExpression $expr, $desc=false){
			$this->expr = $expr;
			$this->desc = $desc;
		}
		
		public function __toString(){
			return $this->expr.' '.($this->desc?'DESC':'ASC');
		}
		
	}

?>
