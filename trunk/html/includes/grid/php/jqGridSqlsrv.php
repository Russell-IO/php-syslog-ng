<?php
class jqGridDB
{

	public static function getInterface()
	{
		return 'sqlsrv';
	}

	public static function prepare ($conn, $sqlElement, $params, $bind=true)
	{
		if($conn && strlen($sqlElement)>0) {
			if(is_array($params) && count($params)>0) {
				for ($i = 0; $i < count($params); $i++) {
					$aprm[$i] = &$params[$i];
				}
			} else {
				$aprm = $params;
			}
			$sql = sqlsrv_prepare( $conn, (string)$sqlElement, $aprm);
			if(!$sql)
				print_r(sqlsrv_errors(), true);
			return $sql;
		}
		return false;
	}
	public static function limit($sqlId, $dbtype, $nrows=-1,$offset=-1,$order='', $sort='' )
	{
		$sql = $sqlId;
		$nrows = intval($nrows);
		if($nrows < 0)  return false;
		$offset = intval($offset);
		if($offset < 0 ) return false;
		$orderby = $order && strlen($order) > 0;
		////stristr($sqlId, 'ORDER BY');
		if ($orderby !== false) {
			$sort  = (stripos($sort, 'desc') !== false) ? 'desc' : 'asc';
			//$order = " ORDER BY ".$order;
			//str_ireplace('ORDER BY', '', $orderby);
			//$order = trim(preg_replace('/\bASC\b|\bDESC\b/i', '', $order));
		}
		//$sql = preg_replace('/^SELECT\s/i', 'SELECT TOP ' . ($nrows+$offset) . ' ', $sql);

		$sql = preg_replace('/^SELECT\s+(DISTINCT\s)?/i', 'SELECT $1TOP ' . ($nrows+$offset) . ' ', $sql );
		$sql = 'SELECT * FROM (SELECT TOP ' . $nrows . ' * FROM (' . $sql . ') AS inner_tbl';
		if ($orderby !== false) {
			$sql .= ' ORDER BY ' . $order . ' ';
			$sql .= (stripos($sort, 'asc') !== false) ? 'DESC' : 'ASC';
		}
		$sql .= ') AS outer_tbl';
		if ($orderby !== false) {
			$sql .= ' ORDER BY ' . $order . ' ' . $sort;
		}
		return $sql;
	}
	public static function execute($psql, $prm=null)
	{
		$ret = false;
		if($psql) {
			$ret = sqlsrv_execute($psql);
		}
		return $ret;
	}
	public static function query($conn, $sql)
	{
		if($conn && strlen($sql)>0) {
			return sqlsrv_query( $conn, $sql);
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
		// SQL Server does not support bind by name
		return true;
	}
	public static function beginTransaction( $conn )
	{
		return sqlsrv_begin_transaction( $conn );
	}
	public static function commit( $conn )
	{
		return sqlsrv_commit( $conn );
	}
	public static function rollBack( $conn )
	{
		return sqlsrv_rollback( $conn );
	}
	public static function lastInsertId($conn, $table, $IdCol, $dbtype)
	{
		$sql = "SELECT SCOPE_IDENTITY()";
		$stmt = sqlsrv_query( $conn, $sql);
		$idCol = false;
		if( $stmt === false )
		{
			echo "Error in statement preparation/execution.\n";
			die( print_r( sqlsrv_errors(), true));
		}
		if( sqlsrv_fetch( $stmt ) === false )
		{
			echo "Error in retrieving row.\n";
			die( print_r( sqlsrv_errors(), true));
		}
		$idCol = sqlsrv_get_field( $stmt, 0);
		return $idCol;
	}
	public static function fetch_object( $psql, $fetchall, $conn=null )
	{
		if($psql) {
			if(!$fetchall)
			{
				return sqlsrv_fetch_object( $psql);
			} else {
				$ret = array();
				while ($obj = sqlsrv_fetch_object( $psql))
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
			return sqlsrv_fetch_array( $psql, SQLSRV_FETCH_NUMERIC);
		}
		return false;
	}
	public static function fetch_assoc( $psql, $conn )
	{
		if($psql)
		{
			return sqlsrv_fetch_array( $psql, SQLSRV_FETCH_ASSOC );
		}
		return false;
	}
	public static function closeCursor($sql)
	{
		if($sql) sqlsrv_free_stmt($sql);
	}
	public static function columnCount( $rs )
	{
		if($rs)
			return sqlsrv_num_fields( $rs );
		else
			return 0;
	}
	public static function getColumnMeta($index, $sql)
	{
		if($sql && $index >= 0) {
			$metaData = sqlsrv_field_metadata( $sql);
			if(isset($metaData[$index]))
			{
				$newmeta = $metaData[$index];
				$newmeta["name"] = $newmeta["Name"];
				unset($newmeta["Name"]);
				$newmeta["native_type"] = $newmeta["Type"];
				unset($newmeta["Type"]);
				$newmeta["len"] = $newmeta["Size"];
				unset($newmeta["Size"]);
				return $newmeta;
			}
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
				case -11 : //uniqueidentifier
				case -7 : // bit
				case -5 : // bigint
				case -6 : //tynint
				case 4 : //int
				case 5 : //smallint
					return 'int';

				case -152 : //xml
				case -10 : //ntext
				case -9 : //nvarcher
				case -8 : //nchar
				case -1 : //text
				case  1 : // char
				case 12 : //varchar
					return 'string';
				case -151 : //UDT
				case -4 : //image
				case -3 : //varbinary
					return 'blob';
				case -2 : // binary
					return $len > 0 ? 'blob' : 'datetime';
				case 91 : //date
					return 'date';
				case -155 : // datetimeoffset
				case -154 : //time
				//case -2 : //timestamp
				case 93 : //datetime, datetime2, smalldatetime
					return 'datetime';
				default : return 'numeric';
				//case 3 : //decimal, money, smalmoney
				//case 2 : //numeric
				//case 6 : //float
				//case 7 : //real
			}
		}
		return 'numeric';
	}
	public static function getPrimaryKey($table, $conn, $dbtype)
	{
		/**
		* Discover metadata information about this table.
		*/
		$sql    = "exec sp_columns @table_name = '".$table."'";
		$stmt   = self::query( $conn, $sql);
		if(!$stmt) {
			return false;
		}
		$result = array();
		while($row = self::fetch_num( $stmt )){
			$result[] = $row;
		}

		$owner           = 1;
		$table_name      = 2;
		$column_name     = 3;
		$type_name       = 5;
		$precision       = 6;
		$length          = 7;
		$scale           = 8;
		$nullable        = 10;
		$column_def      = 12;
		$column_position = 16;
		
		if(count($result)==0) { return false; }
		self::closeCursor($stmt);
		/**
		* Discover primary key column(s) for this table.
		*/
		$tableOwner = $result[0][$owner];
		$sql        = "exec sp_pkeys @table_owner = " . $tableOwner
					. ", @table_name = '".$table."'";
		$stmt       = self::query( $conn, $sql);
		/*
		while($row = self::fetch_num( $stmt )){
			$primaryKeysResult = $row;
		}
		*
		*/
		// Currently we suppose to use only one PK.
		//$primaryKeyColumn  = array();
		if($stmt) {
			$primaryKeysResult = self::fetch_num( $stmt );
			self::closeCursor($stmt);
		} 
		// Per http://msdn.microsoft.com/en-us/library/ms189813.aspx,
		// results from sp_keys stored procedure are:
		// 0=TABLE_QUALIFIER 1=TABLE_OWNER 2=TABLE_NAME 3=COLUMN_NAME 4=KEY_SEQ 5=PK_NAME

		$pkey_column_name = 3;
		$pkey_key_seq     = 4;
		//foreach ($primaryKeysResult as $pkeysRow) {
		//$primaryKeyColumn[$pkeysRow[$pkey_column_name]] = $pkeysRow[$pkey_key_seq];
		//}
		if($primaryKeysResult && $primaryKeysResult[$pkey_column_name]) return $primaryKeysResult[$pkey_column_name];
		return false;
	}
	public static function errorMessage ( $conn )
	{
		//SQLSTATE
		//code
		//message
		 $errors = sqlsrv_errors();
		 return ($errors && is_array($errors)) ? "Code: ". $errors[0]['code'].". ".$errors[0]['message'] : "Unknown Error.";
	}

}
?>
