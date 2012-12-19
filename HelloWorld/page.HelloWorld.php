<?
//Check if user is "logged in"
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
//Handling form stuff....
isset($_REQUEST['action'])?$action = $_REQUEST['action']:$action='';
//the item we are currently displaying
isset($_REQUEST['itemid'])?$itemid=$db->escapeSimple($_REQUEST['itemid']):$itemid='';

switch ($action) {
	case "add":
		needreload();
	break;
	case "delete":
		needreload();
		redirect_standard();
	break;
	case "edit": 
		needreload();
		redirect_standard();
	break;
echo "<h2>"._("Hello World")."</h2>";

}

?>
