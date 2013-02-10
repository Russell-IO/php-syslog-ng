<?php
session_start();
$basePath = dirname( __FILE__ );
require_once ($basePath . "/../common_funcs.php");
$dbLink = db_connect_syslog(DBADMIN, DBADMINPW);

$operation = $_POST['oper'];
$tab = $_POST['tab'];
$tabname = $_POST['tabname'];
$col = $_POST['col'];
$colwidth = $_POST['colwidth'];
$rowindex = $_POST['rowindex'];
$header = $_POST['header'];
$content = $_POST['content'];
$id = $_POST['id'];

if ($operation == "edit") {
  $sql = "UPDATE ui_layout SET tab='$tab', tabname='$tabname', col='$col', colwidth='$colwidth', rowindex='$rowindex', header='$header', content='$content' WHERE id = '$id'";
  $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
} 
if ($operation == "del") {
  $sql = "DELETE from ui_layout where id='$id'";
  $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']); 
}

if ($operation == "get") {
$page = $_POST['page']; // get the requested page 
$limit = $_POST['rows']; // get how many rows we want to have into the grid 
$sidx = $_POST['sidx']; // get index row - i.e. user click to sort 
$sord = $_POST['sord']; // get the direction 
if(!$sidx) $sidx =1; // connect to the database 



if(isset($_GET["user_mask"])) $user_mask = $_GET['user_mask']; 
else $user_mask = ""; 
if(isset($_GET["tab_mask"])) $tab_mask = $_GET['tab_mask']; 
else $tab_mask = ""; 
if(isset($_GET["tabname_mask"])) $tabname_mask = $_GET['tabname_mask']; 
else $tabname_mask = ""; 
if(isset($_GET["col_mask"])) $col_mask = $_GET['col_mask']; 
else $col_mask = ""; 
if(isset($_GET["colwidth_mask"])) $colwidth_mask = $_GET['colwidth_mask']; 
else $colwidth_mask = ""; 
if(isset($_GET["rowindex_mask"])) $rowindex_mask = $_GET['rowindex_mask']; 
else $rowindex_mask = ""; 
if(isset($_GET["header_mask"])) $header_mask = $_GET['header_mask']; 
else $header_mask = ""; 
if(isset($_GET["content_mask"])) $content_mask = $_GET['content_mask']; 
else $content_mask = ""; 

//construct where clause 
$where = "WHERE 1=1"; 
/*
    if (LOG_QUERIES == 'TRUE') {
    $myFile = MYSQL_QUERY_LOG;
    $fh = fopen($myFile, 'a') or die("can't open file $myFile");
    fwrite($fh, print_r($_GET));
    fclose($fh);
    }
*/
$where.= " AND user='".$_SESSION['username']."'"; 
$count = get_total_rows("ui_layout", $dbLink, "$where"); 
if( $count >0 ) { $total_pages = ceil($count/$limit); } else { $total_pages = 0; } 
if ($page > $total_pages) $page=$total_pages; 
$start = $limit*$page - $limit; // do not put $limit*($page - 1) 
$response->page = $page; 
$response->total = $total_pages; 
$response->records = $count; 

$sql = "SELECT * FROM ui_layout $where ORDER BY $sidx $sord LIMIT $start , $limit";  
$result = perform_query($sql, $dbLink, $_REQUEST['pageId']); 
$i=0; 
while($row = fetch_array($result)) { 
	$response->rows[$i]['id']=$row[id]; 
	$response->rows[$i]['cell']=array($row[user],$row[tab],$row[tabname],$row[col],$row[colwidth],$row[rowindex],$row['header'],$row['content']); 
	$i++; 
} 
echo json_encode($response); 
}
mysql_close($dbLink);
?>
