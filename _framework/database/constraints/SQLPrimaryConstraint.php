<?php

	class SQLPrimaryConstraint extends SQLUniqueConstraint {

		public function validate($sqlrecord) {
			if (!$sqlrecord->isNew()) {
				// Record is valid in database and the values of primary key columns can't be changed once the record is in the database
				return true;
			}
			$sql = "SELECT 1 FROM `$this->dbName`.`$this->tableName` WHERE NOT (" . $this->buildQueryWhere($sqlrecord) . ')';
			return SQL::query($sql)->isEmpty();
		}

	}

?>