<?php

	/**
	 * Description of URLShortener
	 *
	 * @author Thomas
	 */
	class URLShortener {
		
		private static $version = '2.0.1';
		
		public static function shorten($url) {
			$bitly_url = 'http://api.bit.ly/shorten?version='.self::$version.'&longUrl='.urlencode($url).'&login='.URL_SHORTENER_USERNAME.'&apiKey='.URL_SHORTENER_API_KEY.'&format=json';
			$response = file_get_contents($bitly_url);
			if ($json = json_decode($response,true)){
				if ($json['errorCode']){
					Messages::msg($json['errorMessage'],Messages::M_CODE_ERROR);
					return null;
				}				
				return $json['results'][$url]['shortUrl'];
			}
			Messages::msg('Invalid response from '.$bitly_url.' : '.$response,Messages::M_CODE_ERROR);
			return null;
			
		}
		
	}

?>
