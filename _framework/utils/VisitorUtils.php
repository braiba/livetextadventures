<?php

	/**
	 * Description of VisitorUtils
	 *
	 * @author thomas
	 */
	class VisitorUtils {

		private function __construct() {
			
		}
		
		public static function getCrawlerName(){
			$crawlers = array(
				'AdsBot'          => 'AdsBot-Google',
				'Alexa'           => 'ia_archiver',
				'Alta Vista'      => 'Scooter/',
				'Ask Jeeves'      => 'Ask Jeeves',
				'Baidu'           => 'Baiduspider',
				'BingBot'         => 'bingbot/',
				'CommonCrawl'     => 'CCBot/',
				'Discobot'        => 'discobot/',
				'Exabot'          => 'Exabot/',
				'Ezooms'          => 'Ezooms/',
				'FAST Enterprise' => 'FAST Enterprise Crawler',
				'FAST WebCrawler' => 'FAST-WebCrawler/',
				'Facebook'        => 'facebookexternalhit/',
				'FlipboardProxy'  => 'FlipboardProxy/',
				'Francis'         => 'http://www.neomo.de/',
				'Gigabot'         => 'Gigabot/',
				'Google Adsense'  => 'Mediapartners-Google',
				'Google Desktop'  => 'Google Desktop',
				'Google Feeds'    => 'Feedfetcher-Google',
				'Google'          => 'Googlebot',
				'Heise IT-Markt'  => 'heise-IT-Markt-Crawler',
				'Heritrix'        => 'heritrix/1.',
				'IBM Research'    => 'ibm.com/cs/crawler',
				'ICCrawler'       => 'ICCrawler - ICjobs',
				'ichiro'          => 'ichiro/2',
				'Jolibot'         => 'Jolibot/',
				'LongURL API'     => 'LongURL API',
				'Majestic-12'     => 'MJ12bot/',
				'Metager'         => 'MetagerBot/',
				'MSN NewsBlogs'   => 'msnbot-NewsBlogs/',
				'MSN'             => 'msnbot/',
				'MSNbot Media'    => 'msnbot-media/',
				'NG-Search'       => 'NG-Search/',
				'Nutch'           => 'http://lucene.apache.org/nutch/',
				'Nutch/CVS'       => 'NutchCVS/',
				'OmniExplorer'    => 'OmniExplorer_Bot/',
				'Online link'     => 'online link validator',
				'Paper.li Bot'    => 'PaperLiBot/',
				'psbot'           => 'psbot/0',
				'Seekport'        => 'Seekbot/',
				'Sensis'          => 'Sensis Web Crawler',
				'SEO Crawler'     => 'SEO search Crawler/',
				'Seoma'           => 'Seoma',
				'SEOSearch'       => 'SEOsearch/',
				'Snappy'          => 'Snappy/1.1 ( http://www.urltrends.com/ )',
				'Speedy Spider'   => 'Speedy Spider (',
				'Steeler'         => 'http://www.tkl.iis.u-tokyo.ac.jp/~crawler/',
				'Synoo'           => 'SynooBot/',
				'Telekom'         => 'crawleradmin.t-info@telekom.de',
				'TurnitinBot'     => 'TurnitinBot/',
				'TweetedTimes'    => 'TweetedTimes Bot/',
				'WiseGuys'        => 'Vagabondo/',
				'Voyager'         => 'voyager/1.0',
				'W3'              => 'W3 SiteSearch Crawler',
				'W3C'             => 'W3C-checklink/',
				'WiseNut'         => 'http://www.WISEnutbot.com',
				'YaCy'            => 'yacybot',
				'Yahoo MMCrawler' => 'Yahoo-MMCrawler/',
				'Yahoo Slurp'     => 'Yahoo! DE Slurp',
				'Yahoo'           => 'Yahoo! Slurp',
				'YahooSeeker'     => 'YahooSeeker/',
				'YandexBot'       => 'YandexBot',
			);
			if (!isset($_SERVER['HTTP_USER_AGENT'])){
				return null;
			}
			$user_agent = $_SERVER['HTTP_USER_AGENT'];
			foreach ($crawlers as $name => $identifier){
				if (stripos($user_agent,$identifier)!==false){
					return $name;
				}
			}
			return null;
		}
		
	}

?>
