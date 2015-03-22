<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class SQLSelect {

	protected $name;
	protected $res;
	protected $selected = null;

	protected $id_field;
	protected $display_field;
	protected $colour_field = null;

	protected $none_label;
	protected $other_label;
	protected $other_input_label;

	protected $empty_text = '[None Available]';
	protected $error_text = '[ERROR]';

	/**
	 *
	 * @param string $name
	 * @param SQLQueryResult|string $data
	 * @param string $id_field
	 * @param string $display_field
	 */
	public function __construct($name,$data,$id_field=null,$display_field=null,$selected=null){
		$this->name = $name;
		if ($data instanceof SQLQueryResult){
			$this->res = $data;
		} elseif (is_string($data)) {
			$this->res = SQL::query($data);
		} else {
			Messages::msg('Unknown data specified for SQLSelect',Messages::M_CODE_ERROR);
		}
		$this->id_field = $id_field;
		$this->display_field = $display_field;
		$this->selected = $selected;
	}

	public function setNoneLabel($none_label){
		if (preg_match('/^[a-zA-z0-9 _]+$/',$none_label)){
			$this->none_label = "[$none_label]";
		} else {
			$this->none_label = $none_label;
		}
	}
	public function setOtherLabel($other_label,$other_input_label){
		if (preg_match('/^[a-zA-z0-9 _]+$/',$other_label)){
			$this->other_label = "[$other_label]";
		} else {
			$this->other_label = $other_label;
		}
		$this->other_input_label = $other_input_label;
	}
	public function setSelected($selected){
		$this->selected = $selected;
	}


	public function setColourField($colour_field){
		$this->colour_field = $colour_field;
	}

	public function display($attrs=array()){
		$attrs['name'] = $this->name;
		if (!isset($attrs['id'])){
			$attrs['id'] = $this->name;
		}
		if (is_null($this->res) || !$this->res->success()){
			return '<span class="error_select_sql">'.$this->error_text.'</span>';
		}
		if ($this->res->isEmpty()){
			return '<span class="empty_select_sql">'.$this->empty_text.'</span>';
		}
		// Load defaults if we don't have anything
		if (is_null($this->id_field) || is_null($this->display_field)){
			$cols = $this->res->getColumnNames();
			if (is_null($this->id_field)) $this->id_field = $cols[0];
			if (is_null($this->display_field)) $this->display_field = $cols[1];
		}
		$values = array();

		$bg_colour = 'FFF';
		if (!is_null($this->none_label)){
			$options[] = HTMLUtils::option('null',$this->none_label);
		} else if (!is_null($this->colour_field)){
			$bg_colour = ($this->res->getFirst()->{$this->colour_field}?:'FFF');
		}
		foreach ($this->res as $row){
			if ( $is_selected = HTMLUtils::equal($row->{$this->id_field},$this->selected) ){
				$bg_colour = $row->{$this->colour_field};
			}
			$op_attrs = array();
			if (isset($this->colour_field)){
				if (!is_null($row->{$this->colour_field})){
					HTMLUtils::addStyle($op_attrs, 'background-color', '#'.$row->{$this->colour_field});
				} else {
					HTMLUtils::addStyle($op_attrs, 'background-color', '#FFF');
				}
			}
			$options[] = HTMLUtils::option($row->{$this->id_field},$row->{$this->display_field},$is_selected,$op_attrs);
		}

		HTMLUtils::addClass($attrs,$this->name);
		$attrs['onchange'] = 'onChange_colouredSelect(event)';
		if (isset($this->colour_field) && isset($bg_colour)){
			HTMLUtils::addStyle($attrs, 'background-color', '#'.$bg_colour);
		}
		if (isset($this->other_label)){
			$attrs['onchange'] = "document.getElementById('{$this->name}_other_span').style.display = (this.selectedIndex==".(sizeof($options))."?'inline':'none')";
			$options[] = HTMLUtils::option('other',$this->other_label,false);
		}
		$html = HTMLUtils::tag('select',implode('',$options),$attrs);
		if (!is_null($this->other_label)){
			$html.= HTMLUtils::tag('span','<br />'.$this->other_input_label.': '.HTMLUtils::text($this->name.'_other'),array('id'=>$this->name.'_other_span','class'=>'label','style'=>'display:none;'));
		}
		return $html;
	}

}

?>
