<?php

class SMSMessageHandler extends MessageHandler {

	protected $sms_number;

	public function __construct($filter,$sms_number){
		parent::__construct($filter);
		$this->sms_number = $sms_number;
	}
	public function msg($msg,$msg_level){
		// FRAMEWORK: Sending SMS messages
	}
	
}

?>