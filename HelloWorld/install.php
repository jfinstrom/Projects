<?php
/* $Id$ */
global $db;
global $amp_conf;

if (! function_exists("out")) {
	function out($text) {
		echo $text."<br />";
	}
}

if (! function_exists("outn")) {
	function outn($text) {
		echo $text;
	}
}

$sql = "CREATE TABLE IF NOT EXISTS HelloWorld_settings (
hwid varchar(32) NOT NULL default '',
hwset varchar(32) NOT NULL default '',
PRIMARY KEY (hwid)
);";

$check = $db->query($sql);
if (DB::IsError($check)) {
        die_freepbx( "Can not create `HelloWorld` table: " . $check->getMessage() .  "\n");
}

?>
