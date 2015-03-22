<?php

class WriterController extends Controller {

	public function _init(){
		$this->setClass('writer');
		$this->setDefaultAction('index');
	}
	
	public function index()
	{
		$writerId = $this->getNextParam();
		$writer = new Writer($writerId);
		if ($writer->isNew()) {
			die('denied (no writer id)');
		}
		
		$stories = Story::findWhere('writer_ID = ' . $writerId, 'start_timestamp DESC', 1);
		if (empty($stories)) {
			die('denied (no story)');
		}
		$story = reset($stories);
		
		$this->data['writerId'] = $writerId;
		$this->data['storyId'] = $story->story_ID;
		$this->generatePage();
	}
	
	public function ajax()
	{
		$writerId = $this->getNextParam();		
		$storyId = $this->getNextParam();
		$story = new Story($storyId);
		if ($story->writer_id != $writerId) {
			die('denied (incorrect story)');
		}
		
		$timestamp = (isset($_GET['timestamp']) ? (int)$_GET['timestamp'] : 0);
		$output = $this->getMessageData($storyId, $timestamp);
		
		die(json_encode($output));
	}
	
	public function message()
	{
		$storyId = $this->getNextParam();
		$messageText = $_POST['message'];
		
		$message = new Message();
		$message->story_ID = $storyId;
		$message->message = htmlentities($messageText);
		$message->source = 'writer';
		$message->save();
	}
	
	protected function getMessageData($storyId, $timestamp)
	{
		$messages = Message::findWhere(
			'story_ID = ' . $storyId . ' AND timestamp > ' . SQLUtils::formatTimestamp($timestamp),
			'timestamp ASC'
		);
		$lastTimestamp = $timestamp;
		
		$messagesOutput = array() ;
		foreach ($messages as $message) {
			$timestamp = strtotime($message->timestamp);
			$messagesOutput[] = array(
					'source' => $message->source,
					'message' => $message->message,
					'timestamp' => $timestamp,
			);
			$lastTimestamp = $timestamp;
		}
		
		return array(
			'messages' => $messagesOutput,
			'lastTimestamp' => $lastTimestamp,
		);
	}
	
}

?>