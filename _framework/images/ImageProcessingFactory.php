<?php
/**
 *
 * @author Thomas
 */
abstract class ImageProcessingFactory extends ImageFactory {
	
	/** @var Image the image being processed */
	protected $source;
	
	/**
	 *
	 * @param Image $source the input image
	 * @return Image the processed image
	 */
	public function getImageFromSource(Image $source){
		$this->setSource($source);
		return $this->getImage();
	}
	
	/**
	 * Sets the image to use as the source
	 * @param Image $source 
	 */
	protected function setSource(Image $source){
		$this->source = $source;
	}
	
}
?>