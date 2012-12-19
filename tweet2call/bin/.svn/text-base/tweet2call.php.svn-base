#!/usr/bin/php
<?
/*
	Tweet2Call v0.4
	
	Tweet2Call is a simple PHP script, run as a daemon, that
	polls a Twitter account for messages in the form of ""
	and schedules a call.
	
	Installation: Just add it to your crontab
	
	Author: James Finstrom <jfinstrom@rhinoequipment.com>
    Code Review/Cleanup: Bryce Chidester	
   
	LICENCE:
    Redistribution and use in source and binary forms, with or without
    modification, are permitted provided that the following conditions are
    met:
       
     * Redistributions of source code must retain the above copyright
       notice, this list of conditions and the following disclaimer.
     * Redistributions in binary form must reproduce the above
       copyright notice, this list of conditions and the following disclaimer
       in the documentation and/or other materials provided with the
       distribution.
     * Neither the name of the  nor the names of its
       contributors may be used to endorse or promote products derived from
       this software without specific prior written permission.
     
     THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
     "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
     LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
     A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
     OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
     SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
     LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
     DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
     THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
     (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
     OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

*/
$ampconfraw=trim(file_get_contents('/etc/amportal.conf'));
$ampconfraw = preg_replace('/#.+/', '', $ampconfraw);
preg_match_all('/(.+)\=(.+)/', $ampconfraw, $ampconfs);
$settings = array_combine($ampconfs[1], $ampconfs[2]);
$dbServer = 'localhost';
$dbUser = $settings[AMPDBUSER];
$dbPass = $settings[AMPDBPASS];
$database = $settings[AMPDBNAME];
$con = mysql_connect($dbServer, $dbUser, $dbPass);
if (!$con)
  {
  die('Could not connect: ' . mysql_error());
  }

$db_selected = mysql_select_db($database ,$con);
$sql = "SELECT * from tweet2call_settings";
$result = mysql_query($sql,$con);
$db_settings = mysql_fetch_assoc($result);
$sql2 = "SELECT department from tweet2call_departments";
$result2 = mysql_query($sql2,$con);
while($row = mysql_fetch_array($result2, MYSQL_ASSOC))
{
$depart[]=$row['department'];
}
$depts= implode("|", $depart);
mysql_close($con);
// User Settings
define('DEBUG', FALSE);
define('DEPARTMENTS', $depts);			// List of available Asterisk queues, separated by |
define('BLACKLIST', $db_settings['blacklist']);					// Blacklist of numbers NOT to call, separated by |
define('AST_OUTGOING_DIR', $settings[ASTSPOOLDIR].'/outgoing');	// The Asterisk outgoing call spool directory
define('TEMP_DIR', "/tmp");								// Where to store temporary files
define('AST_TRUNK', $db_settings['trunk'] . "/##");					// Asterisk trunk pattern. '##' is replaced by the phone number
define('TWITTER_USER', $db_settings['twitid']);								// Twitter Username
define('TWITTER_PASS', $db_settings['twitpass']);								// Twitter Password
define('DAEMON', 0);										// Whether to run in daemon mode
define('DAEMON_POLLTIME', 1);								// How frequently for the daemon to poll, in minutes
define('LOG_FILE', '/var/log/asterisk/T2C_log');
// Call File Template - Caution
//   ## - Phone number to dial
//   %% - Department
$CALLFILE = "";
$CALLFILE .= "Channel: ".AST_TRUNK."\n";
$CALLFILE .= "MaxRetries: 3\n";
$CALLFILE .= "RetryTime: 60\n";
$CALLFILE .= "WaitTime: 30\n";
$CALLFILE .= "Context: app-tweet2call\n";
$CALLFILE .= "Extension: %%\n";
$CALLFILE .= "Priority: 1\n";
// Stop User Settings

// Internal Variables - Don't Touch These!
define('TWITTER_URL', "http://twitter.com/direct_messages.json");
define('TEMP_LASTTWEET', ''.TEMP_DIR.'/T2C_lastTweet');
define('TEMP_CALL', ''.TEMP_DIR.'/T2C_callFile');

//for Logging
function logToFile($filename, $msg)
{ 
	$fd = fopen($filename, "a");
	$str = "[" . date("Y/m/d h:i:s", mktime()) . "] " . $msg;	
	fwrite($fd, $str . "\n");
	fclose($fd);
}


//PHP < 5.2.x does NOT have json_decode :( fix taken from:
// http://drupal.org/node/392978

if ( !function_exists('json_decode') ){
    function json_decode($content, $assoc=false){
                require_once 'json/JSON.php';
                if ( $assoc ){
                    $json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
        } else {
                    $json = new Services_JSON;
                }
        return $json->decode($content);
    }
}

if ( !function_exists('json_encode') ){
    function json_encode($content){
                require_once 'Services/JSON.php';
                $json = new Services_JSON;
              
        return $json->encode($content);
    }
}



// Function to return an array of the newest direct messages
function getDMs()
{
	$dm=array();
	logToFile(LOG_FILE, "Start DM Polling");
	// Retrieve the time of the last tweet
	$last=@trim(file_get_contents(TEMP_LASTTWEET));
	//$last = "109939390";	//XXX
	
	// Fetch all new DM's
	$tw = curl_init();
	curl_setopt($tw, CURLOPT_URL, TWITTER_URL.($last ? "?since_id=$last" : ""));
	curl_setopt($tw, CURLOPT_RETURNTRANSFER, TRUE );
	curl_setopt($tw, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
	curl_setopt($tw, CURLOPT_USERPWD, TWITTER_USER.':'.TWITTER_PASS );
	$json = curl_exec($tw);
	$info = curl_getinfo($tw);
	if(!curl_errno($tw) && $info['http_code'] == '200'){
	curl_close($tw);
	// Decode the returned data, and build an array of tweets
	$dms=json_decode($json, true);
	foreach ($dms as $key => $value)
	{
		$dm[$value['id']] = array('sender' => $value['sender_screen_name'],
								'msg' => $value['text']);
		if($last <= $value['id'])
			$last = $value['id']; 
	}
	
	// Store the time of the last tweet
	file_put_contents(TEMP_LASTTWEET, $last);
		logToFile(LOG_FILE, "Polling Finished Last DM pulled was $last");
	/* XXX
	return array(31854897 => array('sender' => 'god', 'msg' => 'Hello. Please help. I need support. Please call me at 4802253448'),
				 31852298 => array('sender' => 'satan', 'msg' => 'Billing me at 9119531111'),
				);
	*/
	
	// Return said array of DM's
	return $dm;
}else{
logToFile(LOG_FILE, "Curl seems to have Failed! Derver retutned $info[http_code] Curl Error curl_errno($tw)");
(DEBUG?var_dump($info):'');
(DEBUG?curl_errno($tw):'');
curl_close($tw);
}
}

// Main loop...
while(TRUE)
{
	// Refresh the DM information
	if($data=getDMs())
	{
		// Process all results
		foreach($data as $key => $value)
		{
			// Extract the phone number in areacode, exchange, LSB pieces
			//$string="have SuPport call 411-555-1212";	//XXX
			preg_match('/(?:1(?:[. -])?)?(?:\((?=\d{3}\)))?([2-9]\d{2})(?:(?<=\(\d{3})\))? ?(?:(?<=\d{3})[.-])?([2-9]\d{2})[. -]?(\d{4})(?: (?i:ext)\.? ?(\d{1,5}))?$/',
						$value['msg'], $matches);
			list($null, $areacode, $exchange, $lsb)=$matches;
			$phone = $areacode.$exchange.$lsb;	// Stitch the pieces back into one normalized number
			(DEBUG?var_dump($key, $value, $matches):'');
			
			// Now check areacode against the blacklist
			if(preg_match('/^('.BLACKLIST.')/i', $phone))
			{
				echo "Number '$phone' (from '@{$value['sender']}') in the blacklist! Ignoring.\n";
				logToFile(LOG_FILE, "Number '$phone' (from '@{$value['sender']}') in the blacklist! Ignoring.");
				continue;
			}
			
			// Number is safe
			preg_match('/('.DEPARTMENTS.')/i', $value['msg'], $matches);
			$dept = strtolower($matches[0]);
			
			// We now have the department and phone number, generate the call
			if ($dept && $phone)
			{
		        logToFile(LOG_FILE, "Generating call file for $dept to $phone sent by $value[sender]");
				// Build the .call file from the template, fill in the blanks
				$thisCall=$CALLFILE;
				$thisCall=str_replace('##', $phone, $thisCall);
				$thisCall=str_replace('%%', $dept, $thisCall);
				// Save the temporary file
				file_put_contents(TEMP_CALL, $thisCall);
				//set permissions to allow asterisk to read/delete
				chmod(TEMP_CALL, 0666);
				// Move it to the Asterisk Spool directory
				rename(TEMP_CALL, AST_OUTGOING_DIR.'/'.$value['sender'].'.'.rand(0, 15));
			}
		}
	} else	// $data was null, no DMs returned
		echo "No New DM's\n";
		logToFile(LOG_FILE, "NO NEW DM's");
	
	// If we're not a daemon, then go ahead and exit.
	if(!DAEMON)
		break;
	
	// Wait the allotted polltime before polling again
	sleep(DAEMON_POLLTIME*60);	// Minutes * 60 = seconds
}
exit(0);
?>

