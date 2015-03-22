<?php
/**
 * Description of Writer
 *
 * @author Thomas
 * 
 * @property int $writer_ID
 * @property string $name
 * @property Story[] $Stories
 */
class Writer extends DBObject
{
	protected static $currentWriter = null;
	
	public function defineObject(DBObjectDefinition $def){
		$def->setTableName('writer');
		$def->hasMany('Story');
	}
	
	public static function setCurrent(Writer $writer)
	{
		$_SESSION['writer_id'] = $writer->writer_ID;
		self::$currentWriter = $writer;
	}
	
	/**
	 * 
	 * @return Writer
	 */
	public static function getCurrent()
	{
		if (self::$currentWriter === null) {
			if (isset($_SESSION['writer_id'])) {
				self::$currentWriter = new Writer($_SESSION['writer_id']);
			} else {
				self::$currentWriter = false;
			}
		}
		return self::$currentWriter ?: null;
	}
}
