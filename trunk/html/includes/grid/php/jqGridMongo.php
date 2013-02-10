<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 **/

class jqGridDB
{
	public static $mappingTypes = array(
		'MongoDate',
		'MongoId',
		'MongoRegex',
		'MongoEmptyObj',
		'MongoBinData'
	);
	public static function getInterface()
	{
		return 'mongodb';
	}
	public static function prepare ($conn, $sqlElement, $params, $bind=true)
	{
		return $sqlElement;
	}
	public static function limit($sqlId, $dbtype, $nrows=-1,$offset=-1,$order='', $sort='' )
	{
		return $sqlId;
	}
	public static function execute($psql, $prm=null)
	{
		return $psql;
	}
	public static function mongoexecute($collection, $query, &$sql, $limit, $nrows, $offset, $order, $sort, $fields)
	{
		if(!$query) $query = array();
		if(!$fields) $fields = array();
		$ret = false;
		if($collection) {
			$sql = $collection->find($query, $fields);
			if($order) {
				if(strtolower($sort) == 'desc') {
					$sort = -1;
				} else {
					$sort = 1;
				}
				$sql = $sql->sort(array($order=>$sort));
			}
			if($limit && (int)$nrows >= 0) {
				$sql = $sql->limit($nrows)->skip($offset);
			}
			if($sql) { $ret = true;}
		}
		return $ret;
	}
	
	public static function query($conn, $sql)
	{
		if($conn && strlen($sql)>0) {
			return $conn->find($sql);
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
	/**
	 *
	 * Return the last inserted id in a table in case when the serialKey is set to true
	 * @return number
	 */
	public static function lastInsertId($conn, $table, $IdCol, $dbtype)
	{
		return true;
	}

	protected static function findInstancesOf($classname, &$vars, &$numarray=array())
	{
		foreach($vars as $name=>$var)
		{
			if ($var instanceof  $classname)
			{
			//dump it here
			//echo "$name is a $classname<br>";
				$vars[$name] = date("Y-m-d H:i:s", $var->sec);
				$numarray[] = date("Y-m-d H:i:s", $var->sec);
			}
			elseif(is_scalar($var)) {
				$numarray[] = $var;
			}
			elseif(is_array($var))
			{
				if(0 !== count(array_diff_key($var, array_keys(array_keys($var)))))
				 //recursively search array
					self::findInstancesOf($classname, $var,$numarray);
				else {
					$numarray[] = implode(",", $var);
				}
			}
			elseif(is_object($var))
			{
				//recursively search object members
				$members=get_object_vars($var);
				self::findInstancesOf($classname, $members,$numarray);
			}
		}
	}
	public static function fetch_object( $psql, $fetchall, $conn )
	{
		if($psql) {
			if($fetchall === true)  {
				$res= array();
				while($psql->hasNext())
				{
					$v = $psql->getNext();
					if( $v['_id'] ) { $v['_id'] = $psql->key(); }
					$d= array();
					self::findInstancesOf('MongoDate', $v, $d);
					$res[] = $v;
				}
				return $res;
			} else {
				if($psql->hasNext()) {
					$v = $psql->getNext();
					//var_dump($v);
					if( $v['_id'] ) { $v['_id'] = $psql->key(); }
					self::findInstancesOf('MongoDate', $v);
				} else {
					$v = false;
				}
				return $v;
			}
		}
		return false;
	}
	public static function fetch_num( $psql )
	{
		if($psql)
		{
			if($psql->hasNext()) {
				$d = array();
				$v = $psql->getNext();
				if( $v['_id'] ) { $v['_id'] = $psql->key(); }
				self::findInstancesOf('mongoDate', $v, $d);
			} else {
				$d = false;
			}
			return $d;
		}
		return false;
	}
	public static function fetch_assoc( $psql, $conn )
	{
		if($psql)
		{
			if($psql->hasNext()) {
				$v = $psql->getNext();
			}
			return $v;
		}
		return false;
	}
	public static function closeCursor($sql)
	{
		return true;
	}
	public static function columnCount( $rs )
	{
		if($rs) {
			//$cntdata =
			$data = self::fetch_num($rs);
			$rs->reset();
			return $data ? count($data,1) :0;
		} else {
			return 0;
		}
	}
	private static function toArray($data) {
		if (is_object($data)) {
			$data = get_object_vars($data);
		}
		return is_array($data) ? array_map(__FUNCTION__, $data) : $data;
	}

	private static function multiarray_keys($ar,$nm='', &$vals=array())
	{
		foreach($ar as $k => $v) {
			if(is_object($ar[$k])) {
				$ar[$k] = self::toArray ($ar[$k]);
			}
			if ( is_array($ar[$k]) && 0 !== count(array_diff_key($ar[$k], array_keys(array_keys($ar[$k]))))  ) {
				$keys = array_merge(isset ($keys) ? $keys : array(), self::multiarray_keys($ar[$k], $k.".", $vals));
			} else {
				$keys[] = $nm.$k;
				$vals[] = $v;
			}
		}
		return $keys;
	}

	public static function getColumnMeta($index, $sql)
	{
		if($sql && $index >= 0)
		{
			//overhead we should make it not so complex
			$obj = self::fetch_object( $sql, false, 0);
			$sql->reset();
			$values = array();
			$datatypes  = self::multiarray_keys($obj,'', $values);
			$names = $datatypes;
			$newmeta = array();
			$newmeta["name"]  = $names[$index];
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
		return  $t["native_type"];
	}

	/**
	 *
	 * Try to get the primary key of the table automattically
	 * @return mixed the value of the key or false if not presend or not found
	 */
	public static function getPrimaryKey($table, $conn, $dbtype)
	{
		if(strlen($table)>0 && $conn && strlen($dbtype)>0 ) {
			$collection = $conn->selectCollection($table);
			$result = $collection->getIndexInfo();
			if($result[0]['key']) {
				$v = array_keys($result[0]['key']);
				return $v[0];
			}
		}
		return false;
	}
	public static function _mongocount($collection, $query, $sumcols)
	{
		$qryRecs->COUNT = 0;
		if(!$query) $query = array();
		$v = $collection->count($query);
		if ($v && $v>=0) $qryRecs->COUNT = $v;
		$keys = array();
		$initial = array("COUNT" => 0);
		// Summarry only on single fields only for now
		if(is_array($sumcols) && count($sumcols)>0 ) {
			$s = '';
			foreach($sumcols as $k=>$v) {
				$initial[$k] = 0;
				$s .= " prev['".$k."'] += obj.".$v."; ";
			}
		}
		$reduce = "function (obj, prev) { prev.COUNT++;".$s."}";
		$res = $collection->group($keys, $initial, $reduce, array("condition"=>$query));
		return $res['retval'][0];
	}
	public static function _mongoSearch($mongoquery, $GridParams=array(), $encoding='utf-8', $datearray=array(), $mongointegers=array())
	{
		$s = '';
		$v=array();
		$sopt = array('eq' => '===','ne' => '!==','lt' => '<','le' => '<=','gt' => '>','ge' => '>=','bw'=>"",'bn'=>"",'in'=>'==','ni'=> '!=','ew'=>'','en'=>'','cn'=>'','nc'=>'');
		$filters = jqGridUtils::GetParam($GridParams["filter"], "");
		$rules = "";
		// multiple filter
		if($filters) {
			if( function_exists('json_decode') && strtolower(trim($encoding)) == "utf-8")
				$jsona = json_decode($filters,true);
			else
				$jsona = jqGridUtils::decode($filters);
			if(is_array($jsona)) {
				$gopr = strtolower(trim($jsona['groupOp']));
				$rules = $jsona['rules'];
			}
		// single filter
		} else if (jqGridUtils::GetParam($GridParams['searchField'],'')){
			$gopr = 'or';
			$rules[0]['field'] = jqGridUtils::GetParam($GridParams['searchField'],'');
			$rules[0]['op'] = jqGridUtils::GetParam($GridParams['searchOper'],'');
			$rules[0]['data'] = jqGridUtils::GetParam($GridParams['searchString'],'');
		}
		if($gopr == 'or') $gopr = ' || ';
		else $gopr = ' && ';
		$i = 0;
		if(!is_array($mongoquery)) $mongoquery = array();
		foreach($rules as $key=>$val) {
			$field = $val['field'];
			$op = $val['op'];
			$v = $val['data'];
			if(strlen($v) != 0   && $op ) {
				$string = true;
				if(in_array($field,$datearray)){
					$av = explode(",",jqGridUtils::parseDate('d/m/Y H:i:s',$v,'Y,m,d,H,i,s'));
					$av[1] = (int)$av[1]-1;
					$v = "new Date(".implode(",",$av).")";
					$string = false;
				}
				if(in_array($field,$mongointegers)) {
					$string = false;
				}
				$i++;
				if($i > 1) $s .= $gopr;
				switch ($op)
				{
					case 'bw':
						$s .= "this.".$field.".match(/^$v.*$/i)";
						break;
					case 'bn':
						$s .= "!this.".$field.".match(/^$v.*$/i)";
						break;
					case 'ew':
						$s .= "this.".$field.".match(/^.*$v$/i)";
						break;
					case 'en':
						$s .= "!this.".$field.".match(/^.*$v$/i)";
						break;
					case 'cn':
						$s .= "this.".$field.".match(/^.*$v.*$/i)";
						break;
					case 'nc':
						$s .= "!this.".$field.".match(/^.*$v.*$/i)";
						break;
					default :
						if($string) $v = "'".$v."'";
						$s .= " this.".$field." ".$sopt[$op]. $v;
						break;
				}
			}
		}
		if(isset ($mongoquery) && is_array($mongoquery)) {
			$mongoquery = jqGridUtils::array_extend($mongoquery, array('$where'=>"function(){ return ".$s.";}"));
		} else {
			$mongoquery = array('$where'=>"function(){ return ".$s.";}");
		}
		return $mongoquery;
	}
	public static function errorMessage ( $conn )
	{
		return "Mongo Error.";
	}
}

?>
