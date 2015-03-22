<?php

/**
 * Class representing a record in the database.
 * @author Thomas
 * 
 * @property int|string $id The unique identifier for this record. If the record has multiple fields in its primary key they will be hyphen-separated.
 */
abstract class DBObject implements ArrayAccess {

	const RES_FAILED = 0;
	const RES_SUCCESS = 1;
	const RES_UNCHANGED = -1;

	/** @var DBObjectDefinition The table this record belongs to. */
	protected $definition;
	/** @var SQLQueryResultRow An array of {@link SQLValue}s for this table. */
	protected $values;
	/** @var boolean Indicates whether or not the record exists in the database already. */
	protected $new = true;
	/** @var boolean Indicates whether or not the record has been changed since it was last saved to the database. */
	protected $hasChanged = true;

	/**
	 * DBObject constructor.
	 * DBObjects can be initialised from any of the following data:
	 *  - An integer that matches a primary key value (for tables where the primary key is a single integer).
	 *  - An array of name=>value pairs that satisfy a UNIQUE/PRIMARY key on the table. 
	 *  - An SQLQueryResultRow that contains all the data from a row of the table.
	 *  - No data (this creates a new instance of the object)
	 * Note that the DBObject will be initialised with the specified data, even if a matching record doesn't exist
	 *   in the database. Use the isNew() method to check if the resulting object already exists in the database.
	 * @param mixed $data Object data
	 */
	public function __construct($data=null){
		// Load Table
		$class = get_class($this);
		$this->definition = DBObjectDefinition::getByClassName($class,$this);
		
		// Process Input
		$dataLoaded = false;
		if (!is_null($data)){
			if ($data instanceof SQLQueryResultRow){
				if (!$this->loadFromSqlResult($data)){
					throw new DBObjectException(get_class(),'Unable to load '.get_class($this).' object from SQL result row');
				}
				$dataLoaded = true;
			} elseif (is_array($data) || $data instanceof ArrayAccess) {
				$dataLoaded = $this->loadFromQuery($data);
			} elseif (is_numeric($data)) {
				$data = (int)$data;
				$pk = $this->getTable()->getPrimaryKey();
				if ($pk->size()==1){
					$id = $data;
					$data = array(ArrayUtils::getFirst($pk->getColumns())=>$id);
					$dataLoaded = $this->loadFromQuery($data);
				} else {
					throw new DBObjectException(get_class(),'Attempted to create '.get_class($this).' object from ID, but primary key contains multiple fields: '.$data);
				}
			} else {
				throw new DBObjectException(get_class(),'Attempted to create '.get_class($this).' object from unexpected data: '.var_export($data,true).']');
			}
		}

		// Load Data
		if ($dataLoaded){
			$this->new = false;
			$this->hasChanged = false;
			$this->values = SQLQueryResultRow::wrap($this->values);
		} else {
			$this->values = array();
			foreach ($this->definition->getColumns() as $name=>$column){
				$this->values[$name] = new SQLValue($column,$column->getDefaultValue());
			}
			$this->values = SQLQueryResultRow::wrap($this->values);
			
			// If a query was specified, but data has not been loaded from it because the record doesn't exist, load that information
			if (is_array($data)){
				foreach ($data as $field=>$value){
					$this->values[$field]->setValue($value);
				}
			}
		}
		
	}

	private function loadFromSqlResult($res){
		$res = SQLQueryResultRow::wrap($res);
		$this->values = array();
		foreach ($this->definition->getColumns() as $name=>$column){
			if (!$res->key_exists($name)){
				Messages::msg("Value for column '$name' not found in source.".print_r($res,true),Messages::M_CODE_ERROR);
				return false;
			}
			$this->values[$name] = new SQLValue($column,$res[$name]);
		}
		return true;
	}

	private function generateConstraintQuerySQL($query,$constraint){
		$wheres = array();
		foreach ($constraint->getColumns() as $column){
			if (!$query->key_exists($column)){
				return false;
			}
			$sql_val = $this->getTable()->getColumn($column)->valueAsSQL($query[$column]);
			$wheres[] = "`$column` ".($sql_val=='NULL' ? 'IS NULL' : '= '.$sql_val);
		}
		$where = implode(' AND ',$wheres);
		
		//$ex = new Exception();
		//Messages::msg($ex->getTraceAsString(),Messages::M_SQL_QUERY);
		
		return "SELECT * FROM {$this->getFullTableName()} WHERE $where LIMIT 1";
	}
	
	private function loadFromQuery($query){
		$query = SQLQueryResultRow::wrap($query);
		if ($this->getTable()->isQueryable()){
			foreach ($this->getTable()->getConstraints(array(SQLConstraint::TYPE_PRIMARY,SQLConstraint::TYPE_UNIQUE)) as $constraint){
				$sql = $this->generateConstraintQuerySQL($query,$constraint);
				if ($sql===false) {					
					continue;
				}
				$result = SQL::query($sql);
				if (!$result->isEmpty()){
					return $this->loadFromSqlResult($result->getOnly());
				}
				/*
				 * NOTE: No error is given here, because the SQLRecord constructor is intended to be usable for searching.
				 */
				return false;
			}
			throw new DBObjectException(__CLASS__,'No constraints on '.$this->getFullTableName().' match the specified query (Queried columns: '.TextUtils::andList($query->keys()).').');
		} else {
			Messages::msg($this->getFullTableName().' has no unique or primary indexes and cannot be queried.',Messages::M_CODE_ERROR);
		}
		return false;
	}
	
	/**
	 * @param SQLTable $table
	 */
	public abstract function defineObject(DBObjectDefinition $def);	

	/**
	 * __get magic method. Returns the current value of the named column.
	 * @param string $name The name of the column
	 * @return mixed The current value of the named column, or null if the column does not exist
	 */
	public function __get($name){
		if ($name=='id'){
			return $this->getID();
		}
		if ($this->definition->relationshipExists($name)) {			
			if ($this->isNew()){
				throw new DBObjectException(__CLASS__,'Attempting to access relationship '.$name.' of '.TextUtils::aOrAn(get_class($this)).' object that has not been saved and therefore does not exist in the database.');
			}
			return $this->definition->getRelationship($name)->get($this);
		} else if ($this->values->key_exists($name)){
			return $this->values[$name]->getValue();
		} 
		
		$stack = debug_backtrace();
		$call_info = $stack[0];		
			throw new DBObjectException(get_class($this),get_class($this).' does not have '.TextUtils::aOrAn($name).' property.');
	}

	/**
	 * __set magic method. Sets the current value of the named column. This will be ignored if the column is in the primary key and the record is already in the database.
	 * @param string $name The named column.
	 * @param mixed $value The new value for the column.
	 */
	public function __set($name,$value){
		if (!$this->values->key_exists($name)){
			throw new DBObjectException(get_class($this),get_class($this).' does not have '.TextUtils::aOrAn($name).' value.');
		}
		if ( (!$this->isNew() && $this->getTable()->inPrimaryKey($name))) return;
		if ($this->values[$name]->setValue($value)){
			$this->hasChanged = true;
		}
	}


	/*
	 * ArrayAcess interface
	 */

	public function offsetExists($name){return $this->hasColumn($name);}
	public function offsetGet($name){return $this->$name;}
	public function offsetSet($name,$value){return $this->$name = $value;}
	public function offsetUnset($name){return $this->$name = null;}

	public function hasColumn($name){
		return $this->values->key_exists($name);
	}

	public function columnHasChanged($name){
		if (!$this->values->key_exists($name)){
			Messages::msg("Column '$name' does not exist for {$this->getFullTableName()}.",Messages::M_CODE_ERROR);
			return null;
		}
		return $this->values[$name]->hasChanged();
	}
	
	public function getSQLValue($name){
		if (!$this->values->key_exists($name)){
			Messages::msg("Column '$name' not found.",Messages::M_CODE_ERROR);
			return null;
		}
		return $this->values[$name]->getSQLValue();
	}

	public function updateFromArray($array,$prefix=null,$suffix=null,$callback=null){
		$array = SQLQueryResultRow::wrap($array);
		foreach ($this->values as $col_name=>$value){
			if ($array->key_exists("$prefix$col_name$suffix")){
				if (is_null($callback)){
					$this->$col_name = $array[$col_name];
				} else {
					$this->$col_name = call_user_func($callback, $array[$col_name]);
				}
			}
		}
	}
	public function updateFromPOST($id=null){
		$this->updateFromArray((is_null($id)?$_POST:$_POST[$id]));
	}

	public function updateFromGET($id=null){
		$this->updateFromArray((is_null($id)?$_GET:$_GET[$id]));
	}

	/**
	 * Stub method
	 */
	public function beforeCommit(){}
	/**
	 * Stub method
	 */
	public function afterCommit(){}
	
	/**
	 * Updates the record in the database
	 * @return int RES_FAILED, RES_SUCCESS or RES_UNCHANGED
	 */
	public function update(){
		if (!$this->hasChanged) return self::RES_UNCHANGED;
		$table = $this->getTable();
		if ($this->new){
			Messages::msg("Cannot update record in {$this->getFullTableName()}, because the record doesn't exist.",Messages::M_CODE_ERROR);
			return self::RES_FAILED;
		}
		if (!$table->hasPrimaryKey()){
			Messages::msg("Cannot update record in {$this->getFullTableName()}, because the table does not have a primary key.",Messages::M_CODE_ERROR);
			return self::RES_FAILED;
		}
		
		$this->beforeCommit();
		
		$vals = array();
		$where = array();
		$changeCount = 0;
		$error = false;
		foreach ($this->values as $name=>$value){
			if ($value->isErroneous()){
				Framework::reportErrorField($name);
				$error = true;
			}
			if ($value->hasChanged()){
				$vals[] = "`$name` = ".$value->getSQLValue();
				$changeCount++;
			}
		}
		if ($error){
			return self::RES_FAILED;
		}
		
		foreach ($table->getPrimaryKey()->getColumns() as $name){
			$where[] = "`$name` = ".$this->values[$name]->getSQLValue();
		}
		$sql = 'UPDATE '.$this->getFullTableName().' SET '.implode(', ',$vals).' WHERE '.implode(' AND ',$where).' LIMIT 1';
		if (!SQL::query($sql)->success()){
			Messages::msg('Failed to update record in '.$this->getFullTableName().'.',Messages::M_CODE_ERROR);
			return self::RES_FAILED;
		}
		$this->hasChanged = false;
		foreach ($this->values as $value){
			$value->setHasChanged(false);
		}
		
		$this->afterCommit();
		
		return self::RES_SUCCESS;
	}
	
	/**
	 * Inserts the record into the database
	 * @return int RES_FAILED, RES_SUCCESS or RES_UNCHANGED
	 */
	public function insert(){
		if (!$this->new){
			Messages::msg("Cannot insert {$this->getFullTableName()} record: already exists.",Messages::M_CODE_ERROR);
			return self::RES_FAILED;
		}
		
		$this->beforeCommit();
		
		$vals = array();
		$error = false;
		foreach ($this->values as $name=>$value){
			if ($value->isErroneous()){
				Framework::reportErrorField($name);
				$error = true;
			}
			$vals["`$name`"] = $value->getSQLValue();
		}
		if ($error){
			return self::RES_FAILED;
		}
		
		$sql = 'INSERT INTO '.$this->getFullTableName().' ('.implode(', ',array_keys($vals)).') VALUES ('.implode(', ',$vals).')';
		if (!SQL::query($sql)->success()){
			Messages::msg('Failed to insert record into '.$this->getFullTableName().'.',Messages::M_CODE_ERROR);
			return self::RES_FAILED;
		}
		
		// Get this here, because getPrimaryKey can call other SQL queries and thus override this value
		$auto_id = SQL::getInsertId();
		
		$table = $this->getTable();
		// Load the AUTO_INCREMENT value, if any, before marking record as not new (at which point primary fields cannot be changed)
		foreach ($table->getPrimaryKey()->getColumns() as $name){
			if ($table->getColumn($name)->isAutoIncrement()){
				$this->$name = $auto_id;
				break;
			}
		}

		$this->new = false;
		$this->hasChanged = false;
		foreach ($this->values as $value){
			$value->setHasChanged(false);
		}
		
		$this->afterCommit();
			
		return self::RES_SUCCESS;
	}

	/**
	 * Reloads the record
	 * @return int RES_FAILED or RES_SUCCESS
	 */
	public function reload(){
		if (!$this->getTable()->hasPrimaryKey()){
			Messages::msg('Cannot reload a table that doesn\'t have a primary key',Messages::M_CODE_ERROR);
			return self::RES_FAILED;;
		}
		$table = $this->getTable();
		$query = new SQLQuery();
		$query->setBaseTable($table, 'ObjectTable');
		foreach ($table->getPrimaryKey()->getColumns() as $name){
			$query->addWhere("`$name` = ".$this->values[$name]->getSQLValue());
		}
		$query->addSelect('ObjectTable.*');
		$res = SQL::query($query);
		if ($res->isEmpty()){
			return self::RES_FAILED;
		}
		$data = $res->getOnly();
		foreach ($table->getColumns() as $column){
			if ($column->isInPrimaryKey()){
				continue;
			}
			$this->values[$column->getName()]->setValue($data[$column->getName()]);
		}
		$this->hasChanged = false;
		foreach ($this->values as $value){
			$value->setHasChanged(false);
		}
		return self::RES_SUCCESS;
	}

	/**
	 * Deletes the record
	 * @return int RES_FAILED or RES_SUCCESS
	 */
	public function delete(){
		if ($this->new){
			Messages::msg("Cannot delete record from {$this->getFullTableName()}, because the record doesn't exist.",Messages::M_CODE_ERROR);
			return self::RES_FAILED;
		}
		$table = $this->getTable();
		if (!$table->hasPrimaryKey()){
			Messages::msg("Cannot delete record from {$this->getFullTableName()}, because the table does not have a primary key.",Messages::M_CODE_ERROR);
			return self::RES_FAILED;
		}
		$where = array();
		foreach ($table->getPrimaryKey()->getColumns() as $name){
			$where[] = "`$name` = ".$this->values[$name]->getSQLValue();
		}
		if (!SQL::query('DELETE FROM '.$this->getFullTableName().' WHERE '.implode(' AND ',$where).' LIMIT 1')->success()){
			Messages::msg('Failed to delete record from '.$this->getFullTableName().'.',Messages::M_CODE_ERROR);
			return self::RES_FAILED;
		}
		return self::RES_SUCCESS;
	}

	/**
	 * Stores the record in the database, either through INSERT (if the record is new) or UPDATE (if it's not).
	 * @return int RES_FAILED, RES_SUCCESS or RES_UNCHANGED (UPDATE only)
	 */
	public function save(){
		if ($this->new){
			return $this->insert();
		} else {
			return $this->update();
		}
	}

	/**
	 * Checks if the record is being created or whether it already exists in the database.
	 * @return boolean True if the record has not yet been inserted into the database, false otherwise
	 */
	public function isNew(){return $this->new;}

	public function getPossibleColumnValues($name){
		return $this->definition->getPossibleColumnValues($name);
	}

	/**
	 *
	 * @return DBObjectDefinition
	 */
	public function getDefinition(){return $this->definition;}
	/**
	 *
	 * @return SQLTable
	 */
	public function getTable(){return $this->definition->getTable();}
	/**
	 * @return string
	 */
	public function getFullTableName(){return $this->getTable()->getFullName();}
	/**
	 * @return string
	 */
	public function getTidyTableName(){return str_replace('`','',$this->getTable()->getFullName());}
	/**
	 * @return array
	 */
	public function getPrimaryKeyValues(){
		$columns = $this->getTable()->getPrimaryKey()->getColumns();
		$values = array();
		foreach ($columns as $column){
			$values[$column] = $this->$column;
		}
		return $values;
	}
	public function getID(){
		$parts = array();
		foreach ($this->getPrimaryKeyValues() as $value){
			$parts[] = str_replace('-','\\-',$value);
		}
		return implode('-',$parts);
	}	
	
	/**
	 *
	 * @param string $name the name of the relationship
	 * @return SQLQuery 
	 */
	public function getRelationshipQuery($name){
		if ($this->definition->relationshipExists($name)) {			
			if ($this->isNew()){
				$call_info = ArrayUtils::getFirst(debug_backtrace());
				Messages::msg('Attempting to access relationship '.$name.' of '.TextUtils::aOrAn(get_class($this)).' object that has not been saved and therefore does not exist in the database. Reference at at line '.$call_info['line'].' of '.$call_info['file'],Messages::M_CODE_ERROR);
				return null;
			}
			return $this->definition->getRelationship($name)->getQuery($this);
		} 
		Messages::msg('Relationship not defined: '.$name,Messages::M_CODE_ERROR);
		return null;
	}
	
	public function display(){
		if ($display = $this->definition->getDisplay()){
			if (is_string($display)){
				if ($this->hasColumn($display)){
					return htmlentities($this->$display);
				} elseif (method_exists($this, $display)){
					return $this->$display();
				} 
				// Falls through
			} elseif (is_callable($display)){
				return call_user_func($display, $this);
			}
		}
		return '['.get_class($this).':#'.$this->id.']';
	}
	
	public function __toString(){
		return $this->display();
	}
		
	public function buildPrimaryKeyWhere($alias=null){
		return $this->getTable()->getPrimaryKey()->buildQueryWhere($this->getPrimaryKeyValues(), $alias);
	}
		
	public static function findUsingSQL($sql,$class=null){
		$results = array();
		if (!isset($class)){
			$class = get_called_class();
		}
		foreach (SQL::query($sql) as $row){
			$results[] = new $class($row);
		}
		return $results;
	}
		
	public static function findAll($order_by=null,$limit=null,$class=null){
		if (!isset($class)){
			$class = get_called_class();
		}
		$definition = DBObjectDefinition::getByClassName($class);
		$table = $definition->getTable();
		if (is_null($order_by)){
			$order_by = $definition->getDefaultOrderBy();
		}
		$sql = 'SELECT'
			 . ' *'
			 . ' FROM '.$table->getFullName()
			 . (!empty($order_by)?' ORDER BY '.$order_by:'')
			 . (!empty($limit)?' LIMIT '.$limit:'');
		return self::findUsingSQL($sql,$class);
	}
	
	public static function findWhere($where,$order_by=null,$limit=null,$class=null){
		if (!isset($class)){
			$class = get_called_class();
		}
		$definition = DBObjectDefinition::getByClassName($class);
		$table = $definition->getTable();
		if (is_null($order_by)){
			$order_by = $definition->getDefaultOrderBy();
		}
		$sql = 'SELECT'
			 . ' *'
			 . ' FROM '.$table->getFullName()
			 . ' WHERE '.$where
			 . (!empty($order_by)?' ORDER BY '.$order_by:'')
			 . (!empty($limit)?' LIMIT '.$limit:'');
		return self::findUsingSQL($sql,$class);
	}
	
	/**
	 *
	 * @param array $properties
	 * @param string $order_by
	 * @param string $limit
	 * @return type 
	 */
	public static function findByProperties($properties,$order_by=null,$limit=null,$class=null){
		if (!isset($class)){
			$class = get_called_class();
		}
		$definition = DBObjectDefinition::getByClassName($class);
		$table = $definition->getTable();
		if (is_null($order_by)){
			$order_by = $definition->getDefaultOrderBy();
		}
		$wheres = array();
		foreach ($properties as $name=>$value){
			$wheres[] = '`'.$name.'` <=> '.$table->getColumn($name)->valueAsSQL($value);
		}
		$sql = 'SELECT'
			 . ' *'
			 . ' FROM '.$table->getFullName()
			 . ' WHERE '.implode(' AND ',$wheres)
			 . (!empty($order_by)?' ORDER BY '.$order_by:'')
			 . (!empty($limit)?' LIMIT '.$limit:'');
		return self::findUsingSQL($sql);
	}
	
	public static function findUsingQuery(SQLQuery $query,$class=null){
		$results = array();
		if (!isset($class)){
			$class = get_called_class();
		}
		$definition = DBObjectDefinition::getByClassName($class);
		$table = $definition->getTable();
		$alias = 'ObjectTable';
		if ($base_ref = $query->getBaseTable()){
			$alias = $base_ref->getAlias();
		} else {
			$query->setBaseTable($table, 'ObjectTable');			
		}
		$query->clearGroupBy();
		foreach ($table->getPrimaryKey()->getColumns() as $column){
			$query->addGroupBy($alias.'.`'.$column.'`');
		}
		$query->addSelect($alias.'.*');
		if (!$query->hasOrderBy() && $default_order=$definition->getDefaultOrderBy($alias)){
			$query->setOrderBy($default_order);
		}
		foreach (SQL::query($query) as $row){
			try {
				$results[] = new $class($row);
			} catch (Exception $ex){
				Messages::msg($ex->getMessage(),Messages::M_ERROR);
			}
		}
		return $results;
	}
	
}

?>