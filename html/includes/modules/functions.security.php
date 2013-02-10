<?php

function processString($str) {
	$str = str_replace("&","",$str);
	$str = str_replace("(","",$str);
	$str = str_replace(")","",$str);
	$str = str_replace("*","~",$str);
	$str = str_replace("  "," ",$str);
	$tmp =  explode(" ",strip_tags($str));
	foreach($tmp as $key=>$val) {
		$tmp[$key] = strtolower($val);
	}
	$str = implode("-",$tmp);
	$str = str_replace("--","-",$str);
	$str = str_replace("/","-",$str);
	return $str;		
	}
	
//this function returns the extension of a filename
function fileExtension($filename) {
	$tmp = explode(".",$filename);
	$nr = count($tmp);
	$nr--;
	$ext = strtolower($tmp[$nr]);
	return $ext;
}

function clean($var) {
	//cleans a single variable
	$var = mysql_real_escape_string(strip_tags(trim($var)));	
	return $var;	
}

function cleanArray($array) {
	//cleans an entire array recursively
	//both keys and values
    $arrayClean = "";
	foreach($array as $key=>$value) {
		if(is_array($value)) {
			$arrayClean[clean($key)] = cleanArray($value);
		} else {
		$arrayClean[$key] = clean($value);
		}
	}
	return $arrayClean;
}	
	
function encryptPass($pass) {
	//you can use this function instead of md5 for an extra level of security
	//the salt adds an extra layer of complexity against brute-force attacks
	global $appConfig;
	if($appConfig['use_salt_password']=='on') {
		$password = md5($pass.$appConfig['salt']);
	} else {
		$password = md5($pass);
	}
	return $password;
}	
	
?>
