<?php
/* $Id$ */
//
/*
 *      functions.inc.php
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
function tweet2call_get_config($engine) {
    global $db;
	global $ext;
	global $version;
	global $astman;
		switch($engine) {
			case "asterisk":
			$id = "app-tweet2call";				
			$results = sql("SELECT * FROM `tweet2call_departments`;","getAll",DB_FETCHMODE_ASSOC);
			if (empty($results)) {
				return array();
			} else {
				foreach ($results as $result) {
				$exten = $result['queue'];
				$priority = $result['weight'];
				$department = $result['department'];	
				$ext->add($id , $department , '' , new ext_setvar("QUEUE_PRIO", $priority));
				$ext->add($id , $department , '' , new ext_goto('1', $exten, 'ext-queues'));
				}
			}
		}
}

function tweet2call_add_department($dept, $queue, $weight){
global $db;
$results = sql("INSERT INTO tweet2call_departments (department, queue, weight) VALUES ('$dept', '$queue', '$weight') ON DUPLICATE KEY UPDATE queue='$queue', weight = '$weight'");
needreload();
}

function tweet2call_del_department($dept){
global $db;
//$results = sql("DELETE FROM tweet2call_departments WHERE department = '$dept'");
    $sql= "DELETE FROM tweet2call_departments WHERE department = '$dept'";
    $result = $db->query($sql);
    if(DB::IsError($result)) {
        die_freepbx($result->getMessage().$sql);
    }
needreload();

}

function tweet2call_add_settings($twitid, $twitpass, $polltime, $blacklist, $trunk){
 global $db;
$results = sql("INSERT INTO tweet2call_settings (twitid, twitpass, polltime, blacklist, trunk) VALUES ('$twitid', '$twitpass', '$polltime', '$blacklist', '$trunk') ON DUPLICATE KEY UPDATE twitpass='$twitpass', polltime = '$polltime', blacklist='$blacklist', trunk = '$trunk'");
}

?>
