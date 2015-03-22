<?php

/**
 * A static class for number-based methods
 *
 * @author Thomas
 */
class NumberUtils {
	
	protected static $orders_of_magnitude = array(
		1=>'thousand',
		'million',
		'billion',
		'trillion',
		'quadrillion',
		'quintillion',
		'sextillion',
		'septillion',
		'octillion',
		'nonillion',
		'decillion',
		'undecillion',
		'duodecillion',
		'tredecillion',
		'quattuordecillion',
		'quindecillion',
		'sexdecillion',
		'septendecillion',
		'octodecillion',
		'novemdecillion'
	);
	protected static $base_numbers = array( // 1..19
		1=>'one',
		'two',
		'three',
		'four',
		'five',
		'six',
		'seven',
		'eight',
		'nine',
		'ten',
		'eleven',
		'twelve',
		'thirteen',
		'fourteen',
		'fifteen',
		'sixteen',
		'seventeen',
		'eighteen',
		'nineteen'
	);
	protected static $base_inverses = array( // 1^-1..19^-1
		1=>'first',
		'second',
		'third',
		'fourth',
		'fifth',
		'sixth',
		'seventh',
		'eighth',
		'nineth',
		'tenth',
		'eleventh',
		'twelvth',
		'thirteenth',
		'fourteenth',
		'fifteenth',
		'sixteenth',
		'seventeenth',
		'eighteenth',
		'nineteenth'
	);
	
	/**
	 * Unlike is_int and its alias is_integer, this checks the actual value of the number, not the data type of the variable.
	 * It also uses the mathematical definition of integer, rather than limiting to numbers between the boundaries of the integer
	 * datatype (usually +/- 2.15e+9 = 2^31 on 32-bit platforms and +/- 9.22e+18 = 2^63 on 64-bit platforms).
	 * @param number|string $number Number or numeric string.
	 * @return boolean
	 */
	public static function isInteger($number){
		if (!is_numeric($number)) return false;
		return preg_match('/^[0-9]+(\\.0+)?$/',$number);
	}
	
	public static function round_significant($number,$significant_figures=3){
		$shift_size = 0;
		if (preg_match('/\\.[0-9]+E([+\\-][0-9]+)/',$number,$match)){
			$shift_size = $match[1];
		} elseif (preg_match('/\\.([0-9]+)$/',$number,$match)){
			$shift_size = strlen($match[1]);
		}
		$number*= pow(10,$shift_size);
		$digits = strlen($number);
		$number = "0.$number";
		$number = round($number,$significant_figures);
		$number*= pow(10,$digits - $shift_size);
		return $number;
	}
	
	public static function numberToFraction($number){
		$neg = ($number<0);
		$number = self::round_significant($number,8); // prevent it taking aaaaages
		$top = abs($number);
		$bottom = 1;
		$shift_size = 0;
				
		if (preg_match('/\\.[0-9]+E([+\\-][0-9]+)/',$number,$match)){
			$shift_size = $match[1];
		} elseif (preg_match('/\\.([0-9]+)$/',$number,$match)){
			$shift_size = strlen($match[1]);
		}
		$shift = pow(10,$shift_size);
		$top*= $shift;
		$bottom*= $shift;
		
		// reverses the string to make the attern matching *way* more efficient. Also drop the last digit, since it's probably been rounded.
		$digit_str = substr(strrev($top),1);
		if (preg_match('/^([0-9]+?)(\\1\\1.+)$/',$digit_str,$match)){
			// Shift so that first occurance of repeating phrase ends at the decimal point
			$repeat_offset = - strlen($match[2]);
			$shift = pow(10,$repeat_offset);
			$top*= $shift;
			$bottom*= $shift;
			// x*10^repeat_size - x*(10^repeat_size-1) ~ standard mathematical method for handling repeating digits
			$repeat_size = strlen($match[1]);
			$x = pow(10,$repeat_size);
			$top = floor($top*$x)-floor($top);
			$bottom*= ($x-1);
			while (!self::isInteger($bottom)){
				$top*= 10;
				$bottom*= 10;
			}
		}

		$timer = Timer::startTimer();
		
		if (self::isInteger($new_bottom = $bottom/$top)){
			$top = 1;
			$bottom = $new_bottom;
		}
		
		$prime = 0;
		$div = self::getPrime($prime);
		$smallest = ($top<$bottom?$top:$bottom);
		
		while (true){
			$new_top = $top/$div;
			$new_bottom = $bottom/$div;
			if (self::isInteger($new_top) && self::isInteger($new_bottom)){
				$top = $new_top;
				$bottom = $new_bottom;
				$smallest/= $div;
				if ($div*$div > $smallest) break;
			} else {
				if ($div*$div > $smallest) break;
				$div = self::getPrime(++$prime);
			}
		}
		if ($neg) $top*= -1;
		return array($top,$bottom,'top'=>$top,'bottom'=>$bottom);
	}
	
	
	protected static $primes = array(2);
	
	public static function getPrime($i){
		if (!is_integer($i) || $i<0) return null;
		if (array_key_exists($i,self::$primes)){
			return self::$primes[$i];
		}
		$candidate = end(self::$primes);
		$pos = key(self::$primes);
		do {
			$candidate++;
			foreach (self::$primes as $prime){
				if ($prime*$prime>$candidate) break;
				if ($candidate % $prime == 0) continue 2;
			}
			$pos++;
		} while ($pos!=$i);
		self::$primes[] = $candidate;
		return $candidate;
	}
	
	protected static function textNumber_threeDigit($number,$inverse=false){
		if (($number<20) && $inverse){
			return self::$base_inverses[$number];
		}
		if ($number<20){
			return self::$base_numbers[$number];
		} else {
			$hundreds = floor($number/100);
			$str = '';
			if ($hundreds!=0){
				$str = self::textNumber_threeDigit($hundreds,$inverse).' hundred';
				$number-= $hundreds*100;
				if ($number==0) return $str;
				$str.= ' and ';
			}
			if ($number<20){
				$str.= self::textNumber_threeDigit($number,$inverse);
			} else {
				$tens = floor($number/10);
				switch ($tens){
					case 2: $str.= 'twenty'; break;
					case 3: $str.= 'thirty'; break;
					case 4: $str.= 'forty';  break;
					case 5: $str.= 'fifty';  break;
					case 6: $str.= 'sixty';  break;
					case 7: $str.= 'seventy';break;
					case 8: $str.= 'eighty'; break;
					case 9: $str.= 'ninty';  break;
				}
				$number-= $tens*10;
				if ($number==0) return $str;
				$str.= ' '.self::textNumber_threeDigit($number,$inverse);
			}
			return $str;
		}
	}
	protected static function textNumber_integer($integer,$inverse=false){
		$reverse = strrev($integer);
		$segs = str_split($reverse,3);
		$parts = array();
		foreach ($segs as $i=>$seg){
			if ($seg!=0){
				$parts[] = self::textNumber_threeDigit(strrev($seg),$inverse).( $i!=0 ? ' '.self::$orders_of_magnitude[$i] : '' );
			}
		}
		$hasAnd = (strpos(' and ',$parts[0])===false);
		$parts = array_reverse($parts);
		return ( $hasAnd ? implode(', ',$parts) : TextUtils::andList($parts) );
	}
	protected static function textNumber_fraction($denominator,$plural){
		switch ($denominator){
			case 1: return '';
			case 2: return ($plural?'halves':'half');
			case 4: return 'quarter'.($plural?'s':'');
			default: return self::textNumber_integer($denominator,true).($plural?'s':'');
		}
	}
	
	public static function textNumber($number){
		if ($number==0) return 'zero';
		$neg = ($number<0?true:false);
		$abs = abs($number);
		$int = floor($abs);
		$parts = array();
		
		if ($int!=0){
			$parts[] = self::textNumber_integer($int);
		}
		
		if (!self::isInteger($number)){
			$decimal = $abs-$int;
			list($numerator,$denominator) = Number::numberToFraction($decimal);
			$top = ($numerator==1 ? 'a' : self::textNumber($numerator) );
			$bottom = self::textNumber_fraction($denominator,($numerator!=1));
			$parts[] = "$top $bottom";
		}
				
		return ($neg?'minus ':'').implode(' and ',$parts);
	}
	
	public static function parseList($numberList){
		$numbers = array();
		foreach (explode(',',$numberList) as $listItem){
			if (preg_match('/^\\s*([+\\-]?[0-9]+(?:\\.[0-9])?)\\s*\\-\\s*([+\\-]?[0-9]+(?:\\.[0-9])?)\\s*$/',$listItem,$match)){
				list(,$a,$b) = $match;
				if (!is_numeric($a)){
					Messages::msg("'$a' is not a valid numeric value.",Messages::M_WARNING);
					continue;
				}
				if (!is_numeric($b)){
					Messages::msg("'$b' is not a valid numeric value.",Messages::M_WARNING);
					continue;
				}
				if ($a>$b){
					$c = $a;
					$a = $b;
					$b = $c;
				}
				for ($i=$a;$i<=$b;$i++){
					$numbers[] = $i;
				}
			} else if (is_numeric($listItem)){
				$numbers[] = $listItem;
			} else {
				Messages::msg("'$listItem' is not a valid number list entry.",Messages::M_WARNING);
			}
		}
		return $numbers;
	}

	public static function bound($n,$min,$max){
		if ($n<$min) return $min;
		if ($n>$max) return $max;
		return $n;
	}

}
?>