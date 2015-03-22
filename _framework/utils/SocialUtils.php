<?php

	/**
	 * SQL Utilities class
	 * @author Thomas
	 */
	class SocialUtils {

		private static $SHARE_PATTERN_DIGG     = 'http://digg.com/submit?url=[[[url]]]';
		private static $SHARE_PATTERN_FACEBOOK = 'http://www.facebook.com/share.php?u=[[[url]]]';
		private static $SHARE_PATTERN_MYSPACE  = 'http://www.myspace.com/Modules/PostTo/Pages/?l=3&amp;u=[[[url]]]&amp;t=[[[title]]]';
		private static $SHARE_PATTERN_REDDIT   = 'http://reddit.com/submit?url=[[[url]]]&amp;title=[[[title]]]';
		private static $SHARE_PATTERN_STUMBLE  = 'http://stumbleupon.com/submit?url=[[[url]]]&amp;title=[[[title]]]';
		private static $SHARE_PATTERN_TWITTER  = 'http://twitter.com/intent/tweet?url=[[[url]]]&amp;text=[[[title]]]&amp;via=postabargain';
		
		private function SocialUtils() {}
		
		protected static function buildShareURL($url_pattern,$url,$title){
			$url = HTMLUtils::absolute($url);
			$title = urlencode(mb_convert_encoding($title,'UTF-8'));	
			return str_replace(array('[[[title]]]','[[[url]]]'), array($title,$url), $url_pattern);			
		}		
		/**
		 * Generate a URL that allows the visitor to share a page on Digg
		 * @param string $url The URL to share 
		 * @param string $title The title to use when sharing this URL
		 * @return string the sharing URL 
		 */
		public static function buildDiggShareURL($url,$title){
			return self::buildShareURL(self::$SHARE_PATTERN_DIGG, $url, $title);
		}
		/**
		 * Generate a URL that allows the visitor to share a page on their Facebook account
		 * @param string $url The URL to share 
		 * @param string $title The title to use when sharing this URL
		 * @return string the sharing URL 
		 */
		public static function buildFacebookShareURL($url,$title){
			return self::buildShareURL(self::$SHARE_PATTERN_FACEBOOK, $url, $title);
		}
		/**
		 * Generate a URL that allows the visitor to share a page on their MySpace account
		 * @param string $url The URL to share 
		 * @param string $title The title to use when sharing this URL
		 * @return string the sharing URL 
		 */
		public static function buildMySpaceShareURL($url,$title){
			return self::buildShareURL(self::$SHARE_PATTERN_MYSPACE, $url, $title);
		}
		/**
		 * Generate a URL that allows the visitor to share a page on Reddit
		 * @param string $url The URL to share 
		 * @param string $title The title to use when sharing this URL
		 * @return string the sharing URL 
		 */
		public static function buildRedditShareURL($url,$title){
			return self::buildShareURL(self::$SHARE_PATTERN_REDDIT, $url, $title);
		}
		/**
		 * Generate a URL that allows the visitor to share a page through StumpleUpon
		 * @param string $url The URL to share 
		 * @param string $title The title to use when sharing this URL
		 * @return string the sharing URL 
		 */
		public static function buildStumbleUponShareURL($url,$title){
			return self::buildShareURL(self::$SHARE_PATTERN_STUMBLE, $url, $title);
		}
		/**
		 * Generate a URL that allows the visitor to tweet about a page
		 * @param string $url The URL to share 
		 * @param string $title The title to use when sharing this URL
		 * @return string the sharing URL 
		 */
		public static function buildTwitterShareURL($url,$title){
			return self::buildShareURL(self::$SHARE_PATTERN_TWITTER, $url, $title);
		}
		
		protected static function buildShareLink($share_url,$text,$hint,$class,$attrs=array(),$link_attrs=array()){
			HTMLUtils::addClass($attrs,$class);	
			$link_attrs['title'] = $hint;
			$link_attrs['target'] = '_blank';
			return HTMLUtils::tag('div',HTMLUtils::a($share_url,$text,$link_attrs),$attrs);
		}
		/**
		 * Generate a link that allows the visitor to share a page on Digg
		 * @param string $url The URL to share 
		 * @param string $title The title to use when sharing this URL
		 * @param type $text The link text
		 * @param type $hint The hint text for the link
		 * @param type $class The CSS class to apply to the wrapping div
		 * @param array $attrs additional attributes for the wrapping div
		 * @param array $link_attrs additional attributes for the link
		 * @return string the generated link 
		 */
		public static function buildDiggShareLink($url,$title,$text='digg',$hint='Digg this',$class='digg',$attrs=array(),$link_attrs=array()){
			$share_url = self::buildDiggShareURL($url, $title);
			return self::buildShareLink($share_url, $text,$hint,$class,$attrs,$link_attrs);
		}
		/**
		 * Generate a link that allows the visitor to share a page on their Facebook account
		 * @param string $url The URL to share 
		 * @param string $title The title to use when sharing this URL
		 * @param type $text The link text
		 * @param type $hint The hint text for the link
		 * @param type $class The CSS class to apply to the wrapping div
		 * @param array $attrs additional attributes for the wrapping div
		 * @param array $link_attrs additional attributes for the link
		 * @return string the generated link 
		 */
		public static function buildFacebookShareLink($url,$title,$text='like',$hint='Like on Facebook',$class='facebook_like',$attrs=array(),$link_attrs=array()){
			$share_url = self::buildFacebookShareURL($url, $title);
			return self::buildShareLink($share_url, $text,$hint,$class,$attrs,$link_attrs);
		}
		/**
		 * Generate a link that allows the visitor to share a page on their MySpace account
		 * @param string $url The URL to share 
		 * @param string $title The title to use when sharing this URL
		 * @param type $text The link text
		 * @param type $hint The hint text for the link
		 * @param type $class The CSS class to apply to the wrapping div
		 * @param array $attrs additional attributes for the wrapping div
		 * @param array $link_attrs additional attributes for the link
		 * @return string the generated link 
		 */
		public static function buildMySpaceShareLink($url,$title,$text='space',$hint='Post to MySpace',$class='myspace',$attrs=array(),$link_attrs=array()){
			$share_url = self::buildMySpaceShareURL($url, $title);
			return self::buildShareLink($share_url, $text,$hint,$class,$attrs,$link_attrs);
		}
		/**
		 * Generate a link that allows the visitor to share a page on Reddit
		 * @param string $url The URL to share 
		 * @param string $title The title to use when sharing this URL
		 * @param type $text The link text
		 * @param type $hint The hint text for the link
		 * @param type $class The CSS class to apply to the wrapping div
		 * @param array $attrs additional attributes for the wrapping div
		 * @param array $link_attrs additional attributes for the link
		 * @return string the generated link 
		 */
		public static function buildRedditShareLink($url,$title,$text='reddit',$hint='Reddit this',$class='reddit',$attrs=array(),$link_attrs=array()){
			$share_url = self::buildRedditShareURL($url, $title);
			return self::buildShareLink($share_url, $text,$hint,$class,$attrs,$link_attrs);
		}
		/**
		 * Generate a link that allows the visitor to tweet about a page
		 * @param string $url The URL to share 
		 * @param string $title The title to use when sharing this URL
		 * @param type $text The link text
		 * @param type $hint The hint text for the link
		 * @param type $class The CSS class to apply to the wrapping div
		 * @param array $attrs additional attributes for the wrapping div
		 * @param array $link_attrs additional attributes for the link
		 * @return string the generated link 
		 */
		public static function buildStumbleUponShareLink($url,$title,$text='stumble',$hint='StumbleUpon this',$class='stumble',$attrs=array(),$link_attrs=array()){
			$share_url = self::buildStumbleUponShareURL($url, $title);
			return self::buildShareLink($share_url, $text,$hint,$class,$attrs,$link_attrs);
		}
		/**
		 * Generate a link that allows the visitor to tweet about a page
		 * @param string $url The URL to share 
		 * @param string $title The title to use when sharing this URL
		 * @param type $text The link text
		 * @param type $hint The hint text for the link
		 * @param type $class The CSS class to apply to the wrapping div
		 * @param array $attrs additional attributes for the wrapping div
		 * @param array $link_attrs additional attributes for the link
		 * @return string the generated link 
		 */
		public static function buildTwitterShareLink($url,$title,$text='tweet',$hint='Tweet',$class='tweet',$attrs=array(),$link_attrs=array()){
			$share_url = self::buildTwitterShareURL($url, $title);
			return self::buildShareLink($share_url, $text,$hint,$class,$attrs,$link_attrs);
		}
		
		public static function buildAllShareLinks($url,$title){
			return self::buildTwitterShareLink($url,$title)
				 . self::buildFacebookShareLink($url,$title)
				 . self::buildDiggShareLink($url,$title)
				 . self::buildMySpaceShareLink($url,$title)
				 . self::buildRedditShareLink($url,$title)
				 . self::buildStumbleUponShareLink($url,$title);
		}
		
	}
			
?>