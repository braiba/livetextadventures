<?php

class HTMLUtils {

	private function __construct(){}

	const tag_internals_regex = '(?>(?<!\\\\)\'.+?(?<!\\\\)\'|(?<!\\\\)".+?(?<!\\\\)"|[^>"\']+)*';

	/**
	 * Generate a valid id value from a name value
	 * @param string $name
	 * @return string
	 */
	protected static function idFromName($name){
		return preg_replace(
			array(
				// If there are invalid characters at the end, we can just remove these...
				'/[^a-z0-9_\\-:.]+$/i',
				// ...but other invalid characters should be replaced with underscores for readability.
				'/(^[^a-z][^a-z0-9_\\-:.]*|[^a-z0-9_\\-:.]+)/i',
			),
			array(
				'',
				'_',
			)
			,$name
		);
	}
	
	public static function absolute($url){
		if (!preg_match('#^[a-z]+://#',$url)){
			$url = preg_replace('#^\\./#','http://'.$_SERVER['SERVER_NAME'].SITE_PATH.'/',$url,1,$count);
			if (!$count){
				$url = 'http://'.$_SERVER['SERVER_NAME'].'/'.$url;
			}
		}
		return $url;
	}

	/**
	 * Returns an associative array in the XML attribute format
	 * @param array $attrs An associative array where the keys are the attribute names and the values are the
	 *   attribute values. If an attributes value is an array, it will converted to a space seperated list
	 *   (primarily useful for style and class attributes)
	 * @return string The list
	 */
	public static function attrList($attrs){
		if (empty($attrs)) return '';
		$res = '';
		foreach ($attrs as $key=>$value){
			if (is_array($value)) {
				switch ($key){
					case 'class': $value = implode(' ',$value); break;
					case 'style': $value = TextUtils::implodePairs($value,' ','$key: $value;'); break;
				}
			}
			// For some reason &quote; justs displays as "&quote;" in attributes (tested in FF, IE, Chrome and Opera),
			//   so we have to use &#34; instead.
			$value = str_replace('"','&#34;',$value);
			$res.= " $key=\"$value\"";
		}
		return $res;
	}
	/**
	 *
	 * @param array $attrs
	 * @param string|array $classes An array or whitespace-seperated list of class names
	 */
	public static function addClass(&$attrs,$classes){
		if ( isset($attrs['class']) && !is_array($attrs['class']) ){
			$attrs['class'] = array($attrs['class']);
		}
		if (!is_array($classes)){
			$classes = preg_split('/\\t+/',$classes);
		}
		foreach ($classes as $class){
			if (!empty($class)){
				$attrs['class'][] = $class;
			}
		}
	}
	
	/**
	 *
	 * KNOWN ISSUE: If the existing style value is a string that contains a value that is a string with a semicolon in, this will break.
	 * @param array $attrs
	 * @param string $class The class name to be added
	 */
	public static function addStyle(&$attrs,$property,$value){
		if ( isset($attrs['style']) && !is_array($attrs['style']) ){
			$style_parts = explode(';',$attrs['style']);
			$styles = array();
			foreach ($style_parts as $part){
				if (!empty($part)){
					if (preg_match('/^\\s*([^:]+?)\\s*:\\s*(.+?)\\s*$/',$part,$match)){
						list(,$key,$value) = $match;
						$styles[$key] = $value;
					} else {
						Messages::msg("Style '$part' dropped. Not a valid style",Messages::M_CODE_ERROR);
					}
				}
			}
			$attrs['style'] = array($attrs['style']);
		}
		$attrs['style'][$property] = $value;
	}

	/**
	 * Creates a HTML tag
	 * @param string $name The tag name
	 * @param string $content The content of the tag. If this is null, the tag will be self-closing.
	 * @param array $attrs The attributes for the tag in the format used by self::attrList
	 * @return string
	 */
	public static function tag($name,$content=null,$attrs=array()){
		return "<$name".self::attrList($attrs).(!is_null($content)?">$content</$name>":' />');
	}
	public static function openTag($name,$attrs=array()){
		return "<$name".self::attrList($attrs).">";
	}

	public static function ul($items,$attrs=array()){
		$lis = array();
		foreach ($items as $item){
			if (is_null($item)){
				$lis[] = self::tag('li','&nbsp;',array('class'=>'spacer'));
			} else {
				$lis[] = self::tag('li',$item);
			}
		}
		return self::tag('ul',implode("\r\n",$lis),$attrs);
	}

	public static function getLink($link,$dir){
		if ($link=='' || $link=='#' || $link{0}=='/' || $link{0}=='.' || strpos($link,'://')!==false || strpos($link,'javascript:')!==false ){
			return $link;
		} else {
			return $dir.'/'.$link;
		}
	}

	/**
	 * Generates the HTML for a hyperlink
	 * @param string $href
	 * @param string $content
	 * @param array $attrs
	 * @return string
	 */
	public static function a($href,$content='&nbsp;',$attrs=array()){
		$attrs['href'] = self::getLink($href,'/'); 
		return self::tag('a',$content,$attrs);
	}
	public static function img($src,$alt,$attrs=array()){
		$attrs['src'] = self::getLink($src,IMAGES_FOLDER);
		$attrs['alt'] = $alt;
		if (!isset($attrs['title'])){
			$attrs['title'] = $alt;
		}
		return self::tag('img',null,$attrs);
	}

	public static function area($shape,$coords,$href,$title,$attrs=array()){
		$coord_count = sizeof($coords);
		$shape = strtolower($shape);
		switch ($shape){
			case 'rect':
				if ($coord_count!=4) {
					if ($coord_count<4){
						Messages::msg(TextUtils::plurality("'rect' areas require 4 coordinates (x1,y1,x2,y2), but only # (was|were) given.",$coord_count),Messages::M_CODE_ERROR);
						return null;
					}
					Messages::msg("'rect' areas require 4 coordinates (x1,y1,x2,y2), but $coord_count were given. The first 4 coords will be used, the rest will be discarded.",Messages::M_CODE_WARNING);
					$coords = array_slice($coords,0,4);
				}
				break;
			case 'circle':
				if ($coord_count!=3) {
					if ($coord_count<3){
						Messages::msg(TextUtils::plurality("'circle' areas require 3 coordinates (x,y,r), but only # (was|were) given.",$coord_count),Messages::M_CODE_ERROR);
						return null;
					}
					Messages::msg("'circle' areas require 3 coordinates (x,y,r), but $coord_count were given. The first 3 coords will be used, the rest will be discarded.",Messages::M_CODE_WARNING);
					$coords = array_slice($coords,0,3);
				}
				break;
			case 'poly':
				if ($coord_count<6) {
					Messages::msg(TextUtils::plurality("'poly' areas require at least 6 coordinates (x1,y1,x2,y2,x3,y3), but only # (was|were) given.",$coord_count),Messages::M_CODE_ERROR);
					return null;
				} else if ($coord_count % 2 == 1){
					Messages::msg("'poly' areas require an even number of coordinates (x1,y1,x2,y2,...,xn,yn), but $coord_count were given. The last value given will be discarded.",Messages::M_CODE_WARNING);
					$coords = array_slice($coords,0,$coord_count-1);
				}
				break;
			defaulut:
				Messages::msg("Unknown shape type, '$shape'.",Messages::M_CODE_ERROR);
				return null;
		}
		if (is_null($href)){
			$attrs['nohref'] = 'nohref';
		} else {
			$attrs['href'] = self::getLink($href,PAGE_DIR);
		}
		$attrs['shape'] = $shape;
		$attrs['coords'] = implode(',',$coords);
		$attrs['title'] = $title;
		return self::tag('area',null,$attrs);
	}
	
	public static function labelLine($label,$content){
		return self::tag('p',self::tag('span',$label,array('class'=>array('label'))).' '.$content);
	}
	
	/**
	 *
	 * @param array $links
	 * @param array $attrs
	 * @return string 
	 */
	public static function linkList($links,$attrs=array()){
		$items = array();
		$here = $_SERVER['PHP_SELF'].( isset($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : '' );
		self::addClass($attrs,'linklist');
		
		// First parse of the link list to work out which link is the longest one that matches $here
		$selected_index = -1;
		$selected_length = 0;
		foreach ($links as $i=>$linkinfo){
			if (is_null($linkinfo)) continue;
			$text =  ( isset($linkinfo['text']) ? $linkinfo['text'] : 'link');
			$href =  ( isset($linkinfo['href']) ? $linkinfo['href'] : null);
			if (empty($here)){
				if (empty($href)){
					$selected_index = $i;
				}
			} else if (!empty($href) && strpos($here,str_replace('./','',$href))!==false){
				if (strlen($href)>$selected_length){
					$selected_length = strlen($href);
					$selected_index = $i;
				}
			}
		}

		// Generate the links
		foreach ($links as $i=>$linkinfo){
			if (is_null($linkinfo)) {
				$items[] = null;
			} else {
				$text =  ( isset($linkinfo['text']) ? $linkinfo['text'] : 'link');
				$href =  ( isset($linkinfo['href']) ? $linkinfo['href'] : null);
				$link_attrs = ( isset($linkinfo['attrs']) ? $linkinfo['attrs'] :array());
				if (isset($linkinfo['onclick'])){
					$link_attrs['onclick'] = $linkinfo['onclick'];
				}
				if ($selected_index == $i){
					self::addClass($link_attrs,'selected');
				}
				if ($href!=null || isset($link_attrs['onclick'])){
					$items[] = self::a($href,$text,$link_attrs);
				} else {
					$items[] = self::tag('span',$text,$link_attrs);
				}
			}
		}
		
		return self::ul($items,$attrs);
	}

	public static function gridLayout($items,$grid_width,$attrs=array()){
		$trs = array();
		$tds = array();
		$x = 0;
		foreach ($items as $item){
			$tds[] = '<td>'.$item.'</td>';
			if (++$x == $grid_width){
				$x = 0;
				$trs[] = '<tr>'.implode('',$tds).'</tr>';
				$tds = array();
			}
		}
		if ($x!=0){
			while ($x++<$grid_width){
				$tds[] = '<td class="empty">&nbsp;</td>';
			}
			$trs[] = '<tr>'.implode('',$tds).'</tr>';
		}
		return HTMLUtils::tag('table',implode('',$trs),$attrs);
	}
	
	
	/**
	 * Possibly a little bit overkill, but it improves consistency
	 * @return string
	 */
	public static function br(){
		return self::tag('br')."\r\n";
	}

	/**
	 * Generates the HTML for a input field with the 'hidden' type.
	 * @param string $name The name of the field
	 * @param string $value The field's value
	 * @return string The generated HTML
	 */
	public static function hidden($name,$value,$attrs=array()){
		if (!array_key_exists('id',$attrs)) {
			$attrs['id'] = self::idFromName($name);
		}
		$attrs['name'] = $name;
		$attrs['value'] = $value;
		$attrs['type'] = 'hidden';
		return self::tag('input',null,$attrs);
	}

	/**
	 * Generates the HTML for a input field with the 'text' type.
	 * @param string $name The name of the field
	 * @param string $value The field's value
	 * @param array $attrs An array of other attributes for the generated tag (in the format used by self::attrList)
	 * @return string The generated HTML
	 */
	public static function text($name,$value='',$attrs=array()){
		if (!array_key_exists('id',$attrs)) {
			$attrs['id'] = self::idFromName($name);
		}
		$attrs['type'] = 'text';
		$attrs['name'] = $name;
		$attrs['value'] = $value;
		self::addClass($attrs,'text');
		if (preg_match('/^[a-z_][a-z0-9_\\-]*/i',$name,$match)){
			self::addClass($attrs,$match[0]);
		}
		return self::tag('input',null,$attrs);
	}

	/**
	 * Generates the HTML for a input field with the 'image' type.
	 * @param string $src The url of the image to be used
	 * @param string $alt The alt-text for the image
	 * @param string $name The name of the field
	 * @param string $value The field's value
	 * @param array $attrs An array of other attributes for the generated tag (in the format used by self::attrList)
	 * @return string The generated HTML
	 */
	public static function image($src,$alt,$name,$value='',$attrs=array()){
		if (!array_key_exists('id',$attrs)) {
			$attrs['id'] = self::idFromName($name);
		}
		$attrs['type'] = 'image';
		$attrs['src'] = self::getLink($src,IMAGES_FOLDER); 
		$attrs['alt'] = $alt;
		$attrs['name'] = $name;
		$attrs['value'] = $value;
		self::addClass($attrs,'text');
		self::addClass($attrs,$name);
		return self::tag('input',null,$attrs);
	}

	/**
	 * Generates the HTML for a input field with the 'radio' type.
	 * @param string $name The name of the field
	 * @param string $value The field's value
	 * @param boolean $selected If true, the radio box will be selected, otherwise it won't.
	 * @param array $attrs An array of other attributes for the generated tag (in the format used by self::attrList)
	 * @return string The generated HTML
	 */
	public static function radio($name,$value,$selected=false,$attrs=array()){
		if (!array_key_exists('id',$attrs)) {
			$attrs['id'] = self::idFromName($name);
		}
		$attrs['type'] = 'radio';
		$attrs['name'] = $name;
		$attrs['value'] = $value;
		if ($selected){
			$attrs['checked'] = 'checked';
		}
		self::addClass($attrs,'radio');
		return self::tag('input',null,$attrs);
	}

	/**
	 * Generates the HTML for a input field with the 'file' type.
	 * @param string $name The name of the field
	 * @param array $attrs An array of other attributes for the generated tag (in the format used by self::attrList)
	 * @return string The generated HTML
	 */
	public static function file($name,$attrs=array()){
		if (!array_key_exists('id',$attrs)) {
			$attrs['id'] = self::idFromName($name);
		}
		$attrs['type'] = 'file';
		$attrs['name'] = $name;
		self::addClass($attrs,'file');
		return self::tag('input',null,$attrs);
	}

	/**
	 * Generates the HTML for a option tag (i.e. an item in a 'select' tag).
	 * @param string $value The option's value
	 * @param string $text The options's displayed value (i.e. how it appears to the user)
	 * @param string $isSelected If true, the option will be selected, otherwise it won't
	 * @param array $attrs An array of other attributes for the generated tag (in the format used by self::attrList)
	 * @return string The generated HTML
	 */
	public static function option($value,$text=null,$isSelected=false,$attrs=array()){
		$attrs['value'] = $value;
		if ($isSelected){
			$attrs['selected'] = 'selected';
		}
		return self::tag('option',(is_null($text)?$value:$text),$attrs);
	}

	protected static function normaliseValue($value){
		if (is_array($value)){
			return array_map(array('self','normaliseValue'),$val);
		}
		// Note, numerics need to be weeded out before we start doing string comparisons, because 0=="string" is true (for some reason)
		if (is_numeric($value)){
			// It doesn't seem to be possible to detect if a numeric string is an int of float without regexes, but so long as
			//   the type for numbers is consistent across values we consider to be equal it doesn't really matter, so always casting
			//   to float is fine.
			return (float)$value;
		}
		switch ($value){
			case 'null' : return null;
			case 'true' : return true;
			case 'false':return false;
		}
		return $value;
	}
	/**
	 * Finds whether two values are equal within an HTML context. This isn't based on a recognised standard, just a certain degree
	 *   of common sense. Mostly this is for handling the fact that HTML forms can only submit strings, not numbers or null
	 * @param mixed $value1 The first value
	 * @param mixed $value2 The second value
	 * @return boolean Returns true if the values are considered equal and false otherwise.
	 */
	public static function equal($value1,$value2){
		return (self::normaliseValue($value1)===self::normaliseValue($value2));
	}

	/**
	 * Generates the HTML for a select input (i.e. a dropdown menu)
	 * @param string $name The name of the field
	 * @param array $values The values for the select box, in the format [value]=>[display value]. 
	 *   Alternatively an array of associateive arrays with the keys 'value', 'text', and, optionally 'attrs'
	 * @param mixed $selected The value of the option to be selected by default (note: if multiple options have values
	 *   that are non-strictly equal to this value, all of them will be marked as selected, in which case most browsers
	 *   will go with the last of these options)
	 * @param array $attrs An array of other attributes for the generated tag (in the format used by self::attrList)
	 * @return string The generated HTML
	 */
	public static function select($name,$values,$selected=null,$attrs=array()){
		if (!array_key_exists('id',$attrs)) {
			$attrs['id'] = self::idFromName($name);
		}
		$attrs['name'] = $name;
		$options = array();
		foreach ($values as $key=>$data){
			if (is_array($data)){				
				// Advanced option format
				$value = $data['value'];
				$text = $data['text'];
				$li_attrs = (isset($data['attrs']) ? $data['attrs'] : array() );
				$options[] = self::option($data['value'],$data['text'],self::equal($value,$selected),$li_attrs);
			} else {
				// Simple option format
				$value = $key;
				$text = $data;
				$options[] = self::option($value,$text,self::equal($value,$selected));
			}			
		}
		self::addClass($attrs,$name);
		self::addClass($attrs,'single_select');
		return self::tag('select',implode('',$options),$attrs);
	}

	
	/**
	 * Generates the SQL for a select tag, based on the result of an SQL query.
	 * @param string $name
	 * @param string|SQLQueryResult $data SQL query or result
	 * @param string $id_field
	 * @param string $display_field
	 * @param mixed $selected The value of the option to be selected by default (note: if multiple options have values
	 *   that are non-strictly equal to this value, all of them will be marked as selected, in which case most browsers
	 *   will go with the last of these options)
	 * @param string $noneLabel If specified, this will appear as the top option to represent a null choice.
	 * @param string $otherLabel If specified, this will add an option at the end of the list that allows the user to specify their own value
	 * @param string $otherInputLabel If specified, this will add an option at the end of the list that allows the user to specify their own value
	 * @param array $attrs An array of other attributes for the generated tag (in the format used by HTMLUtils::attrList)
	 * @return string The generated HTML
	 */
	public static function sqlSelect($name,$data,$id_field=null,$display_field=null,$selected=null,$noneLabel=null,$otherLabel=null,$otherInputLabel='New',$attrs=array()){
		$select = new SQLSelect($name,$data,$id_field,$display_field,$selected);
		if (!is_null($noneLabel)) $select->setNoneLabel($noneLabel);
		if (!is_null($otherLabel)) $select->setOtherLabel($otherLabel,$otherInputLabel);
		return $select->display($attrs);
	}
	
	/**
	 * Generates the HTML for a multiple-select input (i.e. a list)
	 * @param string $name The name of the field
	 * @param array $values The values for the select box, in the format [value]=>[display value]
	 * @param array $selected An array of the values of the option(s) to be selected by default
	 * @param array $attrs An array of other attributes for the generated tag (in the format used by self::attrList)
	 * @return string The generated HTML
	 */
	public static function multiSelect($name,$values,$selected=array(),$attrs=array()){
		if (!array_key_exists('id',$attrs)) {
			$attrs['id'] = self::idFromName($name);
		}
		$attrs['name'] = $name;
		$attrs['multiple'] = 'multiple';
		$options = array();
		foreach ($values as $value=>$text){
			$options[] = self::option($value,$text,in_array($value,$selected));
		}
		self::addClass($attrs,$name);
		self::addClass($attrs,'multi_select');
		return self::tag('select',implode('',$options),$attrs);
	}

	/**
	 * Generates the HTML for a button that redirects the user with javascript
	 * @param string $href The url for the button to link to
	 * @param string $text The button's value/display text
	 * @param array $attrs An array of other attributes for the generated tag (in the format used by self::attrList)
	 * @return string The generated HTML
	 */
	public static function button($name,$value,$attrs=array()){
		$attrs['name'] = $name;
		$attrs['type'] = 'button';
		$attrs['value'] = $value;
		self::addClass($attrs,$name);
		self::addClass($attrs,'button');
		return self::tag('input',null,$attrs);
	}

	/**
	 * Generates the HTML for a checkbox
	 * @param string $name The name of the checkbox
	 * @param string $checked If true, the checkbox will be selected, otherwise it won't
	 * @param array $attrs An array of other attributes for the generated tag (in the format used by self::attrList)
	 * @return string The generated HTML
	 */
	public static function checkbox($name,$checked=false,$attrs=array()){
		if (!array_key_exists('id',$attrs)) {
			$attrs['id'] = self::idFromName($name);
		}
		if (!array_key_exists('value',$attrs)) {
			$attrs['value'] = 1;
		}
		if ($checked){
			$attrs['checked'] = 'checked';
		}
		$attrs['name'] = $name;
		$attrs['type'] = 'checkbox';
		self::addClass($attrs,'checkbox');
		self::addClass($attrs,$name);
		return self::tag('input',null,$attrs);
	}

	/**
	 * Generates the HTML for a checkbox with an associated label
	 * @param string $name The name of the checkbox
	 * @param string $label The text to be used as a label for the checkbox
	 * @param boolean $checked If true, the checkbox will be selected, otherwise it won't
	 * @param array $attrs An array of other attributes for the generated tag (in the format used by self::attrList)
	 * @return string The generated HTML
	 */
	public static function labeledCheckbox($name,$label,$checked=false,$label_before=false,$attrs=array()){
		$label_attrs = array('for'=>(array_key_exists('id',$attrs)?$attrs['id']:self::idFromName($name)));
		$checkbox = self::checkbox($name,$checked,$attrs);
		$label = self::tag('label',$label,$label_attrs);
		if ($label_before){
			$content = $label.' '.$checkbox;
		} else {
			$content = $checkbox.' '.$label;
		}
		return self::tag('span',$content,array('class'=>'labeled_checkbox'));
	}

	/**
	 * Generates the HTML for a 'submit' input
	 * @param string $name The name of the input
	 * @param string $label The displayed text of the submit button
	 * @param string $value The value text of the submit button
	 * @param array $attrs An array of other attributes for the generated tag (in the format used by self::attrList)
	 * @return string The generated HTML
	 */
	public static function submit($name,$label='Submit',$value='Submit',$attrs=array()){
		$attrs['type'] = 'submit';
		if (!is_null($name)) $attrs['name'] = $name;
		if (!is_null($value)) $attrs['value'] = $value;
		self::addClass($attrs,'submit');
		self::addClass($attrs,$name);
		return self::tag('button',$label,$attrs);
	}

	/**
	 * Generates the HTML for a 'reset' input
	 * @param string $name The name of the input
	 * @param string $label The displayed text of the submit button
	 * @param string $value The value text of the submit button
	 * @param array $attrs An array of other attributes for the generated tag (in the format used by self::attrList)
	 * @return string The generated HTML
	 */
	public static function reset($name,$label='Reset',$attrs=array()){
		$attrs['type'] = 'reset';
		if (!is_null($name)) $attrs['name'] = $name;
		self::addClass($attrs,'reset');
		self::addClass($attrs,$name);
		return self::tag('button',$label,$attrs);
	}

	public static function textarea($name,$cols=24,$rows=8,$content='',$attrs=array()){
		if (!array_key_exists('id',$attrs)) {
			$attrs['id'] = self::idFromName($name);
		}
		$attrs['name'] = $name;
		$attrs['rows'] = $rows;
		$attrs['cols'] = $cols;
		if ($content===null){
			$content = ''; // Make sure textarea doesn't autoclose, because that doesn't work
		}
		return self::tag('textarea',$content,$attrs);
	}

	/**
	 *
	 * @param array $get
	 * @param boolean $unescaped
	 * @return string
	 */
	public static function buildGetQuery($get,$unescaped=false){
		$amp = ($unescaped?'&':'&amp;');
		$query = (empty($get)?'':'?'.http_build_query($get,'',$amp));
		$query = preg_replace('/(?<='.preg_quote($amp).'|\\?)([^=&]+)=('.preg_quote($amp).'|^)/','$1$2',$query);
		return $query;
	}

	/**
	 * Generates the HTML for two span tags (with classes 'label' and 'value' respectively) and wraps it in a paragraph tag.
	 * @param string $label The text to use as a label
	 * @param string $value The HTML to be used as the value
	 * @return string The generated HTML
	 */
	public static function value($label,$value){
		return self::tag('p',self::tag('span',$label.': ',array('class'=>'label')).self::tag('span',$value,array('class'=>'value')),array('class'=>'labeled_value'));
	}

	/**
	 * Generates the HTML for a button that redirects the user with javascript
	 * @param string $href The url for the button to link to
	 * @param string $text The button's value/display text
	 * @param array $attrs An array of other attributes for the generated tag (in the format used by self::attrList)
	 * @return string The generated HTML
	 */
	public static function linkButton($href,$text,$attrs=array()){
		$attrs['type'] = 'button';
		$attrs['value'] = $text;
		$attrs['onclick'] = "window.location.href = '".self::getLink($href,PAGE_DIR)."';";
		return self::tag('input',null,$attrs);
	}

	/**
	 * Generates the HTML for some text with a hint
	 * @param string $hint The hint text
	 * @param string $text The text to attach the hint to
	 * @param array $attrs An array of other attributes for the generated tag (in the format used by self::attrList)
	 * @return string The generated HTML
	 */
	public static function hintedText($hint,$text,$attrs=array()){
		$attrs['title'] = $hint;
		self::addClass($attrs,'hinted_text');
		return self::tag('span',$text,$attrs);
	}
	
	/**
	 * Returns a currency value in the correct format, as per self::currency, but also wraps it in a span tag with the class 'positive',
	 * 'negative' or 'zero' depending on the value given for easy formating (shared/styles.css contains colouring for these classes).
	 * @param number $value The amount, in either pounds or pence (determined by the $inPence argument)
	 * @param string $symbol A string to use for the pound sign (default = HTML pound character code)
	 * @param boolean $invert If true the 'positive' and 'negative' classes will be used in reverse
	 * @return string
	 */
	public static function currency($value,$symbol='&pound;',$invert=false,$attrs=array()){
		self::addClass($attrs,'currency');
		if ($value == 0){
			self::addClass($attrs,'zero');
		} else {
			self::addClass($attrs,($value>0 xor $invert)?'positive':'negative');
		}		
		return self::tag('span',TextUtils::currency($value,$symbol),$attrs);
	}	

	/**
	 * Removes tags from a specified string
	 * @param string $html The string to be stripped of tags
	 * @return The stripped string.
	 */
	public static function stripTags($html){
		// The contents of a tag can be broken down into the following units
		// a) something in quotes : (?<!\\\\)\'.+?(?<!\\\\)\'
		// b) something in double quotes : (?<!\\\\)".+?(?<!\\\\)"
		// c) other non-quote, non-closing brace characters : [^>"\']+
		// This avoids traps such as the following:
		//   <tag attr="<value>argument</value>">Hello WOrld</test>.
		return preg_replace('/<'.self::tag_internals_regex.'>/','',$html);
	}


	protected static function domTableToPlainText($dom_node){
		return '[TABLE]';
	}
	protected static function domIframeToPlainText($dom_node){
		Messages::msg('self::domIframeToPlainText needs to be implemented before iframes can be displayed as plain text.',Messages::M_CODE_WARNING);
		// FRAMEWORK: domIframeToPlainText
		return '';
	}
	protected static function domListToPlainText($dom_node,$numbered=false){
		$content = '';
		$i = 1;
		foreach ($dom_node->childNodes as $child_node){
			if ( ($child_node instanceof DOMElement) && (strtolower($child_node->tagName)=='li') ){
				$content.= ($numbered?str_pad($i,2,' ',STR_PAD_LEFT).'. ':' - ').trim(self::domNodeToPlainText($child_node))."\r\n";
				$i++;
			}
		}
		return $content;
	}

	public static function domNodeToPlainText($dom_node){
		if ($dom_node instanceof DOMText){
			$content = preg_replace('/\\v+/','',$dom_node->wholeText);
			// We should be able to do this by setting DOMDocument->encoding, but for some reason it doesn't work, so this is a workaround to stop us getting �s
			$content = mb_convert_encoding($content,'ISO-8859-1','UTF-8');
			$content = preg_replace('/\\h+/',' ',$content);
		} else if ($dom_node instanceof DOMElement){
			$tag = strtolower($dom_node->tagName);
				
			// Handle tags for which standard content processing isn't appropriate, either because we need to do something more
			//   complicated with the content, or because it's not anything that it going to be displayed and therefore
			//   we don't care about it.
			switch ($tag){
				case 'table' : return self::domTableToPlainText($dom_node);
				case 'iframe': return self::domIframeToPlainText($dom_node);
				case 'ul'	 : // Falls through
				case 'ol'	 : return self::domListToPlainText($dom_node,($tag=='ol'));
				case 'form'  : return "\r\n[this form cannot be displayed in plain text format]\r\n";
				case 'img'	 : // falls through // it's tempting to display the alt value here, but it's not really appropriate
				case 'script': // falls through
				case 'head'  : return '';
			}
				
			// Standard content processing
			$content = '';
			foreach ($dom_node->childNodes as $child_node){
				$content.= self::domNodeToPlainText($child_node);
			}
			switch ($tag){
				case 'a' :					
					if ($content!=='' && $dom_node->hasAttribute('href')){
						$href = $dom_node->getAttribute('href');
						if (stripos($href,'mailto:')===0){
							$href = substr($href,7);
						}
						if ($href!=trim($content)){
							$content.= " ($href)";
						}
					}
					return $content;
				case 'strong': // Falls through
				case 'b' : return "*{$content}*";
				case 'u' : return "_{$content}_";
				case 'em': // Falls through
				case 'i' : return "/{$content}/";
				case 'big':return strtoupper($content);
				case 'br': return "\r\n";
				case 'p' : return trim($content)."\r\n";
				case 'h1': // falls through
				case 'h2': // falls through
				case 'h3': // falls through
				case 'h4': // falls through
				case 'h5': // falls through
				case 'h6':
					$content = trim($content);
					return "\r\n  $content \r\n ".str_repeat('-',strlen(trim($content))+2)."\r\n";
				default: return $content;
			}
		} else {
			$content = '';
			if ($dom_node->hasChildNodes()){
				foreach ($dom_node->childNodes as $child_node){
					$content.= self::domNodeToPlainText($child_node);
				}
			}
		}
		return $content;
	}
	public static function toPlainText($html){
		if (!is_numeric($html) && empty($html)){
			return '';
		}
		$dom_document = @DOMDocument::loadHTML($html);
		return self::domNodeToPlainText($dom_document);
	}

	public static function nl2p($str){		
		return preg_replace("#(?<=^|[\r|\n]|</p>|</h\\d>)(?!\\s*<[p|h])([^\r\n]+?)(?=[\r\n]|<[ph]|$)#",'<p>$1</p>',$str);
	}
	
	protected static function classesToStyles_processNode(DOMNode $dom_node, CSSManager $css, $ancestory = ''){
		if ($dom_node instanceof DOMElement){
			if ($dom_node->hasAttribute('id')){
				$id = $dom_node->getAttribute('id');
			} else {
				$id = null;
			}
			if ($dom_node->hasAttribute('class')){
				$classes = array_map('trim',explode(' ',$dom_node->getAttribute('class')));
				sort($classes);
				$dom_node->removeAttribute('class');
			} else {
				$classes = array();
			}
			
			$taginfo = ($ancestory==''?'':$ancestory.'>').'['.$dom_node->tagName.(is_null($id)?'':'#'.$id).(empty($classes)?'':'.'.implode('.',$classes)).']';
			$styles = $css->getStyleData($taginfo,$classes);
			if (!empty($styles)){
				$style_attr = TextUtils::implodePairs($styles,' ','$key: $value;');
				if ($dom_node->hasAttribute('style')){
					$old_style = $dom_node->getAttribute('style');
					$style_attr = $style_attr.$old_style;
				}
				$dom_node->setAttribute('style',$style_attr);
			}
		} else {
			$taginfo = $ancestory;
		}
		if ($dom_node->hasChildNodes()){
			foreach ($dom_node->childNodes as $child_node){
				self::classesToStyles_processNode($child_node,$css,$taginfo);
			}
		}
	}

	public static function hexCode($char,$leading_char='%'){
		return $leading_char.strtoupper(str_pad(dechex(ord($char)),2,'0',STR_PAD_LEFT));
	}
	
	/**
	 * Handles the result of selecting the 'other' option from an dropdown created by self::selectSQL
	 *
	 * This is really more of an SQLRecord method, but it pairs with self::selectSQL, so I've put it here instead.
	 *  - Thomas
	 * @param string $table_name
	 * @param string $id_field
	 * @param string $name_field
	 * @param string $other_text
	 */
	public static function handleSelectOther($table_name,$id_field,$name_field,$other_text='Other'){
		if ($_POST['payment_terms_id']=='other'){
			if (!empty($_POST["{$id_field}_other"])){
				$res = SQL::query("SELECT $id_field FROM $table_name WHERE $name_field = ".SQLUtils::formatString($_POST["{$id_field}_other"]).' LIMIT 1');
				if ($row = $res->getOnly()){
					// Already exists
					$_POST[$id_field] = $row->$id_field;
					return true;
				} else {
					// Doesn't exist - Make it
					$record = new SQLRecord($table_name);
					$record->$name_field = $_POST["{$id_field}_other"];
					if ($record->insert()){
						$_POST[$id_field] = $record->$id_field;
						return true;
					} else {
						unset($_POST[$id_field]);
					}
				}
			} else {
				unset($_POST[$id_field]);
				Messages::msg("If you select \'[$other_text]\' from the dropdown list you must enter a value in the text box. If no text box appeared, check that JavaScript is enabled.",Messages::M_ERROR);
			}
		}
		return false;
	}

	/**
	 * Check the error code of an uploaded file and return any appropriate error messages.
	 * @param String $name The name of the file input field.
	 * @param boolean $optional If true, the file upload is optional and will not throw errors on UPLOAD_ERR_NO_FILE
	 * @return boolean True if the file was uploaded successfully, False otherwise.
	 */
	public static function validateFileUpload($name,$optional=false){
		$error_code = $_FILES[$name]['error'];
		if ($error_code==UPLOAD_ERR_OK) return true;
		switch ($error_code){
			case UPLOAD_ERR_INI_SIZE:
				Messages::msg('The uploaded file exceeds the upload_max_filesize directive in php.ini.',Messages::M_CODE_ERROR);
				Messages::msg('The uploaded file was too large.',Messages::M_ERROR);
				break;
			case UPLOAD_ERR_FORM_SIZE:
				Messages::msg('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',Messages::M_CODE_ERROR);
				Messages::msg('The uploaded file was too large.',Messages::M_ERROR);
				break;
			case UPLOAD_ERR_PARTIAL:
				Messages::msg('The uploaded file was only partially uploaded.',Messages::M_CODE_ERROR);
				Messages::msg('An error occured when attempting to upload the file. Please try again.',Messages::M_ERROR);
				break;
			case UPLOAD_ERR_NO_FILE:
				if (!$optional){
					Messages::msg('No file was uploaded.',Messages::M_ERROR);
				}
				break;
			case UPLOAD_ERR_NO_TMP_DIR:
				Messages::msg('Missing a temporary folder.',Messages::M_CODE_ERROR);
				Messages::msg('An error occured when attempting to upload the file. Please contact a system admin.',Messages::M_ERROR);
				break;
			case UPLOAD_ERR_CANT_WRITE:
				Messages::msg('Failed to write file to disk.',Messages::M_CODE_ERROR);
				Messages::msg('An error occured when attempting to upload the file. Please contact a system admin.',Messages::M_ERROR);
				break;
			case UPLOAD_ERR_EXTENSION:
				Messages::msg('File upload stopped by extension.',Messages::M_CODE_ERROR);
				Messages::msg('An error occured when attempting to upload the file. Please contact a system admin.',Messages::M_ERROR);
				break;
			default:
				Messages::msg('Unknown error code: '.$_FILES['upload']['error'],Messages::M_CODE_ERROR);
				Messages::msg('An error occured when attempting to upload the file. Please contact a system admin.',Messages::M_ERROR);
				break;
		}
		return false;
	}

	/**
	 * Breaks br tags out of p tags.
	 * @param string $html
	 * @return string
	 */
	public static function sanitiseCKEditorInput($html){
		$html = preg_replace('/(?<!<\\/p>|&#160;|&nbsp;|\\s)(?:&#160;|&nbsp;|\\s)*<br('.self::tag_internals_regex.')(?<! ) ?\\/>(?:&#160;|&nbsp;|\\s|(<br'.self::tag_internals_regex.' ?\\/>))+/','</p><p$1>$2',$html);
		$html = preg_replace('/(<p'.self::tag_internals_regex.'>)(?:&#160;|&nbsp;|\\s)*(<br'.self::tag_internals_regex.'\\/>)+/','$2$1',$html);
		return $html;
	}
	
	/**
	 * Converts empty p tags into br tags.
	 * @param string $html
	 * @return string
	 */
	public static function emptyLinesToBreaks($html){
		return preg_replace('/<p('.self::tag_internals_regex.')>(&#160;|&nbsp;|\\s)*<\\/p>/','<br$1 />',$html);
	}
	
	public static function debug_displayvar($var){
		switch (gettype($var)){
			case 'object':
				if ($var instanceof SQLRecord){
					if ($var->isNew()){
						return '[new '.$var->getTidyTableName().' record]';
					}
					$hint = 'PRIMARY KEY: '.TextUtils::implodePairs($var->getPrimaryKeyValues(),',','$key:$value');
					return  self::hintedText($hint, '['.$var->getTidyTableName().' record]');
				}
				return '['.get_class($var).' object]';
				break;
			case 'resource':
				return '['.get_resource_type($var).' resource]';
				break;
			case 'array':
				$text_els = array_map(
					function($value){
						if (is_string($value)){ // Handle string separately, because it also uses hinted text if we loop back to this function
							return var_export($value,true);
						} else {
							return self::debug_displayvar($value);
						}
					},
					$var
				);
				if (!is_associative_array($var)){
					$text = '('.implode(',',$text_els).')';
				} else {
					$text = '('.TextUtils::implodePairs($text_els,',','\'$key\'=>$value').')';
				}
				if (strlen($text)>24){
					return self::hintedText($text,'['.sizeof($var).' element array]');
				}
				return $text;				
			case 'string':
				if (strlen($var)>24){
					return self::hintedText(var_export($var,true),var_export(substr($var,0,24-3).'...',true));
				}
				// falls through
			default:
				return var_export($var,true);
		}
	}
	
	/**
	 *
	 * @param string $id The id of the text input field
	 * @param string $name
	 * @param int $date
	 * @param array $attrs
	 * @return string
	 */
	public static function dateSelect($id,$name,$date=null,$attrs=array()){
		if (is_null($date)) $date = time();
		$content = self::text($name, (empty($date)?'':date('d/m/Y',$date)),array('id'=>$id,'class'=>'datebox'));
		$content.= self::a(
			'#',
			self::img(SHARED_IMAGE_DIR.'/calendar.gif','[Date]',array('name'=>'imgCalendar','class'=>'calendar_image')),
			array(
				'onmouseover'=>'if (timeoutId) clearTimeout(timeoutId);return true;',
				'onmouseout' =>'if (timeoutDelay) calendarTimeout();',
				'onclick'    =>'g_Calendar.show(event,\''.$id.'\',false,\'dd/mm/yyyy\'); return false;'
			)
		);
		self::addStyle($attrs, 'white-space', 'nowrap');
		self::addClass($attrs, 'date_picker');
		return self::tag('span',$content,$attrs);
	}

	protected static function hiddenGETvalue($key,$value){
		if (is_array($value)){
			$values = array();
			foreach ($value as $subkey=>$subvalue){
				$values[] = self::hiddenGETvalue("{$key}[{$subkey}]", $subvalue);
			}
			return implode("\r\n",$values);
		} else {
			return self::hidden($key, $value);
		}
	}
	public static function hiddenGET($keys=null){
		$values = array();
		if (is_null($keys)){
			foreach ($_GET as $key=>$value){
				$values[] = self::hiddenGETvalue($key,$value);
			}
		} else {
			foreach ($keys as $key){
				if (isset($_GET[$key])){
					$values[] = self::hiddenGETvalue($key,$_GET[$key]);
				}
			}
		}
		return implode("\r\n",$values);
	}

	private static function timeSelect_buildTimeValues($min,$max,$step){
		$values = array_map(
			function($value){
				return str_pad($value,2,'0',STR_PAD_LEFT);
			},
			range($min,$max,$step)
		);
		return array_combine($values,$values);
	}

	public static function timeSelect($name,$selected=null,$minute_step=5,$second_step=null,$use_12h=false){
		$html = '';
		$h = null;
		$m = null;
		$s = null;
		$ampm = null;
		if (!is_null($selected)){
			if (preg_match('/([0-1]?[0-9]|2[0-3]):([0-5][0-9])(?:\\:([0-5][0-9]))?/',$selected,$match)){
				list(,$h,$m) = $match;
				if (isset($match[3])){
					$s = $match[3];
				}
				if ($use_12h){
					if ($h>12){
						$h-= 12;
						$ampm = true;
					} else {
						$ampm = false;
					}
				}
			} else {
				Messages::msg('Invalid time format in timeSelect(): '.$selected,Messages::M_CODE_ERROR);
			}
		}
		if ($use_12h){
			$hour_values = self::timeSelect_buildTimeValues(1,12,1);
		} else {
			$hour_values = self::timeSelect_buildTimeValues(0,23,1);
		}
		$html.= self::select("{$name}[hour]", $hour_values, $h );
		if (!is_null($minute_step)){
			$html.= ':'.self::select("{$name}[minute]", self::timeSelect_buildTimeValues(00,59,$minute_step), $m );
			if (!is_null($second_step)){
				$html.= ':'.self::select("{$name}[second]", self::timeSelect_buildTimeValues(00,59,$second_step), $s );
			} else {
				$html.= self::hidden("{$name}[second]", 00);
			}
		} else {
			$html.= self::hidden("{$name}[minute]", 00);
			$html.= self::hidden("{$name}[second]", 00);
		}
		if ($use_12h){
			$html.= '&nbsp;'.self::select("{$name}[ampm]", array('am','pm'), $ampm );
		}
		return $html;
		
	}

	public static function parseTimeSelectData($array){
		if (isset($array['ampm'])){
			$array['hour'] = ($array['hour']+12) % 24;
		}
		return $array['hour'].':'.$array['minute'].':'.$array['second'];
	}

	protected static function formatNodeContentAsTree($dom_node,$child_indent,$indent_str){
		$tag_name = strtolower($dom_node->nodeName);
		$content = '';
		$text_content = '';
		$is_inline_node = in_array($tag_name,array('u','i','b'));
		$prev_was_inline_node = false;
		foreach ($dom_node->childNodes as $child_node){
			if ($child_node instanceof DOMText && preg_match('/^\\s+$/',$child_node->wholeText)){
				// Ignore emptyt text nodes
				continue;
			}
			$child_tag_name = strtolower($child_node->nodeName);
			$child_is_inline_node =  ( ($child_node instanceof DOMText) || in_array($child_tag_name,array('u','i','b')) );
			if (!$is_inline_node){
				if ($child_is_inline_node){
					if (!$prev_was_inline_node){
						$content.= "\r\n".$child_indent;
					}
				} else {
					$content.= "\r\n";
				}
			}
			if ($child_is_inline_node){
				$text_content.= self::formatNodeAsTree($child_node,$child_indent,$indent_str);
			} else {
				if ($prev_was_inline_node){
					$content.= wordwrap($text_content,128-strlen($child_indent),"\r\n".$child_indent);
					$text_content = '';
				}
				$content.= self::formatNodeAsTree($child_node,$child_indent,$indent_str);
			}
			$prev_was_inline_node = $child_is_inline_node;
		}
		if (!empty($text_content)){
			$content.= wordwrap($text_content,128-strlen($child_indent),"\r\n".$child_indent);
		}
		$close = "</{$dom_node->tagName}>";

		$tag = strtolower($dom_node->tagName);
		if (in_array($tag_name,array('br','input')) && empty($content)){
			$content = null;
		}
		return $content;
	}

	protected static function formatNodeAsTree($dom_node,$indent="",$indent_str="\t"){
		$child_indent = $indent.$indent_str;
		if ($dom_node instanceof DOMText){
			$content = preg_replace('/\\v+/','',$dom_node->wholeText);
			return preg_replace('/\\h+/',' ',$content);
		} else if ($dom_node instanceof DOMElement){
			$attrs = array();
			foreach ($dom_node->attributes as $attr){
				$attrs[$attr->name] = $attr->value;
			}

		$tag_name = strtolower($dom_node->nodeName);
			$is_inline_node = in_array($tag_name,array('u','i','b'));
			$content = self::formatNodeContentAsTree($dom_node,$child_indent,$indent_str);
			
			if ($is_inline_node){
				return self::tag($dom_node->tagName, $content, $attrs);
			} else {
				if (!empty($content)){
					$content.= "\r\n".$indent;
				}
				return $indent.self::tag($dom_node->tagName, $content, $attrs);
			}
		} else {
			$content = '';
			echo get_class($dom_node);
			if ($dom_node->hasChildNodes()){
				foreach ($dom_node->childNodes as $child_node){
					$content.= self::formatAsTree($child_node);
				}
			}
		}
		return $content;
	}

	public static function formatAsTree($html,$default_depth=0,$indent_str="\t"){
		$dom_document = @DOMDocument::loadHTML($html);
		return self::formatNodeContentAsTree($dom_document->documentElement->firstChild, str_repeat($indent_str,$default_depth),$indent_str);
		$html = '';
		foreach ($dom_document->documentElement->firstChild->childNodes as $node){
			$html.= self::formatNodeAsTree($node, str_repeat($indent_str,$default_depth),$indent_str)."\r\n";
		}
		return $html;
	}

	public static function cssClass($selector,$styles,$indent='  ',$indent_level=1){
		if (is_array($selector)){
			$selector = implode(",$indent",$selector);
		}
		$base_indent = str_repeat($indent, $indent_level);

		$css = "{$base_indent}{$selector}{\r\n";
		foreach ($styles as $key=>$value){
			$css.= "{$base_indent}{$indent}{$key}: {$value};\r\n";
		}
		$css.= "{$base_indent}}";
		return $css;
	}

	public static function tagCode($code){
		$comment_list = array();
		$code = preg_replace_callback(
			'~(//|#).+$~m',
			function($match) use (&$comment_list){
				$replace = '{{{COMMENT_'.sizeof($comment_list).'}}}';
				$comment_list[] = $match[0];
				return $replace;
			},
			$code
		);
		$str_list = array();
		$code = preg_replace_callback(
			'/(["\'])(?:[^\\\\]|\\\\.)*?\\1/s',
			function($match) use (&$str_list){
				$replace = '{{{STRING_'.sizeof($str_list).'}}}';
				$str_list[] = $match[0];
				return $replace;
			},
			$code
		);

		$code = preg_replace_callback(
			'/\\b([0-9]+(\\.[0-9]+)?)\\b/',
			function($match) {
				return '<span class="number">'.$match[0].'</span>';
			},
			$code
		);

		$keywords = array(
			'abstract','as','break','case','catch','const','continue','declare','default','die','do','echo','else(if)?',
			'empty','end(declare|for(each)?|if|switch|while)','exit','eval','extends','final','for(each)?','function','global',
			'goto','if','include(_once)?','implements','interface','instanceof','isset','list','namespace','new','or','print',
			'private','protected','public','return','require(_once)?','static','switch','throw','try','use','unset',
			'var','while','xor',
		);
		$code = preg_replace_callback(
			'/\\b('.implode('|',$keywords).')\\b/',
			function($match) {
				return '<span class="keyword">'.$match[0].'</span>';
			},
			$code
		);

		$code = preg_replace_callback(
			'/\\$[a-z]\\w*/',
			function($match) {
				return '<span class="variable">'.$match[0].'</span>';
			},
			$code
		);
			

		$code = preg_replace_callback(
			'/{{{STRING_([0-9]+)}}}/',
			function($match) use ($str_list){
				return '<span class="string">'.$str_list[$match[1]].'</span>';
			},
			$code
		);
		$code = preg_replace_callback(
			'/{{{COMMENT_([0-9]+)}}}/',
			function($match) use ($comment_list){
				return '<span class="comment">'.$comment_list[$match[1]].'</span>';
			},
			$code
		);

		return $code;
	}

}




?>
