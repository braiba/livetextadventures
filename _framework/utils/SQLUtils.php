<?php

/**
 * SQL Utilities class
 * @author Thomas
 */
class SQLUtils {

	private function SQLUtils(){
		
	}
	
	/**
	 *
	 * @param string $columnName
	 * @param mixed $value
	 * @return string 
	 */
	public static function equals($columnName,$value){
		if (is_string($value)){
			$value = self::formatString($value);
			return "$columnName = $value";
		}
		if (is_null($value)){
			return "$columnName IS NULL";
		}
		return "$columnName = $value";
	}
	
	/**
	 * This builds a MySQL 'IN' expression.  It is useful, because it will deal with the case that
	 * 	$set is an empty array, whereas using a simple implode causes MySQL to return a syntax
	 * 	error in this case.
	 * @param string $field The field or value being checked against the set.
	 * @param array $set The list of values $value is to be checked against.
	 * @param boolean $quote_values If true, single quotes will be inserted around the values in $set (use if $set is an array of strings).
	 * @return string Returns the MySQL 'IN' expression.
	 */
	public static function buildIn($field,$set,$quote_values=false){
		if (!is_array($set)){
			$set = array($set);
		}
		switch (sizeof($set)){
			case 0:
				return "FALSE /* $field IN <empty set> (MySQL doesn't consider the empty set to be valid) */";
			case 1:
				$value = ArrayUtils::getFirst($set);
				if ($quote_values){
					$value = self::formatString($value);
				}
				return "$field <=> $value";
			default:
				if ($quote_values){
					$set = array_map(array('SQLUtils','formatString'),$set);
				}
				$set_str = implode(', ',$set);
				return "$field IN ($set_str)";
		}
	}

	/**
	 *
	 * @param string $field The field (or value) being queried
	 * @param int $start Timestamp of the start point
	 * @param int $end Timestamp of the start point
	 * @return string
	 */
	public static function buildBetweenDates($field,$start,$end){
		return $field.' BETWEEN '.self::formatString(date('Y-m-d',$start)).' AND '.self::formatString(date('Y-m-d',$end));
	}

	/**
	 *
	 * @param string $field The field (or value) being queried
	 * @param int $start Timestamp of the start point
	 * @param int $end Timestamp of the start point
	 * @return string
	 */
	public static function buildBetweenDatetimes($field,$start,$end){
		return $field.' BETWEEN '.self::formatTimestamp($start).' AND '.self::formatTimestamp($end);
	}

	/**
	 *
	 * @param string $field The field (or value) being queried
	 * @param int $start Timestamp of the start point
	 * @param int $end Timestamp of the start point
	 * @return string
	 */
	public static function buildBetweenDate($field,$start,$end){
		return $field.' BETWEEN '.self::formatDate($start).' AND '.self::formatDate($end);
	}

	public static function invertOrderClause($orderBy){
		$parts = explode(',',$orderBy);
		$parts = array_map(
			function($value){
				$value = trim($value);
				$value = preg_replace('/\\bASC\\b/i', 'DESC', $value, 1, $count);
				if ($count==0){
					$value = preg_replace('/\\bDESC\\b/i', 'ASC', $value, 1, $count);
					if ($count==0){
						$value.= ' DESC';
					}
				}
				return $value;
			},
			$parts
		);
		return implode(', ',$parts);
	}

	public static function buildTextSearch($field,$str,$matchAny=false){
		$parts = array();
		foreach (preg_split('/[^a-z0-9]+/i',trim($str)) as $word){
			$parts[] = "`$field` LIKE '%".self::escapeString($word)."%'";
		}
		return implode(($matchAny?' OR ':' AND '),$parts);
	}

	public static function escapeLike($str){
		return preg_replace('/(?<!\\\\)([_%])/','\\\\$1',$str);
	}
	
	public static function escapeRegexp($str){
		return preg_replace('/(?<!\\\\)(['.preg_quote('.\\+*?[^]$(){}=!<>|:-').'])/','\\\\\\\\$1',$str);
	}
	
	/**
	 * Escapes a string value for use in SQL, but does not add quotation marks
	 * @param string $str
	 * @return string
	 */
	public static function escapeString($str){
		return SQL::getDefaultConnection()->real_escape_string($str);
	}
	/**
	 * Escapes and quotes a string value for use in SQL
	 * @param string $str
	 * @return string
	 */
	public static function formatString($str,$encoding=null){
		if ($encoding!=null){
			$str = mb_convert_encoding($str,$encoding);
		}
		return '\''.self::escapeString($str).'\'';
	}
	/**
	 * Generates a valid SQL representation of the given unix timestamp as a Timestamp value
	 * @param int $timestamp
	 * @return string
	 */
	public static function formatTimestamp($timestamp){
		return self::formatString(date('Y-m-d H:i:s',$timestamp));
	}
	/**
	 * Generates a valid SQL representation of the given unix timestamp as a Date value
	 * @param int $timestamp
	 * @return string
	 */
	public static function formatDate($timestamp){
		return self::formatString(date('Y-m-d',$timestamp));
	}

	/**
	 * Updates a subsection of a table so that it matches a list of intended values.
	 * @param string|SQLTable $table The table being updated. If a string is specified, an SQLTable will be retrieved with SQLTable::get()
	 * @param array $intended An array of intended value sets, where each value set is specified as an array in the format column_name=>value.
	 * @param array $where An array describing the subection of the table that we are updating in the format column_name=>value.
	 * @return boolean Returns true if successful and false otherwise.
	 */
	public static function updateJoiningTable($table,$intended,$where){
		if (is_string($table)){
			$table = SQLTable::get($table);
		}
		
		$where_sql = $table->generateQuerySQL($where);
		
		if (empty($intended)){
			self::query('DELETE FROM '.$table->getFullName().' WHERE '.$where_sql);
			return true;
		}
		$intended = array_values($intended);
				
		$columns = array_keys($intended[0]);
		sort($columns); // just in case one of the entires specifies the keys in a different order.
		if (sizeof($intended)>1){
			// Check that the same columns have been specified for each entry
			for ($i=1; $i<sizeof($intended); $i++){
				$i_columns = array_keys($intended[$i]);
				sort($i_columns);
				if ($i_columns!=$columns){
					Messages::msg('The columns specified for entry '.$i.' in the intended list for self::updateJoiningTable() do not match those specified for the preceeding entry/entries. All entries must specify the same column names.',Messages::_M_CODE_ERROR);
					return false;
				}
			}
		}
				 
		$res = self::query('SELECT '.implode(', ',$columns).
	  					   ' FROM '.$table->getFullName().
	  					  ' WHERE '.$where_sql);
		
		// Assume we need to insert all of them, then remove them from this list as we find matches in the database
		$insert_list = $intended;
		$delete_list = array();
		
		// Compare $res and $insert list (remove matches from $insert_list, add non-matches from $res to $delete_list)
		foreach ($res as $row){
			foreach ($insert_list as $i=>$value_set){
				foreach ($value_set as $column=>$value){
					if ($value != $row[$column]){
						// There is a value in $insert_list[$i] that doesn't match the relevant column in $row,
						//   so we can move on to the next item in $insert_list
						continue 2;
					}
				}
				// The record, $row, matches item $i in the 'intended' list. Since we have found a match for $row,
				//   we can move on through $res.
				unset($insert_list[$i]);
				continue 2;
			}
			// The record, $row,  does not match any in the 'intended' list, so we add it to the delete list
			$to_delete = array();
			foreach ($columns as $column){
				$to_delete[$column] = $row[$column];
			}
			$delete_list[] = $to_delete;
		}
		
		// Process the INSERT list
		if (!empty($insert_list)){
			$values = array();
			$all_columns = array_merge($columns,array_keys($where));
			foreach ($insert_list as $insert){
				$sub_values = array();
				foreach ($all_columns as $column){
					if (in_array($column,$columns)){
						$sub_values[] = $table->$column->valueAsSQL($insert[$column]);
					} else {
						$sub_values[] = $table->$column->valueAsSQL($where[$column]);
					}
				}
				$values[] = '('.implode(', ',$sub_values).')';
			}
			$sql = 'INSERT INTO '.$table->getFullName().' '.
						'('.TextUtils::implodePairs($all_columns,',','`$value`').') '.
					'VALUES '.implode(', ',$values);
			self::query($sql);
		}
		
		// Process the DELETE list
		if (!empty($delete_list)){
		$values = array();
			$values = array();
			foreach ($delete_list as $delete){
				$sub_values = array();
				foreach ($columns as $column){
					$sub_values[] = $table->$column->valueAsSQL($delete[$column]);
				}
				$values[] = '('.implode(', ',$sub_values).')';
			}
			$sql = 'DELETE FROM '.$table->getFullName().' '.
					'WHERE ('.implode(', ',$columns).') IN ('.implode(', ',$values).') '.
					  'AND '.$where_sql;
			self::query($sql);
		}
		 
		return true;
	}

	/*
	 * Used by self::makeQueryReadable_indentSQL, but refered to as a string there, so netbeans thinks this method is unused
	 */
	private static function makeQueryReadable_indentSQL_callback($match){
		return $match['open'].preg_replace('/^.+$/m',"\t".'$0',self::makeQueryReadable_indentSQL($match['contents'])).$match['close'];
	}
	private static function makeQueryReadable_indentSQL($sql){
		return preg_replace_callback(
			'/
				(?<open>\\(\\h*\\v)
				(?<contents>
					[^()]*
					(
						(?<brackets>
							\\(
							(?>(?P>brackets)|[^()]+)*
							\\)
						)
						[^()]*
					)*
				)
				(?<close>\\v\\s*\\))
			/x',
			array('SQLUtils','makeQueryReadable_indentSQL_callback'),
			$sql
		);
	}

	public static function makeQueryReadable($sql){
		$str_list = array();

		// Remove and store string literals so that they don't confuse things.
		$sql = preg_replace_callback(
			'/(["\'])(.*?)(?<!\\\\)\\1/s',
			function($match) use (&$str_list){
				$replace = '{{{MySQL_STRING_'.sizeof($str_list).'}}}';
				$str_list[] = $match[0];
				return $replace;
			},
			$sql
		);

		// Purge existing whitespace
		$sql = preg_replace('/\\s+/',' ',$sql);

		// New line after a comma or an open bracket
		$sql = preg_replace('/([(,])\\s*/','$1'."\r\n",$sql);

		// New line before these keywords
		$sql = preg_replace('/(?!<\\s)\\v*(\\h*)\\v*(SELECT|INSERT|UPDATE|DELETE|FROM|WHERE|ON|AND|(LEFT\\s+)?((INNER|OUTER)\\s+)?JOIN)\\b/','$1'."\r\n".'$2',$sql);

		// New line after SELECT
		$sql = preg_replace('/(SELECT)\\s*/i','$1'."\r\n",$sql);

		// Functions, and USING, should be on one line
		$sql = preg_replace_callback(
			'/([A-Z_]+|USING\\s+)(\\((?>[^()]+|(?2))*\\))/',
			function ($match){
				return str_replace("\r\n",'',$match[0]);
			},
			$sql
		);

		// If a table or variable is a statement, that statment should have a new line before its closing bracket
		do {
			$sql = preg_replace_callback(
				'/(,\\v+\\s*|=\\s+|(FROM|JOIN|SELECT)\\s*|CREATE (TEMPORARY )?TABLE \\S+\\s*)(?<brackets>\\((?>[^()]+|(?&brackets))*(?<![\\v])\\))/',
				function ($match){
					return $match[1].preg_replace('/\\)$/',"\r\n)",$match['brackets']);
				},
				$sql,
				-1, // infinite
				$count
			);
		} while ($count!=0); // doesn't search inside the replacement it just made, so we need to repeat. FRAMEWORK: replace with recursion

		// Remove linebreaks from BETWEEN ... AND
		$sql = preg_replace_callback(
			'/BETWEEN\\s+\\S+\\s+AND\\s+\\S+/i',
			function ($match){
				return preg_replace('/\\s+/',' ',$match[0]);
			},
			$sql
		);

		// Remove linebreaks from JOIN ... ON ... so long as there is only one line per condition
		$sql = preg_replace_callback(
			'/JOIN\\b\\V+\\v+\\V*\\bON\\b\\V+(\\v+\\V*\\b(AND|OR)\\b\\V+)*(?=\\v)/i',
			function ($match){
				return preg_replace('/\\s+/',' ',$match[0]);
			},
			$sql
		);

		// Remove whitespace from GROUP BY ..., ORDER BY ... and HAVING ...
		$sql = preg_replace_callback(
			'/(GROUP BY|ORDER BY)\\s+([^()]+|\\((?R)\\))/i',
			function ($match){
				return preg_replace('/\\s+/',' ',$match[0])."\r\n";
			},
			$sql
		);
			
		// New line before these keywords
		$sql = preg_replace('/(ORDER BY|GROUP BY|HAVING)/i',"\r\n".'$1',$sql);

		// Contract sets onto one line
		$sql = preg_replace_callback(
			'/\\W\\(  [^()]+  \\)/xi',
			function($match){
				return preg_replace("#\\v+#",'',$match[0]);
			},
			$sql
		);
/* */
		// indent
		$sql = self::makeQueryReadable_indentSQL($sql);
		
		// reinsert the removed string literals
		$sql = preg_replace_callback(
			'/{{{MySQL_STRING_([0-9]+)}}}/',
			function($match) use (&$str_list){
				return $str_list[$match[1]];
			},
			$sql
		);

		return trim($sql);
	}
	
	public static function varExportQuery($sql,$preformatted=false){
		if (!$preformatted){
			$sql = self::formatQuery($sql);
		}
		$sql = preg_replace('/^(\\s*)(.+?)\\s*$/m',"\t".'$1 . \' $2\'',$sql);
		return preg_replace('/^\\t . \' /','$sql = \'',$sql).';'."\r\n";
	}
}

?>