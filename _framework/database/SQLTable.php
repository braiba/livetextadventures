<?php

	/**
	 * Class representing a table in the database.
	 * @author Thomas
	 */
	class SQLTable {

		protected $class = null;
		
		/**
		 * @var string The name of the table.
		 */
		protected $table = null;

		/**
		 * @var string The name of the database that the table belongs to.
		 */
		protected $schema = null;

		/**
		 * @var SQLQueryResultRow The {@link SQLColumn}s that make up this table.
		 */
		protected $columns = null;

		/**
		 * @var string The name of the constraint defining the primary key.
		 */
		protected $primary_key;

		/**
		 * True if the table contains a primary key or a unique index
		 * @var boolean
		 */
		protected $queryable = false;

		/**
		 * @var array The constraints on the table.
		 */
		protected $constraints = array();

		/**
		 * @var boolean
		 */
		protected $constraints_loaded = array();
		
		/**
		 *
		 * @var array 
		 */
		protected static $table_index = array();
		
		/**
		 *
		 * @param string $class
		 */
		private function __construct($table,$schema=null) {
			$this->table = $table;
			$this->schema = ( isset($schema) ? $schema : SQL::getDefaultConnection()->getDatabase());
			self::$table_index[$this->getFullName()] = $this;
		}
		
		public function getTableName(){
			return $this->table;
		}
		
		public function setTableName($table){
			$this->table = $table;
		}
		
		public function getSchemaName(){
			return $this->schema;
		}
		
		public function setSchemaName($schema){
			$this->schema = $schema;
		}
		
				
		/**
		 *
		 * @param array $types
		 * @return boolean
		 */
		protected function loadConstraints($types) {
			foreach ($this->constraints_loaded as $constraint_type) {
				if (($i = array_search($constraint_type, $types)) !== false) {
					unset($types[$i]);
				}
			}
			if (empty($types)){
				return true;
			}
			$sql = 'SELECT'
				 . ' TABLE_CONSTRAINTS.*,'
				 . ' GROUP_CONCAT( COLUMN_NAME ORDER BY ORDINAL_POSITION ASC) AS "COLUMN_NAMES",'
				 . ' GROUP_CONCAT( REFERENCED_TABLE_SCHEMA ORDER BY ORDINAL_POSITION ASC) AS "REFERENCED_TABLE_SCHEMAS",'
				 . ' GROUP_CONCAT( REFERENCED_TABLE_NAME ORDER BY ORDINAL_POSITION ASC) AS "REFERENCED_TABLE_NAMES",'
				 . ' GROUP_CONCAT( REFERENCED_COLUMN_NAME ORDER BY ORDINAL_POSITION ASC) AS "REFERENCED_COLUMN_NAMES"'
				 . ' FROM ('
					 . ' SELECT *'
					 . ' FROM information_schema.TABLE_CONSTRAINTS'
					 . ' WHERE TABLE_SCHEMA = '.SQLUtils::formatString($this->schema)
					 . ' AND TABLE_NAME = '.SQLUtils::formatString($this->table)
					 . ' AND ' . SQLUtils::buildIn('CONSTRAINT_TYPE', $types, true)
				 . ' ) TABLE_CONSTRAINTS'
				 . ' JOIN information_schema.KEY_COLUMN_USAGE USING (CONSTRAINT_NAME)'
				 . ' WHERE KEY_COLUMN_USAGE.TABLE_SCHEMA = '.SQLUtils::formatString($this->schema) // for some reason it's spectacularly more efficient
				 . ' AND KEY_COLUMN_USAGE.TABLE_NAME = '.SQLUtils::formatString($this->table)      // to reference these constants again rather than join on the columns
				 . ' GROUP BY CONSTRAINT_NAME';
			$result = SQL::query($sql);
			if (!$result->success()) {
				return false;
			}
			foreach ($result as $row) {
				$constraint = SQLConstraint::build($row);
				$this->constraints[$row['CONSTRAINT_NAME']] = $constraint;
				if ($constraint->getType() == SQLConstraint::TYPE_PRIMARY) {
					$this->primary_key = $constraint->getName();
				}
				if ($constraint->getType() == SQLConstraint::TYPE_PRIMARY || $constraint->getType() == SQLConstraint::TYPE_UNIQUE) {
					$this->queryable = true;
				}
			}
			$this->constraints = SQLQueryResultRow::wrap($this->constraints);
			$this->constraints_loaded = array_merge($this->constraints_loaded, $types);

			return true;
		}

		/**
		 *
		 * @param string $table
		 * @param string $schema
		 * @return SQLTable 
		 */
		public static function getByTable($table,$schema=null) {
			if (!$schema){
				$schema = SQL::getDefaultConnection()->getDatabase();
			}
			if (!isset(self::$table_index["`$schema`.`$table`"])){
				self::$table_index["`$schema`.`$table`"] = new SQLTable($table,$schema);
			}
			return self::$table_index["`$schema`.`$table`"];
		}

		/**
		 * __get magic method. Returns the named column.
		 * @param String $name The name of the column.
		 * @return SQLColumn The column, or null if it is not found.
		 */
		public function __get($name) {
			return $this->getColumn($name);
		}

		/**
		 * Returns the table's columns.
		 * @return array An array of the {@link SQLColumn}s in the table.
		 */
		public function getColumns() {
			if ($this->columns == null){
				$sql = 'SELECT'
					 . ' *'
					 . ' FROM `information_schema`.`COLUMNS`'
					 . ' WHERE TABLE_NAME = '.SQLUtils::formatString($this->table)
					 . ' AND TABLE_SCHEMA = '.SQLUtils::formatString($this->schema)
					 . ' ORDER BY `ORDINAL_POSITION`';
				$res = SQL::getInfoConnection()->query($sql);
				if ($res->size() != 0) {
					foreach ($res as $row) {
						$this->columns[$row['COLUMN_NAME']] = new SQLColumn($row, $this);
					}
					$this->columns = SQLQueryResultRow::wrap($this->columns);
				}	else {
					throw new SQLTableException($this->table, "Table {$this->schema}.{$this->table} does not exist or contains no columns. (Remember that temporary tables cannot be loaded by SQLTable).");
				}
			}
			return $this->columns;
		}

		/**
		 * Retrieves a column by name.
		 * @param string $name The name of the column.
		 * @return SQLColumn The column, or null if it is not found.
		 */
		public function getColumn($name) {
			if ($this->hasColumn($name)) {
				return $this->columns[$name];
			}
			$trace = debug_backtrace();
			$info = $trace[ ($trace[1]['function']=='__get') ? 1 : 0 ];
			Messages::msg("Column '$name' does not exist in table {$this->getFullName()}. Referenced at line {$info['line']} of {$info['file']}.", Messages::M_CODE_ERROR);
			return null;
		}

		/**
		 * Indicates whether a column exists within this table.
		 * @param string $name The name of the column.
		 * @return SQLColumn Returns true if the column exists, false otherwise.
		 */
		public function hasColumn($name) {
			return $this->getColumns()->key_exists($name);
		}

		/**
		 * Indicates whether the table has a primary key or not.
		 * @return boolean true if the table has a primary key, false otherwise.
		 */
		public function hasPrimaryKey() {
			$this->loadConstraints(array(SQLConstraint::TYPE_PRIMARY));
			return!is_null($this->primary_key);
		}

		/**
		 * Returns the primary key for this table
		 * @return SQLPrimaryConstraint The SQLPrimaryConstraint that defines the primary key.
		 */
		public function getPrimaryKey() {
			$this->loadConstraints(array(SQLConstraint::TYPE_PRIMARY));
			return (is_null($this->primary_key) ? null : $this->constraints[$this->primary_key]);
		}

		/**
		 * Indicates whether the table has a primary key, or unique key or not.
		 * @return boolean true if the table has a primary key, false otherwise.
		 */
		public function isQueryable() {
			$this->loadConstraints(array(SQLConstraint::TYPE_PRIMARY, SQLConstraint::TYPE_UNIQUE));
			return $this->queryable;
		}

		/**
		 * Checks whether the named column is part of the primary key.
		 * @param string $name The name of the column.
		 * @return boolean
		 */
		public function inPrimaryKey($name) {
			return $this->getColumn($name)->isInPrimaryKey();
		}

		/**
		 * 
		 * @param string|array
		 * @return SQLConstraints[]
		 */
		public function getConstraints($types=array()) {
			if (is_string($types)){
				$types = array($types);
			}
			$this->loadConstraints($types);
			$list = array();
			foreach ($this->constraints as $constraint) {
				if (in_array($constraint->getType(), $types)) {
					$list[] = $constraint;
				}
			}
			return $list;
		}

		/**
		 * Returns the full name of the table, including database, in an SQL safe format.
		 * @return string The name of the table.
		 */
		public function getFullName() {
			return "`$this->schema`.`$this->table`";
		}

		public function generateQuerySQL($query) {
			$wheres = array();
			foreach ($query as $column => $value) {
				if (!$this->hasColumn($column)) {
					Messages::msg("Column '$column' does not exist in {$this->getFullName()}. Specified query for SQLTable::generateQuerySQL() is invalid.", Messages::M_CODE_ERROR);
					return false;
				}
				$wheres[] = "`$this->table`.`$column` = " . $this->$column->valueAsSQL($value);
			}
			if (empty($wheres)) {
				return 'TRUE /* EMPTY QUERY */';
			}
			return implode(' AND ', $wheres);
		}

		public function getPossibleColumnValues($name) {
			return $this->getColumn($name)->getPossibleValues();
		}

	}

?>