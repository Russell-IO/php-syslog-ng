<?php
/*
 * Parameters available :
 * SELECT, DISTINCT, FROM, WHERE, ORDER BY, LIMIT, OFFSET
 *
 * Operators available :
 * =, <, >, <=, >=, <>, !=, IS, IS IN, IS NOT, IS NOT IN, LIKE, ILIKE, NOT LIKE, NOT ILIKE
 *
 * Functions available in WHERE parameters :
 * LOWER(var), UPPER(var), TRIM(var)
 */
class jqGridArray
{
	/* Init
	-------------------------------------------- */
	private $query				= FALSE;
	private $parse_query		= FALSE;
	private $parse_query_lower	= FALSE;
	private $parse_select		= FALSE;
	private $parse_select_as	= FALSE;
	private $parse_from			= FALSE;
	private $parse_from_as		= FALSE;
	private $parse_where		= FALSE;
	private $distinct_query		= FALSE;
	private $count_query		= FALSE;
	private $tables				= array();
	private $response			= array();

	/* Query function
	-------------------------------------------- */
	public function query($query)
	{
		$this->destroy();
		$this->query = $query;
		$this->parse_query();
		$this->parse_select();
		$this->parse_select_as();
		$this->parse_from();
		$this->parse_from_as();
		$this->parse_order();
		$this->parse_where();
		$this->exec_query();
		//$this->parse_order();

		return $this->return_response();
	}
	public function execute($query, $params = null)
	{
		if(is_array($params) && count($params) > 0 )
		{
			$prmcount = substr_count($query, '?');
			$acount = count($params);
			if($prmcount != $acount) {
				return false;
			} else {
				for($i=1; $i<=$prmcount; $i++) {
					$v = $params[$i-1];
					if(is_integer($v)) { $v = (int)$v; }
					else if(is_numeric($v)) $v = (float)$v;
					$query = substr_replace($query, $v, strpos($query, '?') , 1);
				}
			}
		}
		return $this->query($query);
	}
	/* Destroy current values
	-------------------------------------------- */
	private function destroy()
	{
		$this->query				= FALSE;
		$this->parse_query			= FALSE;
		$this->parse_query_lower	= FALSE;
		$this->parse_select			= FALSE;
		$this->parse_select_as		= FALSE;
		$this->parse_from			= FALSE;
		$this->parse_from_as		= FALSE;
		$this->parse_where			= FALSE;
		$this->distinct_query		= FALSE;
		$this->count_query			= FALSE;
		$this->tables				= array();
		$this->response				= array();
	}

	/* Parse SQL query
	-------------------------------------------- */
	private function parse_query()
	{
		$this->parse_query 			= preg_replace('#ORDER(\s){2,}BY(\s+)(.*)(\s+)(ASC|DESC)#i', 'ORDER BY \\3 \\5', $this->query);
		$this->parse_query 			= preg_split('#(SELECT|DISTINCT|FROM|JOIN|WHERE|ORDER(\s+)BY|LIMIT|OFFSET|COUNT)+#i', $this->parse_query, -1, PREG_SPLIT_DELIM_CAPTURE);
		$this->parse_query			= array_map('trim', $this->parse_query);
		$this->parse_query_lower	= array_map('strtolower', $this->parse_query);
	}

	/* Parse SQL select parameters
	-------------------------------------------- */
	private function parse_select()
	{
		$key = array_search("distinct", $this->parse_query_lower);

		if ($key === FALSE) {
			$key = array_search("select", $this->parse_query_lower);
			//$key1 = array_search("count", $this->parse_query_lower);
			if( array_search("count", $this->parse_query_lower) ) {
				$this->count_query = TRUE;
			}
		} else {
			$this->distinct_query = TRUE;
		}
		$string	= $this->parse_query[$key+1];

		$arrays	= preg_split('#((\s)*,(\s)*)#i', $string, -1, PREG_SPLIT_NO_EMPTY);

		foreach ($arrays as $array)
			$this->parse_select[] = $array;
	}

	/* Parse again SQL select parameters with as keyword
	-------------------------------------------- */
	private function parse_select_as()
	{
		if(empty($this->parse_select)) return;
		foreach ($this->parse_select as $select)
		{
			if (preg_match('/ AS /i', $select))
			{
				$arrays	= preg_split('#((\s)+AS(\s)+)#i', $select, -1, PREG_SPLIT_NO_EMPTY);
				$this->parse_select_as[$arrays[1]] = $arrays[0];
			}
			else
			{
				$this->parse_select_as[$select] = $select;
			}
		}
	}

	/* Parse SQL from parameters
	-------------------------------------------- */
	private function parse_from()
	{
		$key	= array_search("from", $this->parse_query_lower);
		$string	= $this->parse_query[$key+1];
		$arrays	= preg_split('#((\s)*,(\s)*)#i', $string, -1, PREG_SPLIT_NO_EMPTY);

		foreach ($arrays as $array)
			$this->parse_from[] = $array;
	}

	/* Parse again SQL from parameters with as keyword
	-------------------------------------------- */
	private function parse_from_as()
	{
		foreach ($this->parse_from as $from)
		{
			if (preg_match('/\bAS\b/i', $from))
			{
				$arrays	= preg_split('#((\s)+AS(\s)+)#i', $from, -1, PREG_SPLIT_NO_EMPTY);

				$table = $arrays[0];
				global $$table;
				$this->parse_from_as[$arrays[1]] = $table;
				$this->tables[$arrays[1]] = $$table;
			}
			else
			{
				$table = $from;
				global $$table;
				$this->parse_from_as[$from] = $table;
				$this->tables[$from] = $$table;
			}

		}
	}

	/* Parse SQL where parameters
	-------------------------------------------- */
	private function parse_where()
	{
		$key	= array_search("where", $this->parse_query_lower);

		if ($key == FALSE)
			return $this->parse_where = "return TRUE;";

		$string	= $this->parse_query[$key+1];
		if (trim($string) == '')
			return $this->parse_where =  "return TRUE;";

		/* SQL Functions
		-------------------------------------------- */
		$patterns[]		= '#LOWER\((.*)\)#ie';
		$patterns[]		= '#UPPER\((.*)\)#ie';
		$patterns[]		= '#TRIM\((.*)\)#ie';

		$replacements[]	= "'strtolower(\\1)'";
		$replacements[]	= "'strtoupper(\\1)'";
		$replacements[]	= "'trim(\\1)'";

		/* Basics SQL operators
		-------------------------------------------- */
		$patterns[]		= '#(([a-zA-Z0-9\._]+)(\())?([a-zA-Z0-9\._]+)(\))?(\s)+(=|IS)(\s)+([[:digit:]]+)(\s)*#ie';
		$patterns[]		= '#(([a-zA-Z0-9\._]+)(\())?([a-zA-Z0-9\._]+)(\))?(\s)+(=|IS)(\s)+(\'|\")(.*)(\'|\")(\s)*#ie';
		$patterns[]		= '#(([a-zA-Z0-9\._]+)(\())?([a-zA-Z0-9\._]+)(\))?(\s)+(>|<)(\s)+([[:digit:]]+)(\s)*#ie';
		$patterns[]		= '#(([a-zA-Z0-9\._]+)(\())?([a-zA-Z0-9\._]+)(\))?(\s)+(>|<)(\s)+(\'|\")(.*)(\'|\")*#ie';

		$patterns[]		= '#(([a-zA-Z0-9\._]+)(\())?([a-zA-Z0-9\._]+)(\))?(\s)+(<=|>=)(\s)+([[:digit:]]+)(\s)*#ie';
		$patterns[]		= '#(([a-zA-Z0-9\._]+)(\())?([a-zA-Z0-9\._]+)(\))?(\s)+(<=|>=)(\s)+(\'|\")(.*)(\'|\")*#ie';
		$patterns[]		= '#(([a-zA-Z0-9\._]+)(\())?([a-zA-Z0-9\._]+)(\))?(\s)+(<>|IS NOT|!=)(\s)+([[:digit:]]+)(\s)*#ie';
		$patterns[]		= '#(([a-zA-Z0-9\._]+)(\())?([a-zA-Z0-9\._]+)(\))?(\s)+(<>|IS NOT|!=)(\s)+(\'|\")(.*)(\'|\")(\s)*#ie';
		$patterns[] 	= '#(([a-zA-Z0-9\._]+)(\())?([a-zA-Z0-9\._]+)(\))?(\s)+(IS)?(NOT IN)(\s)+\((.*)\)#ie';
		$patterns[] 	= '#(([a-zA-Z0-9\._]+)(\())?([a-zA-Z0-9\._]+)(\))?(\s)+(IS)?(IN)(\s)+\((.*)\)#ie';

		$replacements[]	= "'\\1'.\$this->parse_where_key(\"\\4\").'\\5 == \\9 '";
		$replacements[]	= "'\\1'.\$this->parse_where_key(\"\\4\").'\\5 == \"\\10\" '";
		$replacements[]	= "'\\1'.\$this->parse_where_key(\"\\4\").'\\5 \\7 \\9 '";
		$replacements[]	= "'\\1'.\$this->parse_where_key(\"\\4\").'\\5 \\7 \'\\10\ '";

		$replacements[]	= "'\\1'.\$this->parse_where_key(\"\\4\").'\\5 \\7 \\9 '";
		$replacements[]	= "'\\1'.\$this->parse_where_key(\"\\4\").'\\5 \\7 \'\\10\ '";
		$replacements[]	= "'\\1'.\$this->parse_where_key(\"\\4\").'\\5 != \\9 '";
		$replacements[]	= "'\\1'.\$this->parse_where_key(\"\\4\").'\\5 != \"\\10\" '";
		$replacements[]	= "'\\1'.\$this->parse_where_key(\"\\4\").'\\5 != ('.\$this->parse_in(\"\\10\").') '";
		$replacements[]	= "'\\1'.\$this->parse_where_key(\"\\4\").'\\5 == ('.\$this->parse_in(\"\\10\").') '";

		/* match SQL operators
		-------------------------------------------- */
		//$ereg = array('%' => '(.*)', '_' => '(.)');

		$patterns[] 	= '#([a-zA-Z0-9\._]+)(\s)+NOT LIKE(\s)*(\'|\")(.*)(\'|\")#ie';
		//$patterns[] 	= '#([a-zA-Z0-9\._]+)(\s)+NOT ILIKE(\s)*(\'|\")(.*)(\'|\")#ie';
		$patterns[] 	= '#([a-zA-Z0-9\._]+)(\s)+LIKE(\s)*(\'|\")(.*)(\'|\")#ie';
		//$patterns[] 	= '#([a-zA-Z0-9\._]+)(\s)+ILIKE(\s)*(\'|\")(.*)(\'|\")#ie';

		$replacements[]	= "'!\$this->isLike(\"\\5\", '.\$this->parse_where_key(\"\\1\").')'";
		//$replacements[]	= "'!\$this->isLike(\"\\5\", '.\$this->parse_where_key(\"\\1\").')'";
		$replacements[]	= "'\$this->isLike(\"\\5\", '.\$this->parse_where_key(\"\\1\").')'";
		//$replacements[]	= "'\$this->isLike(\"\\5\", '.\$this->parse_where_key(\"\\1\").')'";

		$wdata = explode(" AND ", $string);
		foreach($wdata as $k=>$wh) {
			$wdataor = explode(" OR ", $wh);

			foreach($wdataor as $kk=>$whor) {
				$wdataor[$kk] = stripslashes(trim(preg_replace($patterns, $replacements, $whor)));
	}
			$wdata[$k] = implode(" OR ", $wdataor);
		}
		$this->parse_where = "return ".implode(" AND ", $wdata).";";
		//"return ".stripslashes(trim(preg_replace($patterns, $replacements, $string))).";";
	}

	/*
	-------------------------------------------- */
	private function parse_where_key($key)
	{
		if ( preg_match('/\./', $key) )
		{
			list($table, $col) = explode('.', $key);
			//return '$row[$this->parse_select_as['.$col.']]';
			return '$row[\''.$col.'\']';
		}
		else
		{
			//return '$row[$this->parse_select_as['.$key.']]';
			return '$row[\''.$key.'\']';
		}
	}

	/* Format IN parameters for PHP
	-------------------------------------------- */
	private function parse_in($string)
	{
		$array	= explode(',', $string);
		$array	= array_map('trim', $array);

		return implode(' || ', $array);
	}
	private function isLike($needle,$haystack) {
		$regex = '#^'.preg_quote($needle, '#').'$#i';
		//add support for wildcards
		$regex = str_replace(array('%', '_'), array('.*?', '.?'), $regex);
		return 0 != preg_match($regex, $haystack);
	}

	/* Execute query
	-------------------------------------------- */
	private function exec_query()
	{
		$klimit		= array_search("limit", $this->parse_query_lower);
		$koffset	= array_search("offset", $this->parse_query_lower);
		if ($klimit !== FALSE)
			$limit	= (int) $this->parse_query[$klimit+1];

		if ($koffset !== FALSE)
			$offset	= (int) $this->parse_query[$koffset+1];
		$irow		= 0;
		$rcount = 0;
		$distinct	= array();
		foreach ($this->tables as $table)
		{
			foreach ($table as $row)
			{
				// Offset
				//if ($koffset !== FALSE && $irow < $offset)
				//{
					//$irow++;
					//continue;
				//}

				if (eval($this->parse_where))
				{
					if ($koffset !== FALSE && $irow < $offset)
					{
						$irow++;
						continue;
					}

					if ($this->parse_select[0] == '*')
					{
						if($this->count_query == FALSE) {
							foreach (array_keys($row) as $key)
								$temp[$key] = $row[$key];

							if ($this->distinct_query && in_array($temp, $distinct))
								continue;
							else
								$this->response[] = $temp;

							$distinct[] = $temp;
						}
						$rcount++;
					}
					else
					{
						if($this->count_query == FALSE) {
							foreach ($this->parse_select_as as $key => $value)
								$temp[$key] = $row[$value];

							if ($this->distinct_query && in_array($temp, $distinct))
								continue;
							else
								$this->response[] = $temp;

							$distinct[] = $temp;
						}
						$rcount++;
					}
					// Limit
					if ($klimit !== FALSE && count($this->response) == $limit)
						break;
					$irow++;
				}
				if($this->count_query==TRUE) {
					$this->response = array("COUNT"=>$rcount);
				}

			}
		}
	}

	/* Parse SQL order by parameters
	-------------------------------------------- */
	private function parse_order()
	{
		$key	= array_search("order by", $this->parse_query_lower);

		if ($key === FALSE)
			return;

		$string	= $this->parse_query[$key+2];
		$arrays	= explode(',', $string);
		if (!is_array($arrays))
			$arrays[] = $string;
		$arrays	= array_map('trim', $arrays);
		$akey = array_keys($this->tables);
		$multisort	= "array_multisort(";

		foreach ($arrays as $array)
		{
			if(strpos($array, " ASC") === false ) {
				if(strpos($array, " DESC") === false ) {
					$array .= " ASC";
		}
			}
			list($col, $sort) = preg_split('#((\s)+)#', $array, -1, PREG_SPLIT_NO_EMPTY);
			$multisort .= "\$this->split_array(\$this->tables['$akey[0]'], '$col'), SORT_".strtoupper($sort).", SORT_REGULAR, ";
		}
		$multisort	.= "\$this->tables['$akey[0]']);";
		eval($multisort);
	}

	/* Return response
	-------------------------------------------- */
	private function return_response()
	{
		return $this->response;
	}

	/* Return a column of an array
	-------------------------------------------- */
	private function split_array($input_array, $column)
	{
		$output_array	= array();
		foreach ($input_array as $key => $value)
			$output_array[] = $value[$column];

		return $output_array;
	}

	/* Entire array search
	-------------------------------------------- */
	private function entire_array_search($needle, $array)
	{
		foreach($array as $key => $value)
			if ($value === $needle)
				$return[] = $key;

		if (!is_array($return))
			$return = FALSE;

		return $return;
	}
}

class jqGridDB
{
	public static  $acnt = 0;
	public static function getCnt()
	{
		return $this->acnt;
	}
	public static function getInterface()
	{
		return 'array';
	}
	public static function prepare ($conn, $sqlElement, $params, $bind=true)
	{
		if($conn && strlen($sqlElement)>0) {
			if(is_array($params) && count($params)>0) {
				$sql =  $conn->execute($sqlElement, $params);
			} else {
				$sql =  $conn->query($sqlElement);
			}
			return $sql;
		}
		return false;
	}
	public static function limit($sqlId, $dbtype, $nrows=-1,$offset=-1,$order='', $sort='' )
	{
		$psql = $sqlId;
		$offsetStr = ($offset >= 0) ? " OFFSET ".$offset : '';
		$limitStr  = ($nrows >= 0)  ? " LIMIT ".$nrows : '';
		$psql .= "$limitStr$offsetStr";
		return $psql;
	}
	public static function execute($psql, $prm)
	{
		return $psql;
	}
	public static function query($conn, $sql)
	{
		if($conn && strlen($sql)>0) {
			return $conn->query($sql);
		}
		return false;
	}
	public static function bindValues($stmt, $binds, $types)
	{
		return true;
	}
	public static function beginTransaction( $conn )
	{
		return true;
	}
	public static function commit( $conn )
	{
		return true;
	}
	public static function rollBack( $conn )
	{
		return true;
	}
	public static function lastInsertId($conn, $table, $IdCol, $dbtype)
	{
		return true;
	}
	public static function fetch_object( $psql, $fetchall, $conn=null )
	{
		if($psql) {
			$ret = array();
			if(!$fetchall)
			{
				if(is_array($psql) && count($psql)==1 )
				{
					return (object)$psql;
				}
				if( isset($psql[self::$acnt]) ) {
					return  (object)$psql[self::$acnt];
				}
				self::$acnt++;

			} else {
				foreach ($psql as $akey => $aval)
				{
					$ret[] = (object)$psql[$akey];
				}
				return $ret;
			}
		}
		return false;
	}
	public static function fetch_num( $psql )
	{
		if( isset($psql[self::$acnt]))
		{			
			$ret = array_values($psql[self::$acnt]);
			self::$acnt++;
			return $ret;
		}
		return false;
	}
	public static function fetch_assoc( $psql, $conn )
	{
		if($psql)
		{
			if( isset($psql[self::$acnt]) )
			{
				return $psql[self::$acnt];
				self::$acnt++;
			}
		}
		return false;
	}
	public static function closeCursor($sql)
	{
		self::$acnt = 0;
		return true;
	}
	public static function columnCount( $rs )
	{
		if(isset($rs[0]))
			return count( $rs[0] );
		else
			return 0;
	}
	public static function getColumnMeta($index, $sql)
	{
		if($sql && $index >= 0) {
			$keys = array_keys($sql[0]);
			$values = array_values($sql[0]);
			$newmeta = array();
			$newmeta["name"]  = $keys[$index];
			$f_type = 'string';
			if(is_integer($values[$index])) {
				$f_type='int';
			} else if(is_numeric($values[$index])) {
				$f_type='numeric';
			} else if(is_string($values[$index])) {
				$f_type='string';
			} else {
				$f_type='string';
			}
			$newmeta["native_type"]  = $f_type;
			$newmeta["len"]  = $index;
			return $newmeta;
		}
		return false;
	}
	public static function MetaType($t,$dbtype)
	{
		return  $t["native_type"];
	}
 	public static function getPrimaryKey($table, $conn, $dbtype)
	{
		return true;
	}
	public static function errorMessage ( $conn )
	{
		return "Array Error.";
	}

}
?>
