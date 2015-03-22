<?php

	/**
	 * Description of DBObjectDefinition
	 *
	 * @author thomas
	 */
	class DBObjectDefinition {

		/** @var string the class name */
		protected $class = null;
		/** @var SQLTable The backing table for the object */
		protected $table = null;
		/** @var string the name of the schema that the data table exists in */
		protected $schema = null;
		/** @var string the name of the database table that contains data about this object */
		protected $tablename = null;
		/** @var array relationship data, indexed by relationship name */
		protected $relationships = array();
		/** @var string the default sorting clause when nsearching for objects of this type */
		protected $default_order = null;
		/** @var string the name used to refer to this object type in user messages. */
		protected $display_name = null;
		/** @var string the name used to refer to this object type in generated code, such as HTML ids and CSS classes. */
		protected $reference_name = null;
		/** @var string|callback a property name or callback function to bne used when displaying this type of object*/
		protected $display = null;
		
		/** @var DBObjectDefinition[] object definitions indexed by class name */
		protected static $class_index = array();

		/**
		 *
		 * @param string $class
		 */
		private function __construct($class) {
			$this->class = $class;
			$this->tablename = TextUtils::plural(strtolower(preg_replace('/(?<=[a-z])(?=[A-Z])/', '_', $class)));
			$this->schema = SQL::getDefaultConnection()->getDatabase();
			$this->display_name = TextUtils::makeCodeNameReadable($class);
			$this->reference_name = str_replace(' ', '_', $this->display_name);
		}

		public function getClassName() {
			return $this->class;
		}

		public function getTableName() {
			return $this->tablename;
		}

		public function getFullTableName() {
			return $this->getTable()->getFullName();
		}

		public function setTableName($table) {
			$this->tablename = $table;
			$this->table = null;
		}

		public function getSchemaName() {
			return $this->schema;
		}

		public function setSchemaName($schema) {
			$this->schema = $schema;
			$this->table = null;
		}

		public function getTable() {
			if (!isset($this->table)) {
				$this->table = SQLTable::getByTable($this->tablename, $this->schema);
			}
			return $this->table;
		}

		public function setTable(SQLTable $table) {
			$this->table = $table;
		}
		
		public function getDisplayName() {
			return $this->display_name;
		}
		
		public function setDisplayName($display_name) {
			$this->display_name = $display_name;
		}
		
		public function getReferenceName() {
			return $this->reference_name;
		}
		
		public function setReferenceName($reference_name) {
			$this->reference_name = $reference_name;
		}

		public function getDefaultOrderBy($alias=null) {
			// Check standard 'order_by' values if none given
			if (!$this->default_order) {
				$columns = $this->getColumns();
				if ($columns->key_exists('timestamp')) {
					$this->default_order = 'timestamp DESC';
				} if ($columns->key_exists('date')) {
					$this->default_order = 'timestamp DESC';
				}
				elseif ($columns->key_exists('order')) {
					$this->default_order = 'order';
				}
				elseif ($columns->key_exists('order_index')) {
					$this->default_order = 'order_index';
				}
				elseif ($columns->key_exists('name')) {
					$this->default_order = 'name';
				}
				elseif ($columns->key_exists('title')) {
					$this->default_order = 'title';
				}
				elseif ($columns->key_exists('username')) {
					$this->default_order = 'username';
				}
			}

			if ($alias && $this->default_order) {
				return implode(',', array_map(function($val) use ($alias) {
								return "`$alias`.$val";
							}, explode(',', $this->default_order)));
			}
			return $this->default_order;
		}

		public function setDefaultOrderBy($order_by) {
			$this->default_order = $order_by;
		}

		/**
		 *
		 * @return string|callback
		 */
		public function getDisplay() {
			// Check standard 'display' values if none given
			if (!$this->display) {
				$columns = $this->getColumns();
				if ($columns->key_exists('name')) {
					$this->display = 'name';
				}
				elseif ($columns->key_exists('title')) {
					$this->display = 'title';
				}
				elseif ($columns->key_exists('username')) {
					$this->display = 'username';
				}
			}
			return $this->display;
		}

		/**
		 *
		 * @param string|callback $display
		 */
		public function setDisplay($display) {
			$this->display = $display;
		}

		public static function classIsDefined($class) {
			return isset(self::$class_index[$class]);
		}

		/**
		 *
		 * @param string $class
		 * @param DBObject $object if this method is called from the DBObject constructor, it must pass itself to prevent an infinite loop
		 * @return DBObjectDefinition 
		 */
		public static function getByClassName($class, $object=null) {
			if (!self::classIsDefined($class)) {
				if (!$object) {
					$object = new $class();
				}
				self::$class_index[$class] = new DBObjectDefinition($class);
				$object->defineObject(self::$class_index[$class]);
			}
			return self::$class_index[$class];
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
			return $this->getTable()->getColumns();
		}

		/**
		 * Retrieves a column by name.
		 * @param string $name The name of the column.
		 * @return SQLColumn The column, or null if it is not found.
		 */
		public function getColumn($name) {
			$this->getTable()->getColumn($name);
		}

		/**
		 * Indicates whether a column exists within this table.
		 * @param string $name The name of the column.
		 * @return SQLColumn Returns true if the column exists, false otherwise.
		 */
		public function hasColumn($name) {
			return $this->getColumns()->key_exists($name);
		}

		public function relationshipExists($name) {
			return isset($this->relationships[$name]);
		}

		/**
		 * @param string $name
		 * @return Relationship
		 */
		public function getRelationship($name) {
			if (!$this->relationshipExists($name)) {
				Messages::msg("Relationship '$name' does not exist.", Messages::M_CODE_ERROR, array('ClassName' => $this->className, 'RelationshipName' => $name));
				return null;
			}
			return $this->relationships[$name];
		}

		public function belongsTo($linkedclass, $opts=array()) {
			$name = (isset($opts['as']) ? $opts['as'] : ($linkedclass == $this->class ? 'Parent' : $linkedclass));
			$this->relationships[$name] = new BelongsTo($this, $name, $linkedclass, $opts);
		}

		public function hasMany($linkedclass, $opts=array()) {
			$name = (isset($opts['as']) ? $opts['as'] : ($linkedclass == $this->class ? 'Children' : TextUtils::plural($linkedclass)));
			$this->relationships[$name] = new HasMany($this, $name, $linkedclass, $opts);
		}

		public function hasManyAndBelongsTo($linkedclass, $linktablename, $opts=array()) {
			$name = (isset($opts['as']) ? $opts['as'] : ($linkedclass == $this->class ? 'Friends' : TextUtils::plural($linkedclass)));
			$this->relationships[$name] = new HasManyAndBelongsTo($this, $name, $linkedclass, $linktablename, $opts);
		}

	}

?>
