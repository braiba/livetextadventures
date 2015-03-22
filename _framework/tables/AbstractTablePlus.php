<?php

/**
 * Adds support for pagination and sortable columns. Also defines a standard format for defining columns
 *
 * @author tom
 */
abstract class AbstractTablePlus extends AbstractTable {

	protected $sortable = true;

	protected $columns = array();
	protected $data = array();
	
	protected $row_class_callback = null;
	protected $row_style_callback = null;
	
	protected $column_groups = false;

	public abstract function getPage();
	public abstract function getPageSize();
	public abstract function fullDataRowCount();
	public function getPageCount(){
		return ( $this->getPageSize()==0 ? 0 : ceil($this->fullDataRowCount() / $this->getPageSize()) );
	}


	public function getHeadSize(){return ($this->column_groups ? 2 : 1);}
	public function getFootSize(){return 0;}
	public function hasRowLabels(){return false;}
	public function rowCount(){return sizeof($this->data)+$this->getHeadSize()+$this->getFootSize();}
	public function colCount(){return sizeof($this->columns);}

	public function isSortable(){return ($this->sortable && !is_null($this->id));}
	public function setSortable($sortable){
		if ($sortable && is_null($this->id)){
			$this->id = uniqid('table_');
		}
		$this->sortable = $sortable;
	}

	public function getTableClass(){return parent::getTableClass().' abstracttableplus';}
	
	protected function sortLink($field,$desc=false){
		$order = ($desc?'DESC':'ASC');
		$text = ($desc?'down':'up');
		$get = $_GET;
		$get["{$this->id}_sort_clause"] = $field;
		$get["{$this->id}_sort_dir"] = $order;
		unset($get["{$this->id}_page"]);
		return HTMLUtils::a($_SERVER['PHP_SELF'].HTMLUtils::buildGetQuery($get),HTMLUtils::img(IMAGES_FOLDER."/icon/$text.gif","Sort {$text}wards"),array('title'=>"Sort {$text}wards"));
	}

	protected function getRowClass($y){
		if (is_null($this->getRowClassCallback()) || $y<$this->getHeadSize()){
			return null;
		}
		$row = $this->data[$y-$this->getHeadSize()];
		return call_user_func($this->getRowClassCallback(),$row,$y);
	}
	public function getRowClassCallback(){
		return $this->row_class_callback;
	}
	public function setRowClassCallback($callback){
		$this->row_class_callback = $callback;
	}
	protected function getRowStyle($y){
		if (is_null($this->getRowStyleCallback()) || $y<$this->getHeadSize()){
			return null;
		}
		$row = $this->data[$y-$this->getHeadSize()];
		return call_user_func($this->getRowStyleCallback(),$row,$y);
	}
	public function getRowStyleCallback(){
		return $this->row_style_callback;
	}
	public function setRowStyleCallback($callback){
		$this->row_style_callback = $callback;
	}
	
	protected function cellOpen($x,$y,$attrs=array()){
		$this->currCellType = ( ($x==0&&$this->hasRowLabels()) || $y<$this->getHeadSize() ?'th':'td');
		return HTMLUtils::openTag($this->currCellType,$attrs);
	}
	
	protected function buildCell($x,$y){
		$attrs = array();
		if ($this->column_groups){
			if ($this->hasRowLabels() && $x==0){
				switch ($y){
					case 0: $attrs['rowspan'] = 2; break;
					case 1: return '';
				}
			}	else {
				if ($y<=1){
					$col = $this->columns[$x-1];
					switch ($y)	{
						case 0:
							if (!isset($col['group'])){
								$attrs['rowspan'] = 2;
							} else {
								if ($x==1 || !isset($this->columns[$x-2]['group']) || $this->columns[$x-2]['group']!=$col['group']){
									$i=1;
									while ( $x+$i-1!=sizeof($this->columns) && isset($this->columns[$x+$i-1]['group']) && ($this->columns[$x+$i-1]['group']==$col['group']) ){
										$i++;
									}
									$attrs['class'] = 'column_group';
									$attrs['colspan'] = $i;		
								} else {
									return '';
								}
							}
							break;
						case 1:
							if (!isset($col['group'])){
								return '';
							}
							break;
					}
				}
			}
		}
		return parent::buildCell($x,$y,$attrs);
	}
	
	public function getCell($x,$y){
		$col = $this->columns[$x];
		if ($this->column_groups){
			switch ($y)	{
				case 0:
					if (isset($col['group'])){
						return $col['group'];
					}
				case 1:
					$y = 0;
					break;
			}			
		}
		if ($y==0){
			if (isset($col['label'])){
				$value = $col['label'];
			} else {
				$value = $col['field'];
				if (preg_match('/(?:^|\\.)(`[^`]*`|[a-z_][a-z0-9_]*)$/i',$value,$match)){
					$value = trim($match[1],'`');
				}
				$value = preg_replace('/[_\\-]/',' ',$value);
				$value = ucwords($value);
			}
			$value = nl2br($value);
			if ($this->isSortable() && (isset($col['sortfield']) || isset($col['field'])) ){
				if (array_key_exists('sortfield',$col)){
					$sortfield = $col['sortfield'];
				} else {
					$sortfield = $col['field'];
					if (!preg_match('/[.`]/',$sortfield)){
						$sortfield = "`{$sortfield}`";
					}
				}
				if ($sortfield){
					$value = '<div class="sortlabel">'.$value.'</div>';
					$value.= '<div class="sortlinks">'.
								$this->sortLink($sortfield,false).
								$this->sortLink($sortfield,true).
							 '</div>';
				}
			}
		} else {
			$row = $this->data[$y-$this->getHeadSize()];
			
			if (isset($col['callback'])){
				$value = call_user_func($col['callback'],$row,$col);
			} else if (isset($col['field'])){
				$key = $col['field'];
				if (preg_match('/(?:^|\\.)(`[^`]*`|[a-z_][a-z0-9_]*)$/i',$key,$match)){
					$key = trim($match[1],'`');
				}
				$value = htmlspecialchars($row[$key]);
			}
		}
		return $value;
	}
	
	protected function getPageLinks(){
		$links = array();
		$page_count = $this->getPageCount();
		if ($page_count>1){
			$page = $this->getPage();
			if ($page_count<10){
				$pagelist = range(0,$page_count-1);
			} else {
				// This gets the first three, the last three and the middle five, in order, with nulls between the gaps
				// MINOR: seems like there should be a more efficient way to write this code...
				
				$pagelist = array(0,1,2,$page_count-1,$page_count-2,$page_count-3);
				$min = $page-2;
				$max = $page+3;
				if ($min<0) $min = 0;
				if ($max>=$page_count) $max = $page_count-1;
				for ($i=$min;$i<$max;$i++){
					$pagelist[] = $i;
				}
				$pagelist = array_unique($pagelist);
				sort($pagelist);
				
				$i = 0;
				$prev = -1;
				while ($i<sizeof($pagelist)){
					$p = $pagelist[$i++];
					if ($prev+1!=$p){
						array_splice($pagelist,$i-1,1,array(null,$p));
						$i++;
					}
					$prev = $p;
				}
				
			}
			
			foreach ($pagelist as $i){
				$get = $_GET;
				$get[$this->id.'_page'] = $i;
				if (is_null($i)){
					$links[]= '...';
				} else {
					if ($i==$page){
						$links[] = '<span class="current_page">'.number_format($i+1).'</span>';
					} else {
						$links[] = HTMLUtils::a($_SERVER['PHP_SELF'].HTMLUtils::buildGetQuery($get),number_format($i+1));
					}
				}
			}
		}
		if (!empty($links)){
			$max = $this->fullDataRowCount();
			$start = ($this->getPage()*$this->getPageSize()+1);
			$end = (($this->getPage()+1)*$this->getPageSize());
			if ($end>$max) $end = $max;
			return '<div class="pagination">'
					 . 'Page '.($this->getPage()+1).' - Rows '.number_format($start).' to '.number_format($end).' of '.number_format($max).' - '	
					 . implode(', ',$links)
				 . '</div>';
		}
		return null;
	}
	
	public function display($attrs=array()){
		foreach ($this->columns as $column){
			if (isset($column['group'])){
				$this->column_groups = true;
				break;
			}
		}
		$pageLinks = $this->getPageLinks();
		return $pageLinks.parent::display($attrs).$pageLinks;
	}
	
	public static function getFieldValue($row,$col){
		if (!array_key_exists('field',$col)){
			Messages::msg('No \'field\' value was specified in the column info: '.var_export(ArrayWrapper::unwrap($col),true),Messages::M_CODE_ERROR);
			return null;
		}
		if ($row instanceof ArrayAccess){
			if (!$row->offsetExists($col['field'])){
				Messages::msg("The field '{$col['field']}' was not found. Available columns: ".implode(', ',array_keys($row)),Messages::M_CODE_ERROR);
				return null;
			}
		} else {
			if (!array_key_exists($col['field'],$row)){
				Messages::msg("The field '{$col['field']}' was not found. Available columns: ".implode(', ',array_keys($row)),Messages::M_CODE_ERROR);
				return null;
			}
		}
		
		return $row[$col['field']];
	}
	/*
	 * Generic callbacks
	 */
	public static function timestampCallback($row,$col){
		if ($timestamp = self::getFieldValue($row,$col)){
			if (is_numeric($timestamp)){
				$divider = (isset($col['break'])?$col['break']:'<br />');
				return date('d/m/Y',$timestamp).$divider.date('H:i:s',$timestamp);
			}
			Messages::msg("'$timestamp' is not a valid timestamp value.",Messages::M_CODE_WARNING);
		} 
		if (isset($col['ifnull'])){
			return $col['ifnull'];
		} else {
			return '-';
		}
	}
	public static function datetimeCallback($row,$col){
		return self::timestampCallback($row,$col);
	}
	public static function dateCallback($row,$col){
		if ($timestamp = self::getFieldValue($row,$col)){
			return date('d/m/Y',$timestamp);
		} else {
			return '-';
		}
	}
	public static function durationCallback($row,$col){
		$sigfigs = ( isset($col['sigfigs']) ? $col['sigfigs'] : 0 );
		return TextUtils::duration(self::getFieldValue($row,$col),$sigfigs);
	}
	public static function numberCallback($row,$col){
		$value = self::getFieldValue($row,$col);
		if (is_null($value)){
			return '-';
		}
		$decimals = ( isset($col['decimals']) ? $col['decimals'] : 0 );
		$result = number_format($value,$decimals);
		if (isset($col['prefix'])){
			$result = $col['prefix'].$result;
		}
		if (isset($col['suffix'])){
			$result = $result.$col['suffix'];
		}
		return $result;
	}
	public static function numericCallback($row,$col){
		return self::numberCallback($row,$col);
	}
	public static function currencyCallback($row,$col){
		if (isset($col['hidezeros']) && $col['hidezeros'] && $row->{$col['field']}==0) return '-';
		return TextUtils::currency(self::getFieldValue($row,$col),(isset($col['symbol'])?$col['symbol']:'&pound;'));
	}
	public static function htmlCurrencyCallback($row,$col){
		if (isset($col['hidezeros']) && $col['hidezeros'] && $row->{$col['field']}==0) return '-';
		return TextUtils::htmlCurrency(self::getFieldValue($row,$col),(isset($col['symbol'])?$col['symbol']:'&pound;'));
	}
	public static function skuCallback($row,$col){
		$value = self::getFieldValue($row,$col);
		return HTMLUtils::a(PIDDAL_DIR.'details.php?sku='.$value,$value,array('class'=>'sku'));
	}

	protected static function buildInputNameForCallback($row,$col){
		if (isset($col['name'])){
			$name = $col['name'];
			if (isset($col['namerowfield'])) {
				$name.= '['.$row[$col['namerowfield']].']';
			}
			$name.= '['.(isset($col['namecolfield'])?$col['namecolfield']:$col['field']).']';
		} else {
			$name = $col['field'];
		}
		return $name;
	}
	protected static function buildInputValueForCallback($row,$col){
		if (isset($col['value'])){
			$value = $col['value'];
		} else {
			$vfield = ( isset($col['valuefield']) ? $col['valuefield'] : $col['field'] );
			$value  = ( isset($row->$vfield)
				? $row->$vfield
				: ( isset($col['defaultvalue']) ? $col['defaultvalue'] : '' )
			);
		}
		return $value;
	}

	public static function textInputCallback($row,$col){
		$name = self::buildInputNameForCallback($row,$col);
		$value = self::buildInputValueForCallback($row,$col);
		$attrs = array();
		if (isset($col['size'])){
			$attrs['size'] = $col['size'];
		}
		return HTMLUtils::text($name,$value,$attrs);
	}
	public static function currencyInputCallback($row,$col){
		return (isset($col['symbol'])?$col['symbol']:'&pound;').self::textInputCallback($row, $col);
	}
	public static function percentageInputCallback($row,$col){
		return self::textInputCallback($row, $col).'%';
	}
	public static function checkboxCallback($row,$col){
		$name = self::buildInputNameForCallback($row,$col);
		$value = self::buildInputValueForCallback($row,$col);
		return HTMLUtils::checkbox($name,!empty($value));
	}
	public static function yesOrNoCallback($row,$col){
		return ( self::getFieldValue($row,$col) ? 'yes' : 'no' );
	}
	/**
	 * This is intended to be used with records attached to a ProgressMonitor
	 * @param array $row
	 * @param array $col
	 * @return string
	 */
	public static function statusCallback($row,$col){
		if ($row['Status']=='Processing'){
			$progress = $row['Progress'];
			if ($progress>=1) $progress = 0.999; // go to 99.9% rather than 100%, because if it was actually 100% then status would be 'Completed'
			return HTMLUtils::progressBar($progress);
		}
		return $row['Status'];
	}
	
}