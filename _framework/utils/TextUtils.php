<?php

/**
 * A static class for string-based methods
 *
 * @author Thomas
 */
class TextUtils {

	/**
	 * Returns a list of strings in the standard English list format (comma-seperated, but with an 'and' instead of the last comma)
	 * @param array $array
	 * @return string
	 */
	public static function andList($array){
		switch (sizeof($array)){
			case 0: return '';
			case 1: return ArrayUtils::getFirst($array);
			default:
				$tail = array_pop($array);
				return implode(', ',$array).' and '.$tail;
		}
	}

	/**
	 * Truncates a string to a given length without breaking words and inserting a marker ('...' by default)
	 * at the end if it has been truncated to indicated this.
	 * @param string $str The string to be truncated
	 * @param int $limit The maximum length of the string to be returned
	 * @param string $limitstr The marker to be used to indicate where truncation has occured.
	 * @param boolean $htmllimitstr if true, $limitstr will be treated as HTML
	 * @return string
	 */
	public static function neatTruncate($str,$limit,$limitstr='...',$htmllimitstr=false){
		if (strlen($str)>$limit){
			$limitstrlen = $htmllimitstr ? strlen(HTMLUtils::stripTags($limitstr)) : strlen($limitstr);
			$str = wordwrap($str,$limit-$limitstrlen,"\n",true);
			$parts = explode("\n",$str,2);
			$str = $parts[0].$limitstr;
		}
		return $str;
	}
	
	/**
	 * Truncates a string to a given length,  inserting a marker ('...' by default)
	 *   at the end if it has been truncated to indicated this.
	 * @param string $str The string to be truncated
	 * @param int $limit The maximum length of the string to be returned
	 * @param string $limitstr The marker to be used to indicate where truncation has occured.
	 * @return string
	 */
	public static function truncate($str,$limit,$limitstr='...'){
		if (strlen($str)>$limit){
			$str = substr($str, 0, $limit-strlen($limitstr)).$limitstr;
		}
		return $str;
	}

	/**
	 * Returns a currency value in the correct format (with the minus sign before the pound sign if the amount is negative)
	 * @param number $value The amount, in either pounds or pence (determined by the $inPence argument)
	 * @param string $symbol A string to use for the pound sign (default = HTML pound character code)
	 * @return string
	 */
	public static function currency($value,$symbol='&pound;'){
		if (is_null($value)) return '-';
		return ($value<0?'-':'').$symbol.number_format(abs($value),2);
	}

	/**
	 * Returns a currency value in the correct format, as per HTMLUtils::currency, but also wraps it in a span tag with the class 'positive',
	 * 'negative' or 'zero' depending on the value given for easy formating (shared/styles.css contains colouring for these classes).
	 * @param number $value The amount, in either pounds or pence (determined by the $inPence argument)
	 * @param string $symbol A string to use for the pound sign (default = HTML pound character code)
	 * @param boolean $invert If true the 'positive' and 'negative' classes will be used in reverse
	 * @return string
	 */
	public static function htmlCurrency($value,$symbol='&pound;',$invert=false){
		return HTMLUtils::currency($value, $symbol, $invert);
	}

	/**
	 * Implodes an array, making use of both the key and the value for each element of the array
	 * @param array $array The array to be imploded
	 * @param string $pair_glue The glue to use between pairs
	 * @param string $pair_format The format of the pairs. All insteances of '$key' and '$value' will be replaced by the key and value for the pair
	 */
	public static function implodePairs($array,$pair_glue='&amp;',$pair_format='$key=$value'){
		$pairs = array();
		foreach ($array as $key=>$value){
			$pairs[] = str_replace(array('$key','$value'),array($key,$value),$pair_format);
		}
		return implode($pair_glue,$pairs);
	}


	public static function addslashes($str,$charlist='\'"\\'){
		return preg_replace('/['.preg_quote($charlist).']/','\\\\\\1',$str);
	}
	public static function stripslashes($str,$charlist='\'"\\'){
		return preg_replace('/\\\\(['.preg_quote($charlist).'])/','\\1',$str);
	}

	/**
	 * Returns a duration in text form
	 * @param int $duration Duration in seconds
	 * @param int $significant_figures The number of units after the largest to be returned.
	 *		Empty units will still be counted for this, so a duration equal to 1 week, 3 hours and 35 minutes would display to 3
	 *		significant figures as "1 week and 4 hours" ('day' being the second significant figure). Note that the reamining 3 hours
	 *		and 35 minutes rounds up to 4 hours.
	 *		If not specified, or if <=0, the full duration will be returned.
	 * @return string
	 */
	public static function duration($duration,$significant_figures=0){
		//backwards compatibility ($significant_figures was originally a boolean called $approx)
		if (is_bool($significant_figures)){
			$significant_figures = ($significant_figures?1:0);
		}

		$units = array(
				'millenium' => 1000*365*24*60*60,
				'century' => 100*365*24*60*60,
				'decade' => 10*365*24*60*60,
				'year' => 365*24*60*60,
				'month' => 30*24*60*60,
				'week' => 7*24*60*60,
				'day' => 24*60*60,
				'hour' => 60*60,
				'minute' => 60,
				'second' => 1,
		);
		$result = array();
		$significant = false;
		foreach ($units as $unit=>$size){
			if ($duration>=$size){
				if ($significant_figures == 1){
					$i = round($duration/$size); // last significant figure should be rounded.
				} else if ($unit!='second') {
					$i = floor($duration/$size);
				} else {
					$i = round($duration,($significant_figures>1 ? $significant_figures-1 : 2) );
				}
				$result[] = $i.' '.$unit.($i!=1?'s':'');
				$duration-= $i*$size;
				$significant = true;
			}
			if ($significant) {
				if (--$significant_figures == 0) break;
			}
		}
		if (empty($result)){
			return round($duration, ($significant_figures>0 ? $significant_figures : 2) ).' seconds';
		}
		return self::andList($result);
	}

	/**
	 * Parses a string with valid markup to produce a grammatically correct string that contains numbers.
	 * The markup is parsed as follows
	 * if $values is a single number...
	 *   Rule 1.a : # => $values
	 *   Rule 1.b : (str) => str if $values!=1, the empty string otherwise.
	 *   Rule 1.c : (str1|str2) => str2 if $values!=1, str1 otherwise
	 * if $values is an array of numbers
	 *   Rule 2.a : #key or #{key} or {#key} => $values[key]
	 *   Rule 2.b : (key:str) => str if $values[key]!=1, the empty string otherwise
	 *   Rule 2.c : (key:str1|str2) => str2 if $values[key]!=1, str1 otherwise
	 * Literal instances of the following symbols can be escaped with a backslash: #{}()
	 * EXAMPLE:
	 *   plurality('There (is|are) # cat(s) on the wall',1) => 'There is 1 cat on the wall'
	 *   plurality('There (is|are) # cat(s) on the wall',3) => 'There are 3 cats on the wall'
	 *   plurality('There (is|are) (a|#) cat(s) on the wall',1) => 'There is a cat on the wall'
	 *   plurality('There (cats:is|are) #cats cat(cats:s) and #dogs dog(dogs:s) on the wall',array('cats'=>2,'dogs'=>1)) => 'There are 2 cats and 1 dog on the wall'
	 * NOTES: Only keys of the format [a-z0-9_]+ are valid for this method.
	 * @param string $str The formatted input string
	 * @param array|number $values Either a single value to be inserted, or an associative array of numbers
	 * @return string The resulting string.
	 */
	public static function plurality($str,$values){
		if (is_array($values)){
			// This replaces #key with the correct value from $values (i.e. $values[key])
			$str = preg_replace_callback(
				'/(?<!\\\\)(?:#([a-z0-9_]+)|#{([^}]+)}|{#([^}]+)})/i',
				function($match) use ($values){
					$key = implode('',array_slice($match,1));
					if (array_key_exists($key,$values)){
						return number_format($values[$key]);
					}
					Messages::msg("Number #$key not found in values for TextUtils::plurality().",Messages::M_CODE_ERROR);
					return $match[0];
				},
				$str
			);
			
			$str = preg_replace_callback(
				'/(?<!\\\\)\\((?:([a-z0-9_]+)\\:(?:([^\\|\\)])*\\|)?([^\\)]*))\\)/i',
				function($match) use ($values){
					$key = $match[1];
					if (array_key_exists($key,$values)){
						$seg = ($values[$key]==1?2:3);
						return $match[$seg];
					}
					Messages::msg("Number #$key not found in values for TextUtils::plurality().",Messages::M_CODE_ERROR);
					return $match[0];
				},
				$str
			);
		} elseif (is_numeric($values)){
			$str = preg_replace('/(?<!\\\\)#/',$values,$str);
			$seg = ($values==1?1:2);
			$str = preg_replace('/(?<!\\\\)\\((?:(?:([^\\|\\)]*)\\|)?([^\\)]*))\\)/','\\'.$seg,$str);
		}
		return self::stripslashes($str,'{(#)}');
	}

	public static function plural($str){
		if (preg_match('/y$/',$str)){
			return preg_replace('/y$/','ies',$str,1);
		}
		return $str.'s';
	}

	/**
	 * 
	 * @param string $str
	 * @return string
	 */
	public static function aOrAn($str){
		return 'a'.(preg_match('/^[aeiou]/i',$str)?'n':'').' '.$str;
	}
	
	public static function replaceFileExt($filename,$ext){
		return preg_replace('/\\.[^\\.]+$/','.'.$ext,$filename);
	}

	
	/**
	 * Breaks the $name into chunks of [A-Z][a-z]+ and [A-Z][A-Z]+
	 *
	 * @param string $name
	 * @return string
	 */
	public static function makeCodeNameReadable($name){
		$name = preg_replace('/((?<!^)[A-Z][a-z]+(?=$|[A-Z])|(?<!^|[A-Z])[A-Z]+(?=$|[A-Z][a-z]))/',' \\0',$name);
		$name = str_replace('_',' ',$name);
		$name = preg_replace('/\\s+/',' ',$name);
		return strtolower($name);
	}
	
	public static function makeSQLFieldReadable($field){
		if (preg_match('/(?:^|\\.)(`[^`]*`|[a-z_][a-z0-9_]*)$/i',$field,$match)){
			$field = trim($match[1],'`');
		}
		$field = preg_replace('/[_\\-]/',' ',$field);
		$field = self::makeCodeNameReadable($field);
		return ucwords($field);
	}
	
	/**
	 *
	 * @param array $stack_trace A stack trace to output. debug_backtrace() will be used to generate one if none is given.
	 * @param array $ignore_classes An array of class names to ignore from the top of the stack trace. For example, we don't need to know which line of an exception class this was called from.
	 * @param boolean $output If true, the result will be returned, otherwise it will be echoed.
	 * @return string
	 */
	public static function displayStackTrace($stack_trace=null,$ignore_classes=array(),$output=false){
		$columns = array(
			array(
				'label'=>'Location',
				'callback'=>function($row,$col){
					if (!isset($row['file'])){
						return '-';
					}
					if (defined('SVN_FILE_ROOT')){
						$file = $row['file'];
						$svn_page = str_replace('\\','/',str_replace('D:\\WebRoot\\',SVN_FILE_ROOT,$file)).'#L'.$row['line'];
						return HTMLUtils::a($svn_page,basename($file).' ('.$row['line'].')');
					}
					return 'line '.$row['line'].' in '.$row['file'];
				}
			),
			array(
				'label'=>'Call',
				'callback'=>function($row,$col){
					$func = ( isset($row['class']) ? $row['class'].$row['type'] : '' ).$row['function'];
					$readable_args = array_map(array('HTMLUtils','debug_displayvar'),$row['args']);
					return $func.'('.implode(', ',$readable_args).')';
				}
			)
		);
		if (is_null($stack_trace)){
			$stack_trace = debug_backtrace();
			$ignore_classes[] = __CLASS__;
		}
		
		foreach ($stack_trace as $i=>$data){
			if (isset($data['class']) && in_array($data['class'],$ignore_classes)){
				unset($stack_trace[$i]);
			} else {
				break;
			}
		}

		$table = new ArrayTablePlus($stack_trace,$columns);
		$result = $table->display();
		if ($output) return $result;
		echo $result;
	}

	/**
	 * Checks to see if a string is UTF-8 encoded. Based on code from WordPress
	 * text sanititation functions.
	 * @param string $str The string to be checked
	 * @return bool True if $str fits a UTF-8 model, false otherwise.
	 */
	public static function seemsUTF8($str)
	{
		$length = strlen($str);
		for ($i=0; $i < $length; $i++)
		{
			$c = ord($str[$i]);
			if ($c < 0x80) $n = 0; # 0bbbbbbb
			elseif (($c & 0xE0) == 0xC0) $n=1; # 110bbbbb
			elseif (($c & 0xF0) == 0xE0) $n=2; # 1110bbbb
			elseif (($c & 0xF8) == 0xF0) $n=3; # 11110bbb
			elseif (($c & 0xFC) == 0xF8) $n=4; # 111110bb
			elseif (($c & 0xFE) == 0xFC) $n=5; # 1111110b
			else return false; # Does not match any model
			for ($j=0; $j<$n; $j++) # n bytes matching 10bbbbbb follow ?
			{
				if ((++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80))
					return false;
			}
		}
		return true;
	}

	/**
	 * Converts all accent characters to ASCII characters. If there are no
	 * accent characters, then the string given is just returned. Based on code
	 * from WordPress text sanitation functions.
	 * @param string $string Text that might have accent characters
	 * @return string Filtered string with replaced "nice" characters.
	 */
	public static function removeAccents($string)
	{
		if (!preg_match('/[\x80-\xff]/', $string))
		{
			return $string;
		}

		if (self::seemsUTF8($string))
		{
			$chars = array(
			// Decompositions for Latin-1 Supplement
			chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
			chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
			chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
			chr(195).chr(134) => 'AE',chr(195).chr(135) => 'C',
			chr(195).chr(136) => 'E', chr(195).chr(137) => 'E',
			chr(195).chr(138) => 'E', chr(195).chr(139) => 'E',
			chr(195).chr(140) => 'I', chr(195).chr(141) => 'I',
			chr(195).chr(142) => 'I', chr(195).chr(143) => 'I',
			chr(195).chr(144) => 'D', chr(195).chr(145) => 'N',
			chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
			chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
			chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
			chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
			chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
			chr(195).chr(158) => 'TH',chr(195).chr(159) => 's',
			chr(195).chr(160) => 'a', chr(195).chr(161) => 'a',
			chr(195).chr(162) => 'a', chr(195).chr(163) => 'a',
			chr(195).chr(164) => 'a', chr(195).chr(165) => 'a',
			chr(195).chr(166) => 'ae',chr(195).chr(167) => 'c',
			chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
			chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
			chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
			chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
			chr(195).chr(176) => 'd', chr(195).chr(177) => 'n',
			chr(195).chr(178) => 'o', chr(195).chr(179) => 'o',
			chr(195).chr(180) => 'o', chr(195).chr(181) => 'o',
			chr(195).chr(182) => 'o', chr(195).chr(182) => 'o',
			chr(195).chr(185) => 'u', chr(195).chr(186) => 'u',
			chr(195).chr(187) => 'u', chr(195).chr(188) => 'u',
			chr(195).chr(189) => 'y', chr(195).chr(190) => 'th',
			chr(195).chr(191) => 'y',
			// Decompositions for Latin Extended-A
			chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
			chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
			chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
			chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
			chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
			chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
			chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
			chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
			chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
			chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
			chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
			chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
			chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
			chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
			chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
			chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
			chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
			chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
			chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
			chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
			chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
			chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
			chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
			chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
			chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
			chr(196).chr(178) => 'IJ',chr(196).chr(179) => 'ij',
			chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
			chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
			chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
			chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
			chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
			chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
			chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
			chr(197).chr(130) => 'l', chr(197).chr(131) => 'N',
			chr(197).chr(132) => 'n', chr(197).chr(133) => 'N',
			chr(197).chr(134) => 'n', chr(197).chr(135) => 'N',
			chr(197).chr(136) => 'n', chr(197).chr(137) => 'N',
			chr(197).chr(138) => 'n', chr(197).chr(139) => 'N',
			chr(197).chr(140) => 'O', chr(197).chr(141) => 'o',
			chr(197).chr(142) => 'O', chr(197).chr(143) => 'o',
			chr(197).chr(144) => 'O', chr(197).chr(145) => 'o',
			chr(197).chr(146) => 'OE',chr(197).chr(147) => 'oe',
			chr(197).chr(148) => 'R',chr(197).chr(149) => 'r',
			chr(197).chr(150) => 'R',chr(197).chr(151) => 'r',
			chr(197).chr(152) => 'R',chr(197).chr(153) => 'r',
			chr(197).chr(154) => 'S',chr(197).chr(155) => 's',
			chr(197).chr(156) => 'S',chr(197).chr(157) => 's',
			chr(197).chr(158) => 'S',chr(197).chr(159) => 's',
			chr(197).chr(160) => 'S', chr(197).chr(161) => 's',
			chr(197).chr(162) => 'T', chr(197).chr(163) => 't',
			chr(197).chr(164) => 'T', chr(197).chr(165) => 't',
			chr(197).chr(166) => 'T', chr(197).chr(167) => 't',
			chr(197).chr(168) => 'U', chr(197).chr(169) => 'u',
			chr(197).chr(170) => 'U', chr(197).chr(171) => 'u',
			chr(197).chr(172) => 'U', chr(197).chr(173) => 'u',
			chr(197).chr(174) => 'U', chr(197).chr(175) => 'u',
			chr(197).chr(176) => 'U', chr(197).chr(177) => 'u',
			chr(197).chr(178) => 'U', chr(197).chr(179) => 'u',
			chr(197).chr(180) => 'W', chr(197).chr(181) => 'w',
			chr(197).chr(182) => 'Y', chr(197).chr(183) => 'y',
			chr(197).chr(184) => 'Y', chr(197).chr(185) => 'Z',
			chr(197).chr(186) => 'z', chr(197).chr(187) => 'Z',
			chr(197).chr(188) => 'z', chr(197).chr(189) => 'Z',
			chr(197).chr(190) => 'z', chr(197).chr(191) => 's',
			// Decompositions for Latin Extended-B
			chr(200).chr(152) => 'S', chr(200).chr(153) => 's',
			chr(200).chr(154) => 'T', chr(200).chr(155) => 't',
			// Euro Sign
			chr(226).chr(130).chr(172) => 'E',
			// GBP (Pound) Sign
			chr(194).chr(163) => chr(163));

			$string = strtr($string, $chars);
		}
		else
		{
			// Assume ISO-8859-1 if not UTF-8
			$chars['in'] = chr(128).chr(131).chr(138).chr(142).chr(154).chr(158)
				.chr(159).chr(162).chr(165).chr(181).chr(192).chr(193).chr(194)
				.chr(195).chr(196).chr(197).chr(199).chr(200).chr(201).chr(202)
				.chr(203).chr(204).chr(205).chr(206).chr(207).chr(209).chr(210)
				.chr(211).chr(212).chr(213).chr(214).chr(216).chr(217).chr(218)
				.chr(219).chr(220).chr(221).chr(224).chr(225).chr(226).chr(227)
				.chr(228).chr(229).chr(231).chr(232).chr(233).chr(234).chr(235)
				.chr(236).chr(237).chr(238).chr(239).chr(241).chr(242).chr(243)
				.chr(244).chr(245).chr(246).chr(248).chr(249).chr(250).chr(251)
				.chr(252).chr(253).chr(255);

			$chars['out'] = "EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy";

			$string = strtr($string, $chars['in'], $chars['out']);
			$double_chars['in'] = array(chr(140), chr(156), chr(198), chr(208), chr(222), chr(223), chr(230), chr(240), chr(254));
			$double_chars['out'] = array('OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th');
			$string = str_replace($double_chars['in'], $double_chars['out'], $string);
		}

		return $string;
	}
	
	/**
	 * Breaks up a search string into seperate terms, respecting double quotes around grouped words
	 * @param string $query_str
	 * @return array 
	 */
	public static function buildSearchData($query_str){
		$items = array();
		$query_str = preg_replace('/(: +| \\- | \\(|\\)( |$))/',' ',$query_str);
		if (preg_match_all('/(?>(?<=")[^"]+(?=")|((?<=^)|(?<=\\s))[^\\s"]+)/', $query_str, $matches, PREG_SET_ORDER)){
			foreach ($matches as $match){
				$items[] = $match[0];
			}
		}
		return $items;
	}
	
	public static function validateEmailAddress($email){
		return preg_match('/^\\s*[^@]+@[^@.]+\\.[^@]+\\s*$/',$email);
	}
	
	public static function nl2p($text){
		return preg_replace('/(?<=^|[\r\n])(\\V+)(?>$|[\r\n])/','<p>$1</p>',$text);
	}

}
?>
