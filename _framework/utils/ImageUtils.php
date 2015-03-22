<?php
/**
 *
 * @author Thomas
 */
class ImageUtils {
	
	/**
	 * Breaks a colour value down into its component alpha, red, green and blue values
	 * @param int colour the colour value
	 * @return a 3-element int array containing the alpha, red, green and blue components in that order
	 */
	public static function colourToRGB($colour){
		$r = (0xFF0000 & $colour) >> 16;
		$g = (0x00FF00 & $colour) >> 8;
		$b = (0x0000FF & $colour);
		$res = array('r'=>$r,'g'=>$g,'b'=>$b);
		return $res;
	}
	
	/**
	 * Determines if two colours are the same, within a given error, where the error is defined as the distance 
	 *   between the two points obtained by representing the ARGB values of the colours as four dimensional 
	 *   Cartesian coordinates.<br />
	 * For reference, the maximum possible error between two colours with the same alpha value is 4072.023 (3dp).
	 * @param int $c1 the first colour
	 * @param int $c2
	 * @param int $error_margin
	 * @return boolean true if the colours match, otherwise false.
	 */
	public static function coloursMatch($c1, $c2, $error_margin){
		if ($c1==$c2) return true;
		if ($error_margin==0) return false;
		$rgb1 = self::colourToRGB($c1);
		$rgb2 = self::colourToRGB($c2);		
		$dist_sqrd =
			pow($rgb1['r']-$rgb2['r'],2) + 
			pow($rgb1['g']-$rgb2['g'],2) + 
			pow($rgb1['b']-$rgb2['b'],2) 
		;		
		return ($dist_sqrd<=pow($error_margin,2));		
	}
	
	/**
	 * Determines if the given pixel of an image matches a colour to a given margin of error. 
	 * @param resource $image the image
	 * @param int $x the x coordinate of the pixel being tested
	 * @param int $y the y coordinate of the pixel being tested
	 * @param int $colour the colour
	 * @param string $error_margin the error margin. See {@link #coloursMatch(int,int,double)} for information of the error margin calculation.
	 * @return boolean true if the pixel matches the given colour, otherwise false.
	 */
	public static function pixelIsColour($image, $x, $y, $colour, $error_margin=0){
		return self::coloursMatch(imagecolorat($image,$x,$y),$colour,$error_margin);
	}
		
	/**
	 * Determines if a given pixel in an image is white to a given margin of error. 
	 * @param resource $image the image
	 * @param int $x the x coordinate of the pixel being tested
	 * @param int $y the y coordinate of the pixel being tested
	 * @param int $error_margin the error margin. See {@link #coloursMatch(int,int,double)} for information of the error margin calculation.
	 * @return true if the pixel is white, otherwise false.
	 */
	public static function isWhitePixel($image, $x, $y, $error_margin=0){
		return self::pixelIsColour($image, $x, $y, 0xFFFFFF, $error_margin);
	}
	
	/**
	 * Returns a version of the image with the whitespace trimmed from the edge. Note that if nothing 
	 *   can be trimmed from the image, the original image will be returned, not a copy.
	 * @param resource $image the image
	 * @param int $error_margin the error margin to use in determining whether a pixel is white (see {@link #coloursMatch(int, int, double)})
	 * @return the trimmed image
	 */
	public static function cropWhitespace($image, $error_margin=0){
		$width = imagesx($image)-1;
		$height = imagesy($image)-1;
		$min_dim = ($width < $height ? $width : $height);

		//  - FIRST STAGE - 
		// diagonal lines from top left and bottom right identify the middle of the image
		
		// Diagonal line from top left until a non-white pixel is encountered
		$i=0;
		do {
			if (!self::isWhitePixel($image,$i,$i,$error_margin)){
				break;
			}				
		} while (++$i<$min_dim);
		$min_x = $i;
		$min_y = $i;

		// Diagonal line from bottom right until a non-white pixel is encountered
		$i=0;
		do {
			$x = $width-$i;
			$y = $height-$i;
			if ($x<=$min_x || $y<=$min_y || !self::isWhitePixel($image,$x,$y,$error_margin)){
				break;
			}				
		} while (++$i<$min_dim);
		$max_x = $width-$i;
		$max_y = $height-$i;

		// - SECOND STAGE - 
		// Scan the area outside this base middle for non-white pixels and expand to includ them
		
		// Scan the left edge for non-white pixels to the left of min_x
		for ($y=0; $y<$height; $y++){
			if ($min_x==0) break;
			for ($x=0; $x<$min_x; $x++){
				if (!self::isWhitePixel($image,$x,$y,$error_margin)){
					$min_x = $x;
				}
			}
		}

		// Scan the right edge for non-white pixels to the right of max_x
		for ($y=0; $y<$height; $y++){
			if ($max_x==$width) break;
			for ($x=$width; $x>$max_x; $x--){
				if (!self::isWhitePixel($image,$x,$y,$error_margin)){
					$max_x = $x;
				}
			}
		}

		// Scan the top edge for non-white pixels above of min_y
		for ($x=$min_x; $x<=$max_x; $x++){
			if ($min_y==0) break;
			for ($y=0; $y<$min_y; $y++){
				if (!self::isWhitePixel($image,$x,$y,$error_margin)){
					$min_y = $y;
				}
			}
		}

		// Scan the bottom edge for non-white pixels below max_y
		for ($x=$min_x; $x<=$max_x; $x++){
			if ($max_y==$height) break;
			for ($y=$height; $y>$max_y; $y--){
				if (!self::isWhitePixel($image,$x,$y,$error_margin)){
					$max_y = $y;
				}
			}
		}

		if ($max_x<=$min_x){
			$max_x = $min_x + 1;
		}
		if ($max_y<=$min_y){
			$max_y = $min_y + 1;
		}
		
		// - THIRD STAGE - 
		// (Potential algorithm)
		// Scan each line of each edge as a whole measuring its average distance from white
		// Do this until the distance increases by >500% in one step (in which case trim that edge to that point)
		//   or the average distance is more than error_margin (in which case expand that edge by number number of rows you've come in
		
		// MINOR: third stage
		
		if ( ($min_x!=0) || ($min_y!=0) || ($max_x!=$width) || ($max_y!=$height) ){
			$new_width  = $max_x-$min_x+1;
			$new_height = $max_y-$min_y+1;
			$trimmed = imagecreatetruecolor($new_width,$new_height);
			imagecopy($trimmed,$image,0,0,$min_x,$min_y,$new_width,$new_height);
			imagedestroy($image);
			$image = $trimmed;
		}
		
		return $image;
	}
	
	/**
	 * Gets the appropriate file extension for a file from its header data, rather than the filename
	 * @param string $filename
	 * @param string $default The value to return if the image type is not recognised
	 * @return string 
	 */
	public static function getTrueImageExt($filename,$default='jpg'){
		if (function_exists('exif_imagetype')){
			$type = exif_imagetype($filename);
		} else {
			$info = getimagesize($filename);
			$type = $info[2];
		}
		switch ($type){
			case IMAGETYPE_GIF: return 'gif';
			case IMAGETYPE_JPEG: return 'jpg';
			case IMAGETYPE_PNG: return 'png';
			case IMAGETYPE_SWF: return 'swf';
			case IMAGETYPE_PSD: return 'psd';
			case IMAGETYPE_BMP: return 'bmp';
			case IMAGETYPE_TIFF_II: return 'tiff';
			case IMAGETYPE_TIFF_MM: return 'tiff';
			case IMAGETYPE_JPC: return 'jpc';
			case IMAGETYPE_JP2: return 'jp2';
			case IMAGETYPE_JPX: return 'jpx';
			case IMAGETYPE_JB2: return 'jb2';
			case IMAGETYPE_SWC: return 'swc';
			case IMAGETYPE_IFF: return 'iff';
			case IMAGETYPE_WBMP: return 'wbmp';
			case IMAGETYPE_XBM: return 'xbm';
			case IMAGETYPE_ICO: return 'ico';
		}
		return $default;
	}
	
	public static function generateHash($image){
		$w = imagesx($image);
		$h = imagesy($image);
		
		$src = imagecreatetruecolor(4,4);
		imagecopyresampled($src, $image, 0, 0, 0, 0, 4, 4, $w, $h);
				
		$hash = '';
		for ($x = 0; $x < 4; $x++){
			for ($y = 0; $y < 4; $y++){
				$c = imagecolorat($src, $x, $y);
				$rgb = self::colourToRGB($c);
				$hash.= dechex($rgb['r']/16).dechex($rgb['g']/16).dechex($rgb['b']/16);
			}	
		}
		return strtoupper($hash);
	}
	
	public static function loadImage($filename){
		return imagecreatefromstring(file_get_contents($filename));
	}
	public static function saveImage($image,$filename){
		$ext = pathinfo($filename,PATHINFO_EXTENSION);
		switch (strtolower($ext)){
			case 'jpg':
				// falls through
			case 'jpeg':
				return imagejpeg($image,$filename);
				
			case 'png':
				return imagepng($image,$filename);
			
			case 'gif':
				return imagegif($image,$filename);
			
			case 'bmp':
				return imagewbmp($image,$filename);
			
			case 'gd':
				return imagegd($image,$filename);
			
			case 'gd2':
				return imagegd2($image,$filename);			
		}
		return false;
	}
	
}
?>