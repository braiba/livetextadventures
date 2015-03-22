<?php

class SimpleSQLQueryTable extends SQLQueryTable {
		
	public function __construct($id,$sql){
		$res = SQL::query("SELECT * FROM ($sql) tbl LIMIT 1");
		$columns = array();
		foreach ($res->getOnly() as $name=>$value){
			$columns[] = array('field'=>$name);
		}
		parent::__construct($id,$columns,$sql,'`'.$columns[0]['field'].'`');
	}	
	
	public static function simpleTable($sql){
		$table = new SimpleSQLQueryTable('simple',$sql);
		$table->setPageSize(10);
		echo $table->display();
	}
	
}

?>