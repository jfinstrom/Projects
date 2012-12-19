<?php
/* $Id$ */
//
/*
 *      install.php
 *      
 *      Copyright 2009 James Finstrom <jfinstrom@RhinoEquipment.com>
 *      
 *      This program is free software; you can redistribute it and/or modify
 *      it under the terms of the GNU General Public License as published by
 *      the Free Software Foundation; either version 2 of the License, or
 *      (at your option) any later version.
 *      
 *      This program is distributed in the hope that it will be useful,
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *      GNU General Public License for more details.
 *      
 *      You should have received a copy of the GNU General Public License
 *      along with this program; if not, write to the Free Software
 *      Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 *      MA 02110-1301, USA.
 */
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

$sql = "CREATE TABLE IF NOT EXISTS tweet2call_settings (
twitid varchar(32) NOT NULL default '',
twitpass varchar(32) NOT NULL default '',
polltime varchar(32) NOT NULL default '',
blacklist varchar(512) NOT NULL default '',
trunk varchar(32) NOT NULL default '',
lasttweet varchar(32) NOT NULL default '',
PRIMARY KEY (twitid)
);";

$check = $db->query($sql);
if (DB::IsError($check)) {
        die_freepbx( "Can not create `tweet2call` table: " . $check->getMessage() .  "\n");
}

$sql = "CREATE TABLE IF NOT EXISTS tweet2call_departments (
department varchar(32) NOT NULL default '',
queue varchar(32) NOT NULL default '',
weight varchar(32) NOT NULL default '',
PRIMARY KEY (department));";

$check = $db->query($sql);
if (DB::IsError($check)) {
        die_freepbx( "Can not create `tweet2call` table: " . $check->getMessage() .  "\n");
}

?>
