<?php

	/**
	 * Description of Tweeter
	 *
	 * @author Thomas
	 */
	class Tweeter {
		
		public static function tweet($tweet){
			$tmhOAuth = new tmhOAuth(
				array(
					'consumer_key'    => TWITTER_CONSUMER_KEY,
					'consumer_secret' => TWITTER_CONSUMER_SECRET,
					'user_token'      => TWITTER_USER_TOKEN,
					'user_secret'     => TWITTER_USER_SECRET,
				)
			);
			
			$url = $tmhOAuth->url('1/statuses/update');
			$params = array(
				'status' => mb_convert_encoding($tweet,'UTF-8'),
			);
			
			$code = $tmhOAuth->request('POST',$url,$params);
						
			if ($code!=200){
				Messages::msg('Tweeting failed with an unknown error. Error code: #'.$code.'. '.$tmhOAuth->response['response'],Messages::M_CODE_ERROR);
				return false;
			}
			
			$response = json_decode($tmhOAuth->response['response']);
			if (isset($response->error)){
				Messages::msg('Tweeting failed with the following error: '.$response->error,Messages::M_CODE_ERROR);
				return false;
			}
			return true;
		}
		
	}

?>
