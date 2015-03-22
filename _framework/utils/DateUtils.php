<?php

class DateUtils {

	private static $dateformat = 'd/n/Y';
	
	public static function getDefaultDateFormat(){
		return self::$dateformat;
	}
	public static function setDefaultDateFormat($dateformat){
		self::$dateformat = $dateformat;
	}
	
	private static $daynames = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday');
	private static $dayabbrevs = array('Mon','Tue','Wed','Thu','Fri','Sat','Sun');
	private static $monthnames = array('January','February','March','April','May','June','July','August','September','October','November','December');
	private static $monthabbrevs = array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');
	private static $datepatterns = array(
		// Day
		'd'=>'[0-2][0-9]|3[01]',
		'D'=>'Mon|Tue|Wed|Thu|Fri|Sat|Sun',
		'j'=>'[0-2]?[0-9]|3[01]',
		'l'=>'Sunday|Monday|Tuesday|Wednesday|Thursday|Friday|Saturday',
		'N'=>'[1-7]',
		'S'=>'st|nd|rd|th',
		'w'=>'[0-6]',
		'z'=>'[0-2]?[0-9]{1,2}|3[0-5][0-9]|36[0-5]',
		// Week
		'W'=>'[0-4]?[0-9]|5[0-3]',
		// Month
		'F'=>'January|February|March|April|May|June|July|August|September|October|November|December',
		'm'=>'0[1-9]|1[0-2]',
		'M'=>'Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec',
		'n'=>'[1-9]|1[0-2]',
		't'=>'28|29|30|31',
		// Year
		'L'=>'[01]',
		'o'=>'[0-9]{4}',
		'Y'=>'[0-9]{4}',
		'y'=>'[0-9]{2}',
		// Time
		'a'=>'am|pm',
		'A'=>'AM|PM',
		'B'=>'[0-9]{3}',
		'g'=>'[1-9]|1[0-2]',
		'G'=>'1?[0-9]|2[0-3]',
		'h'=>'0[1-9]|1[0-2]',
		'H'=>'[01][0-9]|2[0-3]',
		'i'=>'[0-5][0-9]',
		's'=>'[0-5][0-9]',
		'u'=>'[0-9]{5}',
		// Timezone
		'e'=>'[A-Z\/]+',
		'I'=>'[01]',
		'O'=>'[+\-][0-9]{4}',
		'P'=>'[+\-][0-9]{2}:[0-9]{2}',		
		'T'=>'[A-Z]{3}',	
		'Z'=>'-?[0-9]{1,5}',
		// Full Date/Time
		'U'=>'[0-9]+'
	);

	public static function expandFormat($format){
		return preg_replace(array('/(?<!\\\\)c/','/(?<!\\\\)r/'),array('Y-m-d[\\TH:i:sP]','D, d M Y H:i:sO'),$format);
	}

	public static function makeRegex($format){
		// MINOR: Caching / Loading from Cache
		$format = self::expandFormat($format);
		$pattern = '';
		$escape_flag = false;
		foreach (str_split($format) as $char){
			if (!$escape_flag && $char=='\\'){
				$escape_flag = true;
			} elseif ($char=='/' || in_array($char,Regex::getMetaCharacters())){
				$pattern .= '\\'.$char;
				$escape_flag = false;
			} elseif (!$escape_flag && isset(self::$datepatterns[$char])){
				$pattern .= '(?P<'.$char.'>'.self::$datepatterns[$char].')';
			} else {
				$pattern .= $char;
				$escape_flag = false;
			}
		}
		return "/^$pattern$/i";
	}

	public static function formatForDateOnly($format){
		return preg_replace('/\\[[^\\]]*\\]/','',$format);		
	}
	public static function formatForTimeOnly($format){
		return preg_replace('/^.*?\\[([^\\])]*\\].*$/','\\1',$format);		
	}
	public static function formatForTimestamp($format){
		return preg_replace('/\\[([^\\]]*)\\]/','\\1',$format);
	}
		
	public static function parseString($str,$regex){
		$t = array('hour'=>0,'minute'=>0,'second'=>0,'month'=>1,'day'=>1,'year'=>1970);
		$t2 = array();
		if (!preg_match($regex,$str,$result)){
			Messages::msg("The date '$str' is not in the expected format.",Messages::M_CODE_ERROR);
			return false;
		}
		if (isset($result['U'])){
			return $result['U'];
		}
		foreach ($result as $char=>$value){
			switch ($char){
				// IGNORED: L, S, t, B, u, I
				case 'y': $value += ($value>=70?1900:2000); // falls through
				case 'Y': $t['year'] = $value; break;
				// FRAMEWORK: Date character 'o'
				case 'd': // falls through
				case 'j': $t['day'] = $value; break;
				case 'D': $t2['dofw'] = (array_search($value,self::$dayabbrevs) + 1) % 7; break;
				case 'N': $t2['dofw'] = ($value%7); break;
				case 'l': $t2['dofw'] = array_search($value,self::$daynames); break;
				case 'w': $t2['dofw'] = $value; break;
				case 'z': $t2['dofy'] = $value; break;
				case 'W': $t2['wofy'] = $value; break;
				case 'F': $t['month'] = array_search($value,self::$monthnames) + 1; break;
				case 'M': $t['month'] = array_search($value,self::$monthabbrevs) + 1; break;
				case 'm': $t['month'] = $value; break;
				case 'n': $t['month'] = $value; break;
				case 'a': $t2['ampm'] = $value; break;
				case 'A': $t2['ampm'] = strtolower($value); break;
				case 'g': $t2['12h'] = $value; break;
				case 'h': $t2['12h'] = $value; break;
				case 'G': $t['hour'] = $value; break;
				case 'H': $t['hour'] = $value; break;
				case 'i': $t['minute'] = $value; break;
				case 's': $t['second'] = $value; break;
				case 'e': $t2['offset'] = self::getOffsetFromTimezone($value); break;
				case 'O': $t2['offset'] = preg_replace('/00$/','',$value); break;
				case 'P': $t2['offset'] = preg_replace('/:00$/','',$value); break;
				case 'T': $t2['offset'] = self::getOffsetFromTimezoneAbbrev($value); break;
				case 'Z': $t2['offset'] = $value/(60*60); break;
			}
		}
		// Process $t2
		if (isset($t2['12h']) && isset($t2['ampm'])){
			$t['hour'] = $t2['12h']+($t2['ampm']=='pm'?12:0);
		}
		if (isset($t2['offset'])){
			$t['hour'] += $t2['offset'];
		}
		if (isset($t2['dofw']) && isset($t2['wofy'])){
			// MINOR: dofw, wofy
		}
		if (isset($t2['dofy'])){
			// MINOR: dofy
		}

		return mktime($t['hour'],$t['minute'],$t['second'],$t['month'],$t['day'],$t['year']);		 
	}

	public static function getOffsetFromTimezone($timezone){
		// MINOR: getOffsetFromTimezone
		return 0;
	}

	public static function getOffsetFromTimezoneAbbrev($abbrev){
		// MINOR: getOffsetFromTimezoneAbbrev
		return 0;
	}
	
	public static function stripFormat($ts_format,$chars){
		if (preg_match_all('/['.preg_quote($chars).']/',$ts_format,$matches,PREG_OFFSET_CAPTURE|PREG_SET_ORDER)){
			$start = $matches[0][0][1];
			$end = $matches[count($matches)-1][0][1];
			return substr($ts_format, $start, $end-$start+1);			
		}
		return $ts_format;
	}
	
	public static function stripFormatToDate($ts_format){
		return self::stripFormat($ts_format,'dDjlNSwzWFmMntLoYy');
	}
	
	public static function stripFormatToTime($ts_format){
		return self::stripFormat($ts_format,'aABgGhHisu');
	}
	
	public static function SQLtoUnix($sql_timestamp){
		if (preg_match('/^(\\d{4})-(\\d{2})-(\\d{2}) (\\d{2}):(\\d{2}):(\\d{2})$/',$sql_timestamp,$results)){
			list(,$year,$month,$day,$hour,$minute,$second) = $results;
			return mktime($hour, $minute, $second, $month, $day, $year);
		}
		if (preg_match('/^(\\d{4})-(\\d{2})-(\\d{2})$/',$sql_timestamp,$results)){
			list(,$year,$month,$day) = $results;
			return mktime(0, 0, 0, $month, $day, $year);
		}
		return $sql_timestamp;
	}

	
	const DATE_REGEX = '(?<day>3[01]|[12][0-9]|0?[1-9])/(?<month>1[0-2]|0?[1-9])/(?<year>(?:19|20)?[0-9]{2})';
	const TIME_REGEX = '(?<hour>2[0-3]|[01]?[0-9]):(?<minute>[0-5]?[0-9]):(?<second>[0-5]?[0-9])?\\s*(?<ampm>am|pm)?';

	public static function startOfDay($timestamp=null){
		if ($timestamp==null) $timestamp = time();
		$info = getdate($timestamp);
		return mktime(0,0,0,$info['mon'],$info['mday'],$info['year']);
	}
	public static function endOfDay($timestamp){
		return self::startOfDay(strtotime('+1 day',$timestamp))-1;
	}
	public static function startOfWeek($timestamp=null,$startOnSunday=false){
		$timestamp = self::startOfDay($timestamp);
		// Find start of week
		if ($startOnSunday){
			$wday = date('N',$timestamp)%7; // 0 = Sunday, 6 = Saturday
		} else {
			$wday = date('N',$timestamp)-1; // 0 = Monday, 6 = Sunday
		}
		return strtotime("-$wday days",$timestamp);
	}


	public static function sanitiseMonth($m){
		while ($m<1) $m+=12;
		return (($m-1)%12)+1;
	}
	public static function sanitiseYear($m,$y){
		while ($m<1) {
			$m+=12;
			$y--;
		}
		$y += (floor(($m-1)/12));
		return $y;
	}
	public static function monthName($m){
		return date('F',mktime(0,0,0,self::sanitiseMonth($m)));
	}

	public static function isDateString($date_str){
		$date_regex = self::DATE_REGEX;
		return preg_match('#^\\s*'.self::DATE_REGEX.'\\s*$#',$date_str);
	}
	public static function parseDateString($date_str){
		$date_regex = self::DATE_REGEX;
		if (preg_match('#^\\s*'.self::DATE_REGEX.'\\s*$#',$date_str,$match)){
			extract($match);
			if ($year<50) {
				$year+= 2000;
			} else if ($year<1900){
				$year+= 1900;
			}
			return mktime(0,0,0,$month,$day,$year);
		}
		return false;
	}

	public static function isTimeString($time_str){
		return preg_match('#^\\s*'.self::TIME_REGEX.'\\s*$#',$time_str);
	}
	public static function parseTimeString($time_str){
		$time_regex = self::TIME_REGEX;
		if (preg_match('#^\\s*'.self::TIME_REGEX.'\\s*$#',$time_str,$match)){
			extract($match);
			if (strtolower($ampm)=='pm' && $hour<12){
				$hour+= 12;
			}
			return mktime($hour,$minute,$second);
		}
		return false;
	}
	
	public static function isDateTimeString($time_str){
		return preg_match('#^\\s*'.self::TIME_REGEX.'\\s*'.self::DATE_REGEX.'\\s*$#',$time_str);
	}
	public static function parseDateTimeString($time_str){
		$time_regex = self::TIME_REGEX;
		$date_regex = self::DATE_REGEX;
		if (preg_match('#^\\s*'.self::TIME_REGEX.'\\s*'.self::DATE_REGEX.'\\s*$#i',$value,$match)){
			extract($match);
			if (strtolower($ampm)=='pm' && $hour<12){
				$hour+= 12;
			}
			if ($year<50) {
				$year+= 2000; 
			} else if ($year<1900){
				$year+= 1900;
			}
			$value = mktime($hour,$minute,$second,$month,$day,$year);
		}
	}
	
	public static function approxFromNow($timestamp,$supressDescriptor=false){
		if (empty($timestamp)) return 'never';
		$diff = time() - $timestamp;
		if ($diff == 0) return 'now';
		$ending = ( $diff>0 ? ' ago' : ' from now');
		$duration = TextUtils::duration(abs($diff),true);
		return $duration.($supressDescriptor?'':$ending);
	}
	
	public static function shorthandToInterval($shorthand,$grammar=false){		
		if (preg_match('/^(\\d+)([a-z]+)$/',$shorthand,$match)){
			list(,$qty,$unit) = $match;
			$unit_map = array(
				'y' => 'year',
				'yr' => 'year',
				'm' => 'month',
				'mon' => 'month',
				'w' => 'week',
				'wk' => 'week',
				'd' => 'day',
				'h' => 'hour',
				'hr' => 'hour',
				'min' => 'minute',
				's' => 'second',
				'sec' => 'second',
			);
			if (isset($unit_map[$unit])){
				$unit = $unit_map[$unit];
			} else {
				return null;
			}
			if ($grammar){
				return TextUtils::plurality('# '.$unit.'(s)', $qty);
			} else {
				return $qty.' '.$unit;
			}
		}
		return null;
	}
	
	public static function shorthandToMonth($shorthand){
		switch ($shorthand){
			case 'Jan': return 1;
			case 'Feb': return 2;
			case 'Mar': return 3;
			case 'Apr': return 4;
			case 'May': return 5;
			case 'Jun': return 6;
			case 'Jul': return 7;
			case 'Aug': return 8;
			case 'Sep': return 9;
			case 'Oct': return 10;
			case 'Nov': return 11;
			case 'Dec': return 12;
		}
		return null;
	}
	
	public static function shorthandToSQL($shorthand,$future=false){
		if ($month = self::shorthandToMonth($shorthand)){
			$info = getdate();
			$curr_month = $info['mon'];
			$curr_year = $info['year'];
			$month_start = mktime(0,0,0,$month,1,$curr_year);
			if ($curr_month == $month){
				if ($future){					
					$end = strtotime('+1 month', $month_start);
					return 'BETWEEN NOW() AND '.SQLUtils::formatTimestamp($end);
				} else {
					$start = $month_start;
					return 'BETWEEN '.SQLUtils::formatTimestamp($start).' AND NOW()';
				}
			} else{ 
				if ($curr_month < $month) {
					if ($future){
						$start = $month_start;
					} else {
						$start = mktime(0,0,0,$month,1,$curr_year-1);
					}
				} else {
					if ($future){
						$start = mktime(0,0,0,$month,1,$curr_year+1);
					} else {
						$start = $month_start;
					}
				}
				$end = strtotime('+1 month', $start);
			}
			return 'BETWEEN '.SQLUtils::formatTimestamp($start).' AND '.SQLUtils::formatTimestamp(strtotime('-1 second',$end));
		}
		if ($duration = DateUtils::shorthandToInterval($shorthand)){
			if ($future){
				return 'BETWEEN NOW() AND NOW() + INTERVAL '.$duration;					
			} else {
				return 'BETWEEN NOW() - INTERVAL '.$duration.' AND NOW()';					
			}
		} 
		return null;
	}
		
}
?>