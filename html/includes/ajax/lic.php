<?php
	class retrieveLicense {

		public $path					= '';
		public $licserv_filename		= null;

		public $publicIPAddress			= null;
		public $networkMACAddress		= array();
		public $networkNICAddress		= array();
		public $liveNetworkConnection	= false;

		public $networkInformation		= null;
		public $ifconfigLocation		= '/sbin/ifconfig -a';
		
		// If you would like to see errors and/or for debugging purposes turn $logging to = true
		public $logging					= true;
		public $logFileLocation			= 'license.log';
		public $logFilePointer			= null;
		public $logEntries				= 0;
		
		// At http://licserv.logzilla.pro/files/ not http://licserv.gdd.net/files/
		public $licenseServerURL		= 'http://licserv.logzilla.pro/files/';
		public $licenseDestFileLocation = '';
		public $licenseDestFileName		= null;
		// set this to the full path of the folder were the licence is to be saved, must be writable 
		// by the process running php
		public $licenseFileLocation		= '/var/www/logzilla/html/';
		public $licenseFileName			= 'license.txt';
		
		public $params					= array();

		public $errors					= array();
		public $output					= null;

		function __construct() {
		
			$this->path = dirname(__FILE__);

			// Open Log File for appending if logging enabled.
			if ($this->logging){
				$this->logFileLocation = $this->path . '/' . $this->logFileLocation;
				if (is_writable($this->logFileLocation)){
					$this->logFilePointer = fopen($this->logFileLocation, 'w');
					$this->addToLog('Constructing retrieveLicense Class');
				}else{
					$this->reportError('Cant open (' . $this->logFileLocation. ') for writing, please check file permissions.');
				}
			}

			// Check alive connection
			if ($this->checkWeAreAlive()){
				//$this->getPublicIPAddress(); not required plus adds 1seccond to execution time
				$this->getNICDetails();
				$this->createFileName();
			} else {
					$this->reportError('Check for internet access failed.');
            }

		}

		function __destruct() {
			$this->addToLog('Deconstructing retrieveLicense Class');
			// Close log file
			if ($this->logging){
				fclose($this->logFilePointer);
			}
		}

		private function checkWeAreAlive(){
			if (!$sock = @fsockopen('www.google.com', 80, $num, $error, 5)){
				return "Success";
			}else{
				$this->liveNetworkConnection = true;
				return "Error";
			}
		}

		private function getPublicIPAddress(){
			$foundIP = file_get_contents("http://checkip.dyndns.org");
			preg_match('/\d{1,3}.\d{1,3}.\d{1,3}.\d{1,3}/', $foundIP, $this->publicIPAddress);
		} 

		private function getNICDetails(){			
			$this->networkInformation = shell_exec($this->ifconfigLocation);
			$this->networkInformationLines = split("\n", $this->networkInformation);
			$i = 0;
			foreach($this->networkInformationLines as $networkLine){
				// we will populate the networkNickAddress array with all NIC values,
				// atm we will only be using the first entry - this provides future expansion if needed.
				if (strpos( $networkLine, 'HWaddr' ) !== false ) { 
					preg_match('/((?:[0-9a-f]{2}[:-]){5}[0-9a-f]{2})/i', $networkLine, $macAddress);
					$this->networkNICAddress[$i]['mac'] = $macAddress[0];
				}
				if (strpos( $networkLine, 'inet addr:' ) !== false ) { 
					preg_match('/\s*inet addr:([\d.]+)/', $networkLine, $macAddress);
					$this->networkNICAddress[$i]['ip'] = $macAddress[1];
					$i++;
				}
			}
			unset($this->networkInformation);
			unset($this->networkInformationLines);
			unset($i);
			return true;
		}

		private function createFileName(){
			$n = 0;
			foreach ($this->networkNICAddress as $mac){
				if (!is_null($mac['ip'])){
					$this->licserv_filename[$n] = @preg_replace('/[^a-zA-Z0-9\s]/', '', $mac['ip']);
					$this->licserv_filename[$n] .= @preg_replace('/[^a-zA-Z0-9\s]/', '', $mac['mac']);
					$this->licserv_filename[$n] = $this->licenseServerURL . md5($this->licserv_filename[$n]) . '.txt';
            	}
            	$n++;
            }
		}
		
		private function replace_newline($string) {
			return (string)str_replace(array("\r", "\r\n", "\n"), '', $string);
		}
		
		private function addToLog($logText){
			if ($this->logging){
				if (fwrite($this->logFilePointer, date("d-m-Y H:i:s\t") . microtime(true) . "\t" . $logText . "\n")){
					$this->logEntries++;
				}else{
					$this->reportError('Failed to write to log file ('.$this->logFileLocation.')');
				}
			}
		}
		
		private function reportError($errorMsg){
			if ($this->logging){
				$this->addToLog($errorMsg);
			}
			$this->errors[] = $errorMsg;
		}
		
		private function cleanAjax($elem) { 
			if(!is_array($elem)){ 
				$elem = htmlentities($elem,ENT_QUOTES,"UTF-8"); 
			}else{ 
				foreach ($elem as $key => $value){ 
					$elem[$key] = $this->cleanAjax($value);
				}
			}
			return $elem; 
		}
		
		private function saveLicense($content){
		
			if (is_null($content)){
				$this->reportError('[Error] License Server returned no data.');
				return false;
			}
			
			//if (is_writable($this->licenseFileLocation)){
                $content = preg_replace('/(\r\n|\r|\n)/s',"\n",$content);
				$licenseFilePointer = fopen($this->licenseFileLocation . $this->licenseFileName, 'w');
				$licenseFileWriteSuccess = fwrite($licenseFilePointer, $content);
				fclose($licenseFilePointer);
				
				if ($licenseFileWriteSuccess){ 
					return true;
				}else{
					$this->reportError('[Error] The license file ( '.$this->licenseFileLocation . $this->licenseFileName . ' ) could not be written to. Check file and/or directory permissions');
					return false;
				}
			//}else{
				//$this->reportError('The license destination ( '.$this->licenseFileLocation.' ) is not writable.');
				//return false;
			//}
			
			/*
			
			$basePath = dirname( __FILE__ );
			require_once ($basePath . "/../common_funcs.php");
			
			$dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
			session_start();
			$text = $content;
			$sql = "SELECT value FROM settings where name='PATH_BASE'";
			$result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
			if(num_rows($result)==0){
				$this->reportError("ERROR: Unable to determine installed path<br />Please check your database setting for PATH_BASE");
				return false;
			} else {
				$line = fetch_array($result);
				$path = $line[0];
				$cmd = "sudo $path/scripts/licadd.pl '$text'";
				exec($cmd, $out);
				if ($out[0] == 1) {
					return true;
				} else {
					$this->reportError($out[0]);
				}
			}
			
			*/
		}
		
		public function debugMe(){
			return $this;
		}
		
		public function isError(){
			if (count($this->errors) != 0){
				return true;
			}else{
				return false;
			}
		}
		
		public function checkPost(){
			$this->addToLog('Checking Ajax Values');
			
			$this->params['_get'] = $this->cleanAjax($_GET);
			$this->params['_post'] = $this->cleanAjax($_POST);
						
			switch ($this->params['_get']['exe']){
			
				case 'checklive':
					$this->addToLog('[Success] Live connection found, proceeding...');
					if ($this->liveNetworkConnection){
						$this->output = '[Success] Live connection found, proceeding...<br>';
					}else{
						$this->reportError('[Error] Could not connect to internet');
						$this->output = '[Error] Could not connect to internet<br>';
					}
				break;
				
				case 'startinstall':
					$this->addToLog('Returning Success or fail on Auto Install');
									
                    $license = file_get_contents( $this->licserv_filename[0] );
					if (!$license){
						// There was an error opening the socket, manual install.
						$this->reportError('[Error] There was an error retrieving the license from the server.');
						$this->output = '[Error] There was an error retrieving the license from the server.';
						
					}else{
						if ($this->saveLicense($license)){
						$this->output = '[Success] License file written.';
						}else{
							// $this->output = '0';
						}
					}					
				
				break;
				
				case 'uploadfrominput':
					$this->addToLog('Saving License from user file input');
					if ($this->params['_post']['licenseData']){
						if ($this->saveLicense($this->params['_post']['licenseData'])){
							// $this->output = '1';
						}else{
							// $this->output = '0';
						}
					}else{
						$this->reportError('User input was empty');
						// $this->output = '0';
					}
				
				break;
			
			}
            /*
			header('Content-Type: text/xml');
			echo '<?xml version="1.0" encoding="utf-8" ?>';
			echo '<response>';
            */
			if (count($this->errors) >= 1){
				//echo '<error>';
				foreach ($this->errors as $errorMsg){
					//echo "<errorMessage>$errorMsg</errorMessage>";
                    echo "$errorMsg<br>";
				}
				//echo '</error>';
			}
			//echo '<result>';
			echo $this->output;
			//echo '</result>';
			//echo '</response>';
		}

	}

	$networkRequest = new retrieveLicense();
	$networkRequest->checkPost();

?>
