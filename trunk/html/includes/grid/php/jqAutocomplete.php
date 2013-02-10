<?php
/**
 * @author  Tony Tomov, (tony@trirand.com)
 * @copyright TriRand Ltd
 * @version 4.3.2.0
 * @package jqAutocomplete
 *
 * @abstract
 * A PHP class to work with jQuery UI autocomplete.
 * The main purpose of this class is to create a autocompleter on input
 * element and provide the data from database to it.
 * Work only with jQuery UI 1.8.4+
 *
 */
class jqAutocomplete
{
	/**
	 * Info about the version
	 * @var string
	 */
	public $version = '4.3.2.0';

	/**
	 * Stores the default options for the autocomplete 
	 * @see setOption
	 * @var array
	 */
	protected $aoptions = array(
		"appendTo"=>"body",
		"disabled"=>false,
		"delay"=> 300,
		"minLength" => 1,
		"source"=> null
	);
	/**
	 * Stores the database connection
	 *  @var array
	 */
	protected $conn = null;
	/**
	 * Stores the database type needed in some db functions
	 *  @var string
	 */
	protected $dbtype='';

	/**
	 * Stores the source for the autocomplete. Can be a string which should
	 * point to file from where to obtain the data. It can be array too.
	 * @var mixed
	 */
	protected $source;

	/**
	 * Stores the Id of the element. Should be uniquie value in order to have
	 * multiple autocomplates defined in one file.
	 * @var string
	 */
	protected $element;

	/**
	 * Set the maximum rows send from the query to the autocomplete. If set to
	 * false all the elements are sended to autocomplete
	 * @see setLength 
	 * @var integer
	 */
	protected $itemLength = 10;

	/**
	 * Internal variable to determine whnever the script is run for first time.
	 * Prevent running all commands (except  data providing) when the autocomplete
	 * is crested
	 * @var boolean
	 */
	protected $runAll = true;

	/**
	 * Stores the term parameter send from autocomplete and then used in query
	 * if needed
	 * @var string
	 */
	protected $term = '';

	/**
	 * Defines the uniquie cheche array (used in java script) when a
	 * cache is enabled.
	 * @var string
	 */
	protected $cachearray  = "cache";

	/**
	 * When set to true enables client side caching of the results.
	 * This prevent multiple queries to the database. Please use with care.
	 * @var bollean
	 */
	public $cache = false;

	/**
	 * Defines the select command for obtaining the data from the database.
	 * Usually this type of command contain the SQL LIKE operator.
	 * If the command contain where clause we suppose that this command
	 * contain LIKE operator. The serched fields should comtain ? plece holder
	 * in order to search om the term element send from the autocompleter.
	 * Example: SELECT field1, field2 FROM table WHERE field1 LIKE ? OR field2 LIKE ?
	 * As seen you should place a placeholder on the serched fields.
	 * The class add the term element automatically to the query.
	 * For additional information see the documantation
	 * @see $searchType
	 * @var string
	 */
	public $SelectCommand = '';

	/**
	 * Set the search type for the LIKE SQL operator.
	 * The possible values are
	 * startWith - set LIKE value%;
	 * contain - set LIKE %value%;
	 * endWith - set LIKE %value;
	 *
	 * If the value does not match any of the above setting the SQL command is
	 * interpreted as it is without adding any additional strings.
	 * @var string
	 */
	public $searchType = "startWith";

	/**
	 * Determines if the data should be loaded at once from the SQL siurce.
	 * Also when set to true only one requrest is done and the data then is
	 * stored at client side. No more requests to the server.
	 * @var boolen
	 */
	public $loadAll = false;

	/**
	 * Determines the ajax type made to the server. Defaut is GET. Can be a POST
	 * @var string
	 */
	public $ajaxtype = "GET";

	/**
	 * Determines if the content in autocomplete should have a scroll. Use this
	 * option with the height option - see below. If this option is not set
	 * the content will have height equal of the responce rows.
	 * @var string
	 * @see $height
	 */
	public $scroll = false;

	/**
	 * Determines the height of the autocomplete elemnt. Work only if $scroll
	 * option is set to true.
	 * @var string
	 */
	public $height = "110px";
	/**
	 * Set the encoding
	 */
	public $encoding ="utf-8";
	/**
	 * the font size of the autocmplete
	 * default is 11px. Can be in any measure.
	 * @var string
	 */
	public $fontsize = '11px';
	public $strictcheck = true;
	/**
	 * Check if the autocompleter is already created so that we can do various
	 * things like data export.
	 * @return boolean
	 */
	public function isNotACQuery()
	{
		return $this->runAll;
	}

	/**
	 *
	 * Constructor
	 * @param resource $db the database connection passed to the constructor.
	 * In case of array set to 'local'
	 */
	function __construct($db=null)
	{
		if(class_exists('jqGridDB'))
			$interface = jqGridDB::getInterface();
		else
			$interface = 'local';
		$this->conn = $db;
		if($interface == 'pdo')
		{
			$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->dbtype = $this->conn->getAttribute(PDO::ATTR_DRIVER_NAME);
		} else {
			$this->dbtype = $interface;
		}
		$this->term = jqGridUtils::GetParam('term',-1);
		if($this->term !== -1) $this->runAll = false;
		$this->element = jqGridUtils::GetParam('acelem','');
	}

	/**
	*
	 * Return the the requested option of the autocomplete
	 * @param string - the requested option or event.
	 * @return mixed
	 */
	public function getOption($option) {
		if(array_key_exists($option, $this->aoptions))
			return $this->aoptions[$option];
		else
			return false;
	}

	
	/**
	 *
	 * Set the desired option for autocomplete. For a full list of the option refer
	 * the documentation
	 * @param string $option
	 * @param mixed $value
	 * @return boolean
	 */
	public function setOption($option, $value=null) {
		if(!$this->runAll) return false;
		if(isset ($option) ) {
			if(is_array($option)) {
				foreach($option as $key => $value) {
					$this->aoptions[$key] = $value;
				}
				return true;
			} else if( $value != null) {
				$this->aoptions[$option] = $value;
			}
			return true;
		}
		return false;
	}

	/**
	 * Set a JavaScript event for the autocomplete. For all the possible events
	 * refer the documentation
	 * @param string $event
	 * @param string $code
	 */
	public function setEvent($event, $code) {
		if($this->runAll) {
			$this->aoptions[$event] = "js:".$code;
		}
	}

	/**
	 * Set the source need for autocomlete to send a data. Can be a string or
	 * array. If the option is string then this  is the url from where to obtain the data.
	 * @param mixed $source
	 * @return none
	 */
	public function setSource($source) {
		if(!$this->runAll) return false;
		$this->source = $source;
	}

	/**
	 * Internal method used to set the source
	 * @param string $element on which autocomplete is bound.
	 */
	private function _setSrc($element) {
		if(is_string($this->source)) {
		if($this->cache) {
			$this->cachearray .= rand(0,10000);
		}
$accache = <<< ACCACHE
function (request, response)
{
	request.acelem = '$element';
	request.oper = 'autocmpl';
	if ( request.term in $this->cachearray )
	{
		response( $this->cachearray[ request.term ] );
		return;
	}
	$.ajax({
		url: "$this->source",
		dataType: "json",
		data: request,
		type: "$this->ajaxtype",
		error: function(res, status) {
			alert(res.status+" : "+res.statusText+". Status: "+status);
		},
		success: function( data ) {
			if(data) {
				$this->cachearray[ request.term ] = data;
				response( data );
			}
		}
	});
}
ACCACHE;
$acnocache = <<< ACNOCACHE
function (request, response)
{
	request.acelem = '$element';
	request.oper = 'autocmpl';
	$.ajax({
		url: "$this->source",
		dataType: "json",
		data: request,
		type: "$this->ajaxtype",
		error: function(res, status) {
			alert(res.status+" : "+res.statusText+". Status: "+status);
		},
		success: function( data ) {
			response( data );
		}
	});
}
ACNOCACHE;
			if($this->cache) {
				$res = "js:".$accache;
			} else if($this->loadAll) {
				$res = $this->getACData();
			} else  {
				$res = "js:".$acnocache;
			}
			$this->setOption('source', $res);
		} else if(is_array($this->source)) {
			$this->setOption('source', $this->source);
		}
		//$this->setOption('select', "js:function(e,u){return false;}");
	}

	/**
	 * Set the limit of the requested data in case of SQL command
	 * @param mixed $num - if set as number determines the number of the requestd
	 * itemd from the query. If set to false loads all the data from the query.
	 */
	public function setLength($num) {
		if(is_int($num)&& $num > 0) {
			$this->itemLength = $num;
		} else if(is_bool($num)) {
			$this->itemLength = -1;
			$this->loadAll = true;
		}
	}

	/**
	 * Return the result for the autocomplete as PHP object. Determines automatically
	 * the placeholders (?) used into the SQL command
	 * @return object
	 */
	public function queryAutocomplete()
	{
		return $this->getACData();
	}

	/**
	 * This method is internally used to get the data.
	 * @return array
	 */
	private function getACData()
	{
		$result = array();
		if(strlen($this->SelectCommand) > 0 ) {
			$prmlen = substr_count($this->SelectCommand,"?");
			if($prmlen > 0 ) {
				$params = array();
				if( strtolower($this->encoding) != 'utf-8' ) {
					$this->term = iconv("utf-8", $this->encoding."//TRANSLIT", $this->term);
				}

				for($i=1;$i<=$prmlen;$i++) {
					switch ($this->searchType) {
						case 'startWith':
							array_push($params, $this->term."%");
							break;
						case 'contain':
							array_push($params, "%".$this->term."%");
							break;
						case 'endWith':
							array_push($params, "%".$this->term);
							break;
						default :
							array_push($params, $this->term);
							break;
					}
				}
			} else {
				$params = null;
			}
			if($this->itemLength > 0 && !$this->loadAll) {
				$sqlCmd = jqGridDB::limit($this->SelectCommand, $this->dbtype, $this->itemLength, 0 );
			} else {
				$sqlCmd = $this->SelectCommand;
			}
			$sql1 = jqGridDB::prepare($this->conn,$sqlCmd, $params, true);
			$ret = jqGridDB::execute($sql1, $params);
			$ncols = jqGridDB::columnCount($sql1);
			// Mysqli hack
			if($this->dbtype == 'mysqli') {
				$fld = $sql1->field_count;
				//start the count from 1. First value has to be a reference to the stmt. because bind_param requires the link to $stmt as the first param.
				$count = 1;
				$fieldnames[0] = &$sql1;
				for ($i=0;$i<$ncols;$i++) {
					$fieldnames[$i+1] = &$res_arr[$i]; //load the fieldnames into an array.
				}
				call_user_func_array('mysqli_stmt_bind_result', $fieldnames);
			}
			while($row=jqGridDB::fetch_num($sql1)) {
				if($this->dbtype == 'mysqli') $row = $res_arr;
				if($ncols == 1) {
					array_push($result, array("value"=>$row[0], "label"=>$row[0]));
				} else if($ncols == 2) {
					array_push($result, array("value"=>$row[0], "label"=>$row[1]));
				} else if($ncols >= 3) {
					array_push($result, array("value"=>$row[0], "label"=>$row[1],"id"=>$row[2]));
				}
			}
			jqGridDB::closeCursor($sql1);
		}
		return $result;
	}
	/**
	 * Main method which do everthing for the autocomplete. Should be called
	 * after all settings are done. Note that in one file we can have more than
	 * one autocomplete definitions.
	 * Construct the autocomplete and perform Query operations.
	 * @param string $element The DOM element on which audocomplete should be
	 * applied
	 * @param <type> $target - if set the value selection from autocomplete
	 * will be set to this element
	 * @param boolean $script - if set to false the script tag:
	 * <script type='text/javascript'> will not be included.
	 * @param boolean $echo if set to false the result is not echoed but returned
	 * @param boolean $runme - internal variable used into the jqGrid class
	 * @return string 
	 */
	public function renderAutocomplete($element, $target=false, $script=true, $echo = true, $runme = true) {
		if($this->runAll && $runme) {
			$this->_setSrc($element);
			$s = "";
			if($script) {
				$s .= "<script type='text/javascript'>";
				$s .= "jQuery(document).ready(function() {";
			}
			if($this->cache) {
				$s .= "var $this->cachearray = {};";
			}
			if($target) {
$trg = <<<TARGET
function (event, ui)
{
	// change function to set target value
	var ival;
	if(ui.item) {
		ival = ui.item.id || ui.item.value;
	}
	if(ival) {
		jQuery("$target").val(ival);
	} else {
		jQuery("$target").val("");
		if("$this->strictcheck" == "true"){
		this.value = "";
	}
}
}
TARGET;
				$this->setOption('change', "js:".$trg);
			}
			$s .= "if(jQuery.ui) { if(jQuery.ui.autocomplete){";
			$s .= "jQuery('".$element."').autocomplete(".jqGridUtils::encode($this->aoptions).");";
			$s .= "jQuery('".$element."').autocomplete('widget').css('font-size','".$this->fontsize."');";
			if($this->scroll) {
				$s .= "jQuery('".$element."').autocomplete('widget').css({'height':'$this->height','overflow-y':'auto'});";
			}
			$s .= "} }";
			if($script) $s .= " });</script>";
			if($echo) {
				echo $s;
			}  else {
				return $s;
			}
		} else {
			if(trim($this->element) === trim($element) ) {
				header("Content-type: text/x-json;charset=".$this->encoding);
				if(function_exists('json_encode') && strtolower($this->encoding) == 'utf-8') {
					echo json_encode($this->getACData());
				} else {
					echo jqGridUtils::encode($this->getACData());
				}
			}
		}
	}
}
?>
