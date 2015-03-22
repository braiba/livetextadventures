<?php

	/**
	 * Description of ImageDBObjectException
	 *
	 * @author THomas
	 */
	class ImageDBObjectException extends DBObjectException {

		public function __construct($message, $code=0, $previous=null) {
			parent::__construct('Image',$message,$code,$previous);
		}

	}

?>
