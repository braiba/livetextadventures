<?php
	
/**
 *
 * @author Thomas
 */
abstract class ImageMagickProcessingFactory extends ImageProcessingFactory {
	
	public abstract function getCommand($input_filename);
		
	public function generateImage() {
		$input_filename = IMAGES_FOLDER.'/'.$this->source->filename;
		$output_filename = FileUtils::getNewFile($this->generateOutputFilename(),IMAGES_FOLDER,45);
		$info = pathinfo($output_filename);	
		
		$command = $this->getCommand($input_filename);
		$output = array(
			array('pipe', 'r'), // stdin
			array('pipe', 'w'), // stdout
			array('pipe', 'w')  // stderr
		);
		$pipes = array();
		//dirname($_SERVER['PATH_TRANSLATED'])
		$process = proc_open($command, $output, $pipes, IMAGEMAGICK_PATH);
		if (is_resource($process))
		{
			// Close input pipe (allows process to start to run)
			fclose($pipes[0]);

			$stdout = stream_get_contents($pipes[1]);
			fclose($pipes[1]);
			
			$stderr = stream_get_contents($pipes[2]);
			fclose($pipes[2]);

			// Close Process
			proc_close($process);
						
			if (!empty($stderr)){
				Messages::msg('ImageMagick returned the following error: '.$stderr,Messages::M_CODE_ERROR);
			} else {
				return imagecreatefromstring($stdout);
			}
		} else {			
			Messages::msg('ImageMagick failed to execute the following command: '.$command,Messages::M_CODE_ERROR);
		}
		return null;
	}

}
