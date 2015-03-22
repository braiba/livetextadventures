<?php

	class Session {

		protected static $sess_save_path;

		public static function start() {
			session_set_save_handler(
				array(__CLASS__, 'open'),
				array(__CLASS__, 'close'),
				array(__CLASS__, 'read'),
				array(__CLASS__, 'write'),
				array(__CLASS__, 'destroy'),
				array(__CLASS__, 'gc')
			);
			session_start();
		}

		public static function open($save_path, $session_name) {
			self::$sess_save_path = $save_path;
			return true;
		}

		public static function close() {
			return true;
		}

		public static function read($id) {
			$sess_file = self::$sess_save_path . '/sess_' . $id;
			return (string) @file_get_contents($sess_file);
		}

		public static function write($id, $sess_data) {
			$sess_file = self::$sess_save_path . '/sess_' . $id;
			if ($fp = @fopen($sess_file, "w")) {
				$return = fwrite($fp, $sess_data);
				fclose($fp);
				return $return;
			}
			else {
				return(false);
			}
		}

		public static function destroy($id) {
			$sess_file = self::$sess_save_path . '/sess_' . $id;
			return(@unlink($sess_file));
		}

		public static function gc($maxlifetime) {
			$sess_file_pattern = self::$sess_save_path . '/sess_*';
			foreach (glob($sess_file_pattern) as $filename) {
				if ($mtime = @filemtime($filename)){ // @ : This fails occasionally, but since it's just garbage collection, we don't really care
					if ($mtime + $maxlifetime < time()) {
						@unlink($filename);
					}
				}
			}
			return true;
		}

		public static function setValue($name, $value) {
			$data = &$_SESSION;
			if (is_array($name)){
				$path = $name;
				$name = array_pop($path);
				foreach ($path as $key){
					if (!array_key_exists($key, $data)){
						$data[$key] = array();
					}
					$data = &$data[$key];
				}			
			} 
			$data[$name] = $value;	
		}

		public static function addValue($name, $value) {
			$data = &$_SESSION;
			if (is_array($name)){
				$path = $name;
				$name = array_pop($path);
				foreach ($path as $key){
					if (!array_key_exists($key, $data)){
						$data[$key] = array();
					}
					$data = &$data[$key];
				}						
			}
			$data[$name][] = $value;		
		}

		public static function getValue($name, $default=null) {
			$data = &$_SESSION;
			if (is_array($name)){
				$path = $name;
				$name = array_pop($path);
				foreach ($path as $key){
					if (!array_key_exists($key, $data)){
						return $default;
					}
					$data = &$data[$key];
				}						
			}
			if (array_key_exists($name, $data)){
				return $data[$name];				
			}
			return $default;
		}

		public static function unsetValue($name) {
			$data = &$_SESSION;
			if (is_array($name)){
				$path = $name;
				$name = array_pop($path);
				foreach ($path as $key){
					if (!array_key_exists($key, $data)){
						return;
					}
					$data = &$data[$key];
				}			
			}
			unset($data[$name]);
		}

		public static function valueExists($name) {
			$data = &$_SESSION;
			if (is_array($name)){
				$path = $name;
				$name = array_pop($path);
				foreach ($path as $key){
					if (!array_key_exists($key, $data)){
						return false;
					}
					$data = &$data[$key];
				}						
			}
			if (array_key_exists($name, $data)){
				return true;				
			}
			return false;
		}

		public static function end() {
			session_destroy();
		}

	}

?>