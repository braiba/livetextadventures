<?php
	
/**
 * 
 * @param int image_ID
 * @param int timestamp
 * @param String filename
 * @param int width
 * @param int height
 * @param String process_data
 * @param String image_hash 
 * @author Thomas
 */
class Image extends DBObject {
		
	public function __construct($data=null){
		parent::__construct($data);
		if (!$this->isNew()){
			$file = IMAGES_FOLDER.'/'.$this->filename;
			if (!file_exists($file)){
				$id = $this->id;
				$this->delete();
				throw new ImageDBObjectException('Image record #'.$id.' has been deleted because the associated file does not exist');
			}
		}
	}
	
	public function defineObject(DBObjectDefinition $def){
		// Nothing to define for Image
	}
	
	public static function buildImageRecord($image,$filename,$process_data){
		$rec = new Image();
		$rec->width = imagesx($image);
		$rec->height = imagesy($image);
		$rec->filename = $filename;
		$rec->process_data = $process_data;
		$rec->image_hash = ImageUtils::generateHash($image);
		$rec->save();
		return $rec;
	}
	
	private $image = null;
	/**
	 * Get the image as a PHP image resource.
	 * @return resource Returns an image resource identifier for this record's image
	 */
	public function getImage(){
		if ($this->image==null){
			if ($this->image = ImageUtils::loadImage(IMAGES_FOLDER.'/'.$this->filename)){
				$width = imagesx($this->image);
				$height = imagesy($this->image);
				if ($this->width!=$width || $this->height!=$height){
					Messages::msg('The database had recorded the dimensions of '.$this->filename.' as '.$this->width.'x'.$this->height.', but they are actually '.$width.'x'.$height.'. The database record will be updated.',Messages::M_CODE_WARNING);
					$this->width = $width;
					$this->height = $height;
					$this->save();
				}
			}
		}
		return $this->image;		
	}
	
	public function freeImage(){
		if ($this->image!=null){
			imagedestroy($this->image);
			$this->image = null;
		}
	}
	
	public function trim($error_margin=32) {
		$image= $this->getImage();
		// Load existing image
		if ($image==null){
			throw new IOException("Image not loaded - cannot be trimmed");
		}
		
		// Process image
		$image = ImageUtils::cropWhitespace($image,$error_margin);			

		$width = imagesx($image);
		$height = imagesy($image);
		// Stop if image not changed
		if ($width==$this->width && $height==$this->height) {			
			return;
		}
		
		// Update record
		$this->freeImage();
		$this->image = $image;
		if (!ImageUtils::saveImage($image, IMAGES_FOLDER.'/'.$this->filename)){
			throw new IOException("Trimmed image could not be saved");
		}
		$this->width = $width;
		$this->height = $height;
		$this->save();

		// Clear images generated from the untrimmed image
		$sql = 'SELECT *'
			 . ' FROM images'
			 . ' WHERE process_data LIKE '.SQLUtils::formatString('{'.$this->filename.':resize into %}')
			 . ' OR process_data LIKE '.SQLUtils::formatString($this->process_data.'{%');
		foreach (SQL::query($sql) as $row){
			unlink($row->filename);
			SQL::query('DELETE FROM images WHERE image_ID = '.$row->image_ID);
		}
			
	}
	
	/**
	 *
	 * @param int $max_width
	 * @param int $max_height 
	 */
	public function getThumbnail($max_width,$max_height){
		if ($max_width>$this->width && $max_height>$this->height){
			return $this;
		}
		$factory = new ImageMagickThumbnailFactory($max_width,$max_height);
		return $factory->getImageFromSource($this);
	}
	
	
	public function displayAsLinkBox($width,$height,$href,$title){
		ob_start();
		?>
			<table class="image_box" style="width: <?php echo ($width+2);?>px; height: <?php echo ($height+2);?>px;">
				<tbody>
					<tr>
						<td>
							<?php 
								echo $this; 
							?>
						</td>
					</tr>
				</tbody>
			</table>
		<?php
		return ob_get_clean();
	}
	
	public static function displayEmptyBox($width,$height,$url=null){
		ob_start();
		?>
			<table class="image_box empty" style="width: <?php echo $width;?>px; height: <?php echo $height;?>px;">
				<tbody>
					<tr>
						<td>
							<?php
								if (!empty($url)){
									echo HTMLUtils::a($url,'&nbsp;',array('style'=>'display:inline-block; width:'.$width.'px; height:'.$height.'px;'));
								} else {
									echo '&nbsp;';
								}
							?>
						</td>
					</tr>
				</tbody>
			</table>
		<?php
		return ob_get_clean();
	}
	
	public function generateHTML($alt_text,$absolute=false,$attrs=array()){
		$attrs['width'] = $this->width;
		$attrs['height'] = $this->height;
		$href = $this->filename;
		if ($absolute){
			$href = HTMLUtils::absolute(IMAGES_FOLDER.'/'.$href);
		}
		if (!isset($attrs['title'])){
			$attrs['title'] = $alt_text;
		}
		return HTMLUtils::img($href, $alt_text, $attrs);
	}
	
	public function __toString(){
		$alt_text = TextUtils::makeCodeNameReadable(pathinfo($this->filename,PATHINFO_FILENAME));
		$alt_text = preg_replace('/\\s*\\([^)]*\\)/','',$alt_text);
		return $this->generateHTML($alt_text);
	}
	
}

?>