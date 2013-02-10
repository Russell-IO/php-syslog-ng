<?php
// ** MySQL settings ** //
$basePath = dirname( __FILE__ );
require_once ($basePath . "/../common_funcs.php");
$host = DBHOST;
$db = DBNAME;
define('DB_DSN',"mysql:host=$host;dbname=$db");
define('DB_USER', DBADMIN);     // Your MySQL username
define('DB_PASSWORD', DBADMINPW); // ...and password

define('ABSPATH', dirname(__FILE__).'/');
?>
