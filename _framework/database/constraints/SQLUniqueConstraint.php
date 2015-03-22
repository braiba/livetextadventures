<?php

	class SQLUniqueConstraint extends SQLConstraint {

		public function validate($sqlrecord) {
			$table = SQLTable::get($this->tableName, $this->dbName);
			$sql = "SELECT 1 FROM {$table->getFullName()} WHERE NOT (" . $this->buildQueryWhere($sqlrecord) . ')';
			if ($table->hasPrimaryKey()) {
				$sql.= ' AND NOT(' . $table->getPrimaryKey()->buildQueryWhere($sqlrecord) . ')';
			}
			return SQL::query($sql)->isEmpty();
		}

		public function isValidQuery($query) {
			$intersect = array_intersect(array_keys($query), $this->columns);
			return (sizeof($intersect) == sizeof($this->columns));
		}

		public function buildQueryWhere($query,$alias=null) {
			if (!empty($alias)){
				$alias = '`'.$alias.'`';
			}
			$parts = array();
			$this->table = SQLTable::getByTable($this->table_name, $this->db_name);
			foreach ($this->columns as $col) {
				if ($query[$col] !== null) {
					$parts[] = "$alias.`$col` = " . $this->table->getColumn($col)->valueAsSQL($query[$col]);
				}
				else {
					$parts[] = "$alias.`$col` IS NULL";
				}
			}
			return implode(' AND ', $parts);
		}

	}

?>