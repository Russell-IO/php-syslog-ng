<?php
class jqGridDB
{
	public static function getInterface()
	{
		return 'oci8';
	}
	public static function prepare ($conn, $sqlElement, $params, $bind=true)
	{
		if($conn && strlen($sqlElement)>0) {
			$prmcount = substr_count($sqlElement, '?');
			for($i=1; $i<=$prmcount; $i++) {
				$sqlElement = substr_replace($sqlElement, ":".$i, strpos($sqlElement, '?') , 1);
			}
			$sql = oci_parse($conn, (string)$sqlElement);
			if(!$bind) return $sql;
			if(is_array($params) && count($params)>0) {
				for ($i = 1; $i <= count($params); $i++) {
				//Replace null param by empty string for wellform SQL query
					if ($params[$i-1] == null)
						$params[$i-1] = '';
					oci_bind_by_name($sql, ":".$i, $params[$i-1]);	
					//$sql->bindValue($i, $params[$i-1]);
				}
			}
			return $sql;
		}
		return false;
	}

	public static function limit($sqlId, $dbtype, $nrows=-1,$offset=-1, $order='', $sort='' )
	{
		$psql = $sqlId;
		if($offset>=0 && $nrows >= 0 ) {
			$psql = "SELECT z2.*
				FROM (
					SELECT z1.*, ROWNUM AS \"jqgrid_row\"
					FROM (
						" . $sqlId . "
					) z1
				) z2
				WHERE z2.\"jqgrid_row\" BETWEEN " . ($offset+1) . " AND " . ($offset+$nrows);
		}
		return $psql;
	}
	public static function execute($psql, $prm=null)
	{
		$ret = false;
		if($psql)
			$ret = oci_execute($psql);
		return $ret;
	}
	public static function query($conn, $sql)
	{
		if($conn && strlen($sql)>0) {
			$stmt = oci_parse($conn, (string)$sql);
			oci_execute($stmt);
			return $stmt;
		}
		return false;
	}
	public static function bindValues($stmt, $binds, $types)
	{
		foreach($binds as $key => $field) {
			switch ($types[$key]) {
				case 'numeric':
				case 'string':
				case 'date':
				case 'time':
				case 'datetime':
					oci_bind_by_name($stmt, ":".($key+1), $binds[$key],-1);
					break;
				case 'int':
					oci_bind_by_name($stmt, ":".($key+1), $binds[$key], -1, SQLT_INT);
					break;
				case 'boolean':
					oci_bind_by_name($stmt, ":".($key+1), $binds[$key],-1);
					break;
				case 'blob':
					oci_bind_by_name($stmt, ":".($key+1), $binds[$key], -1, SQLT_BLOB);
					break;
				case 'custom':
					oci_bind_by_name($stmt, ":".($key+1), $binds[$key],-1);
					break;
			}
		}
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
		if($IdCol) {
			$table .= "_".$IdCol;
		}
		$table .= "_SEQ.CURRVAL";
		$sql = "SELECT ".$table." FROM dual";
		$stmt = self::query($conn, $sql);
		if($stmt){
			$res = self::fetch_num($stmt);
			if($res){
				return $res[0];
			}
		}
		return false;
	}
	public static function fetch_object( $psql, $fetchall, $conn=null )
	{
		if($psql) {
			if(!$fetchall)
			{
				return oci_fetch_object( $psql);
			} else {
				$ret = array();
				while ($obj = oci_fetch_object( $psql))
				{
					$ret[] = $obj;
				}
				return $ret;
			}
		}
		return false;
	}
	public static function fetch_num( $psql )
	{
		if($psql)
		{
			return oci_fetch_array($psql, OCI_NUM);
		}
		return false;
	}
	public static function fetch_assoc( $psql, $conn )
	{
		if($psql)
		{
			return oci_fetch_array($psql, OCI_ASSOC+OCI_RETURN_NULLS);
		}
		return false;
	}
	public static function closeCursor($sql)
	{
		if($sql) oci_free_statement($sql);
	}
	public static function columnCount( $rs )
	{
		if($rs)
			return oci_num_fields( $rs );
		else
			return 0;
	}
	public static function getColumnMeta($index, $sql)
	{
		if($sql && $index >= 0) {
			$newmeta = array();
		    $newmeta["name"]  = oci_field_name($sql, $index+1);
			$newmeta["native_type"]  = oci_field_type($sql, $index+1);
			$newmeta["len"]  = oci_field_size($sql, $index+1);
			return $newmeta;
		}
		return false;
	}
	public static function MetaType($t,$dbtype)
	{

		if ( is_array($t)) {
			$type = $t["native_type"];
			$len = $t["len"];
			switch (strtoupper($type)) {
				case 'VARCHAR':
				case 'VARCHAR2':
				case 'CHAR':
				case 'VARBINARY':
				case 'BINARY':
				case 'NCHAR':
				case 'NVARCHAR':
				case 'NVARCHAR2':
					return 'string';

				case 'NCLOB':
				case 'LONG':
				case 'LONG VARCHAR':
				case 'CLOB':
					return 'string';

				case 'LONG RAW':
				case 'LONG VARBINARY':
				case 'BLOB':
					 return 'blob';

				case 'DATE':
					return 'date';
				case 'TIMESTAMP':
					return 'datetime';

				case 'INT':
				case 'SMALLINT':
				case 'INTEGER':
					return 'int';

				default: return 'numeric';
			}
		}
	}
	public static function getPrimaryKey($table, $conn, $dbtype)
	{
		if(strlen($table)>0 && $conn && strlen($dbtype)>0 ) {
			$sql ="SELECT cols.table_name, cols.column_name, cols.position, cons.status, cons.owner"
			." FROM all_constraints cons, all_cons_columns cols"
			." WHERE cols.table_name = '".$table."'"
			." AND cons.constraint_type = 'P'"
			." AND cons.constraint_name = cols.constraint_name"
			." AND cons.owner = cols.owner"
			." ORDER BY cols.table_name, cols.position";
			$stmt = self::query($conn,$sql);
			if($stmt) {
				$res = self::fetch_num($stmt);
				self::closeCursor($stmt);
				if($res) {
					return $res[1];
				}
			}
		}
		return false;
	}
	public static function errorMessage ( $conn )
	{
		try {
			$error = ocierror();
		} catch (Exception $e) {
			$error = oci_error();
		}
		return "Code: ".$error['code'].". ".$error['message'].". SQL:".$error['sqltext'];
	}
}
?>
