<?
global $db;

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
//Johnny Drop Tables
out("Dropping all relevant tables");
$sql = "DROP TABLE `HelloWorld_settings`";
$result = $db->query($sql);

?>
