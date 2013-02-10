<?php
class SimpleXMLExtended extends SimpleXMLElement
{
	public function addCData($cdata_text)
	{
	$node= dom_import_simplexml($this);
	$no = $node->ownerDocument;
	$node->appendChild($no->createCDATASection($cdata_text));
	}
}
/**
 * @author  Tony Tomov, (tony@trirand.com)
 * @copyright TriRand Ltd
 * @package jqGrid
 * @abstract Helper functions for the jqGrid package
 */
class jqGridUtils
{
	public static $days3 = array("Mon","Tue","Wed","Thu","Fri","Sat","Sun");
	public static $days = array("Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday");
	public static $month3 = array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec");
	public static $month = array("January","February","March","April","May","June","July","August","September","October","November","December");
	public static $types=array(
		"d"=>"([0-9]{2})",
		"D"=>"([a-zA-z]{3})",
		"j"=>"([0-9]{1,2})",
		"l"=>"([A-Z][a-z]{4,7})",
		"N"=>"([1-7])",
		"S"=>"(st|nd|rd|th)",
		"w"=>"([0-6])",
		"z"=>"([0-9]{3})",
		"W"=>"([0-9]{2})",
		"F"=>"([A-Z][a-z]{2,8})",
		"m"=>"([0-9]{2})",
		"M"=>"([A-Za-z]{3})",
		"n"=>"([0-9]{1,2})",
		"t"=>"(28|29|30|31)",
		"L"=>"(1|0)",
		"o"=>"([0-9]{4})",
		"Y"=>"([0-9]{4})",
		"y"=>"([0-9]{2})",
		"a"=>"(am|pm)",
		"A"=>"(AM|PM)",
		"B"=>"([0-9]{3})",
		"g"=>"([1-12])",
		"G"=>"([0-23])",
		"h"=>"([0-9]{2})",
		"H"=>"([0-9]{2})",
		"i"=>"([0-9]{2})",
		"s"=>"([0-9]{2})",
		"u"=>"([0-9]{1,5})",
		"e"=>"([A-Za-z0-9_]{3,})",
		"I"=>"(1|0)",
		"O"=>"(+[0-9]{4})",
		"P"=>"(+[0-9]{2}:[0-9]{2})",
		"T"=>"([A-Z]{1,4})",
		"Z"=>"(-?[0-9]{1,5})",
		"c"=>"(\d\d\d\d)(?:-?(\d\d)(?:-?(\d\d)(?:[T](\d\d)(?::?(\d\d)(?::?(\d\d)(?:\.(\d+))?)?)?(?:Z|(?:([-+])(\d\d)(?::?(\d\d))?)?)?)?)?)?",
		"r"=>"([a-zA-Z]{2,}),\040(\d{1,})\040([a-zA-Z]{2,})\040([0-9]{4})\040([0-9]{2}):([0-9]{2}):([0-9]{2})\040([+-][0-9]{4})",
		"U"=>"(\d+)"
	);

	#	0-7		Day
	#	8		Week
	#	9-13	Month
	#	14-17	Year
	#	18-27	Time
	#	28-33	Timezone
	public static $patrVal = "";

	/**
	 * Function for converting to an XML document.
	 * Pass in a multi dimensional array or object and this recrusively loops through and builds up an XML document.
	 *
	 * @param array $data
	 * @param string $rootNodeName - what you want the root node to be - defaultsto data.
	 * @param SimpleXMLElement $xml - should only be used recursively
	 * @return string XML
	 */
	public static function toXml($data, $rootNodeName = 'root', $xml=null, $encoding='utf-8', $cdata=false)
	{
		// turn off compatibility mode as simple xml throws a wobbly if you don't.
		if (ini_get('zend.ze1_compatibility_mode') == 1)
		{
			ini_set ('zend.ze1_compatibility_mode', 0);
		}

		if ($xml == null)
		{
			$xml = new SimpleXMLExtended("<?xml version='1.0' encoding='".$encoding."'?><$rootNodeName />");
		}

		// loop through the data passed in.
		foreach($data as $key => $value)
		{
			// no numeric keys in our xml please!
			if (is_numeric($key))
			{
				// make string key...
                //return;
				$key = "row";
			}
			// if there is another array or object found recrusively call this function
			if (is_array($value) || is_object($value))
			{
				$node = $xml->addChild($key);
				// recrusive call.
				self::toXml($value, $rootNodeName, $node, $encoding, $cdata);
			}
			else
			{
				// add single node.
				$value = is_bool($value) ? ($value ? 'true' : 'false') : $value;
				$value = htmlspecialchars($value);
				if($cdata===true) {
					$node = $xml->addChild($key);
					$node->addCData($value);
				} else {
					$xml->addChild($key,$value);
				}
			}

		}
		// pass back as string. or simple xml object if you want!
		return $xml->asXML();
	}
	/**
	 * Quotes a javascript string.
	 * After processing, the string can be safely enclosed within a pair of
	 * quotation marks and serve as a javascript string.
	 * @param string string to be quoted
	 * @param boolean whether this string is used as a URL
	 * @return string the quoted string
	 */
	public static function quote($js,$forUrl=false)
	{
		if($forUrl)
			return strtr($js,array('%'=>'%25',"\t"=>'\t',"\n"=>'\n',"\r"=>'\r','"'=>'\"','\''=>'\\\'','\\'=>'\\\\'));
		else
			return strtr($js,array("\t"=>'\t',"\n"=>'\n',"\r"=>'\r','"'=>'\"','\''=>'\\\'','\\'=>'\\\\',"'"=>'\''));
	}

	/**
	 * Encodes a PHP variable into javascript representation.
	 *
	 * Example:
	 * <pre>
	 * $options=array('key1'=>true,'key2'=>123,'key3'=>'value');
	 * echo jqGridUtils::encode($options);
	 * // The following javascript code would be generated:
	 * // {'key1':true,'key2':123,'key3':'value'}
	 * </pre>
	 *
	 * For highly complex data structures use {@link jsonEncode} and {@link jsonDecode}
	 * to serialize and unserialize.
	 *
	 * @param mixed PHP variable to be encoded
	 * @return string the encoded string
	 */
	public static function encode($value)
	{
		if(is_string($value))
		{
			if(strpos($value,'js:')===0)
				return substr($value,3);
			else
				return '"'.self::quote($value).'"';
		}
		else if($value===null)
			return "null";
		else if(is_bool($value))
			return $value?"true":"false";
		else if(is_integer($value))
			return "$value";
		else if(is_float($value))
		{
			if($value===-INF)
				return 'Number.NEGATIVE_INFINITY';
			else if($value===INF)
				return 'Number.POSITIVE_INFINITY';
			else
				return "$value";
		}
		else if(is_object($value))
			return self::encode(get_object_vars($value));
		else if(is_array($value))
		{
			$es=array();
			if(($n=count($value))>0 && array_keys($value)!==range(0,$n-1))
			{
				foreach($value as $k=>$v)
					$es[]='"'.self::quote($k).'":'.self::encode($v);
				return "{".implode(',',$es)."}";
			}
			else
			{
				foreach($value as $v)
					$es[]=self::encode($v);
				return "[".implode(',',$es)."]";
			}
		}
		else
			return "";
	}
	/**
	 *
	 * Decodes json string to PHP array. The function is used
	 * when the encoding is diffrent from utf-8
	 * @param string $json string to decode
	 * @return array
	 */
	public static function decode($json)
	{
		$comment = false;
		$out = '$x=';

		for ($i=0; $i<strlen($json); $i++)
		{
			if (!$comment)
			{
				if ($json[$i] == '{')        $out .= ' array(';
				else if ($json[$i] == '}')    $out .= ')';
				else if ($json[$i] == '[')        $out .= ' array(';
				else if ($json[$i] == ']')    $out .= ')';
				else if ($json[$i] == ':')    $out .= '=>';
				else                         $out .= $json[$i];
			}
			else $out .= $json[$i];
			if ($json[$i] == '"')    $comment = !$comment;
		}
		eval($out . ';');
		return $x;
	}
	/**
	 * Strip slashes from a varaible if PHP magic quotes are on
	 * @param mixed $value to be striped
	 * @return mixed
	 */
	public static function Strip($value)
	{
		if(get_magic_quotes_gpc() != 0)
		{
			if(is_array($value))
				// is associative array
				if ( 0 !== count(array_diff_key($value, array_keys(array_keys($value)))) )
				{
					foreach( $value as $k=>$v) {
						$tmp_val[$k] = stripslashes($v);
					}
					$value = $tmp_val;
				}
				else
					for($j = 0; $j < sizeof($value); $j++) {
						$value[$j] = stripslashes($value[$j]);
					}
			else
				$value = stripslashes($value);
		}
		return $value;
	}
	/**
	 * Internal function which generates regex pattern from date pattern
	 *
	 * @param string $dateformat
	 * @return string
	 */
	public static function generatePattern($dateformat){
		$k=0;
		$datearray = preg_split("//",$dateformat);
		$patternkey = array();
		self::$patrVal = "";
		for($i=0;$i<count($datearray);$i++){
			if(isset($datearray[$i-1]) && $datearray[$i-1]=="@"){ $patternkey[$i]=$datearray[$i];}
			elseif($datearray[$i]=="@"){$patternkey[$i]="";}
			elseif($datearray[$i]==" "){$patternkey[$i]="\040";}
			elseif(in_array($datearray[$i],array_keys(self::$types))){
				$patternkey[$i]=self::$types[$datearray[$i]];
				self::$patrVal[$k] = array_search($datearray[$i],array_keys(self::$types));
				$k++;
			}else{$patternkey[$i]=$datearray[$i];}
		}
		$patternkey = implode("",$patternkey);

		return "/".$patternkey."/";
	}
	/**
	 * Converts the string date to array by using the pattern generated by generatePattern() function
	 *
	 * @param string $dateformat
	 * @param string $date
	 * @param boolean $localize
	 * @return array
	 */
	public static function date_parse($dateformat,$date){
		$newdate="";
		$dateformat = str_replace(array("\\","\t","/"),array("@","@t","~"),$dateformat);
		$date = str_replace("/","~", $date);
		$pattern = self::generatePattern($dateformat);
		preg_match_all($pattern,$date,$newdate);
		$newdate = array_slice($newdate,1);
		if(self::$patrVal[0]==34){
			$resultvar = array("Year"=>$newdate[0],
			"Year"=>$newdate[0][0],
			"Month"=>$newdate[1][0],
			"Day"=>$newdate[2][0],
			"Hour"=>$newdate[3][0],
			"Minute"=>$newdate[4][0],
			"Second"=>$newdate[5][0],
			"Timezone"=>$newdate[6][0].$newdate[7][0].$newdate[8][0]);
		}elseif(self::$patrVal[0]==35){
			$resultvar = array("Year"=>$newdate[0],
			"Year"=>$newdate[3][0],
			"Month"=>(array_search($newdate[2][0],self::$month3)+1),
			"Day"=>$newdate[1][0],
			"Hour"=>$newdate[4][0],
			"Minute"=>$newdate[5][0],
			"Second"=>$newdate[6][0],
			"Timezone"=>$newdate[7][0]);
		}elseif(self::$patrVal[0]==36){
			$result = getdate(mktime($newdate));
			$resultvar = array(
			"Year"=>$result["year"],
			"Month"=>array_search($result["month"],self::$month)+1,
			"Day"=>$result["mday"],
			"Hour"=>$result["hours"],
			"Minute"=>$result["minutes"],
			"Second"=>$result["seconds"],
			"Timezone"=>date("O"));
		}else{
			$labels = array_keys(self::$types);
			for($i=0;$i<count($newdate);$i++) {
				if(isset($newdate[$i][0])) {
					$result[$labels[self::$patrVal[$i]]]=$newdate[$i][0];
				}
			}
			if( isset($result["F"])) $month = array_search($result["F"],self::$month)+1;
			elseif(isset($result["M"])) $month = array_search($result["M"],self::$month3)+1;
			elseif(isset($result["m"])) $month = $result["m"];
			elseif(isset($result["n"])) $month = $result["n"];
			else $month = 1;

			if(isset($result["d"])) $day = $result["d"];
			elseif(isset($result["j"])) $day = $result["j"];
			else $day = 1;

			if(isset($result["Y"])) $year = $result["Y"];
			elseif(isset($result["o"])) $year = $result["o"];
			elseif(isset($result["y"])) $year = ($result["y"]>substr(date("Y",time()),2,2))?(substr(date("Y",time()),0,2)-1).$result["y"]:substr(date("Y",time()),0,2).$result["y"];
			else $year = 1970;
			
			if(isset($result["l"])) $weekday = array_search($result["l"],self::$days)+1;
			elseif(isset($result["D"])) $weekday = array_search($result["D"],self::$days3)+1;
			elseif(isset($result["N"])) $weekday = $result["N"];
			elseif(isset($result["w"])) $weekday = $result["w"];
			else $weekday = date("w",mktime(0,0,0,$month,$day,$year));

			if(isset($result["H"])) $hour = $result["H"];
			elseif (isset($result["G"])) $hour = $result["G"];
			elseif (isset($result["h"])) $hour = ($result["A"]=="PM"|$result["a"]=="pm")?($result["h"]+12):($result["h"]);
			elseif (isset($result["g"])) $hour = ($result["A"]=="PM"|$result["a"]=="pm")?($result["g"]+12):($result["g"]);
			else $hour = 0;

			if(isset($result["O"])) $timezone = $result["O"];
			elseif (isset($result["Z"]) ) $timezone = ($result["Z"]/3600);
			else $timezone = date("O");

			$minutes = isset($result["i"]) ? $result["i"] : 0;
			$seconds = isset($result["s"]) ? $result["s"] : 0;

			$resultvar = array(
			"Year"=>$year,
			"Month"=>$month,
			"Day"=>$day,
			"WeekDay"=>$weekday,
			"Hour"=>$hour,
			"Minute"=>$minutes,
			"Second"=>$seconds,
			"Timezone"=>$timezone);
		}
		return $resultvar;
	}
	/**
	 * Parses a $format and $date value and return the date formated via $newformat.
	 * If the $patternTo is not set return the timestamp.
	 * @param string $patternFrom the format of the date to be parsed
	 * @param string $date the value of the data.
	 * @param string $patternTo the new format of the $date
	 * @return mixed
	 */
	public static function parseDate($patternFrom,$date, $patternTo=''){
		$temp = self::date_parse($patternFrom,$date);
		if($patternTo)
			return date($patternTo,mktime($temp["Hour"],$temp["Minute"],$temp["Second"],$temp["Month"],$temp["Day"],$temp["Year"]));
		else
			return mktime($temp["Hour"],$temp["Minute"],$temp["Second"],$temp["Month"],$temp["Day"],$temp["Year"]);
	}
	/**
	 * Return the value from POST or from GET
	 * @param string $parameter_name
	 * @param string $default_value
	 * @return mixed
	 */
	public static function GetParam($parameter_name, $default_value = "")
	{
		$parameter_value = "";
		if(isset($_POST[$parameter_name]))
			$parameter_value = self::Strip($_POST[$parameter_name]);
		else if(isset($_GET[$parameter_name]))
		    $parameter_value = self::Strip($_GET[$parameter_name]);
		else
			$parameter_value = $default_value;
		return $parameter_value;
	}
	/**
	 * "Extend" recursively array $a with array $b values (no deletion in $a, just added and updated values)
	 * @param array $a
	 * @param array $b
	 * @return array
	 */
	public static function array_extend($a, $b) {
		foreach($b as $k=>$v) {
			if( is_array($v) ) {
				if( !isset($a[$k]) ) {
					$a[$k] = $v;
				} else {
					$a[$k] = self::array_extend($a[$k], $v);
				}
			} else {
				$a[$k] = $v;
			}
		}
		return $a;
	}

	/**
	 * Convert the php date string to Java Script date string
	 * @param string $phpdate
	 */
	public static function phpTojsDate ($phpdate)
	{
/*
 * Java Script
d  - day of month (no leading zero)
dd - day of month (two digit)
o  - day of year (no leading zeros)
oo - day of year (three digit)
D  - day name short
DD - day name long

m  - month of year (no leading zero)
mm - month of year (two digit)
M  - month name short
MM - month name long

y  - year (two digit)
yy - year (four digit)
*/

/* PHP
j - Day of the month without leading zeros
d - Day of the month, 2 digits with leading zeros
z - The day of the year (starting from 0)
no item found
D - A textual representation of a day, three letters
l - A full textual representation of the day of the week
 *
 n - Numeric representation of a month, without leading zeros
 m - Numeric representation of a month, with leading zeros
 M - A short textual representation of a month, three letters
 F - A full textual representation of a month, such as January or March
 *
 y - A two digit representation of a year
 Y - A full numeric representation of a year, 4 digits
 */
		//$str = $phpdate;
		$count = 0;
		$phpdate = str_replace('j', 'd', $phpdate, $count);
		$phpdate = $count == 0 ? str_replace('d', 'dd', $phpdate) : $phpdate;
		$phpdate = str_replace('z', 'o', $phpdate);
		$phpdate = str_replace('l', 'DD', $phpdate);
		$count = 0;
		$phpdate = str_replace('n', 'm', $phpdate, $count);
		$phpdate = $count == 0 ? str_replace('m', 'mm', $phpdate) :$phpdate;
		$phpdate = str_replace('F', 'MM', $phpdate);

		$phpdate = str_replace('Y', 'yy', $phpdate);
		return $phpdate;
	}
	/**
	* version of sprintf for cases where named arguments are desired (php syntax)
	*
	* with sprintf: sprintf('second: %2$s ; first: %1$s', '1st', '2nd');
	*
	* with sprintfn: sprintfn('second: %second$s ; first: %first$s', array(
	*  'first' => '1st',
	*  'second'=> '2nd'
	* ));
	*
	* @param string $format sprintf format string, with any number of named arguments
	* @param array $args array of [ 'arg_name' => 'arg value', ... ] replacements to be made
	* @return string|false result of sprintf call, or bool false on error
	*/
	public static function sprintfn ($format, array $args = array()) {
		// map of argument names to their corresponding sprintf numeric argument value
		$arg_nums = array_slice(array_flip(array_keys(array(0 => 0) + $args)), 1);

		// find the next named argument. each search starts at the end of the previous replacement.
		for ($pos = 0; preg_match('/(?<=%)([a-zA-Z_]\w*)(?=\$)/', $format, $match, PREG_OFFSET_CAPTURE, $pos);) {
			$arg_pos = $match[0][1];
			$arg_len = strlen($match[0][0]);
			$arg_key = $match[1][0];

			// programmer did not supply a value for the named argument found in the format string
			if (! array_key_exists($arg_key, $arg_nums)) {
				user_error("sprintfn(): Missing argument '${arg_key}'", E_USER_WARNING);
				return false;
}

			// replace the named argument with the corresponding numeric one
			$format = substr_replace($format, $replace = $arg_nums[$arg_key], $arg_pos, $arg_len);
			$pos = $arg_pos + strlen($replace); // skip to end of replacement for next iteration
		}
		return vsprintf($format, array_values($args));
	}
}

/*
 * Handle sessions with a class
 *
 * Example of using
 * Use the static method getInstance to get the object.
 *
 * We get the instance
 * $data = jqSession::getInstance();
 *
 * Let's store datas in the session
 * $data->nickname = 'Someone';
 * $data->age = 18;
 *
 * Let's display datas
 * printf( '<p>My name is %s and I\'m %d years old.</p>' , $data->nickname , $data->age );
 *
 * Array
 * (
 *	[nickname] => Someone
 *  [age] => 18
 * )
 * printf( '<pre>%s</pre>' , print_r( $_SESSION , TRUE ));
 *
 * //TRUE
 * var_dump( isset( $data->nickname ));
 *
 * // We destroy the session
 * $data->destroy();
 *
 * // FALSE
 * var_dump( isset( $data->nickname ));
*/

class jqSession
{
	const SESSION_STARTED = TRUE;
	const SESSION_NOT_STARTED = FALSE;

	// The state of the session
	private $sessionState = self::SESSION_NOT_STARTED;

	// THE only instance of the class
	private static $instance;


	private function __construct() {}


	/**
	*    Returns THE instance of 'Session'.
	*    The session is automatically initialized if it wasn't.
	*
	*    @return    object
	**/

	public static function getInstance()
	{
		if ( !isset(self::$instance))
		{
			self::$instance = new self;
		}
		self::$instance->startSession();
		return self::$instance;
	}


	/**
	*    (Re)starts the session.
	*
	*    @return    bool    TRUE if the session has been initialized, else FALSE.
	**/

	public function startSession()
	{
		if ( $this->sessionState == self::SESSION_NOT_STARTED  && session_id() == "")
		{
			$this->sessionState = session_start();
		}
		return $this->sessionState;
	}


	/**
	*    Stores datas in the session.
	*    Example: $instance->foo = 'bar';
	*
	*    @param    name    Name of the datas.
	*    @param    value    Your datas.
	*    @return    void
	**/

	public function __set( $name , $value )
	{
		$_SESSION[$name] = $value;
	}


	/**
	*    Gets datas from the session.
	*    Example: echo $instance->foo;
	*
	*    @param    name    Name of the datas to get.
	*    @return    mixed    Datas stored in session.
	**/

	public function __get( $name )
	{
		if ( isset($_SESSION[$name]))
		{
			return $_SESSION[$name];
		}
	}


	public function __isset( $name )
	{
		return isset($_SESSION[$name]);
	}


	public function __unset( $name )
	{
		unset( $_SESSION[$name] );
	}


	/**
	*    Destroys the current session.
	*
	*    @return    bool    TRUE is session has been deleted, else FALSE.
	**/

	public function destroy()
	{
		if ( $this->sessionState == self::SESSION_STARTED )
		{
			$this->sessionState = !session_destroy();
			unset( $_SESSION );

			return !$this->sessionState;
		}
		return FALSE;
	}
}
//------------------------------------------------------------------------
/**
 * Simple template engine class (use [@tag] tags in your templates).
 *
 * @link http://www.broculos.net/ Broculos.net Programming Tutorials
 * @author Nuno Freitas <nunofreitas@gmail.com>
 * @version 1.0
*/

class jqTemplate {
	/**
	 * The filename of the template to load.
	 *
	 * @access protected
	 * @var string
	*/
	protected $file;
	/**
	 * An array of values for replacing each tag on the template (the key for each value is its corresponding tag).
	 *
	 * @access protected
	 * @var array
	*/
	protected $values = array();

	public $sanitize = true;

	/**
	 * Creates a new Template object and sets its associated file.
	 *
	 * @param string $file the filename of the template to load
	*/
	public function __construct($file) {
		$this->file = $file;
	}
	/**
	 * Sets a value for replacing a specific tag.
	 *
	 * @param string $key the name of the tag to replace
	 * @param string $value the value to replace
	*/
	public function set($key, $value) {
		$this->values[$key] = $value;
	}

	/**
	 * Outputs the content of the template, replacing the keys for its respective values.
	 *
	 * @return string
	*/
	public function output($str_template='') {
	/**
	* Tries to verify if the file exists.
	* If it doesn't return with an error message.
	* Anything else loads the file contents and loops through the array replacing every key for its value.
	*/
		if ($str_template && strlen($str_template) > 0) {
			$output = $str_template;
		} else {
			if (!file_exists($this->file)) {
				return "Error loading template file ($this->file).<br />";
			}
			$output = file_get_contents($this->file);
		}
		foreach ($this->values as $key => $value) {
			$tagToReplace = "[@$key]";
			$output = str_replace($tagToReplace, $value, $output);
		}
		if ($this->sanitize)
			$output = preg_replace("/\[@(.+?)\]/", "", $output);

		return $output;
	}
	/**
	* Merges the content from an array of templates and separates it with $separator.
	*
	* @param array $templates an array of Template objects to merge
	* @param string $separator the string that is used between each Template object
	* @return string
	*/
	static public function merge($templates, $str_template = '', $separator = "\n") {
	/**
	* Loops through the array concatenating the outputs from each template, separating with $separator.
	* If a type different from Template is found we provide an error message.
	*/
		$output = "";
		foreach ($templates as $template) {
			$content = (get_class($template) !== "jqTemplate")
				? "Error, incorrect type - expected Template."
				: $template->output($str_template);
			$output .= $content . $separator;
		}
		return $output;
	}
}
?>
