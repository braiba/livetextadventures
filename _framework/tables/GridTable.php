<?php

class GridTable {
	
	protected $width;
	protected $cells;
	
	public function GridTable($width = 5){
		$this->width = $width;
	}
	
	public function addCell($cell){
		$this->cells[] = $cell;
	}
	
	public function display($attrs=array()){
		$td_attrs = array('width'=>round(100/$this->width,1).'%');
		$trs = array();
		$tds = array();
		foreach ($this->cells as $i=>$cell){
			if ($i%$this->width==0){
				if (!empty($tds)){
					$trs[] = HTMLUtils::tag('tr',implode('',$tds));
				}
				$tds = array();
			}
			$tds[] = HTMLUtils::tag('td',$cell,$td_attrs);
		}
		while (++$i%$this->width != 0){
			$tds[] = HTMLUtils::tag('td','&nbsp;',$td_attrs);
		}
		if (!empty($tds)){
			$trs[] = HTMLUtils::tag('tr',implode('',$tds));
		}
		
		$tbody = HTMLUtils::tag('tbody',implode('',$trs));
		HTMLUtils::addClass($attrs,'grid_table');
		return HTMLUtils::tag('table',$tbody,$attrs);
	}
	
	
}

?>
