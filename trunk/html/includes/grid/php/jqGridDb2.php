<?php
class jqGridDB
{

	public static function getInterface()
	{
		return 'db2';
	}

	public static function prepare ($conn, $sqlElement, &$params, $bind=true)
	{
		if($conn && strlen($sqlElement)>0) {
			
			$sql = db2_prepare($conn, (string)$sqlElement);
			if ($sql === false)
					throw new Exception("db2_prepare failed; error = " 	. db2_stmt_errormsg());
			else
				return $sql;
		}
		return false;
	}
	public static function limit($sqlId, $dbtype, $nrows=-1,$offset=-1, $order='', $sort='' )
	{
		$sql = $sqlId;
		if ($offset == 0 && $nrows > 0 ) {
			$psql = $sql . " FETCH FIRST $nrows ROWS ONLY";
			return $psql;
		} else if($offset > 0 && $nrows > 0 ) {
			if($order && strlen($order)>0) {
				$order = ' ORDER BY '.$order.' ';
				if($sort) $sort = 'ASC';
				$order .= $sort;
			} else {
				$order = "";
			}
			$sql = "SELECT z2.*
			FROM (
				SELECT ROW_NUMBER() OVER(".$order.") AS jqgrid_row, z1.*
					FROM (
						" . $sql . "
				) z1
			) z2
			WHERE z2.jqgrid_row BETWEEN " . ($offset+1) . " AND " . ($offset+$nrows);
		}
		return $sql;
	}
	public static function execute($psql, $prm)
	{
		$ret = false;
		if($psql)
			if(is_array($prm)) {
				$ret = db2_execute($psql, $prm);
			}
			else
				$ret = db2_execute($psql);
		return $ret;
	}
	public static function query($conn, $sql)
	{
		if($conn && strlen($sql)>0) {
			$stmt = db2_prepare($conn, $sql);
			if(db2_execute($stmt)) {
				return $stmt;
			} 
		}
		return false;
	}
	public static function bindValues($stmt, $binds, $types)
	{
		return true;
	}
	public static function beginTransaction( $conn )
	{
		return db2_autocommit($conn, DB2_AUTOCOMMIT_OFF);
	}
	public static function commit( $conn )
	{
		return db2_commit( $conn  );
	}
	public static function rollBack( $conn )
	{
		return db2_rollback( $conn );
	}
	public static function lastInsertId($conn, $table, $IdCol, $dbtype)
	{
		$idCol = db2_last_insert_id($conn);
		return $idCol;
	}
	public static function fetch_object( $psql, $fetchall, $conn=null )
	{
		if($psql) {
			if(!$fetchall)
			{
				return db2_fetch_object($psql);
			} else {
				$ret = array();
				while ($obj = db2_fetch_object($psql))
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
			return db2_fetch_array( $psql );
		}
		return false;
	}
	public static function fetch_assoc( $psql, $conn )
	{
		if($psql)
		{
			return db2_fetch_assoc($psql );
		}
		return false;
	}
	public static function closeCursor($sql)
	{
		if($sql) db2_free_stmt($sql);
	}
	public static function columnCount( $rs )
	{
		if($rs)
			return db2_num_fields( $rs );
		else
			return 0;
	}
	public static function getColumnMeta($index, $sql)
	{
		if($sql && $index >= 0) {
			$newmeta = array();
			$newmeta["name"]  = db2_field_name($sql, $index);
			$newmeta["native_type"]  = db2_field_type($sql, $index);
			$newmeta["len"]  = db2_field_width($sql, $index);
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
			switch(strtoupper($type))
			{
				case 'INT':
					return 'int';
				case 'STRING':
					return 'string';
				case 'CLOB' : 
				case 'DBCLOB' : 
				case 'BLOB' : 
					return 'blob';
				case 'DATE' : //date
					return 'date';
				case 'TIME' :
				case 'TIMESTAMP' :
					return 'datetime';
				case 'real' :
					return 'numeric';
				default : 
					return 'string';
			}
		}
		return 'numeric';
	}
	public static function getPrimaryKey($table, $conn, $dbtype)
	{
		/**
		* Discover metadata information about this table.
		*/
		$server_info = db2_server_info($conn); // $server_info->DBMS_NAME == "QSQ" is iSeries / i5

		if ($server_info->DBMS_NAME == "QSQ") {
			$sql="SELECT column_name as colname FROM qsys2.syskeycst ";
			$sql.="WHERE system_table_name = '".$table."' and ordinal_position > 0 order by ordinal_position asc";

		} else {
			$sql = "SELECT colname FROM SYSCAT.COLUMNS WHERE TABNAME = '".$table."' AND KEYSEQ > 0 ORDER BY KEYSEQ ASC";
		}
		$rs = self::query($conn, $sql);
		if($rs){
			$res = self::fetch_num($rs);
			self::closeCursor($rs);
			if($res) {
				return $res[0];
			}
		}
		return false;
	}
	public static function errorMessage ( $conn )
	{
		return db2_stmt_errormsg();
	}
}
?>
