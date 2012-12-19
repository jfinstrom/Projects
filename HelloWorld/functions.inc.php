<?php
/* $Id$ */
class HelloWorld_conf {
	function get_filename() {
			$files = array(
				'extensions_additional.conf'
			);
			return $files;
	}//get_filename()
		//This function is called for every file defined in 'get_filename()' function above
	function generateConf($file) {
        global $version,$amp_conf,$astman;
        foreach  ($this->get_filename() as $f){
			if(!file_exists($amp_conf['ASTETCDIR'] . "/$f")) {
				touch($amp_conf['ASTETCDIR'] . "/$f");
			}//if
		}//foreach
		switch($file) {
			case extensions_additional.conf:
				return $this->generate_extensions_conf($version);
                break;
		}//switch
	}//generateConf
	function generate_extensions_conf($ast_version) {
		global $ext
		//create a dialplan
		//www.freepbx.org/trac/wiki/ApiExtensions
		$id = 'app-HelloWorld';
		$dial = '*42';
		$ext->add($id, $dial, '', new ext_answer(''));
		$ext->add($id, $dial, '', new ext_playback('hello-world'));
		$ext->add($id, $dial, '', new ext_macro('hangupcall'));
		return $ext->generateConf();
	}//generate_extensions_conf()
}//class HelloWorld

/*
 * If you include the ending php closing tag 
 * please make sure there is/are no newlines after it as this can cause issues in FreePBX
 */
?>
