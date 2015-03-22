<?php

	/**
	 * Description of SQLTableReference
	 *
	 * @author thomas
	 */
	abstract class SQLTableReference {
				
		/** @var string */
		protected $alias = null;
		
		public function __construct($alias=null){
			$this->alias = $alias;
		}
		
		public function getAlias(){
			return $this->alias;
		}
				
	}

?>
