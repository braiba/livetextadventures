<?php

class JumbotronController extends Controller {

	public function _init(){
		$this->setClass('jumbotron');
		$this->setDefaultAction('index');
	}
	
	public function index()
	{
		$stories = Story::findWhere('end_timestamp IS NULL');
		
		$this->data['stories'] = $stories;
		$this->generatePage();
	}
	
	public function ajax()
	{
		$timestamp = (int)$_GET['timestamp'];
		$messages = Message::findWhere(
			'timestamp > ' . SQLUtils::formatTimestamp($timestamp),
			'timestamp ASC'
		);
		$lastTimestamp = $timestamp;
		
		$messagesOutput = array() ;
		foreach ($messages as $message) {
			$timestamp = strtotime($message->timestamp);
			$messagesOutput[] = array(
					'story_id' => $message->story_ID,
					'source' => $message->source,
					'message' => htmlentities($message->message),
					'timestamp' => $timestamp,
			);
			$lastTimestamp = $timestamp;
		}
		
		$output = array(
				'messages' => $messagesOutput,
				'lastTimestamp' => $lastTimestamp,
		);
		
		die(json_encode($output));
	}
	
}

?>