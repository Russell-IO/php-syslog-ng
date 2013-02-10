<?php
/**
 * @author  Tony Tomov, (tony@trirand.com)
 * @copyright TriRand Ltd
 * @version 4.3.2.0
 * @package jqGrid
 *
 * @abstract
 * A PHP class to work with jqGrid jQuery plugin.
 * The main purpose of this class is to provide the data from database to jqGrid,
 * simple subgrid and export to excel. Also can be used for search provided
 * from toolbarFilter and searchGrid methods
 *
 * How to use
 *
 * Using table
 *
 * require_once 'jqGridPdo.php';
 * $dsn = "mysql:host=localhost;dbname=griddemo";
 * $db = new PDO($dsn, 'username', 'password');
 *
 * $mygrid = new jqGrid($db);
 * $mygrid->table = "invoices";
 * $mygrid->queryGrid();
 *
 * Using custom SQL
 *
 * $mygrid = new jqGrid($db);
 * $mygrid->SelectCommand ="SELECT * FROM invoices";
 * $mygrid->queryGrid();
 *
 * Using xml file where the sql commands are stored
 *
 * $mygrid = new jqGrid($db);
 * $mygrid->readFromXML = true
 * $mygrid->SelectCommand ="xmlfile.getInvoiceTable";
 * $mygrid->queryGrid();
 *
 * Using summary fields. Note that in this case in jqGrid footerrow and
 * userDataOnFooter should be set
 *
 * $mygrid = new jqGrid($db);
 * $mygrid->table = "invoices";
 * $mygrid->queryGrid(array("amount"=>"amount");
 */
require_once 'jqUtils.php';

class jqGrid
{
	/**
	 * Get te current version
	 * @var string
	 */
	public $version = '4.3.2.0';
	/**
	 *
	 * Stores the connection passed to the constructor
	 * @var resourse
	 */
	protected $pdo;
	/**
	 * Used to perform case insensitive search in PostgreSQL. The variable is
	 * detected automatically depending on the griver from jqGrid{driver}.php
	 * @var string
	 */
	protected $I = '';
	/**
	 * This is detected automatically from the passed connection. Used to
	 * construct the appropriate pagging for the database and in case of
	 * PostgreSQL to set case insensitive search
	 * @var string
	 */
	protected $dbtype;
	/**
	 *
	 * Holds the modified select command used into grid
	 * @var string
	 */
	protected $select="";
	/**
	 *
	 * Date format accepted in the database. See getDbDate
	 * and setDbDate and datearray. Also this format is used to automatically
	 * convert the date for CRUD and search operations
	 * @var string
	 */
	protected $dbdateformat = 'Y-m-d';
	/**
	 *
	 * Datetime format accepted in the database. See getDbTime
	 * and setDbTime and datearray. Also this format is used to automatically
	 * convert the date for CRUD and search operations
	 * @var string
	 */
	protected $dbtimeformat = 'Y-m-d H:i:s';
	/**
	 * The date format used by the user when a search is performed and CRUD operation
	 * See setUserDate and getUserDate. Also this format is used to automatically convert the date
	 * passed from grid to database. Used in CRUD operations and search
	 * @var string
	 */
	protected $userdateformat = 'd/m/Y';
	/**
	 *
	 * The datetime format used by the user when a search is performed and CRUD operation
	 * See setUserTime and getUserTime. Also this format is used to automatically convert the datetime
	 * passed from grid to database. Used in CRUD operations and search
	 * @var string
	 */
	protected $usertimeformat = 'd/m/Y H:i:s';
	/*
	 * Array that holds the the current log
	 */
	protected static $queryLog = array();

	/**
	 * Temporary variable for internal use
	 * @var mixed
	 */
	protected $tmpvar = false;
	/**
	 * Log query
	 *
	 * @param string $sql
	 * @param array $data
	 * @param array $types
	 */
	public function logQuery($sql, $data = null, $types=null, $input= null, $fld=null, $primary='')
	{
		self::$queryLog[] = array(
			'time' => date('Y-m-d H:i:s'),
			'query' => $sql,
			'data' => $data,
			'types'=> $types,
			'fields' => $fld,
			'primary' => $primary,
			'input' => $input
			);
	}
	/**
	 * Enable disable debuging
	 * @var boolean
	 */
	public $debug = false;
	/**
	 * Determines if the log should be written to file or echoed.
	 * Ih set to created is a file jqGrid.log in the directory where the script is
	 * @var boolean
	 */
	public $logtofile = true;
	/**
	 * Prints all executed SQL queries to file or console
	 * @see $logtofile
	 */
	public function debugout()
	{
		if($this->logtofile) {
			$fh = @fopen( "jqGrid.log", "a+" );
			if( $fh ) {
				$the_string = "Executed ".count(self::$queryLog)." query(s) - ".date('Y-m-d H:i:s')."\n";
				$the_string .= print_r(self::$queryLog,true);
				fputs( $fh, $the_string, strlen($the_string) );
				fclose( $fh );
				return( true );
			} else {
				echo "Can not write to log!";
			}
		} else {
			echo "<pre>\n";
			print_r(self::$queryLog);
			echo "</pre>\n";
		}
	}
	/**
	 * If set to true all errors from the server are shown in the client in a
	 * dialog. Curretly work only for grid and form edit.
	 * @var boolean
	 */
	public $showError = false;
	/**
	 * Last error message from the server
	 * @var string
	 */
	public $errorMessage = '';
	/**
	 *  Function to simulate 500 Internal error so that it sends the error
	 * to the client. It is activated only if $showError is true.
	 */
	public function sendErrorHeader () {
		if($this->errorMessage) {
		header($_SERVER["SERVER_PROTOCOL"]." 500 Internal Server error.");
		if($this->customClass) {
			try {
				$this->errorMessage = call_user_func(array($this->customClass,$this->customError),$this->oper,$this->errorMessage);
			} catch (Exception $e) {
				echo "Can not call the method class - ".$e->getMessage();
			}
		} else if(function_exists($this->customError)) {
				$this->errorMessage = call_user_func($this->customError,$this->oper,$this->errorMessage);
		}
		die($this->errorMessage);
	}
	}
	/**
     * Holds the parameters that are send from the grid to the connector.
	 * Correspond to the prmNames in jqGrid Java Script lib
	 * @todo these parameters should be changed according to the jqGrid js
	 * @var array
	 */
	protected $GridParams = array(
		"page" => "page",
		"rows" => "rows",
		"sort" => "sidx",
		"order" => "sord",
		"search" => "_search",
		"nd" => "nd",
		"id" => "id",
		"filter" => "filters",
		"searchField" => "searchField",
		"searchOper" => "searchOper",
		"searchString" => "searchString",
		"oper" => "oper",
		"query" => "grid",
		"addoper" => "add",
		"editoper" => "edit",
		"deloper" => "del",
		"excel" => "excel",
		"subgrid"=>"subgrid",
		"totalrows" => "totalrows",
		"autocomplete"=>"autocmpl"
	);
	/**
	 * The output format for the grid. Can be json or xml
	 * @var string the
	 */
	public $dataType = "xml";
	/**
	 * Default enconding passed to the browser
	 * @var string
	 */
	public $encoding ="utf-8";
	/**
	 * If set to true uses the PHP json_encode if available. If this is set to
	 * false a custom encode function in jqGrid is used. Also use this to false
	 * if the encoding of your database is not utf-8
	 * @deprecated this not needed anymore also the related option is $encoding
	 * @var boolean
	 */
	public $jsonencode = true;
	/**
	 * Store the names which are dates. The name should correspond of the name
	 * in colModel. Used to perform the conversion from userdate and dbdate
	 * @var array
	 */
	public $datearray = array();
	/**
	 * Store the names for the int fields when database is MongoDB. Used to perform
	 * right serach in MongoDB. The array should contain the right names as listed
	 * into the collection
	 * @var array
	 */
	public $mongointegers = array();
	/**
	 * Array which set which fields should be selected in case of mongodb.
	 * If the array is empty all fields will be selected from the collection.
	 * @var array
	 */
	public $mongofields = array();
	/**
	 * In case if no table is set, this holds the sql command for
	 * retrieving the data from the db to the grid
	 * @var string
	 */
	public $SelectCommand = "";
	/**
	 *
	 * Set the sql command for excel export. If not set a _setSQL
	 * function is used to set a sql for export
	 * @see _setSQL
	 * @var string
	 */
	public $ExportCommand = "";
	/**
	 * Maximum number of rows to be exported for the excel export
	 * @var integer
	 */
	public $gSQLMaxRows = 1000;
	/**
	 * Set a sql command used for the simple subgrid
	 * @var string
	 */
	public $SubgridCommand = "";
	/**
	 * set a table to display a data to the grid
	 * @var string
	 */
	public $table = "";
	/**
	* Holds the primary key for the table
	* @var string
	*/
	protected $primaryKey;
	/**
	 *
	 * Obtain the SQL qurery from XML file.
	 * In this case the SelectCommand should be set as xmlfile.sqlid.
	 * The xmlfile is the name of xml file where we store the sql commands,
	 * sqlid is the id of the required sql.
	 * The simple xml file can look like this
	 * < ?xml version="1.0" encoding="UTF-8"?>
	 * <queries>
	 * <sql Id="getUserById">
	 *   Select *
	 *   From users
	 *   Where Id = ?
	 *   </sql>
	 *  <sql Id="validateUser">
	 *   Select Count(Id)
	 *   From users
	 *   Where Email = ? AND Password = ?
	 *  </sql>
	 * </queries>
	 * Important note: The class first look for readFromXML, then for
	 * selectCommand and last for a table.
	 * @var boolean
	 */
	public $readFromXML = false;
	/**
	 * Used to store the additional userdata which will be transported
	 * to the grid when the request is made. Used in addRowData method
	 * @var <array>
	 */
	protected $userdata = null;
	/**
	 * Custom function which can be called to modify the grid output. Parameters
	 * passed to this function are the response object and the db connection
	 * @var function
	 */
	public $customFunc = null;
	/**
	 * Custom call can be used again with custom function customFunc. We can call
	 * this using static defined functions in class customClass::customFunc - i.e
	 * $grid->customClass = Custom, $grid->customFunc = myfunc
	 * or $grid->customClass = new Custom(), $grid->customFunc = myfunc
	 * @var mixed
	 */
	public $customClass = false;
	public $customError = null;
	/**
	 * Defines if the xml otput should be enclosed in CDATA when xml output is enabled
	 * @var boolean
	 */
	public $xmlCDATA = false;
	/**
	 * Optimizes the search SQL when used in MySQL with big data sets.
	 * Use this option carefully on complex SQL
	 * @var boolean
	 */
	public $optimizeSearch = false;
	/**
	 *
	 * @var boolean
	 */
	public $cacheCount = false;
	/**
	 * Internal set in queryGrid if we should use count query in order to set
	 * the grid output
	 * @var boolean
	 */
	public $performcount = true;
	public $oper;
	/**
	 *
	 * Constructor
	 * @param resource -  $db the database connection passed to the constructor
	 */
	function __construct($db=null, $odbctype='')
	{
		if(class_exists('jqGridDB'))
			$interface = jqGridDB::getInterface();
		else
			$interface = 'local';
		$this->pdo = $db;
		if($interface == 'pdo' && is_object($this->pdo))
		{
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->dbtype = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
			if($this->dbtype == 'pgsql') $this->I = 'I';
		} else {
			$this->dbtype = $interface.$odbctype;
		}
		$oper = $this->GridParams["oper"];
		$this->oper = jqGridUtils::GetParam($oper,false);
	}
	/**
	 * Prepares a $sqlElement and binds a parameters $params
	 * Return prepared sql statement
	 * @param string $sqlElement sql to be prepared
	 * @param array $params - parameters passed to the sql
	 * @return string
	 */
	protected function parseSql($sqlElement, $params, $bind=true)
	{
		$sql = jqGridDB::prepare($this->pdo,$sqlElement, $params, $bind);
		return $sql;
	}
	/**
	 * Executes a prepared sql statement. Also if limit is set to true is used
	 * to return limited set of records
	 * Return true on success
	 * @param string $sqlId - sql to pe executed
	 * @param array $params - array of values which are passed as parameters
	 * to the sql
	 * @param resource $sql - pointer to the constructed sql
	 * @param boolean $limit - if set to true we use a pagging mechanizm
	 * @param integer $nrows - number of rows to return
	 * @param integer $offset - the offset from which the nrows should be returned
	 * @return boolean
	 */
	protected function execute($sqlId, $params, &$sql, $limit=false,$nrows=-1,$offset=-1, $order='', $sort='')
	{
		if($this->dbtype == 'mongodb') {
			return jqGridDB::mongoexecute($sqlId, $params, $sql, $limit, $nrows=0, $offset, $order, $sort, $this->mongofields);
		}
		if($this->dbtype == 'array') {
			if($params && is_array($params)) {
				foreach($params as $k=>$v)
					$params[$k] = "'".$v."'";
			}
		}
		$this->select= $sqlId;
		if($limit) {
			$this->select = jqGridDB::limit($this->select, $this->dbtype, $nrows,$offset, $order, $sort );
		}
		if($this->debug) $this->logQuery($this->select, $params);
		try {
		$sql = $this->parseSql($this->select, $params);
			$ret = true;
			if($sql) $ret = jqGridDB::execute($sql, $params); //DB2
			if(!$ret) {
				$this->errorMessage = jqGridDB::errorMessage( $this->pdo );
				throw new Exception($this->errorMessage);
			}
		} catch (Exception $e) {
			if(!$this->errorMessage) $this->errorMessage = $e->getMessage();
			if($this->showError) {
				$this->sendErrorHeader();
			} else {
				echo $this->errorMessage;
			}
			return false;
	}
		return true;
	}

	/**
	 * Read a xml file and the SelectCommand and return the sql string
	 * Return string if the query is found false if not.
	 * @param string $sqlId the string of type xmlfile.sqlId
	 * @return mixed
	 */
	protected function getSqlElement($sqlId)
	{
		$tmp = explode('.', $sqlId);
		$sqlFile = trim($tmp[0]) . '.xml';
		if(file_exists($sqlFile)) {
			$root = simplexml_load_file($sqlFile);
			foreach($root->sql as $sql)
			{
				if ($sql['Id'] == $tmp[1]) {
					if(isset ($sql['table']) && strlen($sql['table'])>0 ) {
						$this->table = $sql['table'];
					}
					if(isset ($sql['primary']) && strlen($sql['primary'])>0 ) {
						$this->primaryKey = $sql['primary'];
					}
					return $sql;
				}
			}
		}
		return false;
	}
	/**
	 * Returns object which holds the total records in the query and optionally
	 * the sum of the records determined in sumcols
	 * @param string $sql - string to be parsed
	 * @param array $params - parameters passed to the sql query
	 * @param array $sumcols - array which holds the sum of the setted rows.
	 * The array should be associative where the index corresponds to the names
	 * of colModel in the grid, and the value correspond to the actual name in
	 * the query
	 * @return object
	 */
	protected function _getcount($sql, array $params=null, array $sumcols=null)
	{
		$qryRecs = new stdClass();
		$qryRecs->COUNT = 0;
		$s ='';
		if(is_array($sumcols) && !empty($sumcols)) {
			foreach($sumcols as $k=>$v) {
				if(is_array($v)) {
					foreach($v as $dbfield=>$oper){
						$s .= ",".trim($oper)."(".$dbfield.") AS ".$k;
					}
				} else {
					$s .= ",SUM(".$v.") AS ".$k;
				}
			}
		}
		if (preg_match("/^\s*SELECT\s+DISTINCT/is", $sql) ||
			preg_match('/\s+GROUP\s+BY\s+/is',$sql) ||
			preg_match('/\s+UNION\s+/is',$sql) ||
			substr_count(strtoupper($sql), 'SELECT') > 1 ||
			substr_count(strtoupper($sql), 'FROM') > 1 ||
			$this->dbtype == 'oci8'	) {
			// ok, has SELECT DISTINCT or GROUP BY so see if we can use a table alias
			// but this is only supported by oracle and postgresql... and at end in mysql5
			//if($this->dbtype == 'pgsql' )
				$rewritesql = "SELECT COUNT(*) AS COUNT ".$s." FROM ($sql) gridalias";
				//else $rewritesql = "SELECT COUNT(*) AS COUNT ".$s." FROM ($sql)";
		} else {
			// now replace SELECT ... FROM with SELECT COUNT(*) FROM
			$rewritesql = preg_replace('/^\s*SELECT\s.*\s+FROM\s/Uis','SELECT COUNT(*) AS COUNT '.$s.' FROM ',$sql);
		}

		if (isset($rewritesql) && $rewritesql != $sql) {
			if (preg_match('/\sLIMIT\s+[0-9]+/i',$sql,$limitarr)) $rewritesql .= $limitarr[0];
			$qryRecs = $this->queryForObject($rewritesql, $params, false);
			if ($qryRecs) return $qryRecs;
		}
		return $qryRecs;
	}

	/**
	 * Return the object from the query
	 * @param string $sqlId the sql to be queried
	 * @param array $params - parameter values passed to the sql
	 * @param boolean $fetchAll - if set to true fetch all records
	 * @return object
	 */
	protected function queryForObject($sqlId, $params, $fetchAll=false)
	{
		$sql = null;
		$ret = $this->execute($sqlId, $params, $sql, false);
		if ($ret) {
			$ret = jqGridDB::fetch_object($sql,$fetchAll,$this->pdo);
			jqGridDB::closeCursor($sql);
		}
		return $ret;
	}
	/**
	 *
	 * Recursivley build the sql query from a json object
	 * @param object $group the object to parse
	 * @param array $prm parameters array
	 * @return array - first element is the where clause secon is the array of values to pass
	 */
	protected function getStringForGroup( $group, $prm )
	{
		$i_ = $this->I;
		$sopt = array('eq' => "=",'ne' => "<>",'lt' => "<",'le' => "<=",'gt' => ">",'ge' => ">=",'bw'=>" {$i_}LIKE ",'bn'=>" NOT {$i_}LIKE ",'in'=>' IN ','ni'=> ' NOT IN','ew'=>" {$i_}LIKE ",'en'=>" NOT {$i_}LIKE ",'cn'=>" {$i_}LIKE ",'nc'=>" NOT {$i_}LIKE ", 'nu'=>'IS NULL', 'nn'=>'IS NOT NULL');
		$s = "(";
		if( isset ($group['groups']) && is_array($group['groups']) && count($group['groups']) >0 )
		{
			for($j=0; $j<count($group['groups']);$j++ )
			{
				if(strlen($s) > 1 ) {
					$s .= " ".$group['groupOp']." ";
				}
				try {
					$dat = $this->getStringForGroup($group['groups'][$j], $prm);
					$s .= $dat[0];
					$prm = $prm + $dat[1];
				} catch (Exception $e) {
					echo $e->getMessage();
				}
			}
		}
		if (isset($group['rules']) && count($group['rules'])>0 ) {
			try{
				foreach($group['rules'] as $key=>$val) {
					if (strlen($s) > 1) {
						$s .= " ".$group['groupOp']." ";
					}
					$field = $val['field'];
					$op = $val['op'];
					$v = $val['data'];
					if( strtolower($this->encoding) != 'utf-8' ) {
						$v = iconv("utf-8", $this->encoding."//TRANSLIT", $v);
					}

					if( $op ) {
						if(in_array($field,$this->datearray)){
							$v = jqGridUtils::parseDate($this->userdateformat,$v,$this->dbdateformat);
						}
						switch ($op)
						{
							case 'bw':
							case 'bn':
								$s .= $field.' '.$sopt[$op]." ?";
								$prm[] = "$v%";
								break;
							case 'ew':
							case 'en':
								$s .= $field.' '.$sopt[$op]." ?";
								$prm[] = "%$v";
								break;
							case 'cn':
							case 'nc':
								$s .= $field.' '.$sopt[$op]." ?";
								$prm[] = "%$v%";
								break;
							case 'in':
							case 'ni':
								$s .= $field.' '.$sopt[$op]."( ?)";
								$prm[] = $v;
								break;
							case 'nu':
							case 'nn':
								$s .= $field.' '.$sopt[$op]." ";
								//$prm[] = $v;
								break;
							default :
								$s .= $field.' '.$sopt[$op]." ?";
								$prm[] = $v;
								break;
						}
					}
				}
			} catch (Exception $e) 	{
				echo $e->getMessage();
			}
		}
		$s .= ")";
		if ($s == "()") {
			return array("",$prm); // ignore groups that don't have rules
		} else {
			return array($s,$prm);
		}
	}

	/**
	 * Builds the search where clause when the user perform a search
	 * Return arrray the first element is a strinng with the where clause,
	 * the second element is array containing the value parameters passed to
	 * the sql.
	 *
	 * @param array $prm - parameters passed to the sql
	 * @return array
	 */
	protected function _buildSearch( array $prm=null, $str_filter = '' )
	{
		$filters = ($str_filter && strlen($str_filter) > 0 ) ? $str_filter : jqGridUtils::GetParam($this->GridParams["filter"], "");
		$rules = "";
		// multiple filter
		if($filters) {
			$count = 0;
			$filters = str_replace('$', '\$', $filters, $count);
			if( function_exists('json_decode') && strtolower(trim($this->encoding)) == "utf-8" && $count==0 ) {
				$jsona = json_decode($filters,true);
			} else {
				$jsona = jqGridUtils::decode($filters);
			}
			if(is_array($jsona)) {
				$gopr = $jsona['groupOp'];
				$rules[0]['data'] = 'dummy'; //$jsona['rules'];
			}
		// single filter
		} else if (jqGridUtils::GetParam($this->GridParams['searchField'],'')){
			$gopr = '';
			$rules[0]['field'] = jqGridUtils::GetParam($this->GridParams['searchField'],'');
			$rules[0]['op'] = jqGridUtils::GetParam($this->GridParams['searchOper'],'');
			$rules[0]['data'] = jqGridUtils::GetParam($this->GridParams['searchString'],'');
			$jsona = array();
			$jsona['groupOp'] = "AND";
			$jsona['rules'] = $rules;
			$jsona['groups'] = array();
		}
		$ret = array("",$prm);
		if($jsona) {
			if($rules && count($rules) > 0 ) {
				if(!is_array($prm)) { $prm=array(); }
				$ret = $this->getStringForGroup($jsona, $prm);
				if(count($ret[1]) == 0 ) $ret[1] = null;
			}
		}
		return $ret;
	}
	/**
	 * Build a search string from filter string posted from the grid
	 * Usually use this functio separatley
	 * @param string $filter
	 * @param string $otype
	 * @return mixed - string or array depending on $otype param
	 */
	public function buildSearch ( $filter, $otype = 'str' )
	{
		$ret = $this->_buildSearch( null, $filter );
		if($otype === 'str') {
			$s2a = explode("?",$ret[0]);
			$csa = count($s2a);
			$s = "";
			for($i=0; $i < $csa-1; $i++)
			{
				$s .= $s2a[$i]." '".$ret[1][$i]."' ";
			}
			$s .= $s2a[$csa-1];
			return $s;
		}
		return $ret;
	}
	/**
	 * Bulid the sql based on $readFromXML, $SelectCommand and $table variables
	 * The logic is: first we look if readFromXML is set to true, then we look for
	 * SelectCommand and at end if none of these we use the table varable
	 * Return string or false if the sql found
	 * @return mixed
	 */
	protected function _setSQL()
	{
		$sqlId = false;
		if($this->readFromXML==true && strlen($this->SelectCommand) > 0 ){
			$sqlId = $this->getSqlElement($this->SelectCommand);
		} else if($this->SelectCommand && strlen($this->SelectCommand) > 0) {
			$sqlId = $this->SelectCommand;
		} else if($this->table && strlen($this->table)>0) {
			if($this->dbtype == 'mongodb') {
				$sqlId = $this->table;
			} else {
			$sqlId = "SELECT * FROM ".(string)$this->table;
		}
		}
		if($this->dbtype == 'mongodb') {
			$sqlId = $this->pdo->selectCollection($sqlId);
		}
		return $sqlId;
	}
	/**
	 * Return the current date format used from the client
	 * @return string
	 */
	public function getUserDate()
	{
		return $this->userdateformat;
	}
	/**
	 * Set a new user date format using PHP convensions
	 * @param string $newformat - the new format
	 */
	public function setUserDate($newformat)
	{
		$this->userdateformat = $newformat;
	}
	/**
	 * Return the current datetime format used from the client
	 * @return string
	 */
	public function getUserTime()
	{
		return $this->usertimeformat;
	}
	/**
	 * Set a new user datetime format using PHP convensions
	 * @param string $newformat - the new format
	 */
	public function setUserTime($newformat)
	{
		$this->usertimeformat = $newformat;
	}
	/**
	 * Return the current date format used in the undelayed database
	 * @return string
	 */
	public function getDbDate()
	{
		return $this->dbdateformat;
	}
	/**
	 * Set a new  database date format using PHP convensions
	 * @param string $newformat - the new database format
	 */
	public function setDbDate($newformat)
	{
		$this->dbdateformat = $newformat;
	}
	/**
	 * Return the current datetime format used in the undelayed database
	 * @return string
	 */
	public function getDbTime()
	{
		return $this->dbtimeformat;
	}
	/**
	 * Set a new  database datetime format using PHP convensions
	 * @param string $newformat - the new database format
	 */
	public function setDbTime($newformat)
	{
		$this->dbtimeformat = $newformat;
	}
	/**
	 *
	 * Return the associative array which contain the parameters
	 * that are sended from the grid to request, search, update delete data.
	 * @return array
	 */
	public function getGridParams()
	{
		return $this->GridParams;
	}
	/**
	 * Set a grid parameters to identify the action from the grid
	 * Note that these should be set in the grid - i.e the parameters from the grid
	 * should equal to the GridParams.
	 * @param array $_aparams set new parameter.
	 */
	public function setGridParams($_aparams)
	{
		if(is_array($_aparams) && !empty($_aparams)) {
			$this->GridParams = array_merge($this->GridParams, $_aparams);
		}
	}
	/**
	 * Will select, getting rows from $offset (1-based), for $nrows.
	 * This simulates the MySQL "select * from table limit $offset,$nrows" , and
	 * the PostgreSQL "select * from table limit $nrows offset $offset". Note that
	 * MySQL and PostgreSQL parameter ordering is the opposite of the other.
	 * eg. Also supports Microsoft SQL Server
	 * SelectLimit('select * from table',3); will return rows 1 to 3 (1-based)
	 * SelectLimit('select * from table',3,2); will return rows 3 to 5 (1-based)
	 * Return object containing the limited record set
	 * @param string $limsql - optional sql clause
	 * @param integer is the number of rows to get
	 * @param integer is the row to start calculations from (1-based)
	 * @param array	array of bind variables
	 * @return object
	 */
	public function selectLimit($limsql='', $nrows=-1, $offset=-1, array $params=null, $order='', $sort='')
	{
		$sql = null;
		$sqlId = strlen($limsql)>0 ? $limsql : $this->_setSQL();
		if(!$sqlId) return false;
		$ret = $this->execute($sqlId, $params, $sql, true,$nrows,$offset, $order, $sort);
		if ($ret === true) {
			$ret = jqGridDB::fetch_object($sql, true, $this->pdo);
			jqGridDB::closeCursor($sql);
			return $ret;
		} else
			return $ret;
	}
	/**
	 * Return the result of the query to jqGrid. Support searching
	 * @param array $summary - set which columns should be sumarized in order to be displayed to the grid
	 * By default this parameter uses SQL SUM function: array("colmodelname"=>"sqlname");
	 * It can be set to use the other one this way
	 * array("colmodelname"=>array("sqlname"=>"AVG"));
	 * By default the first field correspond to the name of colModel the second to
	 * the database name
	 * @param array $params - parameter values passed to the sql
	 * @param boolen $echo if set to false return the records as object, otherwiese json encoded or xml string
	 * depending on the dataType variable
	 * @return mixed
	 */
	public function queryGrid( array $summary=null, array $params=null, $echo=true)
	{
		$sql = null;
		$sqlId = $this->_setSQL();
		if(!$sqlId) return false;
		$page = $this->GridParams['page'];
		$page = (int)jqGridUtils::GetParam($page,'1'); // get the requested page
		$limit = $this->GridParams['rows'];
		$limit = (int)jqGridUtils::GetParam($limit,'20'); // get how many rows we want to have into the grid
		$sidx = $this->GridParams['sort'];
		$sidx = jqGridUtils::GetParam($sidx,''); // get index row - i.e. user click to sort
		$sord = $this->GridParams['order'];
		$sord = jqGridUtils::GetParam($sord,''); // get the direction
		$search = $this->GridParams['search'];
		$search = jqGridUtils::GetParam($search,'false'); // get the direction
		$totalrows = jqGridUtils::GetParam($this->GridParams['totalrows'],'');
		$sord = preg_replace("/[^a-zA-Z0-9]/", "", $sord);
		$sidx = preg_replace("/[^a-zA-Z0-9. _,]/", "", $sidx);
		$performcount = true;
		$gridcnt = false;
		$gridsrearch = '1';
		if($this->cacheCount) {
			$gridcnt = jqGridUtils::GetParam('grid_recs',false);
			$gridsrearch = jqGridUtils::GetParam('grid_search','1');
			if($gridcnt && (int)$gridcnt >= 0 ) $performcount = false;
		}
		if($search == 'true') {
			if($this->dbtype == 'mongodb') {
				$params = jqGridDB::_mongoSearch($params, $this->GridParams, $this->encoding, $this->datearray, $this->mongointegers);
			} else {
				$sGrid = $this->_buildSearch($params);
				if($this->optimizeSearch === true || $this->dbtype=='array') {
					$whr = "";
					if($sGrid[0]) {
						if(preg_match("/WHERE/i",$sqlId)) // to be refined
							$whr = " AND ".$sGrid[0];
						else
							$whr = " WHERE ".$sGrid[0];
					}
					$sqlId .= $whr;
				} else {
					$whr = $sGrid[0] ? " WHERE ".$sGrid[0] : "";
					$sqlId = "SELECT * FROM (".$sqlId.") gridsearch".$whr;
				}
				$params = $sGrid[1];
				if($this->cacheCount && $gridsrearch !="-1") {
					$tmps = crc32($whr."data".implode(" ",$params));
					if($gridsrearch != $tmps) {
						$performcount = true;
					}
					$gridsrearch = $tmps;
				}
			}
		} else {
			if($this->cacheCount && $gridsrearch !="-1") {
				if($gridsrearch != '1') {
					$performcount = true;
				}
			}
		}
		$performcount = $performcount && $this->performcount;
		if($performcount) {
			if($this->dbtype == 'mongodb') {
				$qryData = jqGridDB::_mongocount($sqlId, $params, $summary);
			} else {
				$qryData = $this->_getcount($sqlId,$params,$summary);
			}
			if(is_object($qryData)) {
				if(!isset($qryData->count)) $qryData->count = null;
				if(!isset($qryData->COUNT)) $qryData->COUNT = null;
					$count = $qryData->COUNT ? $qryData->COUNT : ($qryData->count ?  $qryData->count : 0);
			} else {
					$count = isset($qryData['COUNT']) ? $qryData['COUNT'] : 0;
			}
		} else {
			$count = $gridcnt;
		}
		if( $count > 0 ) {
			$total_pages = ceil($count/$limit);
		} else {
			$count = 0;
			$total_pages = 0;
			$page = 0;
		}
		if ($page > $total_pages) $page=$total_pages;
		$start = $limit*$page - $limit; // do not put $limit*($page - 1)
		if ($start<0) $start = 0;
		if($this->dbtype == 'sqlsrv' || $this->dbtype == 'odbcsqlsrv') {
			$difrec = abs($start-$count);
			if( $difrec < $limit)
			{
				$limit = $difrec;
			}
		}
		$result = new stdClass();
		if(is_array($summary)) {
			if(is_array($qryData)) unset($qryData['COUNT']);
			else unset($qryData->COUNT,$qryData->count);
			foreach($qryData as $k=>$v) {
				if ($v == null) $v = 0;
				$result->userdata[$k] = $v;
			}
		}
		if($this->cacheCount) {
			$result->userdata['grid_recs'] = $count;
			$result->userdata['grid_search'] = $gridsrearch;
			$result->userdata['outres'] = $performcount;
		}
		if($this->userdata) {
			if(!isset ($result->userdata)) $result->userdata = array();
			$result->userdata = jqGridUtils::array_extend($result->userdata, $this->userdata);
		}
		$result->records = $count;
		$result->page = $page;
		$result->total = $total_pages;
		$uselimit = true;
		if($totalrows ) {
			$totalrows = (int)$totalrows;
			if(is_int($totalrows)) {
				if($totalrows == -1) {
					$uselimit = false;
				} else if($totalrows >0 ){
					$limit = $totalrows;
				}
			}
		}
		// build search before order clause
		if($this->dbtype !== 'mongodb') {
		if($sidx) $sqlId .= " ORDER BY ".$sidx." ".$sord;
		}
		$ret = $this->execute($sqlId, $params, $sql, $uselimit ,$limit,$start, $sidx, $sord);
		if ($ret) {
			$result->rows = jqGridDB::fetch_object($sql, true, $this->pdo);
			jqGridDB::closeCursor($sql);
			if($this->customClass) {
				try {
					$result = call_user_func(array($this->customClass,$this->customFunc),$result,$this->pdo);
				} catch (Exception $e) {
					echo "Can not call the method class - ".$e->getMessage();
				}
			} else if(function_exists($this->customFunc)) {
					$result = call_user_func($this->customFunc,$result,$this->pdo);
			}
			if($echo){
				$this->_gridResponse($result);
			} else {
				if($this->debug) $this->debugout();
				return $result;
			}
		} else {
			echo "Could not execute query!!!";
		}
		if($this->debug) $this->debugout();
	}
	/**
	 * Export the recordset to excel xml file.
	 * Can use the ExportCommand. If this command is not set uses _setSQL to set the query.
	 * The number of rows exported is limited from gSQLMaxRows variable
	 * @see _setSQL
	 * @param array $summary - set which columns should be sumarized in order to be displayed to the grid
	 * By default this parameter uses SQL SUM function: array("colmodelname"=>"sqlname");
	 * It can be set to use the other one this way
	 * array("colmodelname"=>array("sqlname"=>"AVG"));
	 * By default the first field correspond to the name of colModel the second to
	 * the database name
	 * @param array $params parameter values passed to the sql array(value)
	 * @param array $colmodel - different description for the headers - see rs2excel
	 * @param boolean $echo determines if the result should be returned or echoed
	 * @param string $filename the filename to which the sheet can be saved in case if $echo is true
	 * @return string
	 */
	public function exportToExcel(array $summary=null,array $params=null, array $colmodel=null,$echo = true, $filename='exportdata.xml')
	{
		$sql = null;
		$sql = $this->_rs($params, $summary, true);
		if ($sql) {
			$ret = $this->rs2excel($sql, $colmodel, $echo, $filename, $summary);
			jqGridDB::closeCursor($sql);
			return $ret;
		}
		else
			return "Error:Could not execute the query";
	}
////////////////////////////////////////////////////////////////////////////////////////////
	/**
	 * Convert the query to record set and set the summary data if available
	 * @param array $params parameters to the qurery
	 * @param array $summary summary option
	 * @param boolean excel
	 * @return recordset object
	 */
	protected function _rs($params=null, $summary=null, $excel=false)
	{
		if($this->ExportCommand && strlen($this->ExportCommand)>0 ) $sqlId = $this->ExportCommand;
		else $sqlId = $this->_setSQL();
		if(!$sqlId) return false;

		$sidx = $this->GridParams['sort'];
		$sidx = jqGridUtils::GetParam($sidx, ''); // get index row - i.e. user click to sort
		$sord = $this->GridParams['order'];
		$sord = jqGridUtils::GetParam($sord,''); // get the direction
		$search = $this->GridParams['search'];
		$search = jqGridUtils::GetParam($search,'false'); // get the direction
		$sord = preg_replace("/[^a-zA-Z0-9]/", "", $sord);
		$sidx = preg_replace("/[^a-zA-Z0-9. _,]/", "", $sidx);

		if($search == 'true') {
			if($this->dbtype == 'mongodb') {
				$params = jqGridDB::_mongoSearch($params, $this->GridParams, $this->encoding, $this->datearray, $this->mongointegers);
			} else {
			$sGrid = $this->_buildSearch( $params);
				if( $this->dbtype=='array') {
					$whr = "";
					if($sGrid[0]) {
						if(preg_match("/WHERE/i",$sqlId)) // to be refined
							$whr = " AND ".$sGrid[0];
						else
							$whr = " WHERE ".$sGrid[0];
					}
					$sqlId .= $whr;
				} else {
			$whr = $sGrid[0] ? " WHERE ".$sGrid[0] : "";
			$sqlId = "SELECT * FROM (".$sqlId.") gridsearch".$whr;
				}
			$params = $sGrid[1];
			}
		}
		if($this->dbtype !== 'mongodb') {
		if($sidx) $sqlId .= " ORDER BY ".$sidx." ".$sord;
		}
		if(!$excel && is_array($summary)) {
			if($this->dbtype == 'mongodb') {
				$qryData = jqGridDB::_mongocount($sqlId, $params, $summary);
			} else {
			$qryData = $this->_getcount($sqlId, $params, $summary);
			}
			unset($qryData->COUNT,$qryData->count);
			foreach($qryData as $k=>$v)
			{
				if ($v == null) $v = 0;
				$this->tmpvar[$k] = $v;
			}
		}
		if($this->userdata) {
			if(!$this->tmpvar) {
				$this->tmpvar = array();
			}
			$this->tmpvar = jqGridUtils::array_extend($this->tmpvar, $this->userdata);

		}
		if($this->debug) {
			$this->logQuery($sqlId, $params);
			$this->debugout();
		}
		$ret = $this->execute($sqlId, $params, $sql, true, $this->gSQLMaxRows, 0, $sidx, $sord );
		return $sql;
	}

	/**
	 * Holds the default settings for excel export
	 * @var array
	 */
	protected $PDF = array(
		"page_orientation" => "P",
		"unit"=>"mm",
		"page_format"=>"A4",
		"creator"=>"jqGrid",
		"author"=>"jqGrid",
		"title"=>"jqGrid PDF",
		"subject"=>"Subject",
		"keywords"=>"table, grid",
		"margin_left"=>15,
		"margin_top"=>7,
		"margin_right"=>15,
		"margin_bottom"=>25,
		"margin_header"=>5,
		"margin_footer"=>10,
		"font_name_main"=>"helvetica",
		"font_size_main"=>10,
		"header_logo"=>"",
		"header_logo_width"=>0,
		"header_title"=>"",
		"header_string"=>"",
		"header"=>false,
		"footer"=>true,
		"font_monospaced"=>"courier",
		"font_name_data"=>"helvetica",
		"font_size_data"=>8,
		"image_scale_ratio"=>1.25,
		"grid_head_color"=>"#dfeffc",
		"grid_head_text_color"=>"#2e6e9e",
		"grid_draw_color"=>"#5c9ccc",
		"grid_header_height"=>6,
		"grid_row_color"=>"#ffffff",
		"grid_row_text_color"=>"#000000",
		"grid_row_height"=>5,
		"grid_alternate_rows"=>false,
		"path_to_pdf_class"=>"tcpdf/tcpdf.php",
		"shrink_cell" => true,
		"reprint_grid_header"=>false,
		"shrink_header" => true,
		"unicode" => true,
		"encoding" => "UTF-8"
	);

	/**
	 * Set options for PDF export.
	 * @param array $apdf
	 */
	public function setPdfOptions( $apdf )
	{
		if(is_array($apdf) and count($apdf) > 0 ) {
			$this->PDF = jqGridUtils::array_extend($this->PDF, $apdf);
		}
	}

	/**
	 * Convert a recordeset to pdf object
	 * @param object $rs the recorde set object from the query
	 * @param pdf created object $pdf
	 * @param array $colmodel can be either manually created array see rs2excel or genereted
	 * from setColModel methd.
	 * @return null
	 */
	protected function rs2pdf($rs, &$pdf, $colmodel=false, $summary=null)
	{
		$s ='';$rows=0;
		$gSQLMaxRows = $this->gSQLMaxRows; // max no of rows to download

		if (!$rs) {
			printf('Bad Record set rs2pdf');
			return false;
		}
		$typearr = array();
		$ncols = jqGridDB::columnCount($rs);
		$model = false;
		$nmodel = is_array($colmodel) ? count($colmodel) : -1;
		// find the actions collon
		if($nmodel > 0) {
			for ($i=0; $i < $nmodel; $i++) {
				if($colmodel[$i]['name']=='actions') {
					array_splice($colmodel, $i, 1);
					$nmodel--;
					break;
				}
			}
		}
		if($colmodel && $nmodel== $ncols) {
			$model = true;
		}
		$aSum = array();
		$aFormula = array();
		$ahidden = array();
		$aselect = array();
		$totw = 0;
		$pw = $pdf->getPageWidth();
		$margins  = $pdf->getMargins();
		$pw = $pw - $margins['left']-$margins['right'];
		for ($i=0; $i < $ncols; $i++) {
			$ahidden[$i] = ($model && isset($colmodel[$i]["hidden"])) ? $colmodel[$i]["hidden"] : false;
			$colwidth[$i] = ($model && isset($colmodel[$i]["width"])) ? (int)$colmodel[$i]["width"] : 150;
			if($ahidden[$i]) continue;
			$totw = $totw+$colwidth[$i];
		}
		$pd = $this->PDF;
		// header

		$pdf->SetLineWidth(0.2);

		$field = array();
		$fnmkeys = array();

		function printTHeader($ncols, $maxheigh, $awidth, $aname, $ahidden, $pdf, $pd)
		{
			$pdf->SetFillColorArray($pdf->convertHTMLColorToDec($pd['grid_head_color']));
			$pdf->SetTextColorArray($pdf->convertHTMLColorToDec($pd['grid_head_text_color']));
			$pdf->SetDrawColorArray($pdf->convertHTMLColorToDec($pd['grid_draw_color']));
			$pdf->SetFont('', 'B');
			for ($i=0; $i < $ncols; $i++) {
				if($ahidden[$i]) {
					continue;
				}
				if(!$pd['shrink_header']) {
					$pdf->MultiCell($awidth[$i], $maxheigh, $aname[$i], 1, 'C', true, 0, '', '', true, 0, true, true, 0, 'B', false);
				} else {
					$pdf->Cell($awidth[$i], $pd['grid_header_height'], $aname[$i], 1, 0, 'C', 1, '', 1);
				}
			}
		}
		$maxheigh = $pd['grid_header_height'];
		for ($i=0; $i < $ncols; $i++) {
			// hidden columns
			$aselect[$i] = false;
			if($model && isset($colmodel[$i]["formatter"])) {
				if($colmodel[$i]["formatter"]=="select") {
					$asl = isset($colmodel[$i]["formatoptions"]) ? $colmodel[$i]["formatoptions"] : $colmodel[$i]["editoptions"];
					if(isset($asl["value"]))  $aselect[$i] = $asl["value"];
				}
			}
			$fnmkeys[$i] = "";
			if($ahidden[$i]) {
				continue;
			}
			if($model) {
				$fname[$i] = isset($colmodel[$i]["label"]) ? $colmodel[$i]["label"] : $colmodel[$i]["name"];
				$typearr[$i] = isset($colmodel[$i]["sorttype"]) ? $colmodel[$i]["sorttype"] : '';
				$align[$i] = isset($colmodel[$i]["align"]) ? strtoupper(substr($colmodel[$i]["align"],0,1)) : "L";
			} else {
				$field = jqGridDB::getColumnMeta($i,$rs);
				$fname[$i] = $field["name"];
				$typearr[$i] = jqGridDB::MetaType($field, $this->dbtype);
				$align[$i] = "L";
			}
			$fname[$i] = htmlspecialchars($fname[$i]);
			$fnmkeys[$i] = $model ? $colmodel[$i]["name"] : $fname[$i];
			$colwidth[$i]= ($colwidth[$i]/$totw)*100;
			$colwidth[$i] = ($pw/100)*$colwidth[$i];
			if (strlen($fname[$i])==0) $fname[$i] = '';
			if(!$pd['shrink_header']) {
				$maxheigh = max($maxheigh, $pdf->getStringHeight($colwidth[$i], $fname[$i], false, true, '', 1) );
			} 
			//$maxheigh = $pd['grid_header_height'];
			//$pdf->Cell($colwidth[$i], $pd['grid_header_height'], $fname[$i], 1, 0, 'C', 1);
		}
		printTHeader($ncols, $maxheigh, $colwidth, $fname, $ahidden, $pdf, $pd);
		$pdf->Ln();
		//Hack for mysqli driver
		if($this->dbtype == 'mysqli') {
			$fld = $rs->field_count;
			//start the count from 1. First value has to be a reference to the stmt. because bind_param requires the link to $stmt as the first param.
			$count = 1;
			$fieldnames[0] = &$rs;
			for ($i=0;$i<$fld;$i++) {
				$fieldnames[$i+1] = &$res_arr[$i]; //load the fieldnames into an array.
			}
			call_user_func_array('mysqli_stmt_bind_result', $fieldnames);
		}
		$datefmt = $this->userdateformat;
		$timefmt = $this->usertimeformat;

		$pdf->SetFillColorArray($pdf->convertHTMLColorToDec($pd['grid_row_color']));
		$pdf->SetTextColorArray($pdf->convertHTMLColorToDec($pd['grid_row_text_color']));
		$pdf->SetFont('');

		$fill = false;
		if(!$pd['shrink_cell']) {
			$dimensions = $pdf->getPageDimensions();
		}
		while ($r = jqGridDB::fetch_num($rs))
		{
			if($this->dbtype == 'mysqli') $r = $res_arr;
			$varr = array();
			$maxh = $pd['grid_row_height'];
			for ($i=0; $i < $ncols; $i++)
			{
				if(isset($ahidden[$i]) && $ahidden[$i]) continue;
				$v = $r[$i];
				if(is_array($aselect[$i])) {
					if(isset($aselect[$i][$v])) {
						$v1 = $aselect[$i][$v];
						if($v1)  $v = $v1;
					}
					$typearr[$i] = 'string';
				}
				$type = $typearr[$i];
				switch($type) {
				case 'date':
					$v = $datefmt != $this->dbdateformat ? jqGridUtils::parseDate($this->dbdateformat, $v, $datefmt) : $v;
					break;
				case 'datetime':
					$v = $timefmt != $this->dbtimeformat ? jqGridUtils::parseDate($this->dbtimeformat,$v,$timefmt) : $v;
					break;
				case 'numeric':
				case 'int':
					$v = trim($v);
					break;
				default:
					$v = trim($v);
					if (strlen($v) == 0) $v = '';
				}
				if(!$pd['shrink_cell'])  {
					$varr[$i] = $v;
					$maxh = max($maxh, $pdf->getStringHeight($colwidth[$i], $v, false, true, '', 1) );
				} else {
					$pdf->Cell($colwidth[$i], $pd['grid_row_height'], $v, 1, 0,$align[$i], $fill,'',1);
				}
			} // for
			if(!$pd['shrink_cell'])  {
				$startY = $pdf->GetY();
				if (($startY + $maxh) + $dimensions['bm'] > ($dimensions['hk'])) {
					//this row will cause a page break, draw the bottom border on previous row and give this a top border
					//we could force a page break and rewrite grid headings here
					$pdf->AddPage();
					if($pd['reprint_grid_header']) {
						printTHeader($ncols, $maxheigh, $colwidth, $fname, $ahidden, $pdf, $pd);
						$pdf->Ln();
						$pdf->SetFillColorArray($pdf->convertHTMLColorToDec($pd['grid_row_color']));
						$pdf->SetTextColorArray($pdf->convertHTMLColorToDec($pd['grid_row_text_color']));
						$pdf->SetFont('');
					}
				}
				for ($i=0; $i < $ncols; $i++) {
					if(isset($ahidden[$i]) && $ahidden[$i]) continue;
					$pdf->MultiCell($colwidth[$i], $maxh, $varr[$i], 1, $align[$i], $fill, 0, '', '', true, 0, true, true, 0, 'T', false);
				}
			}
			if($pd['grid_alternate_rows']) {
				$fill=!$fill;
			}
			$pdf->Ln();
			$rows += 1;
			if ($rows >= $gSQLMaxRows) {
				break;
			} // switch
		} // while
		if($this->tmpvar) {
			$pdf->SetFont('', 'B');
			for ($i=0; $i < $ncols; $i++)
			{
				if(isset($ahidden[$i]) && $ahidden[$i]) continue;
				foreach($this->tmpvar as $key=>$v) {
					if($fnmkeys[$i]==$key) {
						$vv = $v;
						break;
					} else {
						$vv = '';
					}
				}
				$pdf->Cell($colwidth[$i],  $pd['grid_row_height'], $vv, 1, 0,$align[$i], $fill,'',1);
			}
		}
	}

	/**
	 * Export the recordset to pdf file.
	 * Can use the ExportCommand. If this command is not set uses _setSQL to set the query.
	 * The number of rows exported is limited from gSQLMaxRows variable
	 * @see _setSQL
	 * @param array $summary - set which columns should be sumarized in order to be displayed to the grid
	 * By default this parameter uses SQL SUM function: array("colmodelname"=>"sqlname");
	 * It can be set to use the other one this way
	 * array("colmodelname"=>array("sqlname"=>"AVG"));
	 * By default the first field correspond to the name of colModel the second to
	 * the database name
	 * @param array $params parameter values passed to the sql array(value)
	 * @param array $colmodel - different description for the headers - see rs2excel
	 * @param string $filename the filename to which the sheet can be saved in case if $echo is true
	 * @return
	 */

	public function exportToPdf(array $summary=null,array $params=null, array $colmodel=null, $filename='exportdata.pdf')
	{
		$sql = null;
		global $l;
		$sql = $this->_rs($params, $summary);
		if ($sql) {
			$pd = $this->PDF;
			try {
			include($pd['path_to_pdf_class']);
			// create new PDF document
			$pdf = new TCPDF($pd['page_orientation'], $pd['unit'], $pd['page_format'], $pd['unicode'], $pd['encoding'], false);

	// set document information
			$pdf->SetCreator($pd['creator']);
			$pdf->SetAuthor($pd['author']);
			$pdf->SetTitle($pd['title']);
			$pdf->SetSubject($pd['subject']);
			$pdf->SetKeywords($pd['keywords']);
			//set margins
			$pdf->SetMargins($pd['margin_left'], $pd['margin_top'], $pd['margin_right']);
			$pdf->SetHeaderMargin($pd['margin_header']);
			$pdf->setHeaderFont(Array($pd['font_name_main'], '', $pd['font_size_main']));
			if($pd['header'] === true) {
				$pdf->SetHeaderData($pd['header_logo'], $pd['header_logo_width'], $pd['header_title'], $pd['header_string']);
			} else {
				$pdf->setPrintHeader(false);
			}

			$pdf->SetDefaultMonospacedFont($pd['font_monospaced']);

			$pdf->setFooterFont(Array($pd['font_name_data'], '', $pd['font_size_data']));
			$pdf->SetFooterMargin($pd['margin_footer']);
			if($pd['footer'] !== true) {
				$pdf->setPrintFooter(false);
			}
			$pdf->setImageScale($pd['image_scale_ratio']);
			$pdf->SetAutoPageBreak(TRUE, 17);

			//set some language-dependent strings
			$pdf->setLanguageArray($l);
			$pdf->AddPage();
			$pdf->SetFont($pd['font_name_data'], '', $pd['font_size_data']);

			$this->rs2pdf($sql, $pdf, $colmodel, $summary);
			jqGridDB::closeCursor($sql);
			$pdf->Output($filename, 'D');
			exit();
			} catch (Exception $e) {
				return false;
				//echo "Error:".$e->getMessage();
			}
		} else {
			return "Error:Could not execute the query";
		}
	}

	/**
	 * Convert a record set to csv data
	 *
	 * @param objec $rs the record set from the query
	 * @param array $colmodel colmodel which holds the names
	 * @param string $sep The string separator
	 * @param string $sepreplace with what to replace the separator if in string
	 * @param boolean $echo shoud be the  output echoed
	 * @param string $filename if echo is tru se the file name name for download
	 * @param boolen $addtitles should we set titles as first row
	 * @param string $quote
	 * @param string $escquote
	 * @param string $replaceNewLine with what to replace new line in the sstring
	 * @return string
	 */
	private function rs2csv($rs, $colmodel, $sep=';', $sepreplace=' ', $echo=true, $filename='exportdata.csv', $addtitles=true, $quote = '"', $escquote = '"', $replaceNewLine = ' ')
	{
		if (!$rs) return '';
		// CONSTANTS
		$NEWLINE = "\r\n";
		$escquotequote = $escquote.$quote;
		$gSQLMaxRows = $this->gSQLMaxRows; // max no of rows to download
		$s = '';
		$ncols = jqGridDB::columnCount($rs);
		$model = false;
		$nmodel = is_array($colmodel) ? count($colmodel) : -1;
		// find the actions collon
		if($nmodel > 0) {
			for ($i=0; $i < $nmodel; $i++) {
				if($colmodel[$i]['name']=='actions') {
					array_splice($colmodel, $i, 1);
					$nmodel--;
					break;
				}
			}
		}
		if($colmodel && $nmodel== $ncols) {
			$model = true;
		}

		$fnames = array();
		for ($i=0; $i < $ncols; $i++) {
			if($model) {
				$fname = isset($colmodel[$i]["label"]) ? $colmodel[$i]["label"] : $colmodel[$i]["name"];
				$field["name"] = $colmodel[$i]["name"];
				$typearr[$i] = isset($colmodel[$i]["sorttype"]) ? $colmodel[$i]["sorttype"] : '';
			} else {
				$field = jqGridDB::getColumnMeta($i,$rs);
				$fname = $field["name"];
				$typearr[$i] = jqGridDB::MetaType($field, $this->dbtype);
			}
			$fnames[$i] = $field["name"];
			$v = $fname;
			if ($escquote) $v = str_replace($quote,$escquotequote,$v);
			$v = strip_tags(str_replace("\n", $replaceNewLine, str_replace("\r\n",$replaceNewLine,str_replace($sep,$sepreplace,$v))));
			//$elements[] = $v;

			$ahidden[$i] = ($model && isset($colmodel[$i]["hidden"])) ? $colmodel[$i]["hidden"] : false;
			if(!$ahidden[$i])
			$elements[] = $v;

			$aselect[$i] = false;
			if($model && isset($colmodel[$i]["formatter"])) {
				if($colmodel[$i]["formatter"]=="select") {
					$asl = isset($colmodel[$i]["formatoptions"]) ? $colmodel[$i]["formatoptions"] : $colmodel[$i]["editoptions"];
					if(isset($asl["value"]))  $aselect[$i] = $asl["value"];
				}
			}
		}
		if ($addtitles) {
			$s .= implode($sep, $elements).$NEWLINE;
		}
		$datefmt = $this->userdateformat;
		$timefmt = $this->usertimeformat;

		//Hack for mysqli driver
		if($this->dbtype == 'mysqli') {
			$fld = $rs->field_count;
			//start the count from 1. First value has to be a reference to the stmt. because bind_param requires the link to $stmt as the first param.
			$count = 1;
			$fieldnames[0] = &$rs;
			for ($i=0;$i<$fld;$i++) {
				$fieldnames[$i+1] = &$res_arr[$i]; //load the fieldnames into an array.
			}
			call_user_func_array('mysqli_stmt_bind_result', $fieldnames);
		}
		if($echo) {
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: private");
			header("Content-type: application/octet-stream");
			header("Content-Disposition: attachment; filename=\"".$filename."\"");
			header("Accept-Ranges: bytes");
		}
		$line = 0;
		while ($r = jqGridDB::fetch_num($rs) /*!$rs->EOF*/) {
			if($this->dbtype == 'mysqli') $r = $res_arr;

			$elements = array();
			$i = 0;
////
			for ($i=0; $i < $ncols; $i++)
			{
				if(isset($ahidden[$i]) && $ahidden[$i]) continue;
				$v = $r[$i];
				if(is_array($aselect[$i])) {
					if(isset($aselect[$i][$v])) {
						$v1 = $aselect[$i][$v];
						if($v1)  $v = $v1;
					}
					$typearr[$i] = 'string';
				}
				$type = $typearr[$i];
				switch($type) {
				case 'date':
					$v = $datefmt != $this->dbdateformat ? jqGridUtils::parseDate($this->dbdateformat, $v, $datefmt) : $v;
					break;
				case 'datetime':
					$v = $timefmt != $this->dbtimeformat ? jqGridUtils::parseDate($this->dbtimeformat,$v,$timefmt) : $v;
					break;
				case 'numeric':
				case 'int':
					$v = trim($v);
					break;
				default:
					$v = trim($v);
					if (strlen($v) == 0) $v = '';
				}

				if ($escquote) $v = str_replace($quote,$escquotequote,trim($v));
				$v = strip_tags(str_replace("\n", $replaceNewLine, str_replace("\r\n",$replaceNewLine,str_replace($sep,$sepreplace,$v))));

				if (strpos($v,$sep) !== false || strpos($v,$quote) !== false) $elements[] = "$quote$v$quote";
				else $elements[] = $v;
			} // for
////
			$s .= implode($sep, $elements).$NEWLINE;

			$line += 1;
			if ($echo) {
				if ($echo === true) echo $s;
				$s = '';
			}
			if ($line >= $gSQLMaxRows) {
				break;
			}

		}
		if ($echo) {
			if ($echo === true) echo $s;
			$s = '';
		}
		if($this->tmpvar) {
			$elements = array();
			for ($i=0; $i < $ncols; $i++)
			{
				if(isset($ahidden[$i]) && $ahidden[$i]) continue;
				foreach($this->tmpvar as $key=>$vv) {
					if($fnames[$i]==$key) {
						$v = $vv;
						break;
					} else {
						$v = '';
					}
				}
				if ($escquote) $v = str_replace($quote,$escquotequote,trim($v));
				$v = strip_tags(str_replace("\n", $replaceNewLine, str_replace("\r\n",$replaceNewLine,str_replace($sep,$sepreplace,$v))));

				if (strpos($v,$sep) !== false || strpos($v,$quote) !== false) $elements[] = "$quote$v$quote";
				else $elements[] = $v;
			}
			$s .= implode($sep, $elements).$NEWLINE;
			if ($echo) {
				if ($echo === true) echo $s;
				$s = '';
			}
		}

		return $s;
	}

	/**
	 * Public method to export a grid data to csv data.
	 * Can use the ExportCommand. If this command is not set uses _setSQL to set the query.
	 * The number of rows exported is limited from gSQLMaxRows variable
	 * @see _setSQL
	 * @param array $summary - set which columns should be sumarized in order to be displayed to the grid
	 * By default this parameter uses SQL SUM function: array("colmodelname"=>"sqlname");
	 * It can be set to use the other one this way
	 * array("colmodelname"=>array("sqlname"=>"AVG"));
	 * By default the first field correspond to the name of colModel the second to
	 * the database name
	 * @param array $params parameter values passed to the sql array(value)
	 * @param array $colmodel - different description for the headers
	 * @see rs2excel
	 * @param boolean $echo determines if the result should be returned or echoed
	 * @param string $filename the filename to which the sheet can be saved in case if $echo is true
	 * @param string $sep - the separator for the csv data
	 * @param string $sepreplace - with what to replace the separator if in data
	 * @return string
	 */
	public function exportToCsv(array $summary=null,array $params=null, array $colmodel=null, $echo=true, $filename='exportdata.csv', $sep=';', $sepreplace=' ')
	{
		$sql = null;
		$sql = $this->_rs($params, $summary, false);
		if ($sql) {
			//rs2csv($rs, $colmodel, $sep=',', $sepreplace=',', $echo=false, $addtitles=true, $quote = '"', $escquote = '"', $replaceNewLine = ' ')
			$ret = $this->rs2csv($sql, $colmodel, $sep, $sepreplace, $echo, $filename);
			jqGridDB::closeCursor($sql);
			return $ret;
		}
		else
			return "Error:Could not execute the query";
	}
	/**
	 *
	 * Return the result of the query for the simple subgrid
	 * The format depend of dataType variable
	 * @param array $params parameters passed to the query
	 * @param boolean $echo if set to false return object containing the data
	 */
	public function querySubGrid($params, $echo=true)
	{
		if($this->SubgridCommand && strlen($this->SubgridCommand)>0) {
			$result = new stdClass();
			$result->rows = $this->queryForObject($this->SubgridCommand, $params,true);
			if($echo)
				$this->_gridResponse($result);
			else
				return $result;
		}
	}
	/**
	 *
	 * Check in which format data should be returned to the grid based on dataType property
	 * Add the appropriate headers and echo the result
	 * @param string $response can be xml or json
	 */
	protected function _gridResponse($response)
	{
		if($this->dataType=="xml")
		{
			if(isset($response->records)) {
				$response->rows["records"]= $response->records;
				unset($response->records);
			}
			if(isset($response->total)) {
				$response->rows["total"]= $response->total;
				unset($response->total);
			}
			if(isset($response->page)) {
				$response->rows["page"]= $response->page;
				unset($response->page);
			}
			if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") )
			{
				header("Content-type: application/xhtml+xml;charset=",$this->encoding);
			} else {
				header("Content-type: text/xml;charset=".$this->encoding);
			}
			echo jqGridUtils::toXml($response,'root', null, $this->encoding, $this->xmlCDATA );
		} else if ($this->dataType=="json") {
			header("Content-type: text/x-json;charset=".$this->encoding);
			if(function_exists('json_encode') && strtolower($this->encoding) == 'utf-8') {
				echo json_encode($response);
			} else {
				echo jqGridUtils::encode($response);
			}
		}
	}
	/**
	 *
	 * From a given recordset returns excel xml file. If the summary array is
	 * defined add summary formula at last row.
	 * Return well formated xml excel string
	 * @param pdo recordset $rs recordset from pdo execute command
	 * @param array $colmodel diffrent descriptions for the headars, width, hidden cols
	 * This array is actually a colModel array in jqGrid.
	 * The array can look like
	 * Array(
	 *      [0]=>Array("label"=>"Some label", "width"=>100, "hidden"=>true, "name"=>"client_id", "formatter"=>"select", editoptions=>...),
	 *      [1]=>Array("label"=>"Other label", "width"=>80, "hidden"=>false, "name"=>"date",... ),
	 *      ...
	 * )
	 * @param boolean $echo determines if the result should be send to browser or returned as string
	 * @param string $filename filename to which file can be saved
	 * @param array $summary - set which columns should be sumarized in order to be displayed to the grid
	 * By default this parameter uses SQL SUM function: array("colmodelname"=>"sqlname");
	 * It can be set to use the other one this way
	 * array("colmodelname"=>array("sqlname"=>"AVG"));
	 * By default the first field correspond to the name of colModel the second to
	 * the database name
	 * @return string
	 */
	protected function rs2excel($rs, $colmodel=false, $echo = true, $filename='exportdata.xls', $summary=false)
	{
		$s ='';$rows=0;
		$gSQLMaxRows = $this->gSQLMaxRows; // max no of rows to download

		if (!$rs) {
			printf('Bad Record set rs2excel');
			return false;
		}
		$typearr = array();
		$ncols = jqGridDB::columnCount($rs);
		$hdr = '<?xml version="1.0" encoding="'.$this->encoding.'"?>';
		$hdr .='<?mso-application progid="Excel.Sheet"?>';
		$hdr .=  '<ss:Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">';

		// bold the header
		$hdr .= '<ss:Styles>'
			// header style
			.'<ss:Style ss:ID="1"><ss:Font ss:Bold="1"/></ss:Style>'
			// Short date style
			.'<ss:Style ss:ID="sd"><NumberFormat ss:Format="Short Date"/></ss:Style>'
			// long date format
			.'<ss:Style ss:ID="ld"><NumberFormat ss:Format="General Date"/></ss:Style>'
			// numbers
			.'<ss:Style ss:ID="nmb"><NumberFormat ss:Format="General Number"/></ss:Style>'
			.'</ss:Styles>';
			//define the headers
		$hdr .= '<ss:Worksheet ss:Name="Sheet1">';
		$hdr .= '<ss:Table>';
		// if we have width definition set it
		$model = false;
		if($colmodel && is_array($colmodel) && count($colmodel)== $ncols) {
			$model = true;
		}
		$hdr1 = '<ss:Row ss:StyleID="1">';
		$aSum = array();
		$aFormula = array();
		$ahidden = array();
		$aselect = array();
		$hiddencount = 0;
		for ($i=0; $i < $ncols; $i++) {
			// hidden columns
			$ahidden[$i] = ($model && isset($colmodel[$i]["hidden"])) ? $colmodel[$i]["hidden"] : false;
			$aselect[$i] = false;
			if($model && isset($colmodel[$i]["formatter"])) {
				if($colmodel[$i]["formatter"]=="select") {
					$asl = isset($colmodel[$i]["formatoptions"]) ? $colmodel[$i]["formatoptions"] : $colmodel[$i]["editoptions"];
					if(isset($asl["value"]))  $aselect[$i] = $asl["value"];
				}
			}
			if($ahidden[$i]) {
				$hiddencount++;
				continue;
			}
			// width
			$column = ($model && isset($colmodel[$i]["width"])) ? (int)$colmodel[$i]["width"] : 0;
			// pixel to point conversion
			if( $column > 0 ) {$column = $column*72/96; $hdr .= '<ss:Column ss:Width="'.$column.'"/>'; }
			else $hdr .= '<ss:Column ss:AutoFitWidth="1"/>';
			//names
			$field = array();
			if($model) {
				$fname = isset($colmodel[$i]["label"]) ? $colmodel[$i]["label"] : $colmodel[$i]["name"];
				$field["name"] = $colmodel[$i]["name"];
				$typearr[$i] = isset($colmodel[$i]["sorttype"]) ? $colmodel[$i]["sorttype"] : '';
			} else {
				$field = jqGridDB::getColumnMeta($i,$rs);
				$fname = $field["name"];
				$typearr[$i] = jqGridDB::MetaType($field, $this->dbtype);
			}
			if($summary && is_array($summary)) {
				foreach($summary as $key => $val)
				{
					if(is_array($val)) {
						foreach($val as $fld=>$formula) {
							if ($field["name"] == $key ){
								$aSum[] = $i-$hiddencount;
								$aFormula[] = $formula;
							}
						}
					} else {
						if ($field["name"] == $key ){
							$aSum[] = $i-$hiddencount;
							$aFormula[] = "SUM";
						}
					}
				}
			}
			$fname = htmlspecialchars($fname);
			if (strlen($fname)==0) $fname = '';
			$hdr1 .= '<ss:Cell><ss:Data ss:Type="String">'.$fname.'</ss:Data></ss:Cell>';
		}
		$hdr1 .= '</ss:Row>';
		if (!$echo) $html = $hdr.$hdr1;
		//Hack for mysqli driver
		if($this->dbtype == 'mysqli') {
			$fld = $rs->field_count;
			//start the count from 1. First value has to be a reference to the stmt. because bind_param requires the link to $stmt as the first param.
			$count = 1;
			$fieldnames[0] = &$rs;
			for ($i=0;$i<$fld;$i++) {
				$fieldnames[$i+1] = &$res_arr[$i]; //load the fieldnames into an array.
			}
			call_user_func_array('mysqli_stmt_bind_result', $fieldnames);
		}
		while ($r = jqGridDB::fetch_num($rs)) {
			if($this->dbtype == 'mysqli') $r = $res_arr;
			$s .= '<ss:Row>';
			for ($i=0; $i < $ncols; $i++)
			{
				if(isset($ahidden[$i]) && $ahidden[$i]) continue;
				$v = $r[$i];
				if(is_array($aselect[$i])) {
					if(isset($aselect[$i][$v])) {
						$v1 = $aselect[$i][$v];
						if($v1)  $v = $v1;
					}
					$typearr[$i] = 'string';
				}
				$type = $typearr[$i];
				switch($type) {
				case 'date':
					if(substr($v,0,4) == '0000' || empty($v) || $v=='NULL') {
						$v='1899-12-31T00:00:00.000';
						$s .= '<ss:Cell ss:StyleID="sd"><ss:Data ss:Type="DateTime">'.$v.'</ss:Data></ss:Cell>';
					} else if (!strpos($v,':')) {
						$v .= "T00:00:00.000";
						$s .= '<ss:Cell ss:StyleID="sd"><ss:Data ss:Type="DateTime">'.$v.'</ss:Data></ss:Cell>';
					} else {
						$thous = substr($v, -4);
						if( strpos($thous, ".") === false && strpos($v, ".") === false ) $v .= ".000";
						$s .= '<ss:Cell ss:StyleID="sd"><ss:Data ss:Type="DateTime">'.str_replace(" ","T",trim($v)).'</ss:Data></ss:Cell>';
					}
					break;
				case 'datetime':
					if(substr($v,0,4) == '0000' || empty($v) || $v=='NULL') {
						$v = '1899-12-31T00:00:00.000';
						$s .= '<ss:Cell ss:StyleID="ld"><ss:Data ss:Type="DateTime">'.$v.'</ss:Data></ss:Cell>';
					} else {
						$thous = substr($v, -4);
						if( strpos($thous, ".") === false && strpos($v, ".") === false) $v .= ".000";
						$s .= '<ss:Cell ss:StyleID="ld"><ss:Data ss:Type="DateTime">'.str_replace(" ","T",trim($v)).'</ss:Data></ss:Cell>';
					}
					break;
				case 'numeric':
				case 'int':
					$s .= '<ss:Cell ss:StyleID="nmb"><ss:Data ss:Type="Number">'.stripslashes((trim($v))).'</ss:Data></ss:Cell>';
					break;
				default:
					$v = htmlspecialchars(trim($v));
					if (strlen($v) == 0) $v = '';
					$s .= '<ss:Cell><ss:Data ss:Type="String">'.stripslashes($v).'</ss:Data></ss:Cell>';
				}
			} // for
			$s .= '</ss:Row>';

			$rows += 1;
			if ($rows >= $gSQLMaxRows) {
				break;
			} // switch
		} // while
		if(count($aSum)>0 && $rows > 0)
		{
			$s .= '<Row>';
			foreach($aSum as $ind => $ival)
			{
				$s .= '<Cell ss:StyleID="1" ss:Index="'.($ival+1).'" ss:Formula="='.$aFormula[$ind].'(R[-'.($rows).']C:R[-1]C)"><Data ss:Type="Number"></Data></Cell>';
			}
			$s .= '</Row>';
		}
		if ($echo) {
			header('Content-Type: application/ms-excel;');
			header("Content-Disposition: attachment; filename=".$filename);
			echo $hdr.$hdr1;
			echo $s . '</ss:Table></ss:Worksheet></ss:Workbook>';
		} else {
			$html .= $s .'</ss:Table></ss:Worksheet></ss:Workbook>';
			return $html;
		}
	}
	/**
	 * Add a custom data to the grid footer row if it is enabled.
	 * Also can be used to transport additional data in userdata array to be
	 * used later at client side.
	 * The syntax is $grid->addUserData(array("Name1"=>"Data1",...));
	 * The method is executed after the sumarry rows are executed, so it can
	 * overwrite some summary data which is palced on the footer.
	 * @param array $adata
	 */
	public function addUserData($adata){
		if(is_array($adata))
			$this->userdata = $adata;
	}
}

/**
 * @author  Tony Tomov, (tony@trirand.com)
 * @copyright TriRand Ltd
 * @package jqGrid
 * @abstract
 * This class extend the main jqGrid class and is used for CRUD operations.
 * Can work on table. Also the table should have one primary key.
 *
 * Usage:
 *
 * 1.Suppose the table has a primary key and this key is serial (autoincrement)
 *
 * $mygrid new jqGridEdit($db);
 * $mygrid->setTable('invoices');
 * $mygrid->editGrid();
 *
 * In this case the parameter names - i.e names with : should correspond to the names
 * in colModel in jqGrid definition.
 */
class jqGridEdit  extends jqGrid
{
	/**
	* Field names and data types from the table
	* @var array
	*/
	protected $fields = array();
	/**
	 * Stores the message which will be send to client grid in case of succesfull
	 * operation
	 * @var string
	 */
	protected $successmsg = "";
	/**
	 * Set the message which will be send to client side grid when succefull CRUD
	 * operation is performed. Usually afterSubmit event should be used in this case.
	 * @param string $msg
	 */
	public function setSuccessMsg($msg)
	{
		if($msg) {
			$this->successmsg = $msg;
		}
	}
	/**
	* Defines if the primary key is serial (autoincrement)
	* @var boolean
	*/
	public $serialKey = true;
	/**
	 * Allow a obtaining and sending the last id (in case of serial key) to
	 * the client.
	 * @var bolean
	 */
	public $getLastInsert = false;
	/**
	 * Stores the last inserted id in case when getLastInsert is true
	 * @var mixed.
	 */
	protected $lastId =null;	
	/**
	*
	* Tell the class if the fields should be get from the query.
	* If set to false the $fields array should be set manually in type
	* $fields = array("dbfield1" => array("type"=>"integer")...);
	* @var boolean
	*/	
	protected $buildfields = false;
	/**
	* If true every CRUD is enclosed within begin transaction - commit/rollback
	* @var boolean
	*/
	public $trans = true;
	/**
	 * Enables/disables adding of record into the table
	 * @var boolean
	 */
	public $add = true;
	/**
	 * Enables/disables updating of record into the table
	 * @var boolean
	 */
	public $edit = true;
	/**
	 * Enables/disables deleting of record into the table
	 * @var boolean
	 */
	public $del = true;
	/**
	 * Determines the type of accepting the input data. Can be POST or GET
	 * @var string
	 */
	public $mtype = "POST";
	/**
	 * Decodes the input for update and insert opeartions using the html_entity_decode
	 * @var booolen
	 */
	public $decodeinput = false;
	/**
	* Return the primary key of the table
	* @return string
	*/
	public function getPrimaryKeyId()
	{
		return $this->primaryKey;
	}
	/**
	* Set a primary key for the table
	* @param string $keyid
	*/
	public function setPrimaryKeyId($keyid)
	{
		$this->primaryKey = $keyid;
	}
	/**
	 * Set table for CRUD and build the fields
	 * @param string $_newtable
	 *
	 */
	public function setTable($_newtable)
	{
		$this->table= $_newtable;
	}
	/**
	 * Build the fields array with a database fields from the table.
	 * Also we get the fields types
	 * Return false if the fields can not be build.
	 * @return boolen
	 */
	protected function _buildFields()
	{
		$result = false;
		if(strlen(trim($this->table))>0 ) {
			if ($this->buildfields) return true;
			$wh = ($this->dbtype == 'sqlite') ? "": " WHERE 1=2";
			$sql = "SELECT * FROM ".$this->table.$wh;
			if($this->debug) {
				$this->logQuery($sql);
				$this->debugout();				
			}
			try {
				$select =  jqGridDB::query($this->pdo,$sql);
				if($select) {
					$colcount = jqGridDB::columnCount($select);
					$rev = array();
					for($i=0;$i<$colcount;$i++)
					{
						$meta = jqGridDB::getColumnMeta($i, $select);
						$type = jqGridDB::MetaType($meta, $this->dbtype);
						$this->fields[$meta['name']] = array('type'=>$type);
					}
					jqGridDB::closeCursor($select);
					$this->buildfields = true;
					$result = true;
				} else {
					$this->errorMessage = jqGridDB::errorMessage( $this->pdo );
					throw new Exception($this->errorMessage);
				}
			} catch (Exception $e) {
				$result = false;
				if(!$this->errorMessage) $this->errorMessage = $e->getMessage();
			}
		} else {
			$this->errorMessage = "No database table is set to operate!";
		}
		if($this->showError && !$result) {
			$this->sendErrorHeader();
		}
		return $result;
	}
	/**
	 * Stores all the dataa need to perform SQL command after add is succesfull
	 * @var array
	 */
	protected $_addarray = array();
	protected $_addarrayb = array();
	/**
	 * Stores all the dataa need to perform SQL command after edit is succesfull
	 * @var array
	 */
	protected $_editarray = array();
	protected $_editarrayb = array();
	/**
	 * Stores all the dataa need to perform SQL command after delete is succesfull
	 * @var array
	 */
	protected $_delarray = array();
	protected $_delarrayb = array();
	/**
	 * Executes the sql command after the crud is succefull
	 * @param string $oper can be add,edit,del
	 * @return true if ooperation is succefull.
	 */
	protected function _actionsCRUDGrid($oper, $event)
	{
		$result = true;
		switch($oper) {
		case 'add':
			if($event == 'before') {
				$ar = $this->_addarrayb;
			} else {
				$ar = $this->_addarray;
			}
			$acnt = count($ar);
			if($acnt > 0)
			{
				for($i=0;$i<$acnt; $i++)
				{
					if($this->debug) $this->logQuery($ar[$i]['sql'], $ar[$i]['params']);
					$stmt = jqGridDB::prepare($this->pdo, $ar[$i]['sql'], $ar[$i]['params']);
					$result = jqGridDB::execute($stmt, $ar[$i]['params']); //DB2
					jqGridDB::closeCursor($stmt);
					if(!$result) {
						break;
					}
				}
			}
			break;
		case 'edit':
			if($event == 'before') {
				$ar = $this->_editarrayb;
			} else {
				$ar = $this->_editarray;
			}
			$acnt = count($ar);
			if($acnt > 0)
			{
				for($i=0;$i<$acnt; $i++)
				{
					if($this->debug) $this->logQuery($ar[$i]['sql'], $ar[$i]['params']);
					$stmt = jqGridDB::prepare($this->pdo,$ar[$i]['sql'], $ar[$i]['params']);
					$result = jqGridDB::execute( $stmt, $ar[$i]['params'] ); //DB2
					jqGridDB::closeCursor($stmt);
					if(!$result) {
						break;
					}
				}
			}
			break;
		case 'del':
			if($event == 'before') {
				$ar = $this->_delarrayb;
			} else {
				$ar = $this->_delarray;
			}
			$acnt = count($ar);
			if($acnt > 0)
			{
				for($i=0;$i<$acnt; $i++)
				{
					if($this->debug) $this->logQuery($ar[$i]['sql'],$ar[$i]['params']);
					$stmt = jqGridDB::prepare($this->pdo,$ar[$i]['sql'],$ar[$i]['params']);
					$result = $stmt ? jqGridDB::execute( $stmt, $ar[$i]['params'] ) : false;
					jqGridDB::closeCursor($stmt);
					if(!$result) {
						return false;
						break;
					}
				}
			}
			break;
		}
		return $result;
	}

	/**
	 * Run a sql command(s) before the operation from the CRUD.
	 * Can run a unlimited
	 *
	 * @param string $oper - the operation after which the command should be run
	 * @param string $sql - the sql command
	 * @param array $params  - parameters passed to the sql query.
	 */
	public function setBeforeCrudAction($oper, $sql, $params = null)
	{
		switch ($oper)
		{
			case 'add':
				$this->_addarrayb[] = array("sql"=>$sql,"params"=>$params);
				break;
			case 'edit':
				$this->_editarrayb[] = array("sql"=>$sql,"params"=>$params);
				break;
			case 'del':
				$this->_delarrayb[] = array("sql"=>$sql,"params"=>$params);
				break;
		}
	}

	/**
	 * Run a sql command(s) after the operation from the CRUD is succefull.
	 * Can run a unlimited
	 *
	 * @param string $oper - the operation after which the command should be run
	 * @param string $sql - the sql command
	 * @param array $params  - parameters passed to the sql query.
	 */
	public function setAfterCrudAction($oper, $sql, $params = null)
	{
		switch ($oper)
		{
			case 'add':
				$this->_addarray[] = array("sql"=>$sql,"params"=>$params);
				break;
			case 'edit':
				$this->_editarray[] = array("sql"=>$sql,"params"=>$params);
				break;
			case 'del':
				$this->_delarray[] = array("sql"=>$sql,"params"=>$params);
				break;
		}
	}

	/**
	 * Return the fields generated for CRUD operations
	 * @return array
	 */
	public function getFields()
	{
		return $this->fields;
	}
	/**
	 *
	 * Insert the data array into the database according to the table element.
	 * A primaryKey should be set. If the key is not set It can be obtained
	 * from jqGridDB::getPrimaryKey
	 * Return true on succes, false otherwiese.
	 * @todo in the future we should return the last insert id from the table
	 * @param array $data associative array which key values correspond to the
	 * names in the table.
	 * @return boolean
	 */
	public function insert($data)
	{
		if(!$this->add) return false;
		if(!$this->_buildFields()) {
			return false;
		}
		if(!$this->checkPrimary()) {
			return false;
		}
		$datefmt = $this->userdateformat;
		$timefmt = $this->usertimeformat;
		if($this->serialKey) unset($data[$this->getPrimaryKeyId()]);
		$tableFields = array_keys($this->fields);
		$rowFields = array_intersect($tableFields, array_keys($data));
		// Get "col = :col" pairs for the update query
		$insertFields = array();
		$binds = array();
		$types = array();
		$v ='';
		foreach($rowFields as $key => $val)
		{
			$insertFields[] = "?";
			//$field;
			$t = $this->fields[$val]["type"];
			$value = $data[$val];
			if( strtolower($this->encoding) != 'utf-8' ) {
				$value = iconv("utf-8", $this->encoding."//TRANSLIT", $value);
			}
			if(strtolower($value)=='null') {
				$v = NULL;
			} else {
			switch ($t) {
				case 'date':
					$v = $datefmt != $this->dbdateformat ? jqGridUtils::parseDate($datefmt,$value,$this->dbdateformat) : $value;
					break;
				case 'datetime' :
					$v = $timefmt != $this->dbtimeformat ? jqGridUtils::parseDate($timefmt,$value,$this->dbtimeformat) : $value;
					break;
				case 'time':
					$v = jqGridUtils::parseDate($timefmt,$value,'H:i:s');
					break;
				default :
					$v = $value;
			}
			if($this->decodeinput) $v = htmlspecialchars_decode($v);
			}
			$types[] = $t;
			$binds[] = $v;
			unset($v);
		}
		$result = false;
		if(count($insertFields) > 0) {
			// build the statement
			$sql = "INSERT INTO " . $this->table .
				" (" . implode(', ', $rowFields) . ")" .
				" VALUES( " . implode(', ', $insertFields) . ")";
			// Prepare insert query
			$stmt = $this->parseSql($sql, $binds, false);
			if($stmt) {
				// Bind values to columns
				jqGridDB::bindValues($stmt, $binds, $types);

				// Execute
				if($this->trans) {
					try {
						jqGridDB::beginTransaction($this->pdo);
						$result = $this->_actionsCRUDGrid('add', 'before');
						if($this->debug) $this->logQuery($sql, $binds, $types, $data, $this->fields, $this->primaryKey);
						if( $result ) $result = jqGridDB::execute($stmt, $binds);
						if( $result ) {
							if($this->serialKey && $this->getLastInsert) {
								$this->lastId = jqGridDB::lastInsertId($this->pdo, $this->table, $this->primaryKey, $this->dbtype);
								if(!is_numeric($this->lastId) ) {
									$result = false;
								}
							}
						}
						if($result) {
							$saver = $this->showError;
							$this->showError = false;
							$result = $this->_actionsCRUDGrid('add', 'after');
							$this->showError = $saver;
						}
						if($result) {
							$result = jqGridDB::commit($this->pdo);
						}
						jqGridDB::closeCursor($stmt);
						if(!$result) {
							$this->errorMessage = jqGridDB::errorMessage( $this->pdo );
							throw new Exception($this->errorMessage);
						}
					} catch (Exception $e) {
						jqGridDB::rollBack($this->pdo);
						$result = false;
						if(!$this->errorMessage) $this->errorMessage = $e->getMessage();
					}
				} else {
					try {
						$result = $this->_actionsCRUDGrid('add', 'before');
						if($this->debug) $this->logQuery($sql, $binds, $types, $data, $this->fields, $this->primaryKey);
						if( $result ) $result = jqGridDB::execute($stmt, $binds);

						jqGridDB::closeCursor($stmt);
						if($this->serialKey && $this->getLastInsert && $result) {
							$this->lastId = jqGridDB::lastInsertId($this->pdo, $this->table, $this->primaryKey, $this->dbtype);
							if(!is_numeric($this->lastId) ) {
								$result = false;
							}
						}
						if($result) $result = $this->_actionsCRUDGrid('add', 'after');
						if(!$result) {
							$this->errorMessage = jqGridDB::errorMessage( $this->pdo );
							throw new Exception($this->errorMessage);
						}
					} catch (Exception $e) {
						$result = false;
						if(!$this->errorMessage) $this->errorMessage = $e->getMessage();
					}
				}
			} else {
				$this->errorMessage = "Error when preparing a INSERT statement!";
				$result = false;
			}
		} else {
			$this->errorMessage = "Data posted does not match insert fields!";
			$result = false;
		}
		if($this->debug) $this->debugout();
		if($this->showError && !$result) {
			$this->sendErrorHeader();
		}
		return $result;
	}
	/**
	 *
	 * Update the data into the database according the table element
	 * A primaryKey should be set. If the key is not set It can be obtained
	 * from jqGridDB::getPrimaryKey
	 * Return true on success, false when the operation is not succefull
	 * @todo possibility to set additional where clause
	 * @param array $data associative array which key values correspond to the
	 * names in the table
	 * @return boolean
	 */
	public function update($data)
	{
		if(!$this->edit) return false;
		if(!$this->_buildFields()) {
			return false;
		}
		if(!$this->checkPrimary()) {
			return false;
		}
		$datefmt = $this->userdateformat;
		$timefmt = $this->usertimeformat;

		$custom = false;

		$tableFields = array_keys($this->fields);
		$rowFields = array_intersect($tableFields, array_keys($data));
		// Get "col = :col" pairs for the update query
		$updateFields = array();
		$binds = array();
		$types = array();
		$pk = $this->getPrimaryKeyId();
		foreach($rowFields as $key => $field) {
			$t = $this->fields[$field]["type"];
			$value = $data[$field];
			if( strtolower($this->encoding) != 'utf-8' ) {
				$value = iconv("utf-8", $this->encoding."//TRANSLIT", $value);
			}
			if(strtolower($value) == 'null') {
				$v = NULL;
			} else {
			switch ($t) {
				case 'date':
					$v = $datefmt != $this->dbdateformat ? jqGridUtils::parseDate($datefmt,$value,$this->dbdateformat) : $value;
					break;
				case 'datetime' :
					$v = $timefmt != $this->dbtimeformat ? jqGridUtils::parseDate($timefmt,$value,$this->dbtimeformat) : $value;
					break;
				case 'time':
					$v = jqGridUtils::parseDate($timefmt,$value,'H:i:s');
					break;
				default :
					$v = $value;
			}
			if($this->decodeinput) $v = htmlspecialchars_decode($v);
			}
			if($field != $pk ) {
				$updateFields[] = $field . " = ?";
				$binds[] = $v;
				$types[] = $t;
			} else if($field == $pk) {
				$v2 = $v;
				$t2 = $t;
			}
			unset($v);
		}
		$result = false;
		if(!isset($v2))  {
			$this->errorMessage = "Primary key/value is missing or is not correctly set!";
			if($this->showError) {
				$this->sendErrorHeader();
			}
			return $result;
		}
		$binds[] = $v2;
		$types[] = $t2;
		if(count($updateFields) > 0) {
			// build the statement
			$sql = "UPDATE " . $this->table .
				" SET " . implode(', ', $updateFields) .
				" WHERE " . $pk . " = ?";
			// Prepare update query
			$stmt = $this->parseSql($sql, $binds, false);
			if($stmt) {
				// Bind values to columns
				jqGridDB::bindValues($stmt, $binds, $types);
				if($this->trans) {
					try {
						jqGridDB::beginTransaction($this->pdo);
						$result = $this->_actionsCRUDGrid('edit', 'before');
						if($this->debug) $this->logQuery($sql, $binds, $types, $data, $this->fields, $this->primaryKey);
						if($result) $result = jqGridDB::execute($stmt, $binds);
						jqGridDB::closeCursor($stmt);
						if($result) {
							$result = $this->_actionsCRUDGrid('edit', 'after');
						}
						if($result)	{
							$result = jqGridDB::commit($this->pdo);
						} else {
							$this->errorMessage = jqGridDB::errorMessage( $this->pdo );
							throw new Exception($this->errorMessage);
						}
					} catch (Exception $e) {
						jqGridDB::rollBack($this->pdo);
						$result = false;
						if(!$this->errorMessage) $this->errorMessage = $e->getMessage();
					}
				} else {
					try {
						$result = $this->_actionsCRUDGrid('edit', 'before');
						if($this->debug) $this->logQuery($sql, $binds, $types, $data, $this->fields, $this->primaryKey);
						if($result) $result = jqGridDB::execute($stmt, $binds);
						jqGridDB::closeCursor($stmt);
						if($result) {
							$result = $this->_actionsCRUDGrid('edit', 'after');
						}
						if(!$result){
							$this->errorMessage = jqGridDB::errorMessage( $this->pdo );
							throw new Exception($this->errorMessage);
						}
					} catch (Exception $e) {
						$result = false;
						if(!$this->errorMessage) $this->errorMessage = $e->getMessage();
					}
				}
			} else {
				$this->errorMessage = "Error when preparing a UPDATE statement!";
			}
		} else {
			$this->errorMessage = "Data posted does not match update fields!";
		}
		if($this->debug) $this->debugout();
		if($this->showError && !$result) {
			$this->sendErrorHeader();
		}
		return $result;
	}
	/**
	 *
	 * Return the last inserted id from the insert method in cas getLastInsert is 
	 * set to true
	 * @return mixed
	 */
	
	public function getLastInsertId ()
	{
		return $this->lastId;
	}
		
	/**
	 *
	 * Delete the data into the database according the table element
	 * A primaryKey should be set. If the key is not set It can be obtained
	 * from jqGridDB::getPrimaryKey
	 * Return true on success, false when the operation is not succefull
	 * @todo possibility to set additional where clause
	 * @param array $data associative array which key values correspond to the
	 * names in the delete command
	 * @return boolean
	 */
	public function delete(array $data, $where='', array $params=null )
	{
		$result = false;
		if(!$this->del) return $result;
		//SQL Server hack
		if(!$this->checkPrimary()) {
			return $result;
		}		
		$ide = null;
		$binds = array(&$ide);
		$types = array();
		if(count($data)>0) {
			if($where && strlen($where)>0) {
				$id = "";
				$sql = "DELETE FROM ".$this->table." WHERE ".$where;
				$stmt = $this->parseSql($sql, $params);
				$delids = "";
				$custom = true;
			} else {
				$id = $this->getPrimaryKeyId();
				if(!isset($data[$id])) {
					$this->errorMessage = "Missed data id value to perform delete!";
					if($this->showError) {
						$this->sendErrorHeader();
					}
					return $result;
				}
				$sql = "DELETE FROM ".$this->table." WHERE ".$id. "=?";
				$stmt = $this->parseSql($sql, $binds, false);
				$delids = explode(",",$data[$id]);
				$custom = false;
			}
			$types[0] = 'custom';
			if($stmt) {
				if($this->trans) {
					try {
						jqGridDB::beginTransaction($this->pdo);
						$result = $this->_actionsCRUDGrid('del', 'before');
						if( $custom ) {
							if($this->debug) $this->logQuery($sql, $params, false, $data, null, $this->primaryKey);
							$result = jqGridDB::execute( $stmt, $params );
						} else {
							foreach($delids as $i => $ide) {
								$delids[$i] = trim($delids[$i]);
								$binds[0] = &$delids[$i];
								if($this->debug) $this->logQuery($sql, $binds, $types, $data, $this->fields, $this->primaryKey);
								jqGridDB::bindValues($stmt, $binds, $types);
								$result = jqGridDB::execute($stmt, $binds);
								if(!$result) {
									break;
								}
								unset($binds[0]);
							}
						}
						jqGridDB::closeCursor($stmt);
						if($result) $result = $this->_actionsCRUDGrid('del', 'after');
						if($result)  {
							jqGridDB::commit($this->pdo);
						} else {
							$this->errorMessage = jqGridDB::errorMessage( $this->pdo );
							throw new Exception($this->errorMessage);
						}
					} catch (Exception $e) {
						jqGridDB::rollBack($this->pdo);
						$result = false;
						if(!$this->errorMessage) $this->errorMessage = $e->getMessage();
					}
				} else {
					try {
						$result = $this->_actionsCRUDGrid('del', 'before');
						if($result)  {
							if($custom) {
								$result = jqGridDB::execute( $stmt, $params );
							} else {
								foreach($delids as $i => $ide) {
									$delids[$i] = trim($delids[$i]);
									$binds[0] = &$delids[$i];
									if($this->debug) $this->logQuery($sql, $binds, $types, $data, $this->fields, $this->primaryKey);
									jqGridDB::bindValues($stmt, $binds, $types);
									$result = jqGridDB::execute($stmt, $binds);
									if(!$result) {
										break;
									}
									unset($binds[0]);
								}
							}
						}
						jqGridDB::closeCursor($stmt);
						if($result) $result = $this->_actionsCRUDGrid('del', 'after');
						if(!$result) {
							$this->errorMessage = jqGridDB::errorMessage( $this->pdo );
							throw new Exception($this->errorMessage);
						}
					} catch (Exception $e){
						$result = false;
						if(!$this->errorMessage) $this->errorMessage = $e->getMessage();
					}
				}
			}
		}
		if($this->debug) $this->debugout();
		if($this->showError && !$result) {
			$this->sendErrorHeader();
		}
		return $result;
	}

	/**
	 * Check for primary key and if not set try to obtain it
	 * Return true on success
	 *
	 * @return boolean
	 */
	protected function checkPrimary()
	{
		$result =  true;
		$errmsg = "Primary key can not be found!";
		if(strlen(trim($this->table))>0 && !$this->primaryKey) {
			$this->primaryKey = jqGridDB::getPrimaryKey($this->table, $this->pdo, $this->dbtype);
			if(!$this->primaryKey) {
				$this->errorMessage = $errmsg." ".jqGridDB::errorMessage($this->pdo);
				$result = false;
			}
		}
		if($this->showError && !$result) {
			$this->sendErrorHeader();
		}
		return $result;
	}
	/**
	 * Perform the all CRUD operations depending on the oper param send from the grid
	 * and the table element
	 * If the primaryKey is not set we try to obtain it using jqGridDB::getPrimaryKey
	 * If the primary key is not set or can not be obtained the operation is aborted.
	 * Also the method call the queryGrid to perform the grid ouput
	 * @param array $summary - set which columns should be sumarized in order to be displayed to the grid
	 * By default this parameter uses SQL SUM function: array("colmodelname"=>"sqlname");
	 * It can be set to use the other one this way
	 * array("colmodelname"=>array("sqlname"=>"AVG"));
	 * By default the first field correspond to the name of colModel the second to
	 * the database name
	 * @param array $params additional parameters that can be passed to the query
	 * @param string $oper if set the requested oper operation is performed without to check
	 * the parameter sended from the grid.
	 */
	public function editGrid(array $summary=null, array $params=null, $oper=false, $echo = true)
	{
		if(!$oper) {
			$oper = $this->oper ? $this->oper : "grid";
		}
		switch ($oper)
		{
			case $this->GridParams["editoper"] :
					$data = strtolower($this->mtype)=="post" ? jqGridUtils::Strip($_POST) : jqGridUtils::Strip($_GET);
					if( $this->update($data) )
					{
						if($this->successmsg) {
							echo $this->successmsg;
						}
					}
				break;
			case $this->GridParams["addoper"] :
					$data = strtolower($this->mtype)=="post" ? jqGridUtils::Strip($_POST) : jqGridUtils::Strip($_GET);
					if($this->insert($data) ) {
						if($this->getLastInsert) {  // inline edit here
							echo $this->getPrimaryKeyId()."#".$this->lastId;
						} else {
							if($this->successmsg)
								echo $this->successmsg;
						}
					}
				break;
			case $this->GridParams["deloper"] :
				$data = strtolower($this->mtype)=="post" ? jqGridUtils::Strip($_POST) : jqGridUtils::Strip($_GET);
				if($this->delete($data))
				{
					if($this->successmsg) {
						echo $this->successmsg;
					}
				}
				break;
			default :
				return $this->queryGrid($summary, $params, $echo);
		}
	}
}
/**
 *
 * @author  Tony Tomov, (tony@trirand.com)
 * @copyright TriRand Ltd
 * @package jqGrid
 * @abstract
 * This is a top level class which do almost evething with the grid without to write
 * Java Script code.
 */
class jqGridRender extends jqGridEdit
{
	/**
	 * Default grid parameters
	 * @var array
	 */
	protected $gridOptions = array(
		"width"=>"650",
		"hoverrows"=>false,
		"viewrecords"=>true,
		"jsonReader"=>array("repeatitems"=>false, "subgrid"=>array("repeatitems"=>false)),
		"xmlReader"=>array("repeatitems"=>false, "subgrid"=>array("repeatitems"=>false)),
		"gridview"=>true
	);
	/**
	 * Enable/disable navigator in the grid. Default false
	 * @var boolean
	 */
	public $navigator = false;
	/**
	 * Enable/disable tollbar search. Default false
	 * @var boolean
	 */
	public $toolbarfilter = false;
	/**
	 *
	 * @var type boolean
	 * If set to true add the inline navigator buttons for inline editing
	 */
	public $inlineNav = false;
	/**
	 * Enable/disable the export to excel
	 * @var boolean
	 */
	public $export = true;
	/**
	 * The export to file to excel
	 * @var string
	 */
	public $exportfile = 'exportdata.xml';
	/**
	 * The export to file to PDF
	 * @var string
	 */
	public $pdffile = 'exportdata.pdf';
	/**
	 * The export to file to CSV
	 * @var string
	 */
	public $csvfile = 'exportdata.csv';
	/**
	 * SCV separator
	 * @var string
	 */
	public $csvsep = ';';
	/**
	 * CSV string to replavce separator
	 * @var string
	 */
	public $csvsepreplace = ";";
	/**
	 * If set to true put the form edit options as grid option so they can be used from other places
	 * @var boolean
	 */
	public $sharedEditOptions = false;
	/**
	 * If set to true put the form add options as grid option so they can be used from other places
	 * @var boolean
	 */
	public $sharedAddOptions = false;
	/**
	 * If set to true put the form delete options as grid option so they can be used from other places
	 * @var boolean
	 */
	public $sharedDelOptions = false;
	/**
	 * Default navigaror options
	 * @var array
	 */
	protected $navOptions = array("edit"=>true,"add"=>true,"del"=>true,"search"=>true,"refresh"=>true, "view"=>false, "excel"=>true, "pdf"=>false, "csv"=>false, "columns"=>false);
	/**
	 * Default editing form dialog options
	 * @var array
	 */
	protected $editOptions = array("drag"=>true,"resize"=>true,"closeOnEscape"=>true, "dataheight"=>150, "errorTextFormat"=>"js:function(r){ return r.responseText;}");
	/**
	 * Default add form dialog options
	 * @var array
	 */
	protected $addOptions = array("drag"=>true,"resize"=>true,"closeOnEscape"=>true, "dataheight"=>150, "errorTextFormat"=>"js:function(r){ return r.responseText;}");
	/**
	 * Default view form dialog options
	 * @var array
	 */
	protected $viewOptions = array("drag"=>true,"resize"=>true,"closeOnEscape"=>true, "dataheight"=>150);
	/**
	 * Default delete form dialog options
	 * @var array default
	 */
	protected $delOptions = array("errorTextFormat"=>"js:function(r){ return r.responseText;}");
	/**
	 * Default search options
	 * @var array
	 */
	protected $searchOptions = array("drag"=>true, "closeAfterSearch"=>true, "multipleSearch"=>true);
	/**
	 * Default fileter toolbar search options
	 * @var array
	 */
	protected $filterOptions = array("stringResult"=>true);
	/**
	 *
	 * Holds the colModel for the grid. Can be passed as param or created
	 * automatically
	 * @var array
	 */
	protected $colModel = array();
	/**
	 *
	 * When set to false some set comands are not executed for spped improvements
	 * Usual this is done after setColModel.
	*var boolen
	 */
	protected $runSetCommands = true;
	/**
	 * Holds the grid methods.
	 * @var array
	 */
	protected $gridMethods = array();
	/**
	 * Custom java script code which is set after creation of the grid
	 * @var string
	 */
	protected $customCode = "";

	/**
	 *  Holds the button options when perform export to excel, pdf, csv ....
	 *  $var array
	 */
	protected $expoptions = array(
		"excel" => array("caption"=>"", "title"=>"Export To Excel", "buttonicon"=>"ui-icon-newwin"),
		"pdf" => array("caption"=>"", "title"=>"Export To Pdf", "buttonicon"=>"ui-icon-print"),
		"csv" => array("caption"=>"", "title"=>"Export To CSV", "buttonicon"=>"ui-icon-document"),
		"columns"=>array("caption"=>"", "title"=>"Visible Columns", "buttonicon"=>"ui-icon-calculator", "options"=>array())
	);
	/**
	 *
	 * @var type array - holds the parameters and events for inline editiong
	 */
	protected $inlineNavOpt = array("addParams"=>array(), "editParams"=>array());
	/**
	 * Return the generated colModel
	 * @return array
	 */
	public function getColModel()
	{
		return $this->colModel;
	}

	/**
	 *
	 * Return a jqGrid option specified by the key, false if the option can not be found.
	 * @param string $key the named grid option
	 * @return mixed
	 */
	public function getGridOption($key)
	{
		if(array_key_exists($key, $this->gridOptions)) return $this->gridOptions[$key];
		else return false;
	}

	/**
	 *
	 * Set a grid option. The method uses array with keys corresponding
	 * to the jqGrid options as described in jqGrid docs
	 * @param array $aoptions A key name pair. Some options can be array to.
	 */
	public function setGridOptions($aoptions)
	{
		if($this->runSetCommands) {
			if(is_array($aoptions))
				$this->gridOptions = jqGridUtils::array_extend($this->gridOptions,$aoptions);
		}
	}

	/**
	 *
	* Set a editing url. Note that this set a url from where to obtain and/or edit
	 * data.
	 * Return false if runSetCommands is already runned (false)
	 * @param string $newurl the new url
	 * @return boolean
	 */
	public function setUrl($newurl)
	{
		if(!$this->runSetCommands) return false;
		if(strlen($newurl) > 0)
		{
			$this->setGridOptions(array("url"=>$newurl,"editurl"=>$newurl, "cellurl"=>$newurl));
			return true;
		}
		return false;
	}

	/**
	 *
	 * Prepares a executuion of a simple subgrid
	 * Return false if no name options for the subgrid.
	 * @param string $suburl Url from where to get the data
	 * @param array $subnames Required - the names that should correspond to fields of the data
	 * @param array $subwidth (optional) - sets a width of the subgrid columns. Default 100
	 * @param array $subalign (optional) - set the aligmend of the columns. default center
	 * @param array $subparams (optional) additional parameters that can be passed when the subgrid
	 * plus icon is clicked. The names should be present in colModel in order to pass the values
	 * @return boolean
	 */
	public function setSubGrid ($suburl='', $subnames=false, $subwidth=false, $subalign=false, $subparams=false)
	{
		if(!$this->runSetCommands) return false;
		if($subnames && is_array($subnames)) {
			$scount = count($subnames);
			for($i=0;$i<$scount;$i++) {
				if(!isset($subwidth[$i])) $subwidth[$i] = 100;
				if(!isset($subalign[$i])) $subalign[$i] = 'center';
			}
			$this->setGridOptions(array("gridview"=>false,"subGrid"=>true,"subGridUrl"=>$suburl,"subGridModel"=>array(array("name"=>$subnames,"width"=>$subwidth,"align"=>$subalign,"params"=>$subparams))));
			return true;
		}
		return false;
	}

	/**
	 *
	 * Prepares a subgrid in the grid expecting any valid html content provieded
	 * via the $suggridurl
	 * @param string $subgridurl url from where to get html content
	 * @return boolean
	 * @param array $subgridnames a array with names from colModel which
	 * values will be posted to the server
	 */
	public function setSubGridGrid($subgridurl, $subgridnames=null)
	{
		if(!$this->runSetCommands) return false;
		$this->setGridOptions(array("subGrid"=>true,"gridview"=>false));
		$setval = (is_array($subgridnames) && count($subgridnames)>0 ) ? 'true' : 'false';
		if($setval=='true') {
			$anames  = implode(",", $subgridnames);
		} else {
			$anames = '';
		}
$subgr = <<<SUBGRID
function(subgridid,id)
{
	var data = {subgrid:subgridid, rowid:id};
	if('$setval' == 'true') {
		var anm= '$anames';
		anm = anm.split(",");
		var rd = jQuery(this).jqGrid('getRowData', id);
		if(rd) {
			for(var i=0; i<anm.length; i++) {
				if(rd[anm[i]]) {
					data[anm[i]] = rd[anm[i]];
				}
			}
		}
	}
    $("#"+jQuery.jgrid.jqID(subgridid)).load('$subgridurl',data);
}
SUBGRID;
		$this->setGridEvent('subGridRowExpanded', $subgr);
		return true;
	}

	/**
	 *
	 * Construct the select used in the grid. The select element can be used in the
	 * editing modules, in formatter or in search module
	 * @param string $colname (requiered) valid colname in colmodel
	 * @param mixed $data can be array (with pair key value) or string which is
	 * the SQL command which is executed to obtain the values. The command should contain a
	 * minimun two fields. The first is the key and the second is the value whch will
	 * be displayed in the select
	 * @param boolean $formatter deternines that the select should be used in the
	 * formatter of type select. Default is true
	 * @param boolean $editing determines if the select should be used in editing
	 * modules. Deafult is true
	 * @param boolean $seraching determines if the select should be present in
	 * the search module. Deafult is true.
	 * @param array $defvals Set the default value if none is selected. Typically this
	 * is usefull in serch modules. Can be something like arrar(""=>"All");
	 * @return boolean
	 */
	public function setSelect($colname, $data, $formatter=true, $editing=true, $seraching=true, $defvals=array(), $sep = ":", $delim=";" )
	{
		$s1 = "";
		//array();
		//new stdClass();
		$prop = array();
		//$oper = $this->GridParams["oper"];
		$goper = $this->oper ? $this->oper : 'nooper';
		if(($goper == 'nooper' || $goper == $this->GridParams["excel"])) $runme = true;
		else $runme = !in_array($goper, array_values($this->GridParams));
		if(!$this->runSetCommands && !$runme) return false;

		if(count($this->colModel) > 0 && $runme)
		{
			if(is_string($data)) {
				$aset = jqGridDB::query($this->pdo,$data);
				if($aset) {
					$i = 0;
					$s = '';
					while($row = jqGridDB::fetch_num($aset))
					{
						if($i == 0) {
							$s1 .= $row[0].$sep.$row[1];
						} else {
							$s1 .= $delim.$row[0].$sep.$row[1];
						}
						$i++;
					}
				}
				jqGridDB::closeCursor($aset);
			} else if(is_array($data) ) {
				$i=0;
				foreach($data as $k=>$v)
				{
					if($i == 0) {
						$s1 .= $k.$sep.$v;
					} else {
						$s1 .= $delim.$k.$sep.$v;
					}
					$i++;
				}
				//$s1 = $data;
			}
			if($editing)  {
				$prop = array_merge( $prop,array('edittype'=>'select','editoptions'=>array('value'=>$s1, 'separator'=>$sep, 'delimiter'=>$delim)) );
			}
			if($formatter)
			{
				$prop = array_merge( $prop,array('formatter'=>'select','editoptions'=>array('value'=>$s1, 'separator'=>$sep, 'delimiter'=>$delim)) );
			}
			if($seraching) {
				if(is_array($defvals) && count($defvals)>0) {
					//$s1 = $defvals+$s1;
					foreach($defvals as $k=>$v) {
						$s1 = $k.$sep.$v.$delim.$s1;
					}
				}
				$prop = array_merge( $prop,array("stype"=>"select","searchoptions"=>array("value"=>$s1, 'separator'=>$sep, 'delimiter'=>$delim)) );
			}
			if(count($prop)>0){
				$this->setColProperty($colname, $prop);
			}
			return true;
		}
		return false;
	}
	/**
	 * Construct autocompleter used in the grid. The autocomplete can be used in the
	 * editing modules or/and in search module.
	 * @uses jqAutocomplete class. This requiere to include jqAutocomplete.php in order
	 * to work
	 * @param string $colname (requiered) valid colname in colModel
	 * @param string $target if set determines the input element on which the
	 * value will be set after the selection in the autocompleter
	 * @param mixed $data can be array or string which is
	 * the SQL command which is executed to obtain the values. The command can contain
	 * one, two or three fields.
	 * If the field in SQL command is one, then this this field will be displayed
	 * and setted as value in the element.
	 * If the fields in SQL command are two,  then the second field will be displayed
	 * but the first will be setted as value in the element.
	 * @param array $options - array with options for the autocomplete. Can be
	 * all available options from jQuery UI autocomplete in pair name=>value.
	 * In case of events a "js:" tag should be added before the value.
	 * Additionally to this the following options can be used - cache, searchType,
	 * ajaxtype, itemLiength. For more info refer to docs.
	 * @param boolean $editing determines if the autocomplete should be used in editing
	 * modules. Deafult is true
	 * @param boolean $seraching determines if the autocomplete should be present in
	 * the search module. Deafult is true.
	 */
	public function setAutocomplete($colname, $target=false, $data='', $options=null, $editing = true, $searching=false)
	{
		try {
			$ac = new jqAutocomplete($this->pdo);
			$ac->encoding = $this->encoding;
			if(is_string($data)) {
				$ac->SelectCommand = $data;
				$url = $this->getGridOption('url');
				if(!$url) {
					$url = basename(__FILE__);
				}
				$ac->setSource($url);
			} else if(is_array($data)) {
				$ac->setSource($data);
			}
			if($colname) {
				if($ac->isNotACQuery()) {
					// options to remove
					//cache, searchType,loadAll, ajaxtype, scroll, height, itemLength
					if(is_array($options) && count($options)>0 ) {
						if(isset($options['cache'])) {
							$ac->cache= $options['cache'];
							unset($options['cache']);
						}
						if(isset($options['searchType'])) {
							$ac->searchType= $options['searchType'];
							unset($options['searchType']);
						}
						if(isset($options['ajaxtype'])) {
							$ac->ajaxtype= $options['ajaxtype'];
							unset($options['ajaxtype']);
						}
						if(isset($options['scroll'])) {
							$ac->scroll= $options['scroll'];
							unset($options['scroll']);
						}
						if(isset($options['height'])) {
							$ac->height= $options['height'];
							unset($options['height']);
						}
						if(isset($options['itemLength'])) {
							$ac->setLength($options['itemLength']);
							unset($options['itemLength']);
						}
						if(isset($options['fontsize']) ) {
							$ac->fontsize = $options['fontsize'];
							unset($options['fontsize']);
						}
						if(isset($options['strictcheck']) ) {
							$ac->strictcheck = $options['strictcheck'];
							unset($options['strictcheck']);
						}
						$ac->setOption($options);
					}
					if($editing) {
						$script = $ac->renderAutocomplete($colname, $target, false, false);
						$script = str_replace("jQuery('".$colname."')", "jQuery(el)", $script);
						$script = "setTimeout(function(){".$script."},200);";
						$this->setColProperty($colname,array("editoptions"=>array("dataInit"=>"js:function(el){".$script."}")));
					}
					if($searching) {
						$ac->setOption('select', "js:function(e,u){ $(e.target).trigger('change');}");
						$script = $ac->renderAutocomplete($colname, false, false, false);
						$script = str_replace("jQuery('".$colname."')", "jQuery(el)", $script);
						$script = "setTimeout(function(){".$script."},100);";
						$this->setColProperty($colname,array("searchoptions"=>array("dataInit"=>"js:function(el){".$script."}")));
					}
				} else {
					if(isset($options['searchType'])) {
						$ac->searchType= $options['searchType'];
					}
					$ac->renderAutocomplete($colname, $target, true, true, false);
				}
			}
		} catch (Exception $e) {
			$e->getMessage();
		}
	}
	/**
	 *
	 * Construct a pop up calender used in the grid. The datepicker can be used in the
	 * editing modules or/and in search module.
	 * @uses jqCalender class. This requiere to include jqCalender.php in order
	 * to work
	 * @param string $colname (requiered) valid colname in colModel
	 * @param array $options - array with options for the datepicker. Can be
	 * all available options from jQuery UI datepicker in pair name=>value.
	 * In case of events a "js:" tag should be added before the value.
	 * @param boolean $editing determines if the datepicker should be used in editing
	 * modules. Deafult is true
	 * @param boolean $seraching determines if the datepicker should be present in
	 * the search module. Deafult is true.
	 */
	public function setDatepicker($colname, $options=null, $editing=true, $searching=true)
	{
		try {
			if($colname){
				if($this->runSetCommands) {
					$dp = new jqCalendar();
					if(isset($options['buttonIcon']) ) {
						$dp->buttonIcon = $options['buttonIcon'];
						unset($options['buttonIcon']);
					}
					if(isset($options['buttonOnly']) ) {
						$dp->buttonOnly = $options['buttonOnly'];
						unset($options['buttonOnly']);
					}
					if(isset($options['fontsize']) ) {
						$dp->fontsize = $options['fontsize'];
						unset($options['fontsize']);
					}
					if(is_array($options) && count($options) > 0 ) {
						$dp->setOption($options);
					}
					if(!isset ($options['dateFormat'])) {
						$ud = $this->getUserDate();
						$ud = jqGridUtils::phpTojsDate($ud);
						$dp->setOption('dateFormat', $ud);
					}
					$script = $dp->renderCalendar($colname, false, false);
					$script = str_replace("jQuery('".$colname."')", "jQuery(el)", $script);
					$script = "setTimeout(function(){".$script."},100);";
					if($editing) {
						$this->setColProperty($colname,array("editoptions"=>array("dataInit"=>"js:function(el){".$script."}")));
					}
					if($searching) {
						$this->setColProperty($colname,array("searchoptions"=>array("dataInit"=>"js:function(el){".$script."}")));
					}
				}
			}
		} catch (Exception $e) {
			$e->getMessage();
		}
	}
	/**
	 *
	 * Set a valid grid event
	 * @param string $event - valid grid event
	 * @param string $code Javascript code which will be executed when the event raises
	 * @return bolean
	 */
	public function setGridEvent($event,$code)
	{
		if(!$this->runSetCommands) return false;
		$this->gridOptions[$event] = "js:".$code;
		return true;
	}

	/**
	 *
	 * Set options in the navigator for the diffrent actions
	 * @param string $module - can be navigator, add, edit, del, view
	 * @param array $aoptions options that are applicable to this module
	 * The key correspond to the options in jqGrid
	 * @return boolean
	 */
	public function setNavOptions($module,$aoptions)
	{
		$ret = false;
		if(!$this->runSetCommands) return $ret;
		switch ($module)
		{
			case 'navigator' :
				$this->navOptions = array_merge($this->navOptions,$aoptions);
				$ret = true;
				break;
			case 'add' :
				$this->addOptions = array_merge($this->addOptions,$aoptions);
				$ret = true;
				break;
			case 'edit' :
				$this->editOptions = array_merge($this->editOptions,$aoptions);
				$ret = true;
				break;
			case 'del' :
				$this->delOptions = array_merge($this->delOptions,$aoptions);
				$ret = true;
				break;
			case 'search' :
				$this->searchOptions = array_merge($this->searchOptions,$aoptions);
				$ret = true;
				break;
			case 'view' :
				$this->viewOptions = array_merge($this->viewOptions,$aoptions);
				$ret = true;
				break;
		}
		return $ret;
	}

	/**
	 *
	 * Set a event in the navigator or in the diffrent modules add,edit,del,view, search
	 * @param string $module - can be navigator, add, edit, del, view
	 * @param string $event - valid event for the particular module
	 * @param string $code - javascript code to be executed when the event occur
	 * @return boolean
	 */
	public function setNavEvent($module,$event,$code)
	{
		$ret = false;
		if(!$this->runSetCommands) return $ret;
		switch ($module)
		{
			case 'navigator' :
				$this->navOptions[$event] = "js:".$code;
				$ret = true;
				break;
			case 'add' :
				$this->addOptions[$event] = "js:".$code;
				$ret = true;
				break;
			case 'edit' :
				$this->editOptions[$event] = "js:".$code;
				$ret = true;
				break;
			case 'del' :
				$this->delOptions[$event] = "js:".$code;
				$ret = true;
				break;
			case 'search' :
				$this->searchOptions[$event] = "js:".$code;
				$ret = true;
				break;
			case 'view' :
				$this->viewOptions[$event] = "js:".$code;
				$ret = true;
    			break;
		}
		return $ret;
	}
	/**
	 * Set a options for inline editing in particulear module
	 * @param string $module - can be navigator or add or edit
	 * @param array $aoptions array of options
	 * @return boolean 
	 */
	public function inlineNavOptions ($module, $aoptions)
	{
		$ret = false;
		if(!$this->runSetCommands) return $ret;
		switch ($module)
		{
			case 'navigator':
				$this->inlineNavOpt = array_merge($this->inlineNavOpt,$aoptions);
				$ret = true;
				break;
			case 'add':
				$this->inlineNavOpt['addParams'] = array_merge($this->inlineNavOpt['addParams'],$aoptions);
				$ret = true;
				break;
			case 'edit':
				$this->inlineNavOpt['editParams'] = array_merge($this->inlineNavOpt['editParams'],$aoptions);
				$ret = true;
				break;
		}
		return $ret;
	}
	
	/**
	 *
	 * Set a event for inline editing in particulear module
	 * @param string $module - can be add or edit
	 * @param string $event the name of the event
	 * @param string $code the javascript code for this event
	 * @return boolean 
	 */
	public function inlineNavEvent ($module, $event, $code)
	{
		$ret = false;
		if(!$this->runSetCommands) return $ret;
		if($module == "add") {
			$this->inlineNavOpt['addParams'][$event] = "js:".$code;
			$ret = true;
		} else if( $module=="edit") {
			$this->inlineNavOpt['editParams'][$event] = "js:".$code;
			$ret = true;
		}
		return $ret;
	}
	/**
	 * Return a array of the all events and options for the inline navigator
	 * @return type 
	 */
	public function getInlineOptions()
	{
		return $this->inlineNavOpt;
	}
	/**
	 *
	 * Set options for the tolbar filter when enabled
	 * @param array $aoptions valid options for the filterToolbat
	 */
	public function setFilterOptions($aoptions)
	{
		 if($this->runSetCommands) {
			if(is_array($aoptions))
				$this->filterOptions = jqGridUtils::array_extend($this->filterOptions,$aoptions);
		}
	}
	/**
	 * Construct a code for execution of valid grid method. This code is putted
	 * after the creation of the grid
	 * @param string $grid valid grid id should be putted as #mygrid
	 * @param string $method valid grid method
	 * @param array $aoptions contain the parameters passed to
	 * the method. Omit this parameter if the method does not have parameters
	 */
	public function callGridMethod($grid, $method, array $aoptions=null)
	{
		if($this->runSetCommands) {
			$prm = '';
			if(is_array($aoptions) && count($aoptions) > 0)
			{
				$prm = jqGridUtils::encode($aoptions);
				$prm = substr($prm, 1);
				$prm = substr($prm,0, -1);
				$prm = ",".$prm;
			}
			$this->gridMethods[] = "jQuery('".$grid."').jqGrid('".$method."'".$prm.");";
		}
	}
	/**
	 * Put a javascript arbitrary code after all things are created. The method is executed
	 * only once when the grid is created.
	 * @param string $code - javascript to be executed
	 */
	public function setJSCode($code)
	{
		if($this->runSetCommands)
		{
			$this->customCode = "js:".$code;
		}
	}
	/**
	 * Construct the column model of the grid. The model can be passed as array
	 * or can be constructed from sql. See _setSQL() to determine which SQL is
	 * used. The method try to determine the primary key and if it is found is
	 * set as key:true to the appropriate field. If the primary key can not be
	 * determined set the first field as key:true in the colModel.
	 * Return true on success.
	 * @see _setSQL
	 * @param array $model if set construct the model ignoring the SQL command
	 * @param array $params if a sql command is used parametters passed to the SQL
	 * @param array $labels if this parameter is set it set the labels in colModel.
	 * The array should be associative which key value correspond to the name of
	 * colModel
	 * @return boolean
	 */
	public function setColModel(array $model=null, array $params=null, array $labels=null)
	{
		$goper = $this->oper ? $this->oper : 'nooper';
		// excel, nooper, !(in_array....)
		if(($goper == 'nooper' || $goper == $this->GridParams["excel"] || $goper == "pdf" || $goper=="csv")) $runme = true;
		else $runme = !in_array($goper, array_values($this->GridParams));
		if($runme) {
			if(is_array($model) && count($model)>0) {
				$this->colModel = $model;
				return true;
			}
			$sql = null;
			$sqlId = $this->_setSQL();
			if(!$sqlId) return false;
			$nof = ($this->dbtype == 'sqlite' || $this->dbtype == 'db2' || $this->dbtype == 'array' || $this->dbtype == 'mongodb') ? 1 : 0;
			//$sql = $this->parseSql($sqlId, $params);
			$ret = $this->execute($sqlId, $params, $sql, true, $nof, 0 );
			//$this->execute($sqlId, $params, $sql, $limit, $nrows, $offset)
			if ($ret)
			{
				if(is_array($labels) && count($labels)>0) $names = true;
				else $names = false;
				$colcount = jqGridDB::columnCount($sql);
				for($i=0;$i<$colcount;$i++) {
					$meta = jqGridDB::getColumnMeta($i,$sql);
					if(strtolower($meta['name']) == 'jqgrid_row') continue; //Oracle, IBM DB2
					if($names && array_key_exists($meta['name'], $labels))
						$this->colModel[] = array('label'=>$labels[$meta['name']], 'name'=>$meta['name'], 'index'=>$meta['name'], 'sorttype'=> jqGridDB::MetaType($meta,$this->dbtype));
					else
						$this->colModel[] = array('name'=>$meta['name'], 'index'=>$meta['name'], 'sorttype'=> jqGridDB::MetaType($meta,$this->dbtype));
				}
				jqGridDB::closeCursor($sql);
				if($this->primaryKey) $pk = $this->primaryKey;
				else  {
					$pk = jqGridDB::getPrimaryKey($this->table, $this->pdo, $this->dbtype);
					$this->primaryKey = $pk;
				}
				if($pk) {
					$this->setColProperty($pk,array("key"=>true));
				} else {
					$this->colModel[0] = array_merge($this->colModel[0],array("key"=>true));
				}

			} else {
				$this->errorMessage = jqGridDB::errorMessage($sql);
				if($this->showError) {
					$this->sendErrorHeader();
				}
				return $ret;
			}
		}
		if($goper == $this->GridParams["excel"]) {
			// notify all other set methods not to be executed
			$this->runSetCommands = false;
		} else if(!$runme) {
			$this->runSetCommands = false;
		}
		return true;
	}
	/**
	 * Set a new property in the constructed colModel
	 * Return true on success.
	 * @param mixed $colname valid coulmn name or index in colModel
	 * @param array $aproperties the key name properties.
	 * @return boolean
	 */
	public function setColProperty ( $colname, array $aproperties)
	{
		//if(!$this->runSetCommands) return;
		$ret = false;
		if(!is_array($aproperties)) return $ret;
		if(count($this->colModel) > 0 )
		{
			if(is_int($colname)) {
				$this->colModel[$colname] = jqGridUtils::array_extend($this->colModel[$colname],$aproperties);
				$ret = true;
			} else {
				foreach($this->colModel as $key=>$val)
				{
					if($val['name'] == trim($colname))
					{
						$this->colModel[$key] = jqGridUtils::array_extend($this->colModel[$key],$aproperties);
						$ret = true;
						break;
					}
				}
			}
		}
		return $ret;
	}
	/**
	 * Add a column at the first or last position in the colModel and sets a certain
	 * properties to it
	 * @param array $aproperties data representing the column properties - including
	 * name, label...
	 * @param string $position can be first or last or number - default is first.
	 * If a number is set the column is added before the position corresponded
	 * to the position in colmodel
	 * @return boolean
	 */
	public function addCol (array $aproperties, $position='last') {
		if(!$this->runSetCommands) return false;
		if(is_array($aproperties) && count($aproperties)>0 && strlen($position)) {
			$cmcnt = count($this->colModel);
			if( $cmcnt > 0 ) {
				if( strtolower($position) === 'first')
				{
					array_unshift($this->colModel, $aproperties);
				} else if(strtolower($position) === 'last'){
					array_push($this->colModel, $aproperties);
				} else if( (int)$position >= 0 && (int)$position <= $cmcnt-1 ) {
					$a = array_slice($this->colModel, 0, $position+1);
					$b = array_slice($this->colModel, $position+1);
					array_push($a, $aproperties);
					$this->colModel = array();
					foreach($b as $cm) {
						$a[] = $cm;
				}
					$this->colModel =  $a;
				}
				$aproperties = null;
				return true;
			}
		}
		return false;
	}
	/**
	 *
	 * Set a various options for the buttons on the pager. tite, caption , icon
	 *
	 * @param string $exptype
	 * @param array $aoptions
	 */
	public function setButtonOptions( $exptype, $aoptions)
	{
		if(is_array($aoptions) && count($aoptions)  > 0) {
			switch ($exptype) {
				case 'excel' :
					$this->expoptions['excel'] = jqGridUtils::array_extend($this->expoptions['excel'], $aoptions);
					break;
				case 'pdf' :
					$this->expoptions['pdf'] = jqGridUtils::array_extend($this->expoptions['pdf'], $aoptions);
					break;
				case 'csv' :
					$this->expoptions['csv'] = jqGridUtils::array_extend($this->expoptions['csv'], $aoptions);
					break;
				case 'columns':
					$this->expoptions['columns'] = jqGridUtils::array_extend($this->expoptions['columns'], $aoptions);
					break;
			}
		}
	}
	/**
	 * Main method which do allmost everthing for the grid.
	 * Construct the grid, perform CRUD operations, perform Query and serch operations,
	 * export to excel, set a jqGrid method, and javascript code
	 * @param string $tblelement the id of the table element to costrict the grid
	 * @param string $pager the id for the pager element
	 * @param boolean $script if set to true add a script tag before constructin the grid.
	 * @param array $summary - set which columns should be sumarized in order to be displayed to the grid
	 * By default this parameter uses SQL SUM function: array("colmodelname"=>"sqlname");
	 * It can be set to use other one this way :
	 * array("colmodelname"=>array("sqlname"=>"AVG"));
	 * By default the first field correspond to the name of colModel the second to
	 * the database name
	 * @param array $params parameters passed to the query
	 * @param boolean $createtbl if set to true the table element is created automatically
	 * from this method. Default is false
	 * @param boolean $createpg if set to true the pager element is created automatically
	 * from this script. Default false.
	 * @param boolean $echo if set to false the function return the string representing
	 * the grid
	 * @return mixed.
	 */
	public function renderGrid($tblelement='', $pager='', $script=true, array $summary=null, array $params=null, $createtbl=false, $createpg=false, $echo=true)
	{
		$oper = $this->GridParams["oper"];
		$goper = $this->oper ? $this->oper : 'nooper';
		if($goper == $this->GridParams["autocomplete"]) {
			return false;
		} else if($goper == $this->GridParams["excel"]) {
			if(!$this->export) return false;
			$this->exportToExcel($summary, $params, $this->colModel, true, $this->exportfile);
		} else if($goper == "pdf") {
			if(!$this->export) return false;
			$this->exportToPdf($summary, $params, $this->colModel, $this->pdffile);
		} else if($goper == "csv") {
			if(!$this->export) return false;
			$this->exportToCsv($summary, $params, $this->colModel, true, $this->csvfile, $this->csvsep, $this->csvsepreplace);
		} else if(in_array($goper, array_values($this->GridParams)) ) {
			if( $this->inlineNav ) { $this->getLastInsert = true; }
			return $this->editGrid( $summary, $params, $goper, $echo);
		} else {
			if(!isset ($this->gridOptions["datatype"]) ) $this->gridOptions["datatype"] = $this->dataType;
			// hack for editable=true as default
			$ed = true;
			if(isset ($this->gridOptions['cmTemplate'])) {
				$edt = $this->gridOptions['cmTemplate'];
				$ed = isset($edt['editable']) ? $edt['editable'] : true;
			}
			foreach($this->colModel as $k=>$cm) {
				if(!isset($this->colModel[$k]['editable'])) {
					$this->colModel[$k]['editable'] = $ed;
				}
			}
			$this->gridOptions['colModel'] = $this->colModel;
			if(isset ($this->gridOptions['postData'])) $this->gridOptions['postData'] = jqGridUtils::array_extend($this->gridOptions['postData'], array($oper=>$this->GridParams["query"]));
			else $this->setGridOptions(array("postData"=>array($oper=>$this->GridParams["query"])));
			if(isset($this->primaryKey))  {
				$this->GridParams["id"] = $this->primaryKey;
			}
			$this->setGridOptions(array("prmNames"=>$this->GridParams));
			$s = '';
			if($createtbl) {
				$tmptbl = $tblelement;
				if(strpos($tblelement,"#") === false) {
					$tblelement = "#".$tblelement;
				} else {
					$tmptbl = substr($tblelement,1);
				}
				$s .= "<table id='".$tmptbl."'></table>";
			}
			if(strlen($pager)>0) {
				$tmppg = $pager;
				if(strpos($pager,"#") === false) {
					$pager = "#".$pager;
				} else {
					$tmppg = substr($pager,1);
				}
				if ($createpg ) {
					$s .= "<div id='".$tmppg."'></div>";
				}
			}
			// set the Error handler for data
			if(!isset($this->gridOptions['loadError']))  {
				$err = "function(xhr,status, err){ try {jQuery.jgrid.info_dialog(jQuery.jgrid.errors.errcap,'<div class=\"ui-state-error\">'+ xhr.responseText +'</div>', jQuery.jgrid.edit.bClose,{buttonalign:'right'});} catch(e) { alert(xhr.responseText);} }";
				$this->setGridEvent('loadError',$err );
			}
			//if(!isset($this->editOptions['mtype']) && $this->showError) {
				//$this->setNavEvent('edit', 'afterSubmit', "function(res,pdata){ var result = res.responseText.split('#'); if(result[0]=='$this->successmsg') return [true,result[1],result[2]]; else return [false,result[1],'']; }");
			//}
			//if(!isset($this->addOptions['mtype']) && $this->showError) {
				//$this->setNavEvent('add', 'afterSubmit', "function(res,pdata){ var result = res.responseText.split('#'); if(result[0]=='$this->successmsg') return [true,result[1],result[2]]; else return [false,result[1],''];}");
			//}
			if(strlen($pager)>0) $this->setGridOptions(array("pager"=>$pager));
			//$this->editOptions['mtype'] = $this->mtype;
			//$this->addOptions['mtype'] = $this->mtype;
			//$this->delOptions['mtype'] = $this->mtype;
			if($this->sharedEditOptions==true) {
				$this->gridOptions['editOptions'] = $this->editOptions;
			}
			if($this->sharedAddOptions==true) {
				$this->gridOptions['addOptions'] = $this->addOptions;
			}
			if($this->sharedDelOptions==true) {
				$this->gridOptions['delOptions'] = $this->delOptions;
			}
			if($script) {
				$s .= "<script type='text/javascript'>";
				$s .= "jQuery(document).ready(function($) {";
			}
			$s .= "jQuery('".$tblelement."').jqGrid(".jqGridUtils::encode($this->gridOptions).");";
			if($this->navigator && strlen($pager)>0) {
				$s .= "jQuery('".$tblelement."').jqGrid('navGrid','".$pager."',".jqGridUtils::encode($this->navOptions);
				$s .= ",".jqGridUtils::encode($this->editOptions);
				$s .= ",".jqGridUtils::encode($this->addOptions);
				$s .= ",".jqGridUtils::encode($this->delOptions);
				$s .= ",".jqGridUtils::encode($this->searchOptions);
				$s .= ",".jqGridUtils::encode($this->viewOptions).");";
				if($this->navOptions["excel"]==true)
				{
					$eurl = $this->getGridOption('url');
$exexcel = <<<EXCELE
onClickButton : function(e)
{
	try {
		jQuery("$tblelement").jqGrid('excelExport',{tag:'excel', url:'$eurl'});
	} catch (e) {
		window.location= '$eurl?oper=excel';
	}
}
EXCELE;
					$s .= "jQuery('".$tblelement."').jqGrid('navButtonAdd','".$pager."',{id:'".$tmppg."_excel', caption:'".$this->expoptions['excel']['caption']."',title:'".$this->expoptions['excel']['title']."',".$exexcel.",buttonicon:'".$this->expoptions['excel']['buttonicon']."'});";
				}

				if($this->navOptions["pdf"]==true)
				{
					$eurl = $this->getGridOption('url');
$expdf = <<<PDFE
onClickButton : function(e)
{
	try {
		jQuery("$tblelement").jqGrid('excelExport',{tag:'pdf', url:'$eurl'});
	} catch (e) {
		window.location= '$eurl?oper=pdf';
	}
}
PDFE;
					$s .= "jQuery('".$tblelement."').jqGrid('navButtonAdd','".$pager."',{id:'".$tmppg."_pdf',caption:'".$this->expoptions['pdf']['caption']."',title:'".$this->expoptions['pdf']['title']."',".$expdf.", buttonicon:'".$this->expoptions['pdf']['buttonicon']."'});";
				}

				if($this->navOptions["csv"]==true)
				{
					$eurl = $this->getGridOption('url');
$excsv = <<<CSVE
onClickButton : function(e)
{
	try {
		jQuery("$tblelement").jqGrid('excelExport',{tag:'csv', url:'$eurl'});
	} catch (e) {
		window.location= '$eurl?oper=csv';
	}
}
CSVE;
					$s .= "jQuery('".$tblelement."').jqGrid('navButtonAdd','".$pager."',{id:'".$tmppg."_csv',caption:'".$this->expoptions['csv']['caption']."',title:'".$this->expoptions['csv']['title']."',".$excsv.",buttonicon:'".$this->expoptions['csv']['buttonicon']."'});";
				}

				if($this->navOptions["columns"]==true)
				{
					$clopt = jqGridUtils::encode($this->expoptions['columns']['options']);
$excolumns = <<<COLUMNS
onClickButton : function(e)
{
	jQuery("$tblelement").jqGrid('columnChooser',$clopt);
}
COLUMNS;
					$s .= "jQuery('".$tblelement."').jqGrid('navButtonAdd','".$pager."',{id:'".$tmppg."_col',caption:'".$this->expoptions['columns']['caption']."',title:'".$this->expoptions['columns']['title']."',".$excolumns.",buttonicon:'".$this->expoptions['columns']['buttonicon']."'});";
				}
			}
			// inline navigator
			if($this->inlineNav && strlen($pager)>0) {
$aftersave = <<<AFTERS
function (id, res)
{
	res = res.responseText.split("#");
	try {
		$(this).jqGrid('setCell', id, res[0], res[1]);
		$("#"+id, "#"+this.p.id).removeClass("jqgrid-new-row").attr("id",res[1] );
		$(this)[0].p.selrow = res[1];
	} catch (asr) {}
}
AFTERS;
				$this->inlineNavOpt['addParams'] = jqGridUtils::array_extend($this->inlineNavOpt['addParams'], array("aftersavefunc"=>"js:".$aftersave));
				$this->inlineNavOpt['editParams'] = jqGridUtils::array_extend($this->inlineNavOpt['editParams'], array("aftersavefunc"=>"js:".$aftersave));
				$s .= "jQuery('".$tblelement."').jqGrid('inlineNav','".$pager."',".jqGridUtils::encode($this->inlineNavOpt).");\n";				
			}
			// toolbar filter
			if($this->toolbarfilter){
				$s .= "jQuery('".$tblelement."').jqGrid('filterToolbar',".jqGridUtils::encode($this->filterOptions).");\n";
			}
			// grid methods
			$gM = count($this->gridMethods);
			if($gM>0) {
				for($i=0; $i<$gM; $i++) {
					$s .= $this->gridMethods[$i]."\n";
				}
			}
			//at end the custom code
			if(strlen($this->customCode)>0)
				$s .= jqGridUtils::encode($this->customCode);
			if($script) $s .= " });</script>";
			if($echo) {
				echo $s;
			}
			return $echo ? "" : $s;
		}
	}
}
?>
