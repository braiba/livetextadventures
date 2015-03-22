<?php

class Framework {

	/** @var Framework */
	private static $instance = null;
	
	/** @var Controller */
	protected $controller = null;
	/** @var string */
	protected $page = null;
	/** @var array */
	protected $path = array();
	/** @var array */
	protected $error_fields = array();
	/** @var array */
	protected $tableinfo = array();
	/** @var array */
	protected $core_keywords = array();
	/** @var int */
	protected $start_time;
	
	private function __construct(){
		$this->start_time = microtime(true);
	}
	
	/**
	 * @return Framework
	 */
	public static function getInstance(){
		if (is_null(self::$instance)){
			self::$instance = new Framework();
			include_once('./_data/config/'.str_replace('.','_',$_SERVER['SERVER_NAME']).'_settings.php');
			require_once('./_data/config/settings.php');
		}
		return self::$instance;
	}
		
	public static function handleRequest(){		
		Session::start();	
		$url = (isset($_GET['request_url']) ? $_GET['request_url'] : '');
		if (preg_match('/^[^?]+/',$url,$match)){
			$url = $match[0];
		}
		Framework::loadPage($url);	
	}

	public static function useLibrary($lib){
		addToIncludePath(FRAMEWORK_ROOT.'/lib/'.$lib);
	}
	
	/**
	 *
	 * @return type 
	 */
	public static function getPageTitle(){
		$instance = self::getInstance();
		if ($instance->controller){
			return $instance->controller->getTitle();
		} else {
			return '';
		}
	}

	public static function addCoreKeyword($keyword){
		$instance = self::getInstance();
		$instance->core_keywords[] = $keyword;
	}
	public static function addCoreKeywords($keywords){
		$instance = self::getInstance();
		$instance->core_keywords = array_merge($instance->core_keywords,$keywords);
	}
	public static function getCoreKeywords(){
		$instance = self::getInstance();
		return $instance->core_keywords;
	}
	
	protected static function linkToResource($page,$resourceFolder,$absolute=false){
		$url = './'.(!empty($resourceFolder)?$resourceFolder.'/':'').$page;
		if ($absolute){
			$url = HTMLUtils::absolute($url);
		}
		return $url;
	}
	public static function linkTo($page,$absolute=false){
		return self::linkToResource($page,'',$absolute);
	}
	public static function linkToCSS($page,$absolute=false){
		return self::linkToResource($page,CSS_FOLDER,$absolute);
	}
	public static function linkToJS($page,$absolute=false){
		return self::linkToResource($page,JS_FOLDER,$absolute);
	}
	public static function linkToImage($page,$absolute=false){
		return self::linkToResource($page,IMAGE_FOLDER,$absolute);
	}
	public static function linkToGET($page,$get=array(),$retainget=false,$absolute=false){
		$getvals = $retainget ? $_GET : array();
		foreach ($get as $key=>$value){
			$getvals[$key] = $value;
		}
		unset($getvals['request_url']);
		if (!empty($getvals)) {
			$query = '?'.http_build_query($getvals);
		} else {
			$query = '';
		}
		return self::linkTo($page.$query,$absolute);
	}
	
	public static function redirectTo($page,$status_code = HTTP_STATUS_SEE_OTHER){
		if (!preg_match('#^[a-z]+://#',$page)){
			$page = SITE_PATH.'/'.$page;
		}
		while (ob_get_level()) ob_end_clean();
		header('Status: '.$status_code);
		header('location: '.$page);
		switch ($status_code){
			case HTTP_STATUS_SEE_OTHER:
				echo 'You should now be redirected. If your browser does not redirect you automatically, click '.HTMLUtils::a($page,'here');
				break;
		}
		exit();
	}
		
	/**
	 * Same as redirectTo, but also stores a reference to the current page for redirecting to after the user logs on.
	 * @param string $page 
	 */
	public static function divertTo($page,$status_code = HTTP_STATUS_SEE_OTHER){
		Session::setValue(SESSION_LOGIN_REDIRECT,str_replace(SITE_PATH,'',$_SERVER['REQUEST_URI']));
		self::redirectTo($page,$status_code);
	}
	
	public static function redirectToReferer($fallback = '',$status_code = HTTP_STATUS_SEE_OTHER){
		$page = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $fallback;
		$currentPage = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REDIRECT_URL'];
		if ($page == $currentPage){
			$page = $fallback;
		}
		self::redirectTo($page,$status_code);
	}

	public static function displayMessages(){
		DisplayMessageHandler::displayAll();
	}


	public static function loadPage($url){
		try {
			$framework = self::getInstance();

			// Handle path
			$url = str_replace(dirname($_SERVER['PHP_SELF']).'/','', $url);
			$url = trim($url,'/');

			if (empty($url)){
				$url = DEFAULT_PAGE;
			}
			$framework->page = $url;
			$framework->path = (empty($url)?array():explode('/',$url));
			if (defined('ALWAYS_CONTROLLER')){
				array_unshift($framework->path,ALWAYS_CONTROLLER);
			}
			$controller_name = ucfirst(
				preg_replace_callback(
					'/_([a-z])/',
					function($match){return strtoupper($match[1]);},
					str_replace('-','_',$framework->path[0])
				)
			).'Controller';
			$classfile = SITE_ROOT.'/_data/controllers/'.$controller_name.'.php';

			$get = $_GET;
			unset($get['request_url']);
			
			// Handle classfile
			if (!file_exists($classfile)) {
				Messages::msg('Page not found: '.$url,Messages::M_CODE_ERROR);
				$framework->controller = new Controller();
				$framework->controller->generateErrorPage(HTTP_STATUS_NOT_FOUND);
				return;
			}
			require_once($classfile);

			// Handle controller
			if (class_exists($controller_name)){
				$controller = new $controller_name();
				if ($controller instanceof Controller){				
					$framework->controller = $controller;
					$action = null;
					if (isset($framework->path[1])){
						$action = str_replace('-','_',$framework->path[1]);
						if (!method_exists($framework->controller,$action)){
							if (method_exists($framework->controller,'_action')){
								$framework->controller->_action($framework->path[1]);
								return;
							}
							Messages::msg("The action '$action' does not exist in the '$controller_name' controller.",Messages::M_CODE_ERROR);
							$framework->controller->generateErrorPage(HTTP_STATUS_NOT_FOUND);
							return;
						}
					}
					if (is_null($action)){
						$action = $framework->controller->getDefaultAction();
						if (empty($action)){
							Messages::msg("The '$controller_name' controller does not have a default action.",Messages::M_CODE_ERROR);
							$framework->controller->generateErrorPage(HTTP_STATUS_NOT_FOUND);
							return;
						}
						if (!method_exists($framework->controller,$action)){
							Messages::msg("The default action '$action' does not exist in the '$controller' controller.",Messages::M_CODE_ERROR);
							$framework->controller->generateErrorPage(HTTP_STATUS_NOT_FOUND);
							return;
						} 
					}

					$framework->controller->$action();
					return;

				} else {
					Messages::msg("'$controller_name' does not extend the Controller class.",Messages::M_CODE_ERROR);
				}
			} else {
				Messages::msg("Class '$controller_name' does not exist in file $classfile",Messages::M_CODE_ERROR);
			}
			Messages::display();
		} catch (Exception $ex){
			error_log($ex);
			echo $ex;
		}
		die('An error occured while attempting to generate the page');
	}

	public static function getParam($index,$default=null){
		$framework = self::getInstance();
		if (isset($framework->path[$index]))
		return $framework->path[$index];
		return $default;
	}
	
	public static function getPage(){
		return self::getInstance()->page;
	}
	
	public static function getExecutionTime(){
		return microtime(true) - self::getInstance()->start_time;
	}	
	
	public static function reportErrorField($name){
		self::getInstance()->error_fields[$name] = true;
	}

	public static function isErrorField($name){
		return isset(self::getInstance()->error_fields[$name]);
	}

}

?>