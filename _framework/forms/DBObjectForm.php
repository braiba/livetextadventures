<?php

	class DBObjectForm {
				
		/** @var DBObject object */
		protected $object;
		
		/** @var SQLTable table */
		protected $table;
		
		/** @var string form method*/
		protected $method = 'post';
		/** @var string */
		protected $title = null;
		/** @var string form content (head)*/
		protected $head = '';
		/** @var string form content (body)*/
		protected $body = '';
		/** @var array action buttons*/
		protected $buttons = array();
		
		/**
		 * Construct a DBObjectForm
		 * @param DBObject $object 
		 */
		public function __construct(DBObject $object){
			$this->object = $object;
			$this->table  = $object->getTable();
		}
		
		/**
		 * Get the value for a given field in the form.
		 * @param string $field
		 * @return string 
		 */
		public function getValue($field){
			if (isset($_POST[$field])){
				return $_POST[$field];
			}
			if ($this->object->hasColumn($field)){
				return $this->object->$field;
			}
			return null;
		}
		
		/**
		 * Add an input field appropriate for a specified field on the form's DBObject
		 * @param string $field
		 * @param array $opts
		 * @param array $attrs 
		 */
		public function addInput($field,$opts=array(),$suffix='',$attrs=array()){
			if (empty($opts)){
				$opts = array();
			}
			if (Framework::isErrorField($field)){
				HTMLUtils::addClass($attrs,'error');
			}
			$label = ( isset($opts['label']) ? $opts['label'] : TextUtils::makeSQLFieldReadable($field) );
			$html = $this->table->getColumn($field)->generateInput($this->getValue($field),$opts,$attrs);
			$html = (isset($opts['prefix'])?$opts['prefix']:'').$html.(isset($opts['suffix'])?$opts['suffix']:'');
			$this->addHTML($html.$suffix,$label);
		}
		
		/**
		 *
		 * @param string $name
		 * @param string $value 
		 */
		public function addHiddenInput($name,$value){
			$this->head.= HTMLUtils::hidden($name, $value);
		}
		
		/**
		 * Adds a text input to the form
		 * @param string $name
		 * @param string $label
		 * @param array $attrs 
		 */
		public function addTextInput($name,$label=null,$suffix='',$attrs=array()){
			$text = HTMLUtils::text($name,$this->getValue($name), $attrs);
			$this->addHTML($text.$suffix,$label);
		}
		
		/**
		 * Adds a dropdown menu to the form
		 * @param string $name
		 * @param array $values
		 * @param string $label
		 * @param array $attrs 
		 */
		public function addSelectInput($name,$values,$label=null,$suffix='',$attrs=array()){
			$select = HTMLUtils::select($name, $values, $this->getValue($name), $attrs);
			$this->addHTML($select.$suffix,$label);
		}
		
		/**
		 * Adds a block of HTML to the form
		 * @param string $html
		 * @param string $label 
		 */
		public function addHTML($html,$label=null){			
			$this->body.= '<tr><td class="label">'. (isset($label)?htmlentities($label):'&nbsp;').'</td><td>'.$html.'</td></tr>';
		}
		
		/**
		 * Adds a block of HTML to the form that spans the space for both labels and values
		 * @param string $html
		 */
		public function addSpanningHTML($html,$label=null){
			$this->body.= '<tr><td colspan="2">'.$html.'</td></tr>';
		}
				
		/**
		 * Adds a divider to the form
		 * @param array $attrs 
		 */
		public function addDivider($attrs=array()){
			HTMLUtils::addStyle($attrs, 'margin', '12px 20%');
			$this->body.= '<tr><td colspan="2">'.HTMLUtils::tag('hr', null, $attrs).'</td></tr>';
		}
		
		/**
		 * Adds a submit button to the form
		 * @param string $label
		 * @param type $attrs 
		 */
		public function addSubmitButton($label = null,$attrs=array()){
			if (!isset($label)){
				$label = ($this->object->isNew() ? 'Create' : 'Update').' '.ucwords(TextUtils::makeCodeNameReadable(get_Class($this->object)));
			}
			$this->buttons[] = HTMLUtils::submit('submit', $label, $attrs);
		}
		
		/**
		 * Adds a submit button to the form
		 * @param string $label
		 * @param type $attrs 
		 */
		public function addResetButton($label = null,$attrs=array()){
			if (!isset($label)){
				$label = ($this->object->isNew() ? 'Clear' : 'Clear Changes');
			}
			$this->buttons[] = HTMLUtils::reset('submit', $label, $attrs);
		}
		
		/**
		 * Adds a submit button to the form
		 * @param string $href
		 * @param string $label
		 * @param type $attrs 
		 */
		public function addActionButton($href, $label, $attrs=array()){
			$attrs['onclick'] = 'window.location = "'.str_replace('"','\\"',$href).'";';
			$this->buttons[] = HTMLUtils::button(null, $label, $attrs);
		}
		
		/**
		 * Set form title
		 * @param string $title 
		 */
		public function setTitle($title){
			$this->title = $title;
		}
		
		/**
		 * Generate the form
		 * @param array $attrs
		 * @return string 
		 */
		public function display($attrs=array()){
			if (!isset($attrs['method'])){
				$attrs['method'] = $this->method;
			}
			HTMLUtils::addClass($attrs,'form');
			$content = $this->head;
			$content.= '<table style="width:100%">';
			if ($this->title){
				$content.= '<thead><tr><th colspan="3">'.$this->title.'</th></tr></thead>';
			}
			$content.= '<tbody>'.$this->body.'</tbody>';
			$content.= '<tfoot><tr><td colspan="2" class="buttons">'.implode(' ',$this->buttons).'</td></tr></tfoot>';
			$content.= '</table>';
			return HTMLUtils::tag('form', $content, $attrs);
		}
		
	}
	
	
?>
