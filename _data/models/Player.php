<?php
/**
 * Description of Player
 *
 * @author Thomas
 * 
 * @property int $player_ID
 * @property string $name
 * @property Story[] $Stories
 */
class Player extends DBObject
{
	protected static $currentPlayer = null;
	
	public function defineObject(DBObjectDefinition $def){
		$def->setTableName('player');
		$def->hasMany('Story');
	}
	
	public static function setCurrent(Player $player)
	{
		$_SESSION['player_id'] = $player->player_ID;
		self::$currentPlayer = $player;
	}
	
	/**
	 * 
	 * @return Player
	 */
	public static function getCurrent()
	{
		if (self::$currentPlayer === null) {
			if (isset($_SESSION['player_id'])) {
				self::$currentPlayer = new Player($_SESSION['player_id']);
			} else {
				self::$currentPlayer = false;
			}
		}
		return self::$currentPlayer ?: null;
	}
}
