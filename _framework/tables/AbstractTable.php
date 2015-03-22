<?php

abstract class AbstractTable {

	protected $id = null;
	protected $collapsable = false;
	protected $empty_msg = 'There are no results to be displayed';

	protected $currRowType = '';
	protected $currCellType = '';

	public abstract function getHeadSize();
	public abstract function getFootSize();
	public abstract function hasRowLabels();
	public abstract function rowCount();
	public abstract function colCount();
	public abstract function getCell($x,$y);

	public function getTableClass(){return 'generated';}
	public function getBodySize(){return $this->rowCount()-$this->getHeadSize()-$this->getFootSize();}

	public function getId(){return $this->id;}
	public function setId($id){$this->id = $id;}
	public function isCollapsable(){return ($this->collapsable && !is_null($this->id));}
	public function setCollapsable($collapsable){
		if ($collapsable && is_null($this->id)){
			$this->id = uniqid('table_');
		}
		$this->collapsable = $collapsable;
	}

	/**
	 * Semi-abstract. Doesn't need to be implemented, but doesn't currently do anything either
	 * @param $y
	 * @return unknown_type
	 */
	protected function getRowClass($y){
		return null;
	}
	/**
	 * Semi-abstract. Doesn't need to be implemented, but doesn't currently do anything either
	 * @param $y
	 * @return unknown_type
	 */
	protected function getRowStyle($y){
		return null;
	}
	
	protected function rowOpen($y){
		$this->currRowType = 'tr';
		$attrs = array();
		if ($y==0 && $this->isCollapsable()){
			$attrs['onclick'] = 'toggle_collapse(\''.$this->id.'\')';
		}
		if ($class = $this->getRowClass($y)){
			$attrs['class'] = $class;
		}
		if ($style = $this->getRowStyle($y)){
			$attrs['style'] = $style;
		}
		return HTMLUtils::openTag($this->currRowType,$attrs);
	}
	protected function rowClose(){
		return "</$this->currRowType>";
	}
	protected function cellOpen($x,$y,$attrs=array()){
		$this->currCellType = ( ($x==0&&$this->hasRowLabels()) || $y<$this->getHeadSize() ?'th':'td');
		return HTMLUtils::openTag($this->currCellType,$attrs);
	}
	protected function cellClose(){
		if (is_null($this->currCellType)) return '';
		return "</$this->currCellType>";
	}

	protected function buildCell($x,$y,$attrs=array()){
		return $this->cellOpen($x,$y,$attrs).$this->getCell($x,$y).$this->cellClose();
	}
	protected function buildSection($rows,$tag='tbody',$offset=0){
		if ($rows==0) return '';
		$html = "<$tag>";
		for ($i=0; $i<$rows; $i++){
			$y = $i+$offset;
			$html.= $this->rowOpen($y);
			for ($x=0; $x<$this->colCount(); $x++){
				$html.= $this->buildCell($x,$y);
			}
			$html.= $this->rowClose();
		}
		$html .= "</$tag>";
		return $html;
	}

	public function setEmptyMessage($empty_msg){$this->empty_msg = $empty_msg;}
	public function getEmptyMessage(){return $this->empty_msg;}
	
	protected function buildEmptyBody(){
		$html = '<tbody><tr class="emptyrow">';
		$span = $this->colCount();
		if ($this->hasRowLabels()){
			$html.= '<th>-</th>';
			$span--;
		}
		$html.= '<td colspan="'.$span.'">'.$this->getEmptyMessage().'</td>';
		$html.= '</tbody>';
		return $html;
	}
	public function display($attrs=array()){
		if (!isset($attrs['cellpadding'])) $attrs['cellpadding'] = 0;
		if (!isset($attrs['cellspacing'])) $attrs['cellspacing'] = 0;
		if (!isset($attrs['id']) && isset($this->id)) $attrs['id'] = $this->id;
		if (!isset($attrs['class'])) $attrs['class'] = array();
		HTMLUtils::addClass($attrs,$this->getTableClass());

		$html = '';
		$y = 0;
		$curr_seg = null;

		$html.= $this->buildSection($this->getHeadSize(),'thead');
		// tfoot comes before tbody according to w3
		$html.= $this->buildSection($this->getFootSize(),'tfoot',$this->rowCount()-$this->getFootSize());
		if ($this->getBodySize()!=0){
			$html.= $this->buildSection($this->getBodySize(),'tbody',$this->getHeadSize());
		} else {
			$html.= $this->buildEmptyBody();
		}

		return HTMLUtils::tag('table',$html,$attrs);
	}
	
	public function saveAsCSV($filename){		
		$file = fopen($filename, 'w');
		for ($y=0; $y<$this->rowCount(); $y++){
			$rowData = array();
			for ($x=0; $x<$this->colCount(); $x++){
				$rowData[] = preg_replace('/\\v+/','',HTMLUtils::toPlainText($this->getCell($x,$y)));
			}
			fputcsv($file, $rowData);
		}
		fclose($file);
	}

}
?>
