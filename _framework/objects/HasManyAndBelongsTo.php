<?php

class hasManyAndBelongsTo extends Relationship {

	protected $tableid;
	protected $linkon;
	protected $linktable;
	protected $linkedtableid;
	
	public function __construct(DBObjectDefinition $owner,$name,$linkedclass,$linktablename,$opts=array()){
		parent::__construct($owner,$name,$linkedclass,$opts);
		$this->tableid = self::getTableId($owner->getTable());
		$this->linktable = SQLTable::getByTable($linktablename);
		$this->linkedtableid = self::getTableId($this->linkedobject->getTable());
		$this->on = (isset($opts['on'])?$opts['on']:($this->linksToOwnTable()?'friend': $this->linkedtableid));
		$this->linkon = (isset($opts['linkon'])?$opts['linkon']:self::getTableId($owner->getTable()));		
		$this->aliases['LinkTable'] = $this->linktable->getTableName();
	}
	
	protected function initJoins(SQLQuery $query){
		$query->joinOn($this->linktable, 'LinkTable', "LinkTable.{$this->on} = LinkedTable.{$this->linkedtableid}");
		$query->joinOn($this->owner->getTable(), 'ThisTable', "ThisTable.{$this->tableid} = LinkTable.{$this->linkon}");
	}
		
}

?>