<?
//Check if user is "logged in"
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

//Array to load settings from page, and defaults
$get_vars = array (
	'action'	=> '',
	'id'		=> '',
	'field1'	=> '',
	'field2'	=> ''
);

foreach ($get_vars as $k => $v) {
	$vars[$k] = isset($_REQUEST[$k]) ? $_REQUEST[$k] : $v;
}

//action switch
switch ($action) {
	case "add":
		//ADD THE RECORD
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

//rnav
echo load_view(dirname(__FILE__) . '/views/rnav.php');

//view switch
switch ($action) {
	case 'delete':
	default:
		echo load_view(dirname(__FILE__) . '/views/landing.php')
		break;
}
}

?>
