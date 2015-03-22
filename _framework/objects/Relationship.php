<?php

abstract class Relationship {

	/** @var string */
	protected $name;
	/** @var DBObjectDefinition */
	protected $owner;
	/** @var string */
	protected $linkedclass;
	/** @var DBObjectDefinition */
	protected $linkedobject;
	/** @var array */
	protected $links;
	/** @var array */
	protected $aliases;
	/** @var string */
	protected $where;
	/** @var string */
	protected $order;
	/** @var string */
	protected $on;
	/** @var string */
	protected $display;
	/** @var string */
	protected $limit;	
	
	/** @var string */
	protected $selectwhere;
	/** @var string */
	protected $selectorder;

	/** @var array */
	protected $cachedResults = array();

	/**
	 *
	 * @param ObjectDefinition $owner
	 * @param string $name
	 * @param string $linkedclass
	 * @param array $opts 
	 */
	public function __construct(DBObjectDefinition $owner,$name,$linkedclass,$opts){
		$this->owner = $owner;
		$this->name = $name;
		$this->linkedclass = $linkedclass;
		$this->linkedobject = DBObjectDefinition::getByClassName($linkedclass);
				
		$this->where       = (isset($opts['where'])?$opts['where']:null);
		$this->order       = (isset($opts['orderby'])?$opts['orderby']:$this->linkedobject->getDefaultOrderBy('LinkedTable'));
		$this->aliases     = (isset($opts['aliases'])?$opts['aliases']:array());
		$this->display     = (isset($opts['display'])?$opts['display']:'getDisplayText()');
		$this->limit       = (isset($opts['limit'])?$opts['limit']:null);
		
		$this->selectwhere = (isset($opts['selectwhere'])?$opts['selectwhere']:$this->where);
		$this->selectorder = (isset($opts['selectorder'])?$opts['selectorder']:$this->order);
		
		$this->aliases['LinkedTable'] = $this->linkedobject->getFullTableName();
	}
	
	protected static function getTableId(SQLTable $table){
		if ($pk = $table->getPrimaryKey()){
			if ($pk->size()==1){
				return ArrayUtils::getFirst($pk->getColumns());
			} else {
				throw new SQLTableException($table,'Primary key must have exactly one field to joined to in a relationship');
			}
		} else {
			throw new SQLTableException($table,'A primary key is required to join to a table in a relationship');
		}
	}

	public function linksToOwnTable(){
		return ($this->linkedobject->getFullTableName()==$this->owner->getFullTableName());
	}

	protected abstract function initJoins(SQLQuery $query);
	
	private function getWhere(DBObject $object){
		return $object->buildPrimaryKeyWhere('ThisTable').(isset($this->where)?" AND $this->where":'');
	}

	/**
	 * Returns the Query to be used for getting the relationship data on the specified object.
	 * @param int $id The id of the specified object.
	 * @return SQLQuery The query.
	 */
	public function getQuery(DBObject $object){
		$query = new SQLQuery();
		$query->setBaseTable($this->linkedobject->getTable(), 'LinkedTable');		
		$this->initJoins($query);
		$query->addWhere($this->getWhere($object));
		$query->addOrderBy($this->order);
		$query->setLimit($this->limit);
		return $query;
	}
	
	/**
	 * Returns the objects related to an object by this relationship.
	 * @param $object The object
	 * @return array The objects related by this relationship.
	 */
	public function get(DBObject $object){
		$hash = $object->getID();
		if (!isset($this->cachedResults[$hash])){
			$query = $this->getQuery($object);
			$this->cachedResults[$hash] = DBObject::findUsingQuery($query, $this->linkedclass);
		}
		return $this->cachedResults[$hash];
	}
	
	/**
	 * Gets the relationship using the current paging settings
	 *
	 * @param unknown_type $id
	 * @return unknown
	 */
	public function getPaged($id,$pagesize){
		$query = $this->getQuery($id);
		$query->setPageSize($pagesize);
		$query->usePaging();
		return $query->find('LinkedTable',$this->linkedclass);
	}
	
	/**
	 * Adds the relationship to an existing query
	 * @param Query $query The query.
	 * @param string $alias The query alias of the table this relationship belongs to.
	 * @return array The query aliases for this relationship.
	 */
	public function addToQuery($query,$baseAlias){
		// Store the query's aliases
		$queryAliases = $query->getUserAliases();

		$aliases = $this->aliases;
		unset($aliases['ThisTable']);
		$query->addTables($aliases);
		$query->addUserAlias('ThisTable',$queryAliases[$baseAlias]);
		$query->addWhere($this->getWhere());

		// Restore the query's original user aliases
		$query->setUserAliases($queryAliases);
		return $query;
	}

	public function getLinkedTable(){return $this->linkedobject;}
	public function set($value){}

	public function clearCacheEntry($id){
		unset($this->cachedResults[$id]);
	}

	public function getReadableName(){
		return TextUtils::makeCodeNameReadable($this->getName());
	}

}

?>