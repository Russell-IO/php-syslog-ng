<?php
// MYSQLi Driver
// When using this driver class please do not use the functions here
// They are written only for jqGrid.
class jqGridDB
{
	public static function getInterface()
	{
		return 'mysqli';
	}
	public static function prepare ($conn, $sqlElement, $params, $bind=true)
	{
		if($conn && strlen($sqlElement)>0) {
			$sql = mysqli_stmt_init($conn);
			mysqli_stmt_prepare($sql,(string)$sqlElement);
			if(!$bind) return $sql;
			if(is_array($params)) {
				$t =""; $cnt = count($params);
				for ($i = 0; $i < $cnt; $i++) {
					$v = $params[$i];
					if(is_string($v))
						$t .= "s";
					else if(is_int($v))
						$t .= "i";
					else if (is_float($v))
						$t .= "d";
					else
						$t .= "b";
					$ar[] = &$params[$i];
				}
				if($t) {
					call_user_func_array('mysqli_stmt_bind_param', array_merge(array($sql, $t), $ar));
				}
			}
			return $sql;
		}
		return false;
	}
	public static function limit($sqlId, $dbtype, $nrows=-1,$offset=-1,$order='', $sort='' )
	{
		$psql = $sqlId;
		$offsetStr =($offset>=0) ? "$offset, " : '';
		if ($nrows < 0) $nrows = '18446744073709551615';
		$psql .= " LIMIT $offsetStr$nrows";
		return $psql;
	}
	public static function execute($psql, $prm=null)
	{
		$ret = false;
		if($psql) {
			$ret = mysqli_stmt_execute($psql);
		}
		return $ret;
	}
	public static function query($conn, $sql)
	{
		if($conn && strlen($sql)>0) {
			return mysqli_query($conn,$sql);
		}
		return false;
	}
	/**
	 *
	 * Bind the values for CRUD using the PDO bindValue.
	 * In case of table we use the type to bind the values accordantly.
	 * In case of custom SQL we use the default PDO::PARAM
	 *
	 * @param resurce $stmt the prapared statement
	 * @param array $binds array containing the value for binding
	 * @param array $types array containing the type of the field
	 * @return boolean true on success
	 */
	public static function bindValues($stmt, $binds, $types)
	{
		$tp = "";
		foreach($binds as $key => $field) {
			switch ($types[$key]) {
				case 'numeric':
					$tp .="d";
					break;
				case 'string':
				case 'date':
				case 'time':
				case 'boolean':
				case 'datetime':
					$tp .="s";
					break;
				case 'int':
					$tp .="i";
					break;
				case 'blob':
					$tp .="d";
					break;
				case 'custom':
					$v = $field;
					if(is_int($v))
						$tp .= "i";
					else if(is_float($v))
						$tp .= "d";
					else if (is_string($v))
						$tp .= "s";
					else
						$tp .= "d";
					// little
					break;
			}
			$ar[] = &$binds[$key];
		}
		call_user_func_array('mysqli_stmt_bind_param', array_merge(array($stmt, $tp), $ar));
		//mysqli_stmt_bind_param($stmt,$tp,$binds);
		return true;
	}
	public static function beginTransaction( $conn )
	{
		return mysqli_autocommit($conn, FALSE);
	}
	public static function commit( $conn )
	{
		return mysqli_commit($conn);;
	}
	public static function rollBack( $conn )
	{
		return mysqli_rollback($conn);
	}
	/**
	 *
	 * Return the last inserted id in a table in case when the serialKey is set to true
	 * @return number
	 */
	public static function lastInsertId($conn, $table, $IdCol, $dbtype)
	{
		return mysqli_insert_id($conn);
	}
	public static function fetch_object( $psql, $fetchall, $conn )
	{
		if($psql) {
			$ret = null;
			$meta = mysqli_stmt_result_metadata($psql);
			while ($column = mysqli_fetch_field($meta)) {
				// this is to stop a syntax error if a column name has a space in
				// e.g. "This Column". 'Typer85 at gmail dot com' pointed this out
				$colname = str_replace(' ', '_', $column->name);
				$result[$colname] = "";
				$resultArray[$colname] = &$result[$colname];

			}
			call_user_func_array(array($psql, 'bind_result'), $resultArray);
			if(!$fetchall)
			{
				mysqli_stmt_fetch($psql);
				$ret = new stdClass();
				foreach ($resultArray as $key => $value) {
					$ret->$key = $value;
				}
			} else {
				while (mysqli_stmt_fetch($psql))
				{
					$obj = new stdClass();
					foreach ($resultArray as $key => $value) {
						$obj->$key = $value;
					}
					$ret[] = $obj;
				}
				return $ret;
			}
			return $ret;
		}
		return false;
	}
	public static function fetch_num( $psql )
	{
		if($psql)
		{
			if(get_class($psql)=="mysqli_result")
				return mysqli_fetch_array($psql, MYSQLI_NUM);
			else
				return mysqli_stmt_fetch($psql);
		}
		return false;
	}
	public static function fetch_assoc( $psql, $conn )
	{
		if($psql)
		{
			if(get_class($psql)=="mysqli_result")
				return mysqli_fetch_array($psql, MYSQLI_ASSOC);
			else
				return mysqli_stmt_fetch($psql);
		}
		return false;
	}
	public static function closeCursor($sql)
	{
		if($sql) {
			if(get_class($sql)=="mysqli_result") mysqli_free_result($sql);
			else mysqli_stmt_free_result($sql);
		}
	}
	public static function columnCount( $rs )
	{
		if($rs){
			if(get_class($rs) == "mysqli_result")  return  mysqli_num_fields($rs);
			else return mysqli_stmt_field_count($rs);
		}
		else 
			return 0;
	}
	public static function getColumnMeta($index, $sql)
	{
		if($sql && $index >= 0)
		{
			$newmeta = array();
			if(get_class($sql)=="mysqli_result") {
				$mt = mysqli_fetch_field_direct($sql,$index);
			} else {
				$fd = mysqli_stmt_result_metadata($sql);
				$mt = mysqli_fetch_field_direct($fd,$index);
			}
			$newmeta["name"]  = $mt->name;
			$newmeta["native_type"]  = $mt->type;
			$newmeta["len"]  = $mt->length;
			return $newmeta;
		}
		return false;
	}
	/**
	 *
	 * Return the meta type of the field based on the underlayng db
	 * @param array $t object returned from pdo getColumnMeta
	 * @param string $dbtype the database type
	 * @return string the type of the field can be string, date, datetime, blob, int, numeric
	 */
	public static function MetaType($t,$dbtype)
	{

		if ( is_array($t)) {
			$type = $t["native_type"];
			$len = $t["len"];
			switch($type)
			{
				case 1 : // tinyint
				case 2 : // smallint
				case 3 : // int
				case 8 : // bigint
				case 9 : // mediumint
				case 16 : // bit
				case 13 : // year
					return 'int';
				case 253 : // varchar
				case 254 : // char
				case 252 : // text/blob
					return 'string';
				//case 252 : // text/blob
					//return 'blob';
				case 10 : // date
				case 11 : // time
					return 'date';
				case 7 : // timestamp
				case 12 : // datetime
					return 'datetime';
				default : return 'numeric';
			}
		}
	}
	/**
	 *
	 * Try to get the primary key of the table automattically
	 * @return mixed the value of the key or false if not presend or not found
	 */
	public static function getPrimaryKey($table, $conn, $dbtype)
	{
		if(strlen($table)>0 && $conn && strlen($dbtype)>0 ) {
			$sql = "select column_name from information_schema.statistics where table_name='".$table."'";
			$rs = self::query($conn,$sql);
			if($rs) {
				$res = mysqli_fetch_array($rs, MYSQLI_NUM);
				self::closeCursor($rs);
				if($res) {
					return $res[0];
				}
			}
		}
		return false;
	}
	public static function errorMessage ( $conn )
	{
		return mysqli_error($conn);
	}
}
?>
