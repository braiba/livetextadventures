<?php

class ErrorLogMessageHandler extends MessageHandler {

	public function msg($msg,$msg_level){
		$msg_info = TextUtils::truncate(Framework::getPageTitle(),32).' ('.Framework::getPage().')';
		if ($msg_level & Messages::M_DEBUG){
			$ex = new Exception();
			$msg.= "\r\n".($ex->getTraceAsString());
		}
		error_log($msg_info.' ~ '.$msg);
	}

}

?>