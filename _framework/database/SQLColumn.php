<?php

class SQLColumn {

	/** @var SQLTable The table that the column belongs to */
	protected $table;
	protected $name;
	protected $default;
	protected $nullable;
	protected $type;
	protected $datatype;
	protected $length;
	protected $inPrimaryKey;
	protected $autoIncrement;

	public function __construct($data,$table){
		$this->table = $table;
		$this->name = $data['COLUMN_NAME'];
		$this->default = $data['COLUMN_DEFAULT'];
		$this->nullable = ($data['IS_NULLABLE']=='YES');
		$this->type = $data['COLUMN_TYPE'];
		$this->datatype = $data['DATA_TYPE'];
		$this->length = $data['CHARACTER_MAXIMUM_LENGTH'];
		$this->inPrimaryKey = ($data['COLUMN_KEY']=='PRI');
		$this->autoIncrement = ($data['EXTRA']=='auto_increment');
	}

	public function getName(){return $this->name;}
	public function getFullName(){return $this->table->getFullName().'.'.$this->name;}
	public function isText(){
		return ( preg_match('/(text|blob|char)$/',$this->datatype) ||	$this->isTimestamp() ||	in_array($this->datatype,array('set','enum','time')) );
	}
	public function isNumeric(){
		return ( preg_match('/int$/',$this->datatype) ||
		in_array($this->datatype,array('float','double','decimal')) );
	}
	public function isTimestamp(){
		return in_array($this->datatype,array('date','datetime','timestamp'));
	}
	public function isNullable(){return $this->nullable;}
	public function isInPrimaryKey(){return $this->inPrimaryKey;}
	public function getDefaultValue(){return $this->default;}
	public function getType(){return $this->type;}
	public function getDataType(){return $this->datatype;}
	public function isAutoIncrement(){return $this->autoIncrement;}
	public function getMaxLength(){return $this->length;}


	/**
	 * Returns the list of possible values for a SET or ENUM column
	 * @param boolean $lowercase Converts values to lowercase if true (this is useful for comparing valid values, since MySQL is case insensitive, but PHP is not)
	 */
	public function getPossibleValues($lowercase=false){
		if ($this->datatype == 'enum' || $this->datatype == 'set'){
			if (preg_match('/^'.$this->datatype.'\\((.+?)\\)$/',$this->type,$match)){
				$list = $match[1];
				if ($lowercase) $list = strtolower($list);
				$values = preg_split('/(?<!\\\\),/',$list);
				return array_map(
					function($val){
						if (preg_match('/^\'(.+)\'$/',$val,$match)){
							return $match[1];
						}
						return $val;
					},
					$values
				);
			} else {
				Messages::msg("Column '$this->name'\'s type, '$this->type' does not appear to be a valid $this->datatype type.",Messages::M_CODE_ERROR);
			}
		} else {
			Messages::msg("Column '$this->name' is of type '$this->datatype'. Must be of type 'set' or 'enum' for SQLColumn::getPossibleValues().",Messages::M_CODE_ERROR);
		}
		return array();
	}
	
	public function valueAsSQL($value){
		if ( ($this->isNullable()||$this->autoIncrement) && ($value===null) ){
			return 'NULL';
		}
		if (strtoupper($this->type)=='TIMESTAMP' && $value=='CURRENT_TIMESTAMP') {
			return $value;
		}
		if ($this->isText()){
			return SQLUtils::formatString($value);
		}
		if ($this->isNumeric() && $value==null){
			return 0;
		}
		return $value;

	}
	
	public function generateInput($value,$opts=array(),$attrs=array()){
		if (empty($opts)){
			$opts = array();
		}
		switch ($this->datatype){
			case 'enum':
				$values = array(
					'unselected' => 'Select '.TextUtils::makeSQLFieldReadable($this->name),
				);
				if ($this->isNullable()){
					$values['null'] = ( isset($options['null_value']) ? '['.$options['null_value'].']' : '[None]' );
				}
				foreach ($this->getPossibleValues() as $value){
					$values[$value] = TextUtils::makeCodeNameReadable($value);
				}				
				return HTMLUtils::select($this->name, $values, $value, $attrs);
			
			case 'set':
				$values = array();
				foreach ($this->getPossibleValues() as $value){
					$values[$value] = TextUtils::makeCodeNameReadable($value);
				}		
				return HTMLUtils::multiSelect($name, $values, $value, $attrs);
			
			case 'date':
				throw new Exception('Date input generation has not yet been implemented'); // FRAMEWORK: datepicker
				
			case 'timestamp':
			case 'datetime':
				throw new Exception('Datetime input generation has not yet been implemented'); // FRAMEWORK: datetimepicker
			
			default:
				if ($this->isNumeric()){
					if ($this->type=='tinyint(1)'){
						return HTMLUtils::checkbox($this->name, $value, $attrs);
					} else {
						if (preg_match('/_ID$/',$this->name)){
							$foreign_keys = $this->table->getConstraints(SQLConstraint::TYPE_FOREIGN);
							foreach ($foreign_keys as $foreign_key){
								if ($foreign_key->getColumns() == array($this->name)){
									$info = ArrayUtils::getFirst($foreign_key->getForeignColumns());
									$table = SQLTable::getByTable($info['table_name'],$info['db_name']);
									$class = $table->getClassName();
									$values = array(''=>'Select '.ucwords(TextUtils::makeCodeNameReadable($table->getClassName())).':');
									foreach (DBObject::findAll(null,null,$class) as $object){
										$values[$object->id] = $object->display();
									}
									return HTMLUtils::select($this->name, $values, $value, $attrs);
								}
							}
						}
						return HTMLUtils::text($this->name, $value, $attrs); // MINOR: spinner?
					}
				} else {
					$max = $this->getMaxLength();
					if ($max<=255){
						if (!isset($attrs['size'])){
							$attrs['maxlength'] = $this->getMaxLength();
						}
						return HTMLUtils::text($this->name, $value, $attrs);
					} else {
						return HTMLUtils::textarea($this->name, 24, 8, $value, $attrs);						
					}
				} 
		}
	}

}

?>