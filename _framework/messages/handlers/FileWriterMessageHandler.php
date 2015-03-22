<?php

class FileWriterMessageHandler extends MessageHandler {

	protected $filename;

	public function __construct($filter,$filename){
		parent::__construct($filter);
		$this->filename = $filename;
	}

	public function msg($msg,$msg_level){
		if ($file = fopen($this->filename,'a')){
			fwrite($file,'['.date('j/n/Y H:i:s').'] '.TextUtils::truncate(Framework::getPageTitle(),32).' [ '.Framework::getPage().' ] ~ '.$msg."\r\n");
			fclose($file);
		} else {
			error_log("failed to open $this->filename");
		}
	}

}

?>