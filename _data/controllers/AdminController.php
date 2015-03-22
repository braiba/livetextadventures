<?php

class AdminController extends Controller {

	public function _init(){
		$this->setClass('admin');
		$this->setDefaultAction('index');
	}
	
	public function index()
	{
		$links = array();
		
		if (isset($_POST['players'])) {
			$sql = 'UPDATE story SET end_timestamp = NOW() WHERE end_timestamp IS NULL';
			SQL::query($sql);
			foreach ($_POST['players'] as $writerId => $playerName) {
				$player = new Player(array('name' => $playerName));
				$player->save();
				
				$story = new Story();
				$story->player_id = $player->player_ID;
				$story->writer_id = $writerId;
				$story->save();
				
				$links[$playerName] = '/lta/player/index/'.$player->player_ID;
			}
		}
		
		$this->data['links'] = $links;
		$this->data['writers'] = Writer::findAll();
		$this->generatePage();
	}
	
}

?>