<?php

class BelongsTo extends Relationship {

	protected $linkedtableid;
	
	public function __construct(DBObjectDefinition $owner,$name,$linkedclass,$opts=array()){
		parent::__construct($owner,$name,$linkedclass,$opts);
		$this->linkedtableid = self::getTableId($this->linkedobject->getTable());
		$this->on = (isset($opts['on'])?$opts['on']:($this->linksToOwnTable()?'parent_ID':$this->linkedtableid));
	}

	protected function initJoins(SQLQuery $query){
		$query->joinOn($this->owner->getTable(), 'ThisTable', "ThisTable.{$this->on} = LinkedTable.{$this->linkedtableid}");
	}
	
	public function get(DBObject $object){
		$res = parent::get($object);
		if ($res) return $res[0];
		return null;
	}
	
}

?>