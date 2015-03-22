<?php

class StoryController extends Controller {

	public function _init()
	{
		$this->setClass('story');
		$this->setDefaultAction('index');
	}
	
	public function index()
	{
		$stories = Story::findAll();
		$this->data['stories'] = $stories;
		$this->generatePage();
	}
	
	public function view()
	{
		$story = $this->getStoryFromUrl();
		
		$messages = Message::findWhere(
			'story_ID = ' . $story->storyID,
			'timestamp ASC'
		);
		
		$messagesOutput = array() ;
		foreach ($messages as $message) {
			$messagesOutput[] = array(
					'source' => $message->source,
					'message' => $message->message,
			);
		}
		
		$this->data['writer'] = $story->Writer->name;
		$this->data['player'] = $story->Player->name;
		$this->data['messages'] = $messagesOutput;
		$this->generatePage('view');
	}
	
	/**
	 * 
	 * @return Story
	 */
	protected function getStoryFromUrl()
	{
		$storyId = intval($this->getNextParam());
		if ($storyId === null) {
			die('denied (unknown story id)');
		}
		
		$story = new Story($storyId);
		if ($story->isNew()) {
			die('denied (no story id)');
		}
		return $story;
	}
}
