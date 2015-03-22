<?php 
	
/**
 *
 * @author Thomas
 */
class ImageMagickThumbnailFactory extends ImageMagickProcessingFactory {
	
	protected $max_width;
	protected $max_height;
	
	public function __construct($max_width, $max_height) {
		parent::__construct();
		$this->max_width = $max_width;
		$this->max_height = $max_height;
	}
		
	public function generateProcessData() {
		return $this->source->process_data.'{resize to: '.$this->max_width.'x'.$this->max_height.'}';
	}
	
	public function generateOutputFilename() {
		$info = pathinfo($this->source->filename);
		return $info['dirname'].'/thumbnails/'.$info['filename'].'('.$this->max_width.'x'.$this->max_height.').'.$info['extension'];
	}
	
	public function getCommand($input_filename) {
		return 'convert "'.realpath($input_filename).'" -resize	'.$this->max_width.'x'.$this->max_height.' -';
	}

}
?>