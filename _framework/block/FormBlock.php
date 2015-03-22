<?php

class FormBlock extends Block {
	
	protected $method = 'post';
	protected $action = '';
	protected $class = null;
	protected $submittext = 'Submit';
	protected $enctype = 'application/x-www-form-urlencoded';
	protected $overridesubmit = false;
	
	public function __construct($opts=array()){
		if (isset($opts['method'])){$this->method = $opts['method'];}
		if (isset($opts['action'])){$this->action = $opts['action'];}
		if (isset($opts['class'])){$this->class = $opts['class'];}
		if (isset($opts['submittext'])){$this->submittext = $opts['submittext'];}
		if (isset($opts['overridesubmit'])){$this->overridesubmit = $opts['overridesubmit'];}
		if (isset($opts['enctype'])){$this->enctype = $opts['enctype'];}
	}
	
	public function start(){ $this->buffer = HTMLUtils::openForm(array('class'=>$this->class,'method'=>$this->method,'action'=>$this->action,'enctype'=>$this->enctype)); }
	public function text($text){
		$this->buffer.= HTMLUtils::nl2p($text);
	}
	public function display($name,$reference=null,$opts=array()){
		$this->displayValue($name,$reference,$opts);
	}
	public function displayValue($name,$value,$opts=array()){
		$this->buffer.= '<p><b>'.$name.':</b>'.(isset($opts['multiline']) && $opts['multiline']?'</p><p>':' ').$value.'</p>'."\r\n";		
	}
	public function end($return=false){
		if (!$this->overridesubmit){
			$this->buffer.= '<p class="submit">'.HTMLUtils::submitInput('submit',$this->submittext).'</p>'."\r\n";
		}	
		$this->buffer.= HTMLUtils::closeForm(); 
		if ($return){ return $this->buffer; } else { echo $this->buffer; }
	}
		
}

?>