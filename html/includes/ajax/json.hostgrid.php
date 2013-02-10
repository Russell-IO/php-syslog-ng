<?php
$basePath = dirname( __FILE__ );
require_once ($basePath . "/../common_funcs.php");
$dbLink = db_connect_syslog(DBADMIN, DBADMINPW);

$page = $_POST['page']; // get the requested page 
$limit = $_POST['rows']; // get how many rows we want to have into the grid 
#$sidx = $_POST['sidx']; // get index row - i.e. user click to sort 
#$sord = $_POST['sord']; // get the direction 
if(!$sidx) $sidx =1; // connect to the database

if(isset($_GET["host_mask"])) $host_mask = $_GET['host_mask']; 
else $host_mask = "";

//construct where clause 
$where = "WHERE 1=1"; 
if ($_SESSION['OPTION_HGRID_SEARCH'] == "RLIKE") {
    if($host_mask!='') $where.= " AND host RLIKE '$host_mask'"; 
} else {
    if($host_mask!='') $where.= " AND host LIKE '%$host_mask%'"; 
}
// $sql = "SELECT COUNT(DISTINCT host) FROM ".$_SESSION['TBL_MAIN'] ." $where";  
$sql = "SELECT COUNT(*) FROM (SELECT host FROM hosts) AS result" ." $where";
$result = perform_query($sql, $dbLink, $_REQUEST['pageId']);
$total = mysql_fetch_row($result);
$count = $total[0];
if( $count >0 ) { 
    $total_pages = ceil($count/$limit); 
    if ($page > $total_pages) $page=$total_pages; 
    $start = $limit*$page - $limit; // do not put $limit*($page - 1) 
    $response->page = $page; 
    $response->total = $total_pages; 
    $response->records = $count; 
    // $sql = "SELECT DISTINCT(host) FROM ".$_SESSION['TBL_MAIN'] ." $where ORDER BY $sidx $sord LIMIT $start , $limit";  
    // $sql = "SELECT * FROM (SELECT host FROM hosts) AS result $where ORDER BY $sidx $sord LIMIT $start , $limit"; 
    // CDUKES: [[ticket:36]]
    $sql = "SELECT * FROM (SELECT host FROM hosts) AS result $where LIMIT $start , $limit"; 
    $result = perform_query($sql, $dbLink, $_REQUEST['pageId']); 
    $i=0; 
    while($row = fetch_array($result)) { 
        $response->rows[$i]['id']=$row[host]; 
        $response->rows[$i]['cell']=array($row[host]); 
        $i++; 
    } 
} else { 
    // No results returned, display nothing...
    $total_pages = 0; 
    $response->total = $total_pages; 
} 
echo json_encode($response); 
mysql_close($dbLink);
?>
