<?php
	
/**
 *
 * @author Thomas
 */
abstract class ImageFactory {
		
	public function __construct() {
		
	}
	
	protected abstract function generateProcessData();
	protected abstract function generateOutputFilename();
	protected abstract function generateImage();
	
	public function getImage(){
		$process_data = $this->generateProcessData();
		$sql = "SELECT * FROM images WHERE process_data = ".SQLUtils::formatString($process_data);		
		
		try {
			if ($row = SQL::query($sql)->getFirst()){ // TODO: this should be findOnly(), but for some reason even with the locking below we keep getting process_data duplicates
				return new Image($row);			
			}
		} catch (ImageDBObjectException $ex){
			// Image file has been deleted
		}
		
		$image = $this->generateImage();
		
		if ($image == null){
			Messages::msg(get_class($this).' failed to generate the image described by the following process data: '.$process_data,Messages::M_CODE_ERROR);
			return null;
		}
		$filename = $this->generateOutputFilename();
		
		$filename = FileUtils::getNewFile($filename,IMAGES_FOLDER);
		if (ImageUtils::saveImage($image,IMAGES_FOLDER.'/'.$filename)){
			// Before we save this to the database, check that another copy of this image hasn't been created while we were doing it
			$rec = null;	
			try {
				SQL::query('LOCK TABLES images WRITE');			
				$sql = "SELECT * FROM images WHERE process_data = ".SQLUtils::formatString($process_data);	
				try {
					if ($row = SQL::query($sql)->getOnly()){
						$rec = new Image($row);
						@unlink($filename);
					}
				} catch (ImageDBObjectException $ex){
					// Image file has been deleted
				}
				if (!$rec){
					$rec = Image::buildImageRecord($image, $filename, $process_data);
				}
				SQL::query('UNLOCK TABLES');	
			} catch (Exception $ex){
				// Catch all exceptions to make sure we call unlock tables before leaving this method
				SQL::query('UNLOCK TABLES');	// Unlock tables before throwing message otherwise we have lock issues
				Messages::msg($ex->getMessage(),Messages::M_CODE_ERROR);
				@unlink($filename);
				$rec = null;
			}
			imagedestroy($image);
			return $rec;
		} else {
			Messages::msg('Failed to save image: '.$filename,Messages::M_CODE_ERROR);
			return null;
		}
	}
	
}

?>