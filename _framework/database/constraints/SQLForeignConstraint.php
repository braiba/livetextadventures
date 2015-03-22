<?php

	class SQLForeignConstraint extends SQLConstraint {

		protected $foreign_columns = array();

		protected function loadColumns($info) {
			$this->columns = explode(',', $info['COLUMN_NAMES']);
			$ref_schemas = explode(',', $info['REFERENCED_TABLE_SCHEMAS']);
			$ref_tables = explode(',', $info['REFERENCED_TABLE_NAMES']);
			$ref_columns = explode(',', $info['REFERENCED_COLUMN_NAMES']);
			foreach ($this->columns as $i => $column) {
				$this->foreign_columns[] = array(
					'db_name' => $ref_schemas[$i],
					'table_name' => $ref_tables[$i],
					'column_name' => $ref_columns[$i],
				);
			}
		}

		public function getForeignColumns(){
			return $this->foreign_columns;
		}
		
		public function validate($sqlrecord) {
			// FRAMEWORK: SQLForeignConstraint::validate()
		}

	}

?>