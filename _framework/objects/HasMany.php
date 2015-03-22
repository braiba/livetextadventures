<?php

class hasMany extends Relationship {

	protected $tableid;
	
	public function __construct(DBObjectDefinition $owner,$name,$linkedclass,$opts=array()){
		parent::__construct($owner,$name,$linkedclass,$opts);
		$this->tableid = self::getTableId($owner->getTable());
		$this->on = ( isset($opts['on']) ? $opts['on'] : ($this->linksToOwnTable()?'parent':$this->tableid) );		
	}
	
	protected function initJoins(SQLQuery $query){
		$query->joinOn($this->owner->getTable(), 'ThisTable', "ThisTable.{$this->tableid} = LinkedTable.{$this->on}");
	}
}

?>