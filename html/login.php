<?php

/*
 *
 * Developed by Clayton Dukes <cdukes@logzilla.pro>
 * All rights reserved.
 *
 * Changelog:
 * 2006-12-11 - created
 *
 */


session_start();
include_once ("config/config.php");
include_once ("includes/js_header.php");
include_once ("includes/common_funcs.php");
include_once ("includes/modules/functions.security.php");


//Start security update v0.1
if($appConfig['captcha'] == "on") {
if(!isset($_SESSION['num_login_tries'])) {
	$_SESSION['num_login_tries'] = 0;
}
include_once ("includes/modules/recaptchalib.php");
}

//sanitize global variables
 $_SERVER = cleanArray($_SERVER);
$_POST = cleanArray($_POST);
$_GET = cleanArray($_GET);
$_COOKIE = cleanArray($_COOKIE);
//var_dump($_GET);

//check if ip is banned
if($appConfig['ban_ip']=='on') {
	$res = mysql_result(mysql_query("SELECT COUNT(*) FROM banned_ips WHERE bannedIp='{$_SERVER['REMOTE_ADDR']}' AND expirationDate>'".date("Y-m-d h:m:s")."'"),0);
	//echo $res;
	if($res!=0) {
	$act="intrusion detected from ".$_SERVER['REMOTE_ADDR'];
 	action($act);
	die("Ooops");
	}
}

//End security update v0.1

function tryfunc($func, $lib) {
    if (!function_exists("$func")) {
        echo "<p style='color: #444444; font-size: 130%;'>STOP: $lib is ";
        echo '<span style="color: #EE0000; font-weight: bold;">not supported</span> by your server!<br>Please install it before using LogZilla.<br>Please <a style="color: blue; text-decoration:underline;" href="https://www.assembla.com/spaces/LogZillaWiki/wiki/Prerequisites">read the installation guide</a></p>';
        phpinfo();
        exit;
    } else {
     //    echo "function $func supported<br>";
    }
}
tryfunc("zip_open", "php_zip");
tryfunc("xml_parser_create", "php_xml");
tryfunc("gd_info", "php_gd");
tryfunc("ioncube_license_properties", "ionCube Loader");
tryfunc("json_encode", "json");

if($_SESSION['AUTHTYPE'] == "none") {
    $username = "local_noauth";
    $sessionId = session_id();
    $act = "login from local_noauth";
    action($act);
    $_SESSION["pageId"] = (empty($_GET["pageId"])?"searchform":$_GET["pageId"]) ;
    $_SESSION["username"] = 'local_noauth';
    $destination = $_SESSION['SITE_URL']."index.php";
    $dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
    $sql = "SELECT * FROM ui_layout WHERE userid=(SELECT id FROM users WHERE username='$username')";
    $res = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
    if(num_rows($res)==0){
        $sql = "INSERT INTO ui_layout (userid, pagename, col, rowindex, header, content, group_access) SELECT (SELECT id FROM users WHERE username='$username'),pagename,col,rowindex,header,content,group_access FROM ui_layout WHERE userid=0";
        $res = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
    }
    if (!empty($_SERVER['QUERY_STRING']))
    {

        $destination .= '?' . $_SERVER['QUERY_STRING'];
    }
    g_redirect($destination, "JS"); // Redirect unauthenticated member
}

if ($_POST) {
    if (auth($_POST) == $_SESSION["username"]) {
        $act = "logged in";
        action($act);
        $destination = $_SESSION['SITE_URL']."index.php";
        if (!empty($_POST['searchQuery']))
        {
            $destination .= '?' . ($_POST['searchQuery']);
        }

        g_redirect($destination, "JS"); // Redirect unauthenticated member
    } elseif ($_SESSION['error']) {

        g_redirect($SESSION['SITE_URL'], "JS"); // Redirect unauthenticated member
    }

} else {
    ?>
        <html>
        <head>
        <title><?php echo $_SESSION['PROGNAME'] ." v". $_SESSION['VERSION'] ." ". $_SESSION['VERSION_SUB']?> Login</title>
        </head>
        <div align="center">
        <form method="post" action="<?php echo  $_SERVER['PHP_SELF']; ?>">

        <?php

        if (!empty($_SERVER['QUERY_STRING']))
        {
            $queryString = htmlspecialchars($_SERVER['QUERY_STRING']);
            echo '<input type="hidden" name="searchQuery" value="' . $queryString . '">';
        }
    ?>
        <div align="center">

        <br><br><br><br>

        <table width="25%" border="0" cellspacing="0" cellpadding="0">

        <tr>
        <td align="center">
        <fieldset>

        <Legend>
        <font face="Verdana,Tahoma,Arial,sans-serif" size="1" color="gray">
        <img src="images/logo_146x40.png" alt="Authorization Check">
        <br>
        </font>
        </Legend>

        <table width="100%" border="0" cellspacing="3" cellpadding="0">

        <tr>
        <?php 
        if (isset($_SESSION['error'])) {
            echo "<div style='align: center; text-align: center; border: 2px dotted red;'>$_SESSION[error]</div>\n";
            $act = $_SESSION['error'];
            $res = action($act);
            unset($_SESSION['error']);
        }
    ?>
        <!--[if lt IE 8]>
        <div style='align: left; text-align: left; border: 2px dotted red;'>LogZilla has not been certified to work with the browser you are using. You may continue, but the application might not behave as expected.</div>
        <![endif]-->
        <td align="right" valign="middle">
        <b> <font face="Verdana,Tahoma,Arial,sans-serif" size="1" color="gray">Username:</font> </b>
        </td>
        <td align="center" valign="middle">
        <input class="clear" type="text" size="15" name="username">
        </td>
        </tr>

        <tr>
        <td align="right" valign="middle">
        <b><font face="Verdana,Tahoma,Arial,sans-serif" size="1" color="gray">Password: </font></b>
        </td>
        <td align="center" valign="middle">
        <input class="pass" type="password" size="15" name="password">
        </td>
        </tr>

        <tr>
        <td align="right" valign="middle">
        <b><font face="Verdana,Tahoma,Arial,sans-serif" size="1" color="gray">Auth Type:</b></font>
        </td>
        <td align="center" valign="middle">
        <SELECT NAME="authtype" STYLE="width: 120px;">  
        <OPTION VALUE="local">Local
        <?php if($_SESSION['AUTHTYPE'] == "ldap") { ?>
            <OPTION SELECTED VALUE="ldap">LDAP 
                <?php } ?>
                <!--<OPTION VALUE="webbasic">Web Basic 
                <OPTION VALUE="msad">MS AD 
                <OPTION VALUE="cert">SSL Certificate
                <OPTION VALUE="tacacs">TACACS+
                <OPTION VALUE="radius">Radius-->
                </SELECT>    
                </td>
                </tr>
                <?php if($_SESSION['AUTHTYPE'] == "ldap") {
                    if (!function_exists('ldap_connect')) {
                        echo "<div style='align: center; text-align: center; border: 3px dotted red;'>ERROR!<br>Your version of PHP does not have ldap_connect(), which LogZilla requires.</div>\n";
                    }
                } ?>
    </table>
        <?
        //Start security update v0.1
        //echo $_SESSION['num_login_tries'];
        if($appConfig['captcha']=='on' && $appConfig['num_login_tries']<=$_SESSION['num_login_tries']) {
            echo recaptcha_get_html($appConfig['captcha_public_key']);
        }
    //End security update v0.1
    ?>
        <input type=image src="images/GoGo_brn.png" width="50px" height="30px" alt="Login" name="image">
        <br>
        </div>
        </td>
        </tr>
        </fieldset>
        </table>

        <br>
        <table width="49%"><tr><td align="center">
        <font face="Verdana,Tahoma,Arial,sans-serif" size="1" color="silver">
        This System is for the use of authorized users only. Individuals using this computer system
        without authority, or in excess of their authority, are subject to having their activities
        on this system monitored and recorded by system personnel. In the course of monitoring individuals
        improperly using this system, or in the course of system maintenance, the activities of
        authorized users may also be monitored. Anyone using this system expressly consents to
        such monitoring and is advised that if such monitoring reveals possible criminal activity,
             system personnel may provide the evidence of such monitoring to law enforcement officals.
                 This warning has been provided by the United States Department of Justice and is intended to
                 ensure that monitoring of user activity is not in violation of the Communications Privacy Act of
                 1986.
                 </font>
                 </td></tr>
                 </table>

                 </div>
                 </form>

                 </div>
                 </body>
                 </html>
                 <?php 
} 
?>
