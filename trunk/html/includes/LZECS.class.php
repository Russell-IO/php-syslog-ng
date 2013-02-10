<?php
//========================================================================
// Caching Error Lookup Class
//========================================================================

class LZECS {
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
			"SELECT * FROM lzecs" .
			" WHERE name = '{$name}' LIMIT 1",
			$this->dbLink, $_SERVER['PHP_SELF']
		);

		if(! num_rows($result) > 0)
			return false;

		$data = array();
		$row  = fetch_array($result, "ASSOC");

		array_push($data, $name);
		array_push($data, $row['preg_msg']);
		array_push($data, $row['explanation']);
		array_push($data, $row['action']);
		array_push($data, $row['si']);
		array_push($data, $row['psr']);
		array_push($data, $row['suppress']);
		array_push($data, $row['trig_amt']);
		array_push($data, $row['trig_win']);
		array_push($data, $row['vendor']);
		array_push($data, $row['type']);
		array_push($data, $row['class']);
		array_push($data, $row['lastupdate']);
		$this->cacheStore($name, $data);

		return $data;
	}

	//------------------------------------------------------------------------
	// Extract the error name from the message string
	//------------------------------------------------------------------------
	private function extractName($message) {
        $preges = array();
		$result = perform_query(
			"SELECT preg_name FROM lzecs",
			$this->dbLink, $_SERVER['PHP_SELF']
		);
        while($row = fetch_array($result)) { 
            $preges[] = $row['preg_name'];
        }
        foreach($preges as $preg) {
            preg_match_all("/$preg/", $message, $matches);
        }
        if(! isset($matches[1][0]))
            return "";

		// Modified below for http://code.google.com/p/php-syslog-ng/issues/detail?id=43 
		// $name = $matches[1][0];
		$name = addcslashes($matches[1][0], '\'');

		return $name;
	}

	//------------------------------------------------------------------------
	// Fetch a cached LZECS entry
	//------------------------------------------------------------------------
	private function cacheFetch($name) {
		return isset($this->cache[$name]) ? $this->cache[$name] : false;
	}

	//------------------------------------------------------------------------
	// Store a LZECS entry in the cache
	//------------------------------------------------------------------------
	private function cacheStore($name, $data) {
		$this->cache[$name] = $data;
	}
}
?>
