<?php
/**
 * Description of Story
 *
 * @author Thomas
 * 
 * @property int $story_ID
 * @property int $player_id
 * @property int $writer_id
 * @property string $start_timestamp
 * @property string $end_timestamp
 * @property Player $Player
 * @property Writer $Writer
 * @property Message $Messages
 */
class Story extends DBObject
{
	protected static $currentStory = null;
	
	public function defineObject(DBObjectDefinition $def){
		$def->setTableName('story');
		$def->belongsTo('Writer');
		$def->belongsTo('Player');
		$def->hasMany('Message');
	}
	
	/**
	 * 
	 * @return Story[]
	 */
	public static function getCurrent()
	{
		return Story::findWhere('end_timestamp IS NULL');
	}
}
