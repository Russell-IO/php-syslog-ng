<?php
/*
 *
 * Developed by Clayton Dukes <cdukes@logzilla.pro>
 * Copyright (c) 2010 gdd.net
 * All rights reserved.
 *
 * Changelog:
 * 2010-03-13 - created
 *
 */

// session_start();
$basePath = dirname( __FILE__ );
require_once ($basePath . "/../common_funcs.php");
$dbLink = db_connect_syslog(DBADMIN, DBADMINPW);


//------------------------------------------------------------------------
// This functions verifies a username/password combination. If the
// combination exists then the function returns TRUE. If not then it
// returns FALSE.
//------------------------------------------------------------------------
function verify_login($username, $password, $dbLink) {
    // If the username or password is blank then return FALSE.
    if(!$username || !$password) {
        return FALSE;
    }

    // Get the md5 hash of the password and query the database.
    $pwHash = md5($password);
    $query = "SELECT * FROM ".$_SESSION["TBL_AUTH"]." WHERE username='".$username."' AND pwhash='".$pwHash."'";
    $result = perform_query($query, $dbLink, $_SERVER['PHP_SELF']);

    if(num_rows($result) == 1) {
        $sql = "SELECT * FROM ui_layout WHERE userid=(SELECT id FROM users WHERE username='$username')";
        $res = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
        if(num_rows($res)==0){
            reset_layout($username);
        }
        $sessionId = session_id();
        $_SESSION["pageId"] = "searchform" ;
        $expTime = time()+$_SESSION["SESS_EXP"];
        $expTimeDB = date('Y-m-d H:i:s', $expTime);
        $query = "UPDATE ".$_SESSION["TBL_AUTH"]." SET sessionid='".$sessionId."', 
            exptime='".$expTimeDB."' WHERE username='".$username."'";
        $result = perform_query($query, $dbLink, $_SERVER['PHP_SELF']);
        return TRUE;
    }
    else {
        return FALSE;
    }
}

//------------------------------------------------------------------------
// This function verifies a username/sessionId combination. If the
// combination exists then the function returns TRUE. If not then it
// returns FALSE. If the RENEW_SESSION_ON_EACH_PAGE parameter is set then
// the functions also updates the timestamp for the session after it is
// verified.
//------------------------------------------------------------------------
function verify_session($username, $sessionId, $dbLink) {
    // If the username or sessionId is blank then return FALSE.
    if(!$username || !$sessionId) {
        return FALSE;
    }

    // Query the database.
    $query = "SELECT * FROM ".$_SESSION["TBL_AUTH"]." WHERE username='".$username."' 
        AND sessionid='".$sessionId."' AND exptime>now()";
    $result = perform_query($query, $dbLink, $_SERVER['PHP_SELF']);

    // If the query returns one result row then the session is verified.
    if(num_rows($result) == 1) {
        //If RENEW_SESSION_ON_EACH_PAGE is set then update the
        // session timestamp in the database.
        // if(defined('RENEW_SESSION_ON_EACH_PAGE') && RENEW_SESSION_ON_EACH_PAGE == TRUE) {
        // CDUKES: 2009-11-04 Removed check for RENEW_SESSION_ON_EACH_PAGE, what's the 
        // point of doing that, why not just renew the sessions anyways?
        $expTime = time()+$_SESSION["SESS_EXP"];
        $expTimeDB = date('Y-m-d H:i:s', $expTime);
        $query = "UPDATE ".$_SESSION["TBL_AUTH"]." SET exptime='".$expTimeDB."'
            WHERE username='".$username."'";
        perform_query($query, $dbLink, $_SERVER['PHP_SELF']);
        // }
        return TRUE;
    }
    else {
        return FALSE;
    }
}

//========================================================================
// BEGIN ACCESS CONTROL FUNCTIONS
//========================================================================
//------------------------------------------------------------------------
// This function verifies that the user has access to a particular part
// of php-syslog-ng.
// Inputs are:
// username
// actionName
// dbLink
//
// Outputs TRUE or FALSE
//------------------------------------------------------------------------

// currently not used in v3.0
function grant_access($userName, $actionName, $dbLink) {
    // If $_SESSION["AUTHTYPE"] is non (open system), then allow access
    if($_SESSION["AUTHTYPE"] = "none") {
        return TRUE;
    }
    // Get user access
    $sql = "SELECT access FROM ".$_SESSION["TBL_AUTH"]." WHERE username='".$userName."' 
        AND actionname='".$actionName."'";
    $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
    $row = fetch_array($result);
    if(num_rows($result) && $row['access'] == 'TRUE') {
        return TRUE;
    }
    // Get default access
    else {
        $sql = "SELECT defaultaccess FROM ".$_SESSION["TBL_ACTIONS"]." WHERE actionname='".$actionName."'";
        $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
        $row = fetch_array($result);
        if($row['defaultaccess'] == 'TRUE') {
            return TRUE;
        }
        else {
            return FALSE;
        }
    }
}
//========================================================================
// END ACCESS CONTROL FUNCTIONS
//========================================================================


# cdukes - Added below for 2.9.4
function secure () {
    getsettings();
    if (!isset($_SESSION["username"]) || ($_SESSION["username"] == "")) {
        $destination = $_SESSION["SITE_URL"]."login.php";
        // Remember search query across login
        if (!empty($_SERVER['QUERY_STRING']))
        {
            $destination .= '?' . $_SERVER['QUERY_STRING'];
        }
        Header("Location:" . $destination);
        exit();
    } else {
        return $_SESSION["username"];
    }
}
function auth ($postvars) {
	//Start security update v0.1 
	global $appConfig;
	if($appConfig['ban_ip'] == "on" && $appConfig['max_login_tries']<=$_SESSION['num_login_tries']) {
		//insert ip into banned table
		$expdate = time()+$appConfig['ban_time']*60;
		mysql_query("INSERT INTO banned_ips(bannedIp,expirationDate) VALUES('{$_SERVER['REMOTE_ADDR']}','".date("Y-m-d h:m:s",$expdate)."')");
	}
	
	if($appConfig['captcha']=='on' && $appConfig['num_login_tries']<=$_SESSION['num_login_tries']) {
		require_once('includes/modules/recaptchalib.php');
		$resp = recaptcha_check_answer ($appConfig['captcha_private_key'],
                                $_SERVER["REMOTE_ADDR"],
                                $_POST["recaptcha_challenge_field"],
                                $_POST["recaptcha_response_field"]);
		
		if (!$resp->is_valid) {
			return $_SESSION["error"] = "The CAPTCHA wasn't entered correctly. Go back and try it again." .
			"(CAPTCHA said: " . $resp->error . ")";
		}
	}
	//End security update v0.1
	
    $error = "";
    $username = stripslashes($postvars["username"]);
    $password = stripslashes($postvars["password"]);
    if (validate_input($username, 'username') && (validate_input($password, 'password'))) {
        switch ($postvars['authtype']) {

        case "local":
            if ($username && $username !== "local_noauth") {
                $dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
                if ($username && $password && verify_login($username, $password, $dbLink)) {
                    $error ="";
                } else {
                    $error .= " Invalid password for user $username";
                }
            } else {
                if (trim($username) == "") $error .= "Your username is empty.<br>";
                if (trim($password) == "") $error .= "Your password is empty.";
            }
        if (trim($error)!="") {
			//Start security update v0.1
			$_SESSION['num_login_tries']+=1;
			//End security update v0.1
            return $_SESSION["error"] = $error;
        } else {
        	$sql = "SELECT rbac_key FROM ".$_SESSION["TBL_AUTH"]." WHERE username='".$username."'";
            $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
    		$row = fetch_array($result);
        	$_SESSION["rbac"] = $row[0];
            return $_SESSION["username"] = $username;
        }
        break;

		case "ldap":
		   	$dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
	   	$sql = "SELECT name,value FROM settings WHERE name like 'LDAP%'";
	   	$result = perform_query($sql, $dbLink, "authentication.php - LDAP Auth");
	   	while($row = fetch_array($result)) {
				if ($row['name'] == 'LDAP_BASE_DN') { $basedn = $row['value']; }
				if ($row['name'] == 'LDAP_CN') { $cn = $row['value']; }
				if ($row['name'] == 'LDAP_DOMAIN') { $domain = $row['value']; }
				if ($row['name'] == 'LDAP_MS') { $ms = $row['value']; }
				if ($row['name'] == 'LDAP_PRIV') { $priv = $row['value']; }
				if ($row['name'] == 'LDAP_RO_FILTERS') { $ro_filter = $row['value']; }
				if ($row['name'] == 'LDAP_RO_GRP') { $ro_grp = $row['value']; }
				if ($row['name'] == 'LDAP_RW_GRP') { $rw_grp = $row['value']; }
				if ($row['name'] == 'LDAP_SRV') { $srv = $row['value']; }
				if ($row['name'] == 'LDAP_DNU_GRP') { $nuser_grp = $row['value']; }
                                if ($row['name'] == 'LDAP_USERS_RO' ){ $list_of_ldapusers_ro = $row['value']; }
                                if ($row['name'] == 'LDAP_USERS_RW' ){ $list_of_ldapusers_rw = $row['value']; }

	   	}
	   	//define an appropriate ldap search filter to find your users, and filter out accounts such as administrator(administrator should be renamed anyway!).
	  	$filter="(&(|(!(displayname=Administrator*))(!(displayname=Admin*)))(" .$cn. "=$username))";
	   	$dn = $cn . "=$username, ";
	   	if (!($connect = @ldap_connect($srv))) {
		   	$error .= "Could not connect to LDAP server:" . $srv;
	   	}

		switch ($ms) {

			case "1":

				ldap_set_option($connect, LDAP_OPT_PROTOCOL_VERSION,3);
		   	ldap_set_option($connect, LDAP_OPT_REFERRALS,0);

			if (!($bind = @ldap_bind($connect, "$username@" . $domain, $password))) {
			   	$error .= " Unable to bind to LDAP Server: <b>" . $srv . "</b><br> <li>DN: $dn<br> <li>BaseDN: " . $basedn . "<br>";
		   	}

			break;

			default:

			if (!($bind = @ldap_bind($connect, "$dn" . $basedn, $password))) {
			   	$error .= " Unable to bind to LDAP Server: <b>" . $srv . "</b><br> <li>DN: $dn<br> <li>BaseDN: " . $basedn . "<br>";
		   	}

		}

		if (!($sr = @ldap_search($connect, $basedn, $filter))) { #search for user
		   	$error .= " Unable to search: <b>" . $srv . "</b><br> <li>DN: $dn<br> <li>BaseDN: " . $basedn . "<br>";
	   	}

		$info = @ldap_get_entries($connect, $sr);
	   	// print  "Number of entries returned is " .ldap_count_entries($connect, $sr)."<p>";

		if ($priv == "1") {
		   	if (in_array($rw_grp, $info[0]["groupmembership"])) {
			   	$_SESSION["userpriv"] = "rw";
		   	} elseif (in_array($ro_grp, $info[0]["groupmembership"])) {
			   	$_SESSION["userpriv"] = "ro";
		   	} else {
			   	$_SESSION["userpriv"] = "disabled";
		   	} 
                        if ( strlen($list_of_ldapusers_ro) > 0 ){
                          $tmp_miami = explode(',', $list_of_ldapusers_ro);
                          if ( in_array ($username, $tmp_miami ) ){
                            $_SESSION['userpriv'] = 'ro';                          }
                        }
			if ( strlen($list_of_ldapusers_rw) > 0 ){
                          $tmp_miami = explode(',', $list_of_ldapusers_rw);
                          if ( in_array ($username, $tmp_miami ) ){
                            $_SESSION['userpriv'] = 'rw';
                          }
                        }

			if ( $_SESSION['userpriv'] == 'disabled' ){
			  $error.='User not authorized';
			}

		}
	   	if ( trim($error) != "" ) {
			//Start security update v0.1
			$_SESSION['num_login_tries']+=1;
			//End security update v0.1
		   	return $_SESSION["error"] = $error;
	   	} else {

			$fullname=$info[0]["cn"][0];
		   	$fqdn=$info[0]["dn"];

			$_SESSION["username"] = $username;
		   	$_SESSION["groups"] = $info[0]["groupmembership"];
		   	$_SESSION["token"] = $password;
		   	$_SESSION["fullname"] = $fullname;
		   	$_SESSION["fqdn"] = $fqdn;
		   	$flname = explode(" ", $fullname);
		   	$_SESSION["firstname"] = $flname[0];
		   	$_SESSION["lastname"] = $flname[1];
		   	$_SESSION["pageId"] = "searchform" ;
		   	// die(phpinfo());
		   	// die(print_r($info[0]));
		   	// die(print_r($_SESSION));

			// Create user locally
		   	// Add user (if they don't exist)
		   	$sql = "SELECT username from users where username='$username'";
		   	$result = perform_query($sql, $dbLink, "authentication.php - LDAP");
		   	$row = fetch_array($result);
		   	if ($row['username'] !== "$username") {
			   	$sql = "INSERT IGNORE INTO ".$_SESSION['TBL_AUTH']." (username,pwhash) VALUES ('$username',MD5('$password'))";
			   	$result = perform_query($sql, $dbLink, "authentication.php - LDAP");
			   	if(mysql_affected_rows() !== 1) {
				   	$error .= "Unable to add $username to local system";
			   	} else {
				   	$sql = "REPLACE INTO groups (userid, groupname) SELECT (SELECT id FROM users WHERE username='$username'),'$nuser_grp'";
				   	perform_query($sql, $dbLink, "authentication.php - LDAP");
				   	$sql = "REPLACE INTO ui_layout (userid, pagename, col, rowindex, header, content, group_access) SELECT (SELECT id FROM users WHERE username='$username'),pagename,col,rowindex,header,content, group_access FROM ui_layout WHERE userid=0";
				   	perform_query($sql, $dbLink, "authentication.php - LDAP");
			   	}
		   	}
	   	}
		/* from here, do your sql query to query the database to search for existing record with correct username and password */
        if (trim($error)!="") {
			//Start security update v0.1
			$_SESSION['num_login_tries']+=1;
			//End security update v0.1
            return $_SESSION["error"] = $error;
        } else {
            $sessionId = session_id();
            $expTime = time()+$_SESSION["SESS_EXP"];
            $expTimeDB = date('Y-m-d H:i:s', $expTime);
            $query = "UPDATE ".$_SESSION["TBL_AUTH"]." SET sessionid='".$sessionId."', 
                exptime='".$expTimeDB."' WHERE username='".$username."'";
            $result = perform_query($query, $dbLink, $_SERVER['PHP_SELF']);
            $sql = "SELECT rbac_key FROM ".$_SESSION["TBL_AUTH"]." WHERE username='".$username."'";
            $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
            $row = fetch_array($result);
            $_SESSION["rbac"] = $row[0];
            return $_SESSION["username"] = $username;
        }
        break;

        case "webbasic":
            $error .= "Web Basic not implemented yet";
        if (trim($error)!="") {
            //Start security update v0.1
            $_SESSION['num_login_tries']+=1;
            //End security update v0.1
            return $_SESSION["error"] = $error;
        } else {
            return $_SESSION["username"] = $username;
        }
        break;

        case "msad":
            $error .= "Microsoft Authentication not implemented yet";
        if (trim($error)!="") {
            //Start security update v0.1
            $_SESSION['num_login_tries']+=1;
            //End security update v0.1
            return $_SESSION["error"] = $error;
        } else {
            return $_SESSION["username"] = $username;
        }
        break;

        case "cert":
            $error .= "SSL Certificate Authentication not implemented yet";
        if (trim($error)!="") {
            //Start security update v0.1
            $_SESSION['num_login_tries']+=1;
            //End security update v0.1
            return $_SESSION["error"] = $error;
        } else {
            return $_SESSION["username"] = $username;
        }
        break;

        case "tacacs":
            $error .= "Tacacs Authentication not implemented yet";
        if (trim($error)!="") {
            //Start security update v0.1
            $_SESSION['num_login_tries']+=1;
            //End security update v0.1
            return $_SESSION["error"] = $error;
        } else {
            return $_SESSION["username"] = $username;
        }
        break;

        case "radius":
            $error .= "Radius Authentication not implemented yet";
        if (trim($error)!="") {
            //Start security update v0.1
            $_SESSION['num_login_tries']+=1;
            //End security update v0.1
            return $_SESSION["error"] = $error;
        } else {
            return $_SESSION["username"] = $username;
        }
        break;
        }
    } else {
        //Start security update v0.1
        $_SESSION['num_login_tries']+=1;
        //End security update v0.1
        return $_SESSION["error"] = "Invalid Username or Password";
    }
}
