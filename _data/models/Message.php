<?php
/**
 * Description of Message
 *
 * @author Thomas
 * 
 * @property int $message_ID
 * @property string $message
 * @property string $source
 * @property int $story_ID
 * @property Story $Story
 */
class Message extends DBObject
{
	protected static $currentMessage = null;
	
	public function defineObject(DBObjectDefinition $def){
		$def->setTableName('message');
		$def->belongsTo('Story');
	}
	
	public static function setCurrent(Message $message)
	{
		$_SESSION['message_id'] = $message->message_ID;
		self::$currentMessage = $message;
	}
	
	/**
	 * 
	 * @return Message
	 */
	public static function getCurrent()
	{
		if (self::$currentMessage === null) {
			if (isset($_SESSION['message_id'])) {
				self::$currentMessage = new Message($_SESSION['message_id']);
			} else {
				self::$currentMessage = false;
			}
		}
		return self::$currentMessage ?: null;
	}
}
