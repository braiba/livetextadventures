<?php

/**
 *
 * @author Thomas
 */
class FilterableSQLQueryTable extends SQLQueryTable {

	const NO_FILTER_VALUE = '*';

	protected $filters = array();
	protected $core_sql;
	protected $filters_applied = false;

	public function __construct($id,$columns,$sql,$default_sort_clause='Name',$default_sort_dir='ASC',$page_size=null){
		parent::__construct($id,$columns,$sql,$default_sort_clause,$default_sort_dir,$page_size);
		$this->core_sql = $sql;
	}

	public function getValueList($field,$desc=false){
		$values = array();
		$sql = "SELECT $field AS \"value\", COUNT(*) AS \"Count\" FROM ({$this->core_sql}) tbl GROUP BY $field ORDER BY $field ".($desc?'DESC':'ASC');
		
		foreach (SQL::query($sql) as $row){
			$values[$row->value] = "$row->value ($row->count)";
		}
		return $values;
	}

	public function addFilter($filter){
		if (is_string($filter)){
			$filter = array('field'=>$filter);
		}
		if (!isset($filter['type'])){
			$filter['type'] = 'select';
		}
		$this->filters[] = $filter;
		$this->filters_applied = false;
	}
	public function addFilters($filters){
		foreach ($filters as $filter){
			$this->addFilter($filter);
		}
	}

	protected function getFilterName($filter){
		return (isset($filter['name']) ? $filter['name'] : $filter['field']);
	}
	
	protected function buildCondition($filter){
		if (!isset($filter['field'])){
			return null;
		}
		
		$field = $this->getFilterName($filter);
		$value = $this->getCurrentValue($field);
		
		if (!isset($filter['type'])){
			$filter['type'] = 'select';
		}
		switch ($filter['type']){
			case 'search':
				$value = trim($value);
				if (empty($value)) return null;
				$words = preg_split('/\\s+/',$value);
				$parts = array();
				foreach ($words as $word){
					$word = SQLUtils::escapeString($word);
					$parts[] = "({$filter['field']} LIKE \"%$word%\")";
				}
				return '('.implode(' AND ',$parts).')';
				break;
								
			case 'numeric':
				$value = trim($value);
				if (empty($value) || $value==self::NO_FILTER_VALUE) return null;
				$comparator = ( !isset($filter['comparator']) ? '=' : $filter['comparator'] );
				return "{$filter['field']} $comparator $value"; 
				
			case 'select': //fals through
			default:
				if ($value==self::NO_FILTER_VALUE) return null;
				if (isset($filter['comparator'])){
					$comparator = $filter['comparator'];
				} else {
					$comparator = '=';
				}
		}

		if (isset($filter['numeric'])){
			$format_as_string = !$filter['numeric'];
		} else {
			$format_as_string = ($comparator!='BETWEEN' && !is_numeric($value) && !empty($comparator));
		}

		if ($format_as_string){
			$value = SQLUtils::formatString($value);
		}
		return "({$filter['field']} $comparator $value)";
	}

	protected function applyFiltering(){
		if ($this->filters_applied){
			return;
		}
		$conditions = array();
		
		foreach ($this->filters as $filter){
			if (isset($filter['conditioncallback'])){
				$condition = call_user_func($filter['conditioncallback'],$filter);
			} else {
				$condition = $this->buildCondition($filter);
			}
			if ($condition){
				$conditions[] = $condition;
			}
		}

		$this->data = null; // Clear data so that we regenerate $data if it's already been done for some reason
		if (empty($conditions)){
			$this->sql = $this->core_sql;
		} else {
			$this->sql = "SELECT * FROM ({$this->core_sql}) tbl WHERE ".implode(" AND ",$conditions);
		}
		$this->filters_applied = true;
	}

	public function getCurrentValue($field){
		if (isset($_GET["{$this->id}_filters"][$field])){
			return $_GET["{$this->id}_filters"][$field];
		}		
		foreach ($this->filters as $filter){
			if ($this->getFilterName($filter) == $field){
				if (isset($filter['defaultvalue'])){
					return $filter['defaultvalue'];
				}
				if (isset($filter['type']) && $filter['type']=='search'){
					return null;
				}
				return self::NO_FILTER_VALUE;
			}
		}
		return null;
	}

	public function displayFilterForm(){
		if (empty($this->filters)) return null;
		$get = $_GET;
		$inputs = array();
		$table = new HorizontalTable();
		foreach ($this->filters as $filter){
			$field = $this->getFilterName($filter);

			if (isset($filter['label'])){
				$label = $filter['label'];
			} else {
				$label = $filter['field'];
				if (preg_match('/(?:^|\\.)(`[^`]*`|[a-z_][a-z0-9_]*)$/i',$label,$match)){
					$label = trim($match[1],'`');
				}
				$label = preg_replace('/[_\\-]/',' ',$label);
				$label = ucwords($label);
			}

			$name = "{$this->id}_filters[{$field}]";			
			unset($get[$name]);
			$attrs = array();
			if (isset($filter['attrs'])){
				$attrs = $filter['attrs'];
			}
			if (!isset($filter['type'])){
				$filter['type'] = null;
			}
			
			$value = $this->getCurrentValue($field);
			switch ($filter['type']){
				case 'search':
					$table->addColumn($label,HTMLUtils::text($name,($value==self::NO_FILTER_VALUE?'':$value),$attrs));
					break;
				
				case 'numeric':
					if (isset($filter['prefix'])){
						$prefix = $filter['prefix'];
					} else {
						$prefix = (isset($filter['comparator']) ? htmlspecialchars($filter['comparator']) : '=').' ';
					}
					if (isset($filter['suffix'])){
						$suffix = $filter['suffix'];
					} else {
						$suffix = '';
					}
					$table->addColumn($label,$prefix.HTMLUtils::text($name,($value==self::NO_FILTER_VALUE?'':$value),$attrs).$suffix);
					break;
				
				case 'select': //falls through
				default:
					$values = array(self::NO_FILTER_VALUE=>'[All]');
					if (isset($filter['values'])){
						$filter_values = $filter['values'];
					} else if (isset($filter['callback'])){ // LEGACY
						$filter_values = array_map($filter['callback'],$this->getValueList($filter['field'],$desc));
					} else if (isset($filter['values_callback'])){
						$filter_values = array_map($filter['values_callback'],$this->getValueList($filter['field'],$desc));
					} else if (isset($filter['values_sql'])){
						$filter_values = array();
						foreach (SQL::query($filter['values_sql']) as $row){
							$filter_values[$row->value] = $row->text;
						}
					} else {
						$desc = ( isset($filter['valuesortdir']) && strtoupper($filter['valuesortdir'])=='DESC' );
						$filter['values'] = $this->getValueList($filter['field'],$desc);
						$filter_values = $filter['values'];
					}
					// Doing this with array_merge would re-index numeric keys
					foreach ($filter_values as $key=>$val){
						$values[$key] = $val;
					}
					$table->addColumn($label,HTMLUtils::select($name, $values, $value,$attrs));
			}
		}
		unset($get["{$this->id}_page"]);
		$html = HTMLUtils::hiddenGET(array_keys($get));
		$html.= $table->display();
		$html.= HTMLUtils::button(null,'Clear Filters', array('onclick'=>'clearFormFields(this.parentNode);'));
		$html.= '&nbsp;|&nbsp;';
		$html.= HTMLUtils::submit(null,'Apply Filters');
		$html = '<form method="GET" action="" class="filters center">'.$html.'</form>';
		return $html;
	}

	public function getSQLQuery(){
		$this->applyFiltering();
		return $this->sql;
	}
	
	public function getSQLResult(){
		$this->applyFiltering();
		return parent::getSQLResult();
	}
	public function display($attrs=array()){
		return $this->displayFilterForm().parent::display($attrs);
	}
	
	public function displayTable($attrs=array()){
		return parent::display($attrs);
	}



	/**
	 * This deals with the problem that the MySQL BETWEEN operator is not strict at either end (i.e. it includes values that are
	 *   equal to both range boundaries), but we don't want our filter ranges to overlap, so they need to be strict at at least
	 *   one end, and possibly both.
	 * This function takes a filter and builds an SQL condition based on the current value of that field. These values can be in
	 *   the format "min => max". Use -> instead of => for strict comparison against the max value in the range. Omitting the min
	 *   or max values will cause them to be treated as infinite (i.e. no lower/upper restriction)
	 * @param  $filter
	 * @return string
	 */
	public function advancedBetweenCallback($filter){
		$field = $this->getFilterName($filter);
		$value = $this->getCurrentValue($field);
		if ($value==self::NO_FILTER_VALUE) return null;
		
		if (preg_match('/^\\s*(.*?)\\s*([-=]>)\\s*(.*?)\\s*$/',$value,$match)){
			list(,$min,$arrow,$max) = $match;
			$inclusive = ($arrow == '=>');
			if ($min == $max){
				return "$field <=> $max";
			}
			$comparisons = array();
			if ($min!=''){
				$comparisons[] = "$field > $min";
			}
			if ($max!=''){
				$comparator = ( $inclusive ? '<=' : '<' );
				$comparisons[] = "$field $comparator $max";
			}
			switch (sizeof($comparisons)){
				case 0:
					Messages::msg('advancedBetweenCallback encountered an empty range definition.'.$value,Messages::M_CODE_ERROR);
					return 'FALSE';
				case 1:
					return $comparisons[0];
				default:
					return '('.implode(' AND ',$comparisons).')';
			}
		} else {
				return "$field <=> $value";
		}
	}

	/**
	 * 
	 * @param  $filter
	 * @return string
	 */
	public function advancedInCallback($filter){
		$field = $this->getFilterName($filter);
		$value = $this->getCurrentValue($field);
		if ($value==self::NO_FILTER_VALUE) return null;

		$separator = (isset($filter['separator'])?$filter['separator']:',');
		$values = explode($separator,$value);

		$quote_values = (isset($filter['quote_values'])?$filter['quote_values']:false);

		return SQLUtils::buildIn($field, $values, $quote_values);
	}

	public function saveAsCSV($filename){		
		parent::saveAsCSV($filename);
	}
}
?>
