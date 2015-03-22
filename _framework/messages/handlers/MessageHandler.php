<?php

abstract class MessageHandler {
  
  protected $filter;
  
  public function setFilter($filter){$this->filter = $filter;}
  public function getFilter(){return $this->filter;}
  public function checkLevel($level){return (($level&$this->filter)!=0);}
  
  public function __construct($filter){$this->setFilter($filter);}
  
  public abstract function msg($msg,$msg_level);
  
}

?>