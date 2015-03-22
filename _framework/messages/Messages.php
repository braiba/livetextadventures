<?php
		
class Messages {

	// NOTE: the values of these constants are subject to change. As such, they should be refered to be name only.
	const M_INFO = 1;	// Standard info messages
	const M_WARNING = 2;	// Standard warning messages
	const M_SUCCESS = 4;	// Standard error messages
	const M_ERROR = 8;	// Standard error messages
	const M_PHP_INFO = 16;	// Warning messages thrown by PHP and caught by one of Messages::php_error/exception_handler() via set_error/exception_handler()
	const M_PHP_ERROR = 32;	// Error messages thrown by PHP and caught by one of Messages::php_error/exception_handler() via set_error/exception_handler()
	const M_USER_WARNING = 64;	// Warning intended only for the user (these should be displayed, but don't need to be logged)
	const M_USER_ERROR = 128;	// Error intended only for the user (these should be displayed, but don't need to be logged)
	const M_CODE_WARNING = 256;	// Warning intended only for the system admins (these should be logged, but don't need to be displayed)
	const M_CODE_ERROR = 512; // Warning intended only for the system admins (these should be logged, but don't need to be displayed)
	const M_SQL_QUERY = 1024;	// SQL query messages
	const M_SQL_WARNING = 2048;	// SQL warning message
	const M_SQL_ERROR = 4096;	// SQL error message
	const M_ISDB_SQL_QUERY = 8192;	// SQL query messages
	const M_BACKGROUND_WARNING = 16384;	// Reserved for code that will be executed in the background (threads, services, etc.)
	const M_BACKGROUND_ERROR = 32768;	// Reserved for code that will be executed in the background (threads, services, etc.)
	const M_SYSTEM_STATUS_WARNING = 65536;	// Reserved for the system status report
	const M_SYSTEM_STATUS_ERROR = 131072;	// Reserved for the system status report
	const M_DEBUG = 262144;	// Debug message (displays on the test server only)
	
	const M_MAX = 262144; // Highest level value
	const M_ALL = 524287; // 2*M_MAX -1

	private static $instance = null;
	protected $handlers = array();
	protected $filter_layers = array();
	protected $force_display = false;
	private function __construct(){ }

	private static function levelName($level){
		$reflection = new ReflectionClass(__CLASS__);
		$consts = $reflection->getConstants();
		unset($consts['M_MAX']); // prevents overlap between the error level constant with the same value
		$const_index = array_flip($consts);
		if (array_key_exists($level,$const_index)){
			return $const_index[$level];
		}
		@Messages::msg('Invalid base message level: '.$level,Messages::M_CODE_ERROR);
		return null;
	}
	public static function levelAsString($level){
		if ($level>=2*self::M_MAX){
			@Messages::msg('Invalid message level: '.$level,Messages::M_CODE_ERROR);
			return '[INVALID ERROR LEVEL]';
		}
		$levels = array();
		$i = self::M_MAX;
		while ($level!=0 && $i>=1){
			if ($level>=$i){
				$levels[] = self::levelName($i);
				$level-= $i;
			}
			$i/= 2;
		}
		return implode(', ',$levels);
	}

	public static function addHandler($handler){
		self::getInstance()->handlers[] = $handler;
	}

	private static function getInstance(){
		if (is_null(self::$instance)){
			self::$instance = new Messages();
		}
		return self::$instance;
	}
	public static function enablePHPErrorHandling(){
		set_error_handler(array('Messages','php_error_handler'));
		set_exception_handler(array('Messages','php_exception_handler'));
	}
	public static function disablePHPErrorHandling(){
		restore_error_handler();
		restore_exception_handler();
	}

	public static function php_error_handler($errno, $errstr, $errfile, $errline){
		throw new \Exception($errstr);
		if ( (error_reporting()&$errno)==0 ) return true;
		$info = ($errno&(E_WARNING|E_NOTICE|E_CORE_WARNING|E_COMPILE_WARNING||E_USER_NOTICE));
		self::msg("$errstr at line $errline of $errfile.",($info?Messages::M_CODE_WARNING:Messages::M_CODE_ERROR));
		return true;
	}
	/**
	 *
	 * @param Exception $exception
	 * @param int $level
	 */
	public static function php_exception_handler($exception,$level=1){
		if ($level>64) return; // shouldn't ever come up, but will break an infinite loop if one occurs somehow
		if ($previous = $exception->getPrevious()){
			self::php_exception_handler($previous,$level+1);
		}
		self::msg($exception,Messages::M_CODE_ERROR);
		if ($level==1) Messages::display();
	}

	/**
	 * Adds a layer of supression for each of the specified types.
	 * Messages types are supressed so long as there is at least one layer of supression on it.
	 * @param int $types
	 */
	public static function supress($types){
		$instance = self::getInstance();
		foreach ($instance->filter_layers as &$layer){
			$already_supressed = $types & $layer;
			$layer|= $types;
			$types = $already_supressed;
			if ($types==0) break;
		}
		if ($types!=0){
			$instance->filter_layers[] = $types;
		}
	}
	/**
	 * Removes a layer of supression for each of the specified types.
	 * Messages types are supressed so long as there is at least one layer of supression on it.
	 * @param int $types
	 */
	public static function unsupress($types){
		$instance = self::getInstance();
		for ($i=sizeof($instance->filter_layers)-1; $i>=0; $i--){
			$layer = &$instance->filter_layers[$i];
			$to_remove = $layer&$types;
			$layer&= ~$to_remove;
			$types&= ~$to_remove;
			if ($layer==0) {
				array_pop($instance->filter_layers);
			}
			if ($types==0) break;
		}
	}
	public static function clearFilter(){
		$this->filter_layers = array();
	}
	public static function getFilter(){
		$instance = self::getInstance();
		return ( !empty($instance->filter_layers) ? $instance->filter_layers[0] : 0 );
	}

	public static function msg($msg,$type=self::M_INFO){
		if (error_reporting()==0) return;
		$instance = self::getInstance();
		$type&= ~self::getFilter(); // apply filter
		if ( $type==0 ) return;
		$caught = 0;
		foreach ($instance->handlers as $handler){
			if ($handler->checkLevel($type)){
				$handler->msg($msg,$type);
				$caught++;
			}
		}
		return $caught;
	}

	public static function getHandlerCount($filter=null){
		if (is_null($filter)){
			return sizeof(self::getInstance()->handlers);
		}
		$count = 0;
		foreach (self::getInstance()->handlers as $handler){
			if ($handler->checkLevel($filter)){
				$count++;
			}
		}
		return $count;
	}

	public static function forceDisplay($newValue=null){
		$instance = self::getInstance();
		$return = $instance->force_display;
		if (!is_null($newValue)){
			$instance->force_display = $newValue;
		}
		return $return;
	}

	public static function display(){
		DisplayMessageHandler::displayAll();
	}
	
	public static function convertDisplayToAjax(){
		$instance = self::getInstance();
		foreach ($instance->handlers as $i => $handler){
			if ($handler instanceof DisplayMessageHandler){
				$instance->handlers[$i] = new AJAXMessageHandler($handler->getFilter(),$handler->getName());
			}
		}
	}
	
	public static function getAJAXData(){
		return AJAXMessageHandler::getAJAXData();
	}

}

?>