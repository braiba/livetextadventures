<?php

class Controller {

	protected $defaultAction = null;

	protected $class = 'index';
	protected $page = 'index';
	protected $useCache = false;
	protected $cacheParams = null;
	
	protected $template = 'default';
	protected $title = null;
	protected $description = null;
	protected $keywords = array();
	protected $feeds = array();
	protected $data = array();
	protected $breadcrumbs = array();
	protected $paramindex = 2;


	private static $pageismultiobjectform = false;

	public function __construct($parent=null){
		if (method_exists($this,'_init')){
			$this->_init();
		}
	}

	public function getController($name,$folder=null){
		$controller = "{$name}Controller";
		$classfile = SITE_ROOT.'/_data/controllers/'.(isset($folder)?"$folder/":'').$controller.'.php';
		if (!file_exists($classfile)) {
			Messages::msg("The '$name' controller ".(is_null($folder)?'':"in '$folder'").' cannot be found.',Messages::M_ERROR);
			return null;
		}
		include_once $classfile;
		return new $controller($this);
	}

	public function setDefaultAction($defaultAction){$this->defaultAction = $defaultAction;}
	public function getDefaultAction(){return $this->defaultAction;}
	public function setPage($page){$this->page = $page;}
	public function setClass($class){$this->class = $class;}
	public function setTemplate($template){$this->template = $template;}
	public function setTitle($title){$this->title = $title;}
	public function setMetaDescription($description){$this->description = $description;}
	public function addMetaKeyword($keyword){$this->keywords[] = $keyword;}
	public function addFeed($name,$url){$this->feeds[$name] = $url;}
	
	public function getTitle(){return $this->title;}
	public function getMetaDescription(){return $this->description;}
	public function getMetaKeywords(){return array_merge(Framework::getCoreKeywords(),$this->keywords);}
	public function getFeeds(){return $this->feeds;}

	public static function pageIsMultiObjectForm(){
		$this->pageismultiobjectform = true;
	}
	public static function isPageMultiObjectForm(){
		return $this->pageismultiobjectform;
	}

	public function getNextParam($default=null){return Framework::getParam($this->paramindex++,$default);}

	public function generatePage($page=false){
		if ($page!==false) $this->setPage($page);
		require SITE_ROOT.'/_data/content/templates/'.$this->template.'.php';
	}
	
	public function generateErrorPage($error_code, $error_message = null){
		$this->setClass('error');
		header('Status: '.$error_code);
		if (empty($error_message)){
			switch ($error_code){
				case HTTP_STATUS_FORBIDDEN:
					$error_message = 'Access Denied';
					break;
					
				case HTTP_STATUS_BAD_REQUEST:
					$error_message = 'Invalid URL';
					break;
				
				case HTTP_STATUS_NOT_FOUND:
					// Falls through
				default:
					$error_message = 'Page Not Found';
			}
		}
		Messages::msg('Error page returned: '.$error_code.' - '.$error_message.'. Referer: '.(isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'None'),Messages::M_CODE_WARNING);
		$this->setTitle($error_message);
		$this->setMetaDescription('The requested page could not be found.');
		$this->generatePage($error_code);
	}

	public function dropBreadcrumb($name,$page){
		$this->breadcrumbs[$name] = $page;
	}

	protected function displayBreadcrumbs(){
		echo '<ul class="breadcrumbs">';
		foreach ($this->breadcrumbs as $name=>$page){
			echo '<li><a href="'.Framework::linkTo($page).'">'.$name.'</a></li>';
		}
		echo '</ul>';
	}
	
	protected function loadFile($file,$useCache=false,$cacheParams=null){
		$root = SITE_ROOT.'/_data/content/';
		if ($useCache){
			$cfile = new CachedFile(array('filename'=>file,'params'=>$cacheParams));
			if (!$cfile->isNew()){
				echo $cfile->Content;
				return; 
			}
			
			ob_start();
			include($root.$file);
			$content = ob_get_contents();
			ob_end_flush();
			
			$cfile = new CachedFile();
			$cfile->FileName = $file;
			$cfile->Params = $cacheParams;
			$cfile->Content = $content;
			if (!$cfile->insert()){
				Messages::msg("An error occured when attempting to cache the page '$file'.",Messages::M_CODE_ERROR);
			}
		} else {
			include($root.$file);
		}
	}	
	protected function decacheFile($file,$params=null){
		$cfile = new CachedFile(array('filename'=>file,'params'=>$params));
		if (!$cfile->isNew()){
			return $cfile->delete();
		}
		return RESULT_IGNORED;
	}	
	
	protected function render($page,$folder='pages',$useCache=false,$cacheParams=null){
		$root = SITE_ROOT.'/_data/content/'.$folder;
		$file = "/".$this->class."/$page.php";
		if (!file_exists($root.$file)){
			$file = "/$page.php";
			if (!file_exists($root.$file)){
				Messages::msg("Cannot display '$page' as it does not exist. ({$root}/{$this->class}{$file})",Messages::M_CODE_ERROR);
				return false;
			}
		}
		try {
			$this->loadFile($folder.'/'.$file,$useCache,$cacheParams);
		} catch (Exception $ex){
			Messages::msg('Exception rendering page '.$page.': '.$ex,Messages::M_CODE_ERROR);
		}
		return true;
	}
	
	protected function renderPage(){
		if ($this->page!=null){
			if (!$this->render($this->page,'pages',$this->useCache,$this->cacheParams)){
				$root = SITE_ROOT.'/_data/content/pages';
				$file = '/error/404.php';
				if (file_exists($root.$file)){
					$this->loadFile('pages/'.$file);
				} else {
					Messages::msg("404 error page not found",Messages::M_CODE_ERROR);
					echo 'File not found: ' . $this->page;
				}
			}
		}
	}
	protected function prerenderPage(){
		ob_start();
		$this->renderPage();
		return ob_get_clean();
	}

	protected function renderFragment($page,$useCache=false,$cacheParams=null){
		$this->render($page,'fragments',$useCache,$cacheParams);
	}
	protected function prerenderFragment($page,$useCache=false,$cacheParams=null){
		ob_start();
		$this->renderFragment($page,$useCache,$cacheParams);
		return ob_get_clean();
	}

}

?>