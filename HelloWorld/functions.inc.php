<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

/* $Id$ */
class HelloWorld_conf {
	var $_helloworldgeneral = array();
	function get_filename() {
			$files = array(
				'extensions_additional.conf'
			);
			return $files;
	}//get_filename()
		//This function is called for every file defined in 'get_filename()' function above
	function generateConf($file) {
        	global $version, $amp_conf, $astman;
        	foreach  ($this->get_filename() as $f){
			if(!file_exists($amp_conf['ASTETCDIR'] . "/$f")) {
				touch($amp_conf['ASTETCDIR'] . "/$f");
			}//if
		}//foreach
		switch($file) {
			case extensions_additional.conf:
				return $this->generate_helloworld_additional($version);
                break;
		}//switch
	}//generateConf
	//TODO: not
	function addHelloworldGeneral($key, $value) {
		$this->_helloworldgeneral[] = array('key' => $key, 'value' => $value);
	}//generate_extensions_conf()
	
	function generate_helloworld_additional($ast_version) {
	$output = '';

	if (isset($this->_helloworldgeneral) && is_array($this->_helloworldgeneral)) {
		foreach ($this->_helloworldgeneral as $values) {
			$output .= $values['key'] . '=' . $values['value'] . "\n";
		}
	}
	
	return $output;
	}

}//class HelloWorld

function helloworld_get_config($engien) {
	global $amp_conf, $db, $ext;
	
	switch ($engien) {
		case 'asterisk':
			//create a dialplan
			//www.freepbx.org/trac/wiki/ApiExtensions
			$id = 'app-HelloWorld';
			$dial = '*42';
			$ext->add($id, $dial, '', new ext_answer(''));
			$ext->add($id, $dial, '', new ext_playback('hello-world'));
			$ext->add($id, $dial, '', new ext_macro('hangupcall'));
			break;
	}
}
/*
 * If you include the ending php closing tag 
 * please make sure there is/are no newlines after it as this can cause issues in FreePBX
 */
?>
