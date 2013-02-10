<?php
// Copyright (C) 2010 Clayton Dukes, cdukes@logzilla.pro

error_reporting(E_ALL & ~E_NOTICE);
# ini_set("display_errors", 1);

$basePath = dirname( __FILE__ );
require_once ($basePath ."/../config/config.php");
require_once ($basePath ."/modules/authentication.php");

// ------------------------------
// Grab all settings from the settings table in the database
// ------------------------------
getsettings();

//------------------------------------------------------------------------
// This function returns the current microtime.
//------------------------------------------------------------------------
function get_microtime() {
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}


//------------------------------------------------------------------------
// Function used to retrieve input values and if neccessary add slashes.
//------------------------------------------------------------------------
function get_input($varName, $check_session=true) {
    $value="";
    if(isset($_COOKIE[$varName])) {
        $value = $_COOKIE[$varName];
    } elseif(isset($_GET[$varName])) {
        $value = $_GET[$varName];
    } elseif(isset($_POST[$varName])) {
        $value = $_POST[$varName];
        /** 
         * BPK: we can't always use this, else checkboxes never get unset, 
         * rather let js reload the form at the end of index.php
         */
    } elseif($check_session && isset($_SESSION[$varName])) {
        $value = $_SESSION[$varName];
    } 
    if($value && !get_magic_quotes_gpc()) {
        if(!is_array($value)) {
            $value = addslashes($value);
        }
        else {
            foreach($value as $key => $arrValue) {
                $value[$key] = addslashes($arrValue);
            }
        }
    }
    return $value;
}


//------------------------------------------------------------------------
// Function used to validate user supplied variables.
//------------------------------------------------------------------------
function validate_input($value, $regExpName) {
    global $regExpArray;

    if(!$regExpArray[$regExpName]) {
        return FALSE;
    }

    if(is_array($value)) {
        foreach($value as $arrval) {
            if(!preg_match("$regExpArray[$regExpName]", $arrval)) {
                return FALSE;
            }
        }
        return TRUE;
    }
    elseif(preg_match("$regExpArray[$regExpName]", $value)) {
        return TRUE;
    }
    else {
        return FALSE;
    }
}


//========================================================================
// BEGIN DATABASE FUNCTIONS
//========================================================================
//------------------------------------------------------------------------
// This function connects to the MySQL server and selects the database
// specified in the DBNAME parameter. If an error occurs then return
// FALSE.
//------------------------------------------------------------------------
function db_connect_syslog($dbUser, $dbPassword, $connType = 'P') {
    $server_string = DBHOST.":".DBPORT;
    $link = "";
    /* removed pconnect so that LZ uses standard connections that will close more gracefully
       if(function_exists('mysql_pconnect') && $connType == 'P') {
       $link = @mysql_pconnect($server_string, $dbUser, $dbPassword);
       }
       elseif(function_exists('mysql_connect')) {
     */
    $link = @mysql_connect($server_string, $dbUser, $dbPassword);
    // }
    if(!$link) {
        return FALSE;
    }

    $result = mysql_select_db(DBNAME, $link);
    if(!$result) {
        return FALSE;
    }

    return $link;
}


//------------------------------------------------------------------------
// This functions performs the SQL query and returns a result resource. If
// an error occurs then execution is halted an the MySQL error is
// displayed.
// CDUKES: 12-10-09 - Added an optional filename parameter for query logging
//------------------------------------------------------------------------
function perform_query($query, $link, $filename='') {
    if($link) {
        $result = mysql_query($query, $link); 
        if (!$result) {
            print ("Error in \"function perform_query()\" <br>Mysql_error: " .mysql_error() ."<br>Query was: $query<br>"); 
            return ("Error in \"function perform_query()\" <br>Mysql_error: " .mysql_error()); 
        }
    }
    else {
        die("Error in perform_query function<br> No DB link for query: $query<br>Mysql_error: " .mysql_error());
    }
    list($usec, $sec) = explode(" ", microtime());
    $ms = ltrim(round($usec, 4), "0.");
    if (LOG_QUERIES == 'TRUE') {
        $myFile = MYSQL_QUERY_LOG;
        $fh = fopen($myFile, 'a') or die("can't open file $myFile");
        if ($filename) {
            fwrite($fh, date("h:i:s") .".$ms - $filename - " .$query."\n");
        } else {
            fwrite($fh, date("h:i:s") .".$ms - " .$query."\n");
        }
        fclose($fh);
    }
    return $result;
}

//------------------------------------------------------------------------
// This function allows logging debug messages to file
//------------------------------------------------------------------------
function logmsg ($msg) {
    list($usec, $sec) = explode(" ", microtime());
    $ms = ltrim(round($usec, 4), "0.");
    $myFile = LOG_PATH . "/logzilla.log";
    $fh = fopen($myFile, 'a') or die("can't open file $myFile");
    fwrite($fh, date("h:i:s") .".$ms: $msg \n");
    fclose($fh);
} 

//------------------------------------------------------------------------
// This functions returns a result row as an array.
// The type can be BOTH, ASSOC or NUM.
//------------------------------------------------------------------------
function fetch_array($result, $type = 'BOTH') {
    if($type == 'BOTH') {
        return mysql_fetch_array($result);
    }
    elseif($type == 'ASSOC') {
        return mysql_fetch_assoc($result);
    }
    elseif($type == 'NUM') {
        return mysql_fetch_row($result);
    }
    else {
        die('Wrong type for fetch_array()');
    }
}


//------------------------------------------------------------------------
// This functions sets the row offset for a result resource
//------------------------------------------------------------------------
function result_seek($result, $rowNumber) {
    mysql_data_seek($result, $rowNumber);
}


//------------------------------------------------------------------------
// This functions returns a result row as an array
//------------------------------------------------------------------------
function num_rows($result) {
    return mysql_num_rows($result);
}


//------------------------------------------------------------------------
// This function checks if a particular table exists.
//------------------------------------------------------------------------
function table_exists($tableName, $link) {
    $tables = get_tables($link);
    if(array_search($tableName, $tables) !== FALSE) {
        return TRUE;
    }
    else {
        return FALSE;
    }
}


//------------------------------------------------------------------------
// This function returns an array of the names of all tables in the
// database.
//------------------------------------------------------------------------
function get_tables($link) {
    $tableList = array();
    $query = "SHOW TABLES";
    $result = perform_query($query, $link, "common_funcs.php");
    while($row = fetch_array($result)) {
        array_push($tableList, $row[0]);
    }

    return $tableList;
}


//------------------------------------------------------------------------
// This function returns an array with the names of tables with log data.
//------------------------------------------------------------------------
function get_logtables($link) {
    // Create an array of the column names in the default table
    $query = "DESCRIBE ".$_SESSION["TBL_MAIN"];
    $result = perform_query($query, $link, "common_funcs.php");
    $defaultFieldArray = array();
    while($row = mysql_fetch_array($result)) {
        array_push($defaultFieldArray, $row['Field']);
    }

    // Create an array with the names of all the log tables
    $logTableArray = array();
    $allTablesArray = get_tables($link);

    foreach($allTablesArray as $value) {
        // Create an array of the column names in the current table
        $query = "DESCRIBE ".$value;
        $result = perform_query($query, $link, "common_funcs.php");
        // Get the names of columns in current table
        $fieldArray = array();
        while ($row = mysql_fetch_array($result)) {
            array_push($fieldArray, $row['Field']);
        }

        // If the current array is identical to the one from the
        // $_SESSION["TBL_MAIN"] then the name is added to the result
        // array.
        $diffArray = array_diff_assoc($defaultFieldArray, $fieldArray);
        if(!$diffArray) {
            array_push($logTableArray, $value);
        }
    }
    return $logTableArray;
}
//========================================================================
// END DATABASE FUNCTIONS
//========================================================================

//========================================================================
// BEGIN REDIRECT FUNCTION
//========================================================================

function g_redirect($url,$mode)
    /*  It redirects to a page specified by "$url".
     *  $mode can be:
     *    LOCATION:  Redirect via Header "Location".
     *    REFRESH:  Redirect via Header "Refresh".
     *    META:      Redirect via HTML META tag
     *    JS:        Redirect via JavaScript command
     */
{
    // CDUKES - 2/28/2011: Removed - pretty sure I'm only using JS redirects everywhere now
    /*
       if (strncmp('http:',$url,5) && strncmp('https:',$url,6)) {
       if (!isset($_SERVER["HTTPS"])) {
       $_SERVER["HTTPS"] = "undefine";
       }  
    /* CDUKES: 01-15-11 - Change to use server_name only as http_host 
    //  messes up proxies that with apache directive "UseCanonicalName On"
    $starturl = ($_SERVER["HTTPS"] == 'on' ? 'https' : 'http') . '://'.
    (empty($_SERVER['HTTP_HOST'])? $_SERVER['SERVER_NAME'] :
    $_SERVER['HTTP_HOST']);
     */
    /*
       $starturl = ($_SERVER["HTTPS"] == 'on' ? 'https' : 'http') . '://'.
       (empty($_SERVER['HTTP_HOST'])? $_SERVER['SERVER_NAME'] :
       $_SERVER['SERVER_NAME']);

       if ($url[0] != '/') $starturl .= dirname($_SERVER['PHP_SELF']).'/';

       $url = "$starturl$url";
       }
     */

    switch($mode) {

        case 'LOCATION': 

            if (headers_sent()) exit("Headers already sent. Can not redirect to $url");

            header("Location: $url");
            exit;

        case 'REFRESH': 

            if (headers_sent()) exit("Headers already sent. Can not redirect to $url");

            header("Refresh: 0; URL=\"$url\""); 
            exit;

        case 'META': 

            ?><meta http-equiv="refresh" content="0;url=<?php echo $url?>" /><?php
                exit;

        case 'JS': 

            ?><script type="text/javascript">
                window.location.href='<?php echo $url?>';
            </script><?php
                exit;

        default: /* -- Java Script */

            ?><script type="text/javascript">
                window.location.href='<?php echo $url?>';
            </script><?php
    }
    exit;
}

//========================================================================
// END REDIRECT FUNCTION
//========================================================================

/*  Adds commas to a string of numbers
 */
function commify ($str) { 
    $n = strlen($str); 
    if ($n <= 3) { 
        $return=$str;
    } 
    else { 
        $pre=substr($str,0,$n-3); 
        $post=substr($str,$n-3,3); 
        $pre=commify($pre); 
        $return="$pre,$post"; 
    }
    return($return); 
}

function get_week($date) {
    $ts = strtotime($date);
    $start = (date('w', $ts) == 0) ? $ts : strtotime('last sunday', $ts);
    return array(date('Y-m-d', $start),
            date('Y-m-d', strtotime('next saturday', $start)));
}

/* Usage:

   $week = get_weekdates($year,$month,$day);

   for($i = 1; $i<=7 ; $i++) {

   echo 'Year: ' . $week[$i]['year'] . '<br>';
   echo 'Month: ' . $week[$i]['month'] . '<br>';
   echo 'Day: ' . $week[$i]['day'] . '<br>';
   echo 'Longname: ' . $week[$i]['dayname'] . '<br>';
   echo 'Shortname: ' . $week[$i]['shortdayname'] . '<br>';
   echo 'Sqldate: ' . $week[$i]['sqldate'] . '<br>';
   echo '<br>';

   }
 */

function get_weekdates($year, $month, $day){
    setlocale(LC_ALL, "C");
    //echo "Year $year<br>";
    //echo "Month $month<br>";
    //echo "Day $day<br>";

    // make unix time
    $searchdate = mktime(0,0,0,$month,$day,$year);
    //echo "Searchdate: $searchdate<br>";

    // let's get the day of week                //    on solaris <8 the first day of week is sunday, not monday
    $day_of_week = strftime("%u", $searchdate);  
    //echo "Debug: $day_of_week <br><br>";

    $days_to_firstday = ($day_of_week - 1);        //    on solaris <8 this may not work
    //echo "Debug: $days_to_firstday <br>";

    $days_to_lastday = (7 - $day_of_week);        //    on solaris <8 this may not work
    //echo "Debug: $days_to_lastday <br>";

    $date_firstday = strtotime("-".$days_to_firstday." days", $searchdate);
    //echo "Debug: $date_firstday <br>";

    $date_lastday = strtotime("+".$days_to_lastday. " days", $searchdate);
    //echo "Debug: $date_lastday <br>";

    $d_result = "";                    // array to return

    // write an array of all dates of this week 
    for($i=0; $i<=6; $i++) {
        $y = $i + 1;
        $d_date = strtotime("+".$i." days", $date_firstday);

        // feel free to add more values to these hashes
        $result[$y]['year'] = strftime("%Y", $d_date);
        $result[$y]['month'] = strftime("%m", $d_date);
        $result[$y]['day'] = strftime("%d", $d_date);
        $result[$y]['dayname'] = strftime("%A", $d_date);
        $result[$y]['shortdayname'] = strftime("%a", $d_date);
        $result[$y]['sqldate'] = strftime("%Y-%m-%d", $d_date);
    }

    return $result;                    // return the array
}

// Use this instead of count(*), it's faster
// CDUKES: 12-10-09 - Added an optional WHERE parameter to limit found rows using a where clause
function get_total_rows ($table,$dbLink,$where='') {
    $temp = perform_query("SELECT SQL_CALC_FOUND_ROWS * FROM $table $where LIMIT 1", $dbLink, "common_funcs.php");
    $result = perform_query("SELECT FOUND_ROWS()", $dbLink, "common_funcs.php");
    $total = mysql_fetch_row($result);
    return $total[0];
}

// Added for better cookie handling
function getDomain() {
    if ( isset($_SERVER['HTTP_HOST']) ) {
        // Get domain
        $dom = $_SERVER['HTTP_HOST'];
        // Strip www from the domain
        if (strtolower(substr($dom, 0, 4)) == 'syslog.') { $dom = substr($dom, 4); }
        // Check if a port is used, and if it is, strip that info
        $uses_port = strpos($dom, ':');
        if ($uses_port) { $dom = substr($dom, 0, $uses_port); }
        // Add period to Domain (to work with or without www and on subdomains)
        $dom = '.' . $dom;
    } else {
        $dom = false;
    }
    return $dom;
}

function gsearch($s,$fields) {
# e.g.
# gsearch("term", msg)
# would search the msg column for the term


    $st=explode('"',$s);
    $i=0;
    while ($i<count($st)) {
        $st[$i]=str_replace("+","",$st[$i]);
        $st[$i]=preg_replace("@\\s+or\\s+@i"," | ",$st[$i]);
        $st[$i]=preg_replace("@\\s+and\\s+@i"," ",$st[$i]);
        $st[$i]=preg_replace("@\\s+not\\s+@i"," -",$st[$i]);
        $st[$i]=preg_replace("@(-)?($fields):@","@$2 $1",$st[$i]);
        $i=$i+2;
    }

    return implode('"',$st);
}

// -----------------------------
// CDUKES: 11-04-2009
// Added below to grab server settings from database
// -----------------------------
function getsettings() {
    if (!isset($_SESSION["TBL_MAIN"])) {
        $dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
        $sql = "SELECT name,value, type FROM settings";
        $result = perform_query($sql, $dbLink, "common_funcs.php");
        while($row = fetch_array($result)) {
            if ($row['type'] == "int") {
                $_SESSION[$row["name"]] = intval($row["value"]);
            } else {
                $_SESSION[$row["name"]] = $row["value"];
            }
        }
    }
}
    function humanReadable($val,$thousands=0){
        if($val>=1000)
            $val=humanReadable($val/1000,++$thousands);
        else{
            $unit=array('','K','M','B','T','P','E','Z','Y');
            $val=round($val,2).$unit[$thousands];
        }
        return $val;
    }
//------------------------------------------------------------------------------
// Function to include portlet content into a string
//------------------------------------------------------------------------------
function include_contents($filename) {
    if (is_file($filename)) {
        ob_start();
        include $filename;
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }
    return false;
}
//------------------------------------------------------------------------------
// This function is used to display context sensitive help
//------------------------------------------------------------------------------
function gethelp($name) {
    $dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
    $sql = "SELECT description FROM help where name='$name' LIMIT 1";
    $result = perform_query($sql, $dbLink, "common_funcs.php");
    while($row = fetch_array($result)) {
        return $row['description'];
    }
    mysql_close($dbLink);
}
//------------------------------------------------------------------------------
// These functions allow storage of messages in a better format.
// This should help speed up message retrieval.
//------------------------------------------------------------------------------
function msg_encode($str) {
    $hex = "";
    $i = 0;
    do {
        $hex .= sprintf("%02x", ord($str{$i}));
        $i++;
    } while ($i < strlen($str));
    return $hex;
}
function msg_decode($str) {
    $bin = "";
    $i = 0;
    do {
        $bin .= chr(hexdec($str{$i}.$str{($i + 1)}));
        $i += 2;
    } while ($i < strlen($str));
    return $bin;
}
//------------------------------------------------------------------------------
// Return the current page URL
//------------------------------------------------------------------------------
function myURL() {
    $pageURL = 'http';
    if (!isset($_SERVER["HTTPS"])) { 
        $_SERVER["HTTPS"] = "undefine";
    }
    if ($_SERVER["HTTPS"] === "on") {
        $pageURL .= "s";
    }
    $pageURL .= "://";
    if ($_SERVER["SERVER_PORT"] != "80") {
        $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
    } else {
        $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
    }
    return $pageURL;
}

// ------------------------------------------------------
// Used to generate random pie colors in the pie charts
// ------------------------------------------------------
function random_hex_color(){
    // Feel free to alter the RGB value, use (0, 255) to use all colors
    return sprintf("%02X%02X%02X", mt_rand(0, 115), mt_rand(0, 115), mt_rand(0, 255));
}

// ------------------------------------------------------
// Used to find out the group for the specified user
// ------------------------------------------------------
function getgroup($username) {
    $dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
    $sql = "SELECT * FROM groups WHERE userid=(SELECT id FROM users WHERE username='$username')";
    $result = perform_query($sql, $dbLink, "common_funcs.php");
    while($row = fetch_array($result)) {
        $group = $row['groupname'];
        return $group;
    }
}
// ------------------------------------------------------
// Used to find out if the user has access to a portlet
// ------------------------------------------------------
function has_portlet_access($username, $header) {
    $dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
    $sql = "SELECT group_access FROM ui_layout WHERE userid=(SELECT id FROM users WHERE username='$username') AND header='$header'";
    $result = perform_query($sql, $dbLink, "common_funcs.php");
    while($row = fetch_array($result)) {
        $group = $row['group_access'];
        if ($group == getgroup($username)) {
            return TRUE;
        }
    }
}

// ------------------------------------------------------
// Used to find out the rbac_key for the specified user
// ------------------------------------------------------
function getrbac($username) {
    $dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
    $sql = "SELECT rbac_key FROM users WHERE username='$username'";
    $result = perform_query($sql, $dbLink, "common_funcs.php");
    while($row = fetch_array($result)) {
        $rbac_key = $row['rbac_key'];
        return $rbac_key;
    }
}
// ------------------------------------------------------
// Used to find out which groups a user has access to
// Usage:
// $groups[] = rbac_getgroups("user", $username);
// or:
// $groups[] = rbac_getgroups("host", $devicename");
// ------------------------------------------------------
function rbac_getgroups($what, $value) {
    $dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
    switch ($what) {
        case "user":
            $sql = "SELECT rbac_key FROM users WHERE username='$value'";
            $result = perform_query($sql, $dbLink, "common_funcs.php");
            $row = fetch_array($result);
            $key = $row['rbac_key'];
            break;
        case "host":
            $sql = "SELECT rbac_key FROM hosts WHERE host='$value'";
            $result = perform_query($sql, $dbLink, "common_funcs.php");
            $row = fetch_array($result);
            $key = $row['rbac_key'];

            break;
    }

    $sql = "SELECT rbac_bit,rbac_text FROM rbac";
    $result = perform_query($sql, $dbLink, "common_funcs.php");
    while($row = fetch_array($result)) {
        $groupkey = $row['rbac_bit'];
        if ($key & (pow(2,$groupkey))) {
            $groups[] = $row['rbac_text'];
        }
    }
    return $groups;
}


// ----------------------------------------------------------------------
// Put rbac groups into sessions so we can call them from js
// ----------------------------------------------------------------------
function rbac2session() {
    $dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
    $_SESSION['rbac_groups'] = array();
    $sql = "SELECT rbac_bit,rbac_text FROM rbac";
    $result = perform_query($sql, $dbLink, "common_funcs.php");
    while($row = fetch_array($result)) {
        $groupname = $row['rbac_text'];
        $groupkey = $row['rbac_bit'];
        $_SESSION['rbac_groups'][$groupname] = intval($groupkey);
    }
}


// ----------------------------------------------------------------------
// Used to reset layouts
// ----------------------------------------------------------------------
function reset_layout($username) {
    $dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
    $sql = "DELETE FROM ui_layout WHERE userid=(SELECT id FROM users WHERE username='$username')";
    perform_query($sql, $dbLink, "common_funcs.php");
    if (getgroup($username) == 'admins') {
        $sql = "INSERT INTO ui_layout (userid, pagename, col, rowindex, header, content, group_access) SELECT (SELECT id FROM users WHERE username='$username'),pagename,col,rowindex,header,content, 'admins' FROM ui_layout WHERE userid=0";
    } else {
        $sql = "INSERT INTO ui_layout (userid, pagename, col, rowindex, header, content, group_access) SELECT (SELECT id FROM users WHERE username='$username'),pagename,col,rowindex,header,content, group_access FROM ui_layout WHERE userid=0";
    }
    perform_query($sql, $dbLink, "common_funcs.php");
}

// ----------------------------------------------------------------------
// Used to write user activity
// ----------------------------------------------------------------------
function action($task=NULL) {
    $user = $_SESSION['username'];
    $dateline = date(DATE_RFC822).' '.$user.': '.$task. PHP_EOL;
    $dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
    $sql = "SELECT value FROM settings where name='SYSTEM_LOG_DB'";
    $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
    $line = fetch_array($result);
    $result = $line[0];
    if ($result==1) { 
        $sql = "Insert into system_log (action) values ('".mysql_real_escape_string($user).": ".mysql_real_escape_string($task)."')";
        perform_query($sql, $dbLink, "common_funcs.php");
    }
    $sql = "SELECT value FROM settings where name='SYSTEM_LOG_FILE'";
    $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
    $line = fetch_array($result);
    $result = $line[0];
    if ($result==1) { 
        $sql = "SELECT value FROM settings where name='PATH_LOGS'";
        $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
        $line = fetch_array($result);
        $logpath = $line[0];
        $file = $logpath.'/audit.log';
        file_put_contents($file, $dateline, FILE_APPEND | LOCK_EX);
    }
    $sql = "SELECT value FROM settings where name='SYSTEM_LOG_SYSLOG'";
    $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
    $line = fetch_array($result);
    $result = $line[0];
    if ($result==1) {
        openlog("logzillla_system_log", LOG_PID | LOG_ODELAY, LOG_LOCAL3);
        syslog(LOG_INFO, $user.": ".$task);
        closelog();
    }    

}

// ----------------------------------------------------------------------
// Used to display friendly names for program crc's
// ----------------------------------------------------------------------
function crc2prg ($crc) {
    $dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
    $sql = "SELECT name FROM programs WHERE crc='$crc'";
    $result = perform_query($sql, $dbLink, "common_funcs.php");
    $row = fetch_array($result);
    return $row['name'];
}
function prg2crc ($prog) {
    $dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
    $sql = "SELECT crc FROM programs WHERE name='$prog'";
    $result = perform_query($sql, $dbLink, "common_funcs.php");
    $row = fetch_array($result);
    return $row['crc'];
}
// ----------------------------------------------------------------------
// Used to display friendly names for facility codes
// ----------------------------------------------------------------------
function int2fac ($i) {
    $dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
    $sql = "SELECT name FROM facilities WHERE code='$i'";
    $result = perform_query($sql, $dbLink, "common_funcs.php");
    $row = fetch_array($result);
    return $row['name'];
}
function fac2int ($fac) {
    $dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
    $sql = "SELECT code FROM facilities WHERE name='$fac'";
    $result = perform_query($sql, $dbLink, "common_funcs.php");
    $row = fetch_array($result);
    return $row['code'];
}
// ----------------------------------------------------------------------
// Used to display friendly names for severity codes
// ----------------------------------------------------------------------
function int2sev ($i) {
    $dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
    $sql = "SELECT name FROM severities WHERE code='$i'";
    $result = perform_query($sql, $dbLink, "common_funcs.php");
    $row = fetch_array($result);
    return $row['name'];
}
function sev2int ($sev) {
    $dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
    $sql = "SELECT code FROM severities WHERE name='$sev'";
    $result = perform_query($sql, $dbLink, "common_funcs.php");
    $row = fetch_array($result);
    return $row['code'];
}
// ----------------------------------------------------------------------
// Used to display friendly names for mnemonic crc's
// ----------------------------------------------------------------------
function crc2mne ($crc) {
    $dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
    $sql = "SELECT name FROM mne WHERE crc='$crc'";
    $result = perform_query($sql, $dbLink, "common_funcs.php");
    $row = fetch_array($result);
    return $row['name'];
}
function mne2crc ($mne) {
    $dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
    $sql = "SELECT crc FROM mne WHERE name='$mne'";
    $result = perform_query($sql, $dbLink, "common_funcs.php");
    $row = fetch_array($result);
    return $row['crc'];
}


// ----------------------------------------------------------------------
// Returns type of variable
// Usage: echo is_type(''); 
// ----------------------------------------------------------------------
function is_type($var) {
# Setup commonly used types (PHP.net warns against using gettype())
    switch ($var) {
        case is_string($var):
            $type='string';
            break;

        case is_array($var):
            $type='array';
            break;

        case is_null($var):
            $type='NULL';
            break;

        case is_bool($var):
            $type='boolean';
            break;

        case is_int($var):
            $type='integer';
            break;

        case is_float($var):
            // $type='float';
            $type='double';
            break;

        case is_object($var):
            $type='object';
            break;

        case is_resource($var):
            $type='resource';
            break;

        default:
            $type='unknown type';
            break;
    }
    return $type;
}

// ----------------------------------------------------------------------
// Returns relative time
// Usage: getRelativeTime('2011-01-01 12:00:00'); 
// ----------------------------------------------------------------------
function plural($num) {
    if ($num != 1)
        return "s";
}

function getRelativeTime($date) {
    $diff = time() - strtotime($date);
    if ($diff<60)
        return $diff . " second" . plural($diff) . " ago";
    $diff = round($diff/60);
    if ($diff<60)
        return $diff . " minute" . plural($diff) . " ago";
    $diff = round($diff/60);
    if ($diff<24)
        return $diff . " hour" . plural($diff) . " ago";
    $diff = round($diff/24);
    if ($diff<7)
        return $diff . " day" . plural($diff) . " ago";
    $diff = round($diff/7);
    if ($diff<4)
        return $diff . " week" . plural($diff) . " ago";
    return "on " . date("F j, Y", strtotime($date));
}

function array_recurse(&$array) {
    foreach ($array as &$data) {
        if (!is_array($data)) { // If it's not an array, return it
            return $data;
        }
        else { // If it IS an array, call this function on it
            array_recurse($data);
        }
    }
}

function search($json_o, $spx_max,$index="idx_logs idx_delta_logs",$spx_ip,$spx_port) {
    $basePath = dirname( __FILE__ );
    // require_once ($basePath . "/SPHINX.class.php");

    // Grab the settings from the database if not as parameter
    if ($spx_max == '') { $spx_max = $_SESSION[SPX_MAX_MATCHES] ; }
    if ($spx_ip == '') { $spx_ip = $_SESSION[SPX_SRV] ; }
    if ($spx_port == '') { $spx_port = $_SESSION[SPX_PORT] ; }

    // let us try to invoke sphinxql here instead...

    $scl = new mysqli(SPHINXHOST,'','','',SPHINXPORT);
    if (mysqli_connect_errno())
        return sprintf("Sphinxql error in connect: %d %s\n", mysqli_connect_errno(), mysqli_connect_error() . "<br>The Sphinx daemon may not be running.");

    //$cl = new SphinxClient ();
    //$cl->SetServer ( $spx_ip, $spx_port );

    // Decode json object into an array:
    $json_a = json_decode($json_o, true);
    //die(print_r($json_a));

    // Set All Defaults in case they aren't sent via the json object 
    $dupop = (!empty($json_a['dupop'])) ? $json_a['dupop'] : ">=";
    $dupcount = (!empty($json_a['dupcount'])) ? $json_a['dupcount'] : 0;
    $orderby = (!empty($json_a['orderby'])) ? $json_a['orderby'] : "id";
    $order = (!empty($json_a['order'])) ? $json_a['order'] : "ASC";
    $limit = (!empty($json_a['limit'])) ? $json_a['limit'] : $spx_max;
    $show_suppressed = (!empty($json_a['show_suppressed'])) ? $json_a['show_suppressed'] : "all";
    $q_type = (!empty($json_a['q_type'])) ? $json_a['q_type'] : "boolean";
    $search_op = (!empty($json_a['search_op'])) ? $json_a['search_op'] : "|";

    // loop through array to get the fields that the user wants to search on:
    // Note: Only certain values need to be looped here for modification before presenting to sphinx.
    // many of the items not looped below can be called directly using $json_a['name'];
    foreach ($json_a as $key=>$val) {
         // echo "Key = $key, Val = $val\n";
        switch($key) {
            // Strings
            case 'msg_mask':
                //                $val = real_escape_string( $cl->EscapeString ($val);
                if(is_array($val)) {
                    foreach ($val as $subkey=>$subval) {
                        // Fix for #239 - drilling down on message pie clicks
                        $msg_mask .= "\"$subval\"" . " $search_op ";
                    }
                    $val = $scl->real_escape_string($msg_mask);
		    // [[ticket:5]] Need to escape certain special sphinx characters, like @
		    // Solution: EscapeSphinxQL function defined later in this file.
		    $val = EscapeSphinxQL($val);
                    $msg_mask .= $val . " $search_op ";
                } else {
                    $val = $scl->real_escape_string($val);
		    $val = EscapeSphinxQL($val);
                    $msg_mask .= $val . " $search_op ";
                }
                break;
            case 'notes_mask':
                foreach ($val as $subkey=>$subval) {
                    // echo "SubKey = $subkey, SubVal = $subval\n";
                    //                    $subval = $cl->EscapeString ($subval);
                    $subval = mysql_real_escape_string ( $subval, $scl );
                    $notes_mask .= $subval . " $search_op ";
                }
                break;
                /*            case 'hosts':
                              foreach ($val as $subkey=>$subval) {
                // echo "SubKey = $subkey, SubVal = $subval\n";
                //                    $subval = $cl->EscapeString ($subval);
                $subval = $scl->real_escape_string($subval);
                $hosts .= $subval . " $search_op ";
                }
                break;
                 */            case 'mnemonics':
                foreach ($val as $subkey=>$subval) {
                    // echo "SubKey = $subkey, SubVal = $subval\n";
                    if (!preg_match ('/^\d+$/', $subval)) {
                        $mnes[] .= mne2crc($subval);
                    } else {
                        $mnes[] .= $subval;
                    }
                }
                break;
            case 'eids':
                foreach ($val as $subkey=>$subval) {
                    // echo "SubKey = $subkey, SubVal = $subval\n";
                    $eids[] .= $subval;
                }
                break;
            case 'programs':
                foreach ($val as $subkey=>$subval) {
                    // echo "SubKey = $subkey, SubVal = $subval\n";
                    if (!preg_match ('/^\d+$/', $subval)) {
                        $prgs[] .= prg2crc($subval);
                    } else {
                        $prgs[] .= $subval;
                    }                 
                }
                break;
        }
    }
    // die(print_r($json_a));
    $msg_mask = rtrim($msg_mask, " $search_op ");
    //    $hosts = rtrim($hosts, " $search_op ");
    $notes_mask = rtrim($notes_mask, " $search_op ");

    // Add DB column to strings
    if (!preg_match ('/any|all|phrase/', $q_type)) {
        if ($msg_mask)  {
            $msg_mask = "@MSG " . $msg_mask . " ";
        }
        /*        if ($hosts) {
                  $hosts = "@HOST " . $hosts . " ";
                  }
         */        if ($notes_mask) {
             $notes_mask = "@NOTES " . $notes_mask;
         }
    }


    $sphinxfilters = array();


    // SetFilter used on integer fields - takes an array
    if ($json_a['severities']) {
        //        $cl->SetFilter( 'severity', $json_a['severities'] );
        $sphinxfilters[] = "severity in (".implode($json_a[severities],',').")";
    }
    if ($json_a['facilities']) {
        //        $cl->SetFilter( 'facility', $json_a['facilities'] );
        $sphinxfilters[] = "facility in (".implode($json_a[facilities],',').")";
    }
    if (is_array($eids)) {
        //        $cl->SetFilter( 'eid', $eids );
        $sphinxfilters[] = "eid in (".implode($eids,',').")";
    }
    if ($json_a['mnemonics']) {
        //        $cl->SetFilter( 'mne', $mnes );
        $sphinxfilters[] = "mne in (".implode($mnes,',').")";
    }
    if ($json_a['programs']) {
        //        $cl->SetFilter( 'program', $prgs );
        $sphinxfilters[] = "program in (".implode($prgs,',').")";
    }

    // this is not supported by sphinxql proto - due to the old code.
    // for now is only 'extended2' is the active, and the rest could be
    // simulated (and actually do internally) by extended2.
    //    switch ($q_type) {
    //        case "any":
    //            $cl->SetMatchMode ( SPH_MATCH_ANY );
    //        break;
    //        case "all":
    //            $cl->SetMatchMode ( SPH_MATCH_ALL );
    //        break;
    //        case "phrase":
    //            $cl->SetMatchMode ( SPH_MATCH_PHRASE );
    //        break;
    //        case "boolean":
    //            $cl->SetMatchMode ( SPH_MATCH_BOOLEAN );
    //        break;
    //        case "extended":
    //            $cl->SetMatchMode ( SPH_MATCH_EXTENDED2 );
    //        break;
    //    }

    //    if ($orderby == "id") { $orderby = "@id"; }
    if ($json_a['tail'] !== "off") { $order = "DESC"; }


    // Datetime filtering
    $fo_checkbox = $json_a['fo_checkbox'];
    $fo_date = $json_a['fo_date'];
    $fo_time_start = $json_a['fo_time_start'];
    $fo_time_end = $json_a['fo_time_end'];
    $lo_checkbox = $json_a['lo_checkbox'];
    $lo_date = $json_a['lo_date'];
    $lo_time_start = $json_a['lo_time_start'];
    $lo_time_end = $json_a['lo_time_end'];

    if ($fo_checkbox == "on") {
        if($fo_date!='') {
            list($start,$end) = explode(' to ', $fo_date);
            if($end=='') $end = "$start" ; 
            if(($start==$end) and ($fo_time_start>$fo_time_end)) {
                $endx = strtotime($end);
                $endx = $endx+24*3600;
                $end = date('Y-m-d', mktime(0,0,0,date('m',$endx),date('d',$endx),date('Y',$endx))); }
                $start .= " $fo_time_start"; 
                $end .= " $fo_time_end"; 
                $fo_start = "$start" ;
                $fo_end = "$end" ;
        }
    }
    if ($lo_checkbox == "on") {
        if($lo_date!='') {
            list($start,$end) = explode(' to ', $lo_date);
            if($end=='') $end = "$start" ; 
            if(($start==$end) and ($lo_time_start>$lo_time_end)) {
                $endx = strtotime($end);
                $endx = $endx+24*3600;
                $end = date('Y-m-d', mktime(0,0,0,date('m',$endx),date('d',$endx),date('Y',$endx))); }
                $start .= " $lo_time_start"; 
                $end .= " $lo_time_end"; 
                $lo_start = "$start" ;
                $lo_end = "$end" ;
        }
    }


    if (($json_a['fo_checkbox'] == "on") and ($fo_start) and ($fo_end)) {
        $sphinxfilters[] = "fo>=".strtotime("$fo_start")." AND fo<=".strtotime("$fo_end");
    }
    if (($json_a['lo_checkbox'] == "on") and ($lo_start) and ($lo_end)) {
        $sphinxfilters[] = "lo>=".strtotime("$lo_start")." AND lo<=".strtotime("$lo_end");
    }


    // Duplicates filtering
    $min = "0";
    $max = "9999999999";
    if (($dupop) && ($dupop !== 'undefined')) {
        switch ($dupop) {
            case "gt":
                $dupop = ">";
                $min = $dupcount + 1;
                break;

            case "lt":
                $dupop = "<";
                $max = $dupcount - 1;
                break;

            case "eq":
                $dupop = "=";
                $min = $dupcount;
                $max = $dupcount;
                break;

            case "gte":
                $dupop = ">=";
                $min = $dupcount;
                break;
                $min = $dupcount;
            case "lte":
                $dupop = "<=";

                break;
        }
    }
    // echo "$min - $max\n";
    //    $cl->SetFilterRange ( 'counter', intval($min), intval($max) );
    $sphinxfilters[] = "counter>=$min AND counter<=$max";
    $sphinxlimit = "LIMIT 0,$limit";
    $sphinxoptions = "OPTION max_matches=$spx_max ";

    //    $cl->setLimits(0,intval($limit), $spx_max);

    $countfield="";
    if ($json_a['groupby']) {
        $groupby = $json_a['groupby'];
        switch ($groupby) {
            case "mne":
                $val = mne2crc('None');
                $sphinxfilters[] = "mne!=$val";
                //                $cl->SetFilter( 'mne', array($val), true );
                break;
            case "eid":
                //                $cl->SetFilter( 'eid', array(0), true );
                $sphinxfilters[] = "eid!=0";
                break;
        }
        $sphinxgroupby = "GROUP BY ".$json_a['groupby']." ORDER BY $orderby $order";
        $countfield = ", count(*) as count";
        //        $cl->setGroupBy($json_a['groupby'],SPH_GROUPBY_ATTR,"$orderby $order");
    } else {
        //      $cl->SetSortMode ( SPH_SORT_EXTENDED , "$orderby $order" );
        $sphinxgroupby = "ORDER BY $orderby $order";
    }



    // make the querys
    $counter=0;
    $hosts="";
    $ids = array();

    // fetch the hosts


    if (is_array($json_a['hosts'])) {
        foreach ($json_a['hosts'] as $key => $h) {
            if ($h !== '') {
                // #407 - make sure all hosts are crc32
                if (!is_numeric($h)) { 
                    $h = crc32($h); 
                }
            $hosts =  $hosts . $h . ",";
            $counter = $counter+1;
            }

            // split query in max 100 hosts 
            // cdukes - [[ticket:426]] - changed to 15000
            if ($counter>=15000)  {
                $hosts = rtrim($hosts,",");
                $shosts = $scl->real_escape_string($hosts);
                $search_string = $msg_mask . $notes_mask;
                if ($lo_start<(date('Y-m-d')." 00:00:00")) {
                    $query = " AND MATCH ('@dummy dummy $search_string')";
                } else {
                    if ($search_string) $query = " AND MATCH ('@dummy dummy $search_string')";
                }

                // Test for empty search and remove whitespaces
                $search_string = preg_replace('/^\s+$/', '',$search_string);
                $search_string = preg_replace('/\s+$/', '',$search_string);
                // get the columns we are sorting 
                // speedup: when use use today only idx_last_24h is used
                if ($lo_start<(date('Y-m-d')." 00:00:00")) {
                    $sphinxstatement = "Select id, facility, severity, counter, lo from distributed where "; }
                else {
                    $sphinxstatement = "Select id, facility, severity, counter, lo from idx_last_24h where "; }
                if (sizeof($sphinxfilters)>0) {
                    $sphinxstatement.=implode($sphinxfilters,' AND '); }
                $sphinxstatement .= " $query and host_crc in ($hosts) $sphinxgroupby $sphinxlimit $sphinxoptions";
                action("Search Function: ".$sphinxstatement);
                $result = $scl->query($sphinxstatement);

                // Get meta info:
                $mresult = $scl->query("show meta");
                while ( $row = $mresult->fetch_assoc() )
                {
                    $meta[] = $row;
                }
                // $meta[] = $sphinxstatement;


                if ( $result ) {
                    while ( $row = $result->fetch_assoc() )
                    {
                        $ids[] = $row;
                    }    			
                }
                $hosts = "";
                $counter = 0;


            }
        }
    }

    // catch the last few hosts
    if ($hosts != "") {
        $hosts = rtrim($hosts,",");
        $hosts = $scl->real_escape_string($hosts);
                $search_string = $msg_mask . $notes_mask;
                if ($lo_start<(date('Y-m-d')." 00:00:00")) {
                    $query = " AND MATCH ('@dummy dummy $search_string')";
                } else {
                    if ($search_string) $query = " AND MATCH ('@dummy dummy $search_string')";
                }

        // Test for empty search and remove whitespaces
        $search_string = preg_replace('/^\s+$/', '',$search_string);
        $search_string = preg_replace('/\s+$/', '',$search_string);
        // get the columns we are sorting 
        // speedup: when use use today only idx_last_24h is used
        if ($lo_start<(date('Y-m-d')." 00:00:00")) {
            $sphinxstatement = "Select id, facility, severity, counter, lo from distributed where "; }
        else {
            $sphinxstatement = "Select id, facility, severity, counter, lo from idx_last_24h where "; }
        if (sizeof($sphinxfilters)>0) {
            $sphinxstatement.=implode($sphinxfilters,' AND '); }
        $sphinxstatement .= " $query and host_crc in ($hosts) $sphinxgroupby $sphinxlimit $sphinxoptions";
        action("Search Function: ".$sphinxstatement);
        $result = $scl->query($sphinxstatement);

        if ( $result ) {
            while ( $row = $result->fetch_assoc() )
            {
                $ids[] = $row;
            }    			
        }
    }
    // sort the results array
    $candidates = array();
    foreach ($ids as $key => $row) {
        $candidates[$key]  = $row[$orderby];
    }
    if ($order == "ASC") {
        array_multisort($candidates, SORT_ASC, $ids); 
    }
    else {
        array_multisort($candidates, SORT_DESC, $ids);
    }
    // get the ids
    $result_ids = array();
    foreach ($ids as $key => $row) {
        $result_ids[] = $row['id'];
    }
    $ids = array_unique($result_ids);
    // limit to query limit
    for ($i = 0; $i < $limit; $i++) {
        $found_ids[][] = $ids[$i];
    }

    // CDUKES:
    // This will append the $meta data to the array.
    // So, if $limit is set to 100, this would be at array position 100
    // Get meta info:
    $mresult = $scl->query("show meta");
    while ( $row = $mresult->fetch_assoc() )
    {
        $meta[] = $row;
    }
    $return = array_merge($found_ids, $meta);
    // echo "<pre>";
    // die(print_r($return));

    return json_encode($return);

}

function spx_query($sql) {
    /*
       Usage example:
       $sql = "select *, count(*) x from distributed group by msg_crc order by x desc limit 10";
       $result = spx_query($sql);
       $top_ten_msgs = json_decode($result);
     */
    $basePath = dirname( __FILE__ );
    // require_once ($basePath . "/SPHINX.class.php");
    $scl = new mysqli(SPHINXHOST,'','','',SPHINXPORT);
    if (mysqli_connect_errno())
        return sprintf("Sphinxql error in connect: %d %s\n", mysqli_connect_errno(), mysqli_connect_error() . "<br>The Sphinx daemon may not be running.");
    action("Searching using sphinx ".$sql);
    $result = $scl->query($sql);
    if ( $result === FALSE )
    {
        // die (sprintf ("Sphinxql error in query: %d %s", $scl->errno, $scl->error));
        return sprintf ("Sphinxql error in query: %d %s", $scl->errno, $scl->error . "<br>The Sphinx daemon may not be running.");
        $scl->close ();
    }
    $arr = array();
    while ( $row = $result->fetch_row() )
    {
        $arr[] = $row;
    }
    // Append meta info to array:
    $result = $scl->query("show meta");
    while ( $row = $result->fetch_row() )
    {
        $arr[] = $row;
    }

    $result->close();
    $scl->close();
    // logmsg(json_encode($arr)); // DEBUG: Logs results to /var/log/logzilla
    return ($arr);
}


function search_graph($json_o, $spx_max,$index="idx_logs idx_delta_logs",$spx_ip,$spx_port) {
    $basePath = dirname( __FILE__ );
    // require_once ($basePath . "/SPHINX.class.php");

    // Grab the settings from the database if not as parameter
    if ($spx_max == '') { $spx_max = $_SESSION[SPX_MAX_MATCHES] ; }
    if ($spx_ip == '') { $spx_ip = $_SESSION[SPX_SRV] ; }
    if ($spx_port == '') { $spx_port = $_SESSION[SPX_PORT] ; }

    // let us try to invoke sphinxql here instead...

    $scl = new mysqli(SPHINXHOST,'','','',SPHINXPORT);
    if (mysqli_connect_errno())
        return sprintf("Sphinxql error in connect: %d %s\n", mysqli_connect_errno(), mysqli_connect_error() . "<br>The Sphinx daemon may not be running.");

    //$cl = new SphinxClient ();
    //$cl->SetServer ( $spx_ip, $spx_port );

    // Decode json object into an array:
    $json_a = json_decode($json_o, true);
    //die(print_r($json_a));

    // Set All Defaults in case they aren't sent via the json object 
    $dupop = (!empty($json_a['dupop'])) ? $json_a['dupop'] : ">=";
    $dupcount = (!empty($json_a['dupcount'])) ? $json_a['dupcount'] : 0;
    $orderby = (!empty($json_a['orderby'])) ? $json_a['orderby'] : "id";
    $order = (!empty($json_a['order'])) ? $json_a['order'] : "ASC";
    $limit = (!empty($json_a['limit'])) ? $json_a['limit'] : $spx_max;
    $show_suppressed = (!empty($json_a['show_suppressed'])) ? $json_a['show_suppressed'] : "all";
    $q_type = (!empty($json_a['q_type'])) ? $json_a['q_type'] : "boolean";
    $search_op = (!empty($json_a['search_op'])) ? $json_a['search_op'] : "|";

    // loop through array to get the fields that the user wants to search on:
    // Note: Only certain values need to be looped here for modification before presenting to sphinx.
    // many of the items not looped below can be called directly using $json_a['name'];
    foreach ($json_a as $key=>$val) {
        // echo "Key = $key, Val = $val\n";
        switch($key) {
            // Strings
            case 'msg_mask':
                //                $val = real_escape_string( $cl->EscapeString ($val);
                $val = $scl->real_escape_string($val);
		$val = EscapeSphinxQL($val);
                $msg_mask .= $val . " $search_op ";
                break;
            case 'notes_mask':
                foreach ($val as $subkey=>$subval) {
                    // echo "SubKey = $subkey, SubVal = $subval\n";
                    //                    $subval = $cl->EscapeString ($subval);
                    $subval = mysql_real_escape_string ( $subval, $scl );
                    $notes_mask .= $subval . " $search_op ";
                }
                break;
                /*            case 'hosts':
                              foreach ($val as $subkey=>$subval) {
                // echo "SubKey = $subkey, SubVal = $subval\n";
                //                    $subval = $cl->EscapeString ($subval);
                $subval = $scl->real_escape_string($subval);
                $hosts .= $subval . " $search_op ";
                }
                break;
                 */            case 'mnemonics':
                foreach ($val as $subkey=>$subval) {
                    // echo "SubKey = $subkey, SubVal = $subval\n";
                    if (!preg_match ('/^\d+$/', $subval)) {
                        $mnes[] .= mne2crc($subval);
                    } else {
                        $mnes[] .= $subval;
                    }
                }
                break;
            case 'eids':
                foreach ($val as $subkey=>$subval) {
                    // echo "SubKey = $subkey, SubVal = $subval\n";
                    $eids[] .= $subval;
                }
                break;
            case 'programs':
                foreach ($val as $subkey=>$subval) {
                    // echo "SubKey = $subkey, SubVal = $subval\n";
                    if (!preg_match ('/^\d+$/', $subval)) {
                        $prgs[] .= prg2crc($subval);
                    } else {
                        $prgs[] .= $subval;
                    }                 
                }
                break;
        }
    }
    // die(print_r($json_a));
    $msg_mask = rtrim($msg_mask, " $search_op ");
    //    $hosts = rtrim($hosts, " $search_op ");
    $notes_mask = rtrim($notes_mask, " $search_op ");

    // Add DB column to strings
    if (!preg_match ('/any|all|phrase/', $q_type)) {
        if ($msg_mask)  {
            $msg_mask = "@MSG " . $msg_mask . " ";
        }
        /*        if ($hosts) {
                  $hosts = "@HOST " . $hosts . " ";
                  }
         */        if ($notes_mask) {
             $notes_mask = "@NOTES " . $notes_mask;
         }
    }


    $sphinxfilters = array();


    // SetFilter used on integer fields - takes an array
    if ($json_a['severities']) {
        //        $cl->SetFilter( 'severity', $json_a['severities'] );
        $sphinxfilters[] = "severity in (".implode($json_a[severities],',').")";
    }
    if ($json_a['facilities']) {
        //        $cl->SetFilter( 'facility', $json_a['facilities'] );
        $sphinxfilters[] = "facility in (".implode($json_a[facilities],',').")";
    }
    if (is_array($eids)) {
        //        $cl->SetFilter( 'eid', $eids );
        $sphinxfilters[] = "eid in (".implode($eids,',').")";
    }
    if ($json_a['mnemonics']) {
        //        $cl->SetFilter( 'mne', $mnes );
        $sphinxfilters[] = "mne in (".implode($mnes,',').")";
    }
    if ($json_a['programs']) {
        //        $cl->SetFilter( 'program', $prgs );
        $sphinxfilters[] = "program in (".implode($prgs,',').")";
    }

    // this is not supported by sphinxql proto - due to the old code.
    // for now is only 'extended2' is the active, and the rest could be
    // simulated (and actually do internally) by extended2.
    //    switch ($q_type) {
    //        case "any":
    //            $cl->SetMatchMode ( SPH_MATCH_ANY );
    //        break;
    //        case "all":
    //            $cl->SetMatchMode ( SPH_MATCH_ALL );
    //        break;
    //        case "phrase":
    //            $cl->SetMatchMode ( SPH_MATCH_PHRASE );
    //        break;
    //        case "boolean":
    //            $cl->SetMatchMode ( SPH_MATCH_BOOLEAN );
    //        break;
    //        case "extended":
    //            $cl->SetMatchMode ( SPH_MATCH_EXTENDED2 );
    //        break;
    //    }

    //    if ($orderby == "id") { $orderby = "@id"; }
    if ($json_a['tail'] !== "off") { $order = "DESC"; }


    // Datetime filtering
    $fo_checkbox = $json_a['fo_checkbox'];
    $fo_date = $json_a['fo_date'];
    $fo_time_start = $json_a['fo_time_start'];
    $fo_time_end = $json_a['fo_time_end'];
    $lo_checkbox = $json_a['lo_checkbox'];
    $lo_date = $json_a['lo_date'];
    $lo_time_start = $json_a['lo_time_start'];
    $lo_time_end = $json_a['lo_time_end'];

    if ($fo_checkbox == "on") {
        if($fo_date!='') {
            list($start,$end) = explode(' to ', $fo_date);
            if($end=='') $end = "$start" ; 
            if(($start==$end) and ($fo_time_start>$fo_time_end)) {
                $endx = strtotime($end);
                $endx = $endx+24*3600;
                $end = date('Y-m-d', mktime(0,0,0,date('m',$endx),date('d',$endx),date('Y',$endx))); }
                $start .= " $fo_time_start"; 
                $end .= " $fo_time_end"; 
                $fo_start = "$start" ;
                $fo_end = "$end" ;
        }
    }
    if ($lo_checkbox == "on") {
        if($lo_date!='') {
            list($start,$end) = explode(' to ', $lo_date);
            if($end=='') $end = "$start" ; 
            if(($start==$end) and ($lo_time_start>$lo_time_end)) {
                $endx = strtotime($end);
                $endx = $endx+24*3600;
                $end = date('Y-m-d', mktime(0,0,0,date('m',$endx),date('d',$endx),date('Y',$endx))); }
                $start .= " $lo_time_start"; 
                $end .= " $lo_time_end"; 
                $lo_start = "$start" ;
                $lo_end = "$end" ;
        }
    }

    if (($json_a['fo_checkbox'] == "on") and ($fo_start) and ($fo_end)) {
        $sphinxfilters[] = "fo>=".strtotime("$fo_start")." AND fo<=".strtotime("$fo_end");
    }
    if (($json_a['lo_checkbox'] == "on") and ($lo_start) and ($lo_end)) {
        $sphinxfilters[] = "lo>=".strtotime("$lo_start")." AND lo<=".strtotime("$lo_end");
    }

    // Duplicates filtering
    $min = "0";
    $max = "9999999999";
    if (($dupop) && ($dupop !== 'undefined')) {
        switch ($dupop) {
            case "gt":
                $dupop = ">";
                $min = $dupcount + 1;
                break;

            case "lt":
                $dupop = "<";
                $max = $dupcount - 1;
                break;

            case "eq":
                $dupop = "=";
                $min = $dupcount;
                $max = $dupcount;
                break;

            case "gte":
                $dupop = ">=";
                $min = $dupcount;
                break;
                $min = $dupcount;
            case "lte":
                $dupop = "<=";

                break;
        }
    }
    // echo "$min - $max\n";
    //    $cl->SetFilterRange ( 'counter', intval($min), intval($max) );
    $sphinxfilters[] = "counter>=$min AND counter<=$max";
    $sphinxlimit = "LIMIT 0,$limit";
    $sphinxoptions = "OPTION max_matches=$spx_max ";

    //    $cl->setLimits(0,intval($limit), $spx_max);

    $countfield="";
    if ($json_a['groupby']) {
        $groupby = $json_a['groupby'];
        switch ($groupby) {
            case "mne":
                $val = mne2crc('None');
                $sphinxfilters[] = "mne!=$val";
                //                $cl->SetFilter( 'mne', array($val), true );
                break;
            case "eid":
                //                $cl->SetFilter( 'eid', array(0), true );
                $sphinxfilters[] = "eid!=0";
                break;
        }
        // always use top n records count in charts
        $sphinxgroupby = "GROUP BY ".$json_a['groupby']." ORDER BY scount desc";
        $countfield = ", count(*) as count";
        //        $cl->setGroupBy($json_a['groupby'],SPH_GROUPBY_ATTR,"$orderby $order");
    } else {
        //      $cl->SetSortMode ( SPH_SORT_EXTENDED , "$orderby $order" );
        // always use top n records count in charts
        $sphinxgroupby = "ORDER BY scount desc";
    }



    // make the querys
    $counter=0;
    $hosts="";
    $ids = array();

    // fetch the hosts


    if (is_array($json_a['hosts'])) {
        foreach ($json_a['hosts'] as $key => $h) {
            if ($h !== '') { // [[ticket:304]]
                // #407 - make sure all hosts are crc32
                if (!is_numeric($h)) { 
                    $h = crc32($h); 
                }
            $hosts =  $hosts . $h . ",";
            $counter = $counter+1;
            }
            // split query in max 100 hosts 
            // cdukes - [[ticket:426]] - changed to 15000
            if ($counter>=15000)  {
                $hosts = rtrim($hosts,",");
                $shosts = $scl->real_escape_string($hosts);
                $search_string = $msg_mask . $notes_mask;
                if ($lo_start<(date('Y-m-d')." 00:00:00")) {
                    $query = " AND MATCH ('@dummy dummy $search_string')";
                } else {
                    if ($search_string) $query = " AND MATCH ('@dummy dummy $search_string')";
                }

                // Test for empty search and remove whitespaces
                $search_string = preg_replace('/^\s+$/', '',$search_string);
                $search_string = preg_replace('/\s+$/', '',$search_string);
                // get the columns we are sorting 
                // speedup: when use use today only idx_last_24h is used
                if ($lo_start<(date('Y-m-d')." 00:00:00")) {
                    $sphinxstatement = "Select ".$json_a['groupby'].", sum(counter) as scount from distributed where "; }
                else {
                    $sphinxstatement = "Select ". $json_a['groupby'].", sum(counter) as scount from idx_last_24h where "; }
                if (sizeof($sphinxfilters)>0) {
                    $sphinxstatement.=implode($sphinxfilters,' AND '); }
                $sphinxstatement .= " $query and host_crc in ($hosts) $sphinxgroupby $sphinxlimit $sphinxoptions";
                action("GRAPH: Searching using sphinx ".$sphinxstatement);
                $result = $scl->query($sphinxstatement);
                if ( $result ) {
                    while (list($name, $value) = $result->fetch_row())
                    {
                        $ids[$name] += $value;
                    } 
                }   			
                $counter=0;
                $hosts="";
            }
        }
    }

    // catch the last few hosts

    if ($hosts != "") {
        $hosts = rtrim($hosts,",");
        $hosts = $scl->real_escape_string($hosts);
                $search_string = $msg_mask . $notes_mask;
                if ($lo_start<(date('Y-m-d')." 00:00:00")) {
                    $query = " AND MATCH ('@dummy dummy $search_string')";
                } else {
                    if ($search_string) $query = " AND MATCH ('@dummy dummy $search_string')";
                }

        // Test for empty search and remove whitespaces
        $search_string = preg_replace('/^\s+$/', '',$search_string);
        $search_string = preg_replace('/\s+$/', '',$search_string);
        // get the columns we are sorting 
        // speedup: when use use today only idx_last_24h is used
        if ($lo_start<(date('Y-m-d')." 00:00:00")) {
            $sphinxstatement = "Select ".$json_a['groupby'].", sum(counter) as scount from distributed where "; }
        else {
            $sphinxstatement = "Select ".$json_a['groupby'].", sum(counter) as scount from idx_last_24h where "; }
        if (sizeof($sphinxfilters)>0) {
            $sphinxstatement.=implode($sphinxfilters,' AND '); }
        $sphinxstatement .= " $query and host_crc in ($hosts) $sphinxgroupby $sphinxlimit $sphinxoptions";
        action("GRAPH2: Searching using sphinx ".$sphinxstatement);
        $result = $scl->query($sphinxstatement);
        if ( $result ) {
            while (list($name, $value) = $result->fetch_row())
            {
                $ids[$name] += $value;
            }    			
        }
    }
    // sort the results array
    arsort($ids);

    $keys = array_keys($ids);
    $values = array_values($ids);

    // limit to query to 100
    if ($limit >  count($values)) { $limit = count($values); }
    for ($i = 0; $i < $limit; $i++) {
        $found_ids[$i][$json_a['groupby']] = $keys[$i];
        $found_ids[$i]['scount'] = $values[$i];
    }

    return json_encode($found_ids);

}

function utfconvert($content) { 
            // logmsg("converting...");
    if(!mb_check_encoding($content, 'UTF-8') 
            OR !($content === mb_convert_encoding(mb_convert_encoding($content, 'UTF-32', 'UTF-8' ), 'UTF-8', 'UTF-32'))) { 

        $content = mb_convert_encoding($content, 'UTF-8'); 

        if (mb_check_encoding($content, 'UTF-8')) { 
            // logmsg('Converted to UTF-8'); 
        } else { 
            // logmsg('Could not converted to UTF-8'); 
        } 
    } 
    return $content; 
} 
function EscapeSphinxQL ( $string )
{
    $from = array ( '\\', '(',')','|','-','!','@','~','"','&', '/', '^', '$', '=', "'", "\x00", "\n", "\r", "\x1a" );
    $to   = array ( '\\\\', '\\\(','\\\)','\\\|','\\\-','\\\!','\\\@','\\\~','\\\"', '\\\&', '\\\/', '\\\^', '\\\$', '\\\=', "\\'", "\\x00", "\\n", "\\r", "\\x1a" );
    return str_replace ( $from, $to, $string );
}
?>
