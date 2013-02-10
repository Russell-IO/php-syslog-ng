<?php
//========================================================================
// Caching Cisco Error Message Database Lookup Class
// Part of php-syslog-ng
//
// Author : Daniel Berlin <mail@daniel-berlin.de>
// License: GPL v2 (http://www.gnu.org/licenses/gpl.html)
//========================================================================

class CEMDB {
	private $dbLink;
	private $cache = array();

  	function __construct($dbLink) {
		$this->dbLink = $dbLink;
  	}

	//------------------------------------------------------------------------
	// Retrieve information about a logmessage
	//------------------------------------------------------------------------
	public function lookup($message) {
		$name = $this->extractName($message);

		if(empty($name))
			return false;

		if(($data = $this->cacheFetch($name)) !== false)
			return $data;

		$result = perform_query(
			"SELECT message, explanation, action, datetime FROM " . $_SESSION['EMDB_TBL_CISCO'] .
			" WHERE name = '{$name}' LIMIT 1",
			$this->dbLink, $_SERVER['PHP_SELF']
		);

		if(! num_rows($result) > 0)
			return false;

		$data = array();
		$row  = fetch_array($result, "ASSOC");

		array_push($data, $name);
		array_push($data, $row['message']);
		array_push($data, $row['explanation']);
		array_push($data, $row['action']);
		array_push($data, $row['datetime']);

		$this->cacheStore($name, $data);

		return $data;
	}

	//------------------------------------------------------------------------
	// Extract the error name from the message string
	//------------------------------------------------------------------------
	private function extractName($message) {
            // Modified below to match search criteria for new CEMDB data
                // Note that ALL incoming messages must meet this search in order to elicit a DB lookup
                // Example Message: %SYS-5-CONFIG: meets criteria since it has the two delimiters (% and :)
		# Old -  preg_match_all("/(%.*):/", $message, $matches);
                preg_match_all("/(%.*?:).*/", $message, $matches);
		if(! isset($matches[1][0]))
			return "";

		// Modified below for http://code.google.com/p/php-syslog-ng/issues/detail?id=43 
		// $name = $matches[1][0];
		$name = addcslashes($matches[1][0], '\'');

		// Fix for CCO missing EMD info on the EC-SP
		if(strstr($name, "EC-SP-5-DONTBNDL"))
			return "%EC-5-DONTBNDL";

		// Fix for missing name
		if(strstr($name, "PM_SCP-SP-4-LCP_FW_ABLC"))
			return "%PM_SCP-4-LCP_FW_ABLC";

		// Fix for missing name
		if(strstr($name, "STANDBY-3-DIFFVIP1"))
			return "%HSRP-4-DIFFVIP1";

		return $name;
	}

	//------------------------------------------------------------------------
	// Fetch a cached CEMDB entry
	//------------------------------------------------------------------------
	private function cacheFetch($name) {
		return isset($this->cache[$name]) ? $this->cache[$name] : false;
	}

	//------------------------------------------------------------------------
	// Store a CEMDB entry in the cache
	//------------------------------------------------------------------------
	private function cacheStore($name, $data) {
		$this->cache[$name] = $data;
	}
}
?>
