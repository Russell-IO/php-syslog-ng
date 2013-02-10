<?php
require_once ("config/config.php");
include_once ("includes/common_funcs.php");
$act = "logged out";
action($act);
session_start();
global $site_url;
$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
            );
}
foreach ($_SESSION as $key => $value) {
    unset($_SESSION[$key]);
    // session_unregister($key); //Unregister is deprecated as of php v5.3
}
session_destroy();
header("Location: " . $site_url."index.php");
exit;
?> 
