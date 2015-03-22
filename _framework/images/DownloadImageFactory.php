<?php

class DownloadImageFactory extends ImageFactory {
	
	protected $source_url;
	protected $whitespace_error_margin = 16;
	
	public function getImageFromSource($url){
		$this->setSourceURL($url);
		return $this->getImage();
	}
	
	protected function setSourceURL($url){
		$this->source_url = $url;
	}
	
	protected function generateProcessData() {
		return '{downloaded:'.$this->source_url.'}';
	}

	protected function generateOutputFilename() {
		$url_root = preg_replace('/[?#].+$/','',$this->source_url);
		$info = pathinfo($url_root);
		if (empty($info['extension']) || !function_exists('imagecreatefrom'.$info['extension'])){
			$info['extension'] = ImageUtils::getTrueImageExt($this->source_url);
		}
		return FileUtils::sanitiseFilename($info['filename'].'.'.$info['extension']);
	}
	
	protected function generateImage() {
		$sql = 'SELECT 1 FROM external_url_errors WHERE url = '.SQLUtils::formatString($this->source_url).' AND timestamp > DATE_SUB(NOW(),INTERVAL 1 WEEK) LIMIT 1';
		if ($res = SQL::query($sql)->getOnly()){
			return null;
		}
		
		if ($handle = @fopen($this->source_url,'rb')){
			$data = '';
			while (!feof($handle)){
				$data.= fread($handle,1024);
			}
			$image = @imagecreatefromstring($data);
			if ($image){
				fclose($handle);
				return ImageUtils::cropWhitespace($image,16);
			}
			
		} 
		$sql = 'INSERT INTO external_url_errors (url,http_code)'
				 . ' VALUES ('.SQLUtils::formatString($this->source_url).',"UNKNOWN")'
				 . ' ON DUPLICATE KEY UPDATE http_code = VALUES(http_code), timestamp = NOW();';
		SQL::query($sql);
		Messages::msg('Image could not be downloaded: '.$this->source_url,Messages::M_CODE_ERROR);
				
		return null;
	}
		
}
	
?>
