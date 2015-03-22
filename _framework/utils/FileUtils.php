<?php

class FileUtils {

	public static function getNewFile($filename,$root_dir='.',$max_length=null){
		$info = pathinfo($filename);
		$dir = $info['dirname'];
		$orig_file = $info['filename'];
		$ext = $info['extension'];
		
		// Sanitise filename
		$orig_file = preg_replace('/[^a-zA-Z0-9()]+/','_',$orig_file);
		
		if (!file_exists($root_dir.'/'.$dir)){
			$parts = explode('/',$dir);
			$build_dir = $root_dir.'/';
			foreach ($parts as $part){
				$build_dir.= $part;
				if (!file_exists($build_dir) && !mkdir($build_dir)){
					Messages::msg('Could not create dir: '.$build_dir,Messages::M_CODE_WARNING);
					return false;
				}
				$build_dir.= '/';
			}
		}
		
		$filename = $dir.'/'.$orig_file.'.'.$ext;
		if (!file_exists($filename)){
			return $filename;
		}
		
		$i = 2;
		for ($i=2; $i<100; $i++){
			if ($max_length!=null){
				$file = TextUtils::neatTruncate($orig_file, $max_length-strlen("...-$i.$ext"), '...');
			} else {
				$file = $orig_file;
			}
			$filename = "$dir/$file-$i.$ext";
			if (!file_exists($filename)){
				return $filename;
			}
		}
		throw new IOException('100 instances of files with the name '+$file+'.+'+$ext+' already exist at '+$dir+'. Something has almost certainly gone wrong.');
	}
	
	public static function sanitiseFilename($filename) {
		return preg_replace('#[\\\\/:*?"<>]+#', '', $filename);
	}
	
}

?>
