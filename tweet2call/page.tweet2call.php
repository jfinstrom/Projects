<?php
/* $Id$ */
//
/*
 *      page.tweet2call.php
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
$tabindex = 0;
isset($_REQUEST['action'])?$action = $_REQUEST['action']:$action='';
isset($_REQUEST['id'])?$extdisplay = $_REQUEST['id']:$extdisplay='';
$dispnum = 'tweet2call';
$display='tweet2call';
$type = 'setup';
?>
</div> <!-- end content div -->

<script 
			type="text/javascript" 
			src="../../jquery-1.1.3.1.js">
		</script>
		<script type="text/javascript">
		$(function(){
			// start a counter for new row IDs
			// by setting it to the number
			// of existing rows
			var newRowNum = 0;
		
			// bind a click event to the "Add" link
			$('#addnew').click(function(){
				// increment the counter
				newRowNum += 1;
						
				// get the entire "Add" row --
				// "this" refers to the clicked element
				// and "parent" moves the selection up
				// to the parent node in the DOM
				var addRow = $(this).parent().parent();
				
				// copy the entire row from the DOM
				// with "clone"
				var newRow = addRow.clone();
				
				// set the values of the inputs
				// in the "Add" row to empty strings
				$('input', addRow).val('');
				
				// replace the HTML for the "Add" link 
				// with the new row number
				$('td:first-child', newRow).html(newRowNum);
				
				// insert a remove link in the last cell
				$('td:last-child', newRow).html('<a href="" class="remove">Remove<\/a>');
				
				// loop through the inputs in the new row
				// and update the ID and name attributes
				$('input', newRow).each(function(i){
					var newID = newRowNum;
					$(this).attr('id',this.id).attr('name',this.name);

				});
				
				// insert the new row into the table
				// "before" the Add row
				addRow.before(newRow);
				
				// add the remove function to the new row
				$('a.remove', newRow).click(function(){
					$(this).parent().parent().remove();
					return false;				
				});
			
				// prevent the default click
				return false;
			});
		});
		</script>

<div class="content">
<?php
	global $dispnum;
	switch ($action) {
	case "add":
	break;
	case "delete":
	break;
	case "update":
	if(isset($_POST['twuser']) && isset($_POST['twpass'])){
	tweet2call_add_settings($_POST['twuser'], $_POST['twpass'], $_POST['poll'], $_POST['blacklist'], $_POST['trunk']);
	}
	if(isset($_POST['del'])){
		
		foreach($_POST['del'] as $dept ){
			tweet2call_del_department($dept);
			}
		}
 
		if(!empty($_POST['dept']) && !empty($_POST['queue']) && !empty($_POST['weight'])){

	        foreach(array_keys($_POST['dept']) as $n){
			
						$dept =	strtolower($_POST['dept'][$n]);
						$queue = $_POST['queue'][$n];
						$weight = $_POST['weight'][$n];
			if( $dept != ""){
			tweet2call_add_department($dept, $queue, $weight);
		         }
			 }
			  
          }
	break;
}
 if (!empty($_POST['poll'])){
        function croncheck($var){
                if(preg_match('/tweet2call/', $var)){
                        return FALSE;
                        }else{ return TRUE;}
                }

        exec('crontab -l', $crons);
                $cronfile = '*/'.$_POST['poll'].' * * * * /var/lib/asterisk/bin/tweet2call.php'." \n";
        foreach(array_filter($crons, croncheck) as $cron){
        $cronfile .= $cron . "\n";
                }
        file_put_contents('/tmp/cronjobs', $cronfile);
        exec('crontab /tmp/cronjobs');
			}

	$results = sql("SELECT `extension`, `descr` FROM `queues_config`;","getAll",DB_FETCHMODE_ASSOC);
	if (!empty($results)) {
    $queueselect = '';
		foreach ($results as $result) {
	$queueselect .= '<option value ="'. $result[extension].'">'. $result[descr].'</option>';
}
}
	$results = sql("SELECT * FROM `tweet2call_settings` LIMIT 1;","getAll",DB_FETCHMODE_ASSOC);
	if (!empty($results)) {
extract($results[0], EXTR_PREFIX_ALL, "current");

//Array ( [twitid] => Piggy_phone [twitpass] => passwordizzle [polltime] => 1 [blacklist] => 911|411|011|900 [trunk] => ZAP/g0 [lasttweet] => ) 
}

?>

	<h2 id='title'><?php echo _("Tweet 2 Call") ?></h2></td>
<br/></br><hr>
<h3>Settings</h3>
<form autocomplete="off" name="edit" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<input type="hidden" name="display" value="<?php echo $display?>">
<input type="hidden" name="type" value="<?php echo $type?>">

<a href="#" class="info"><?php echo _("Twitter Username: ")?><span><?php echo _("Enter your username from twitter.com");?></span></a> <input type='text' name='twuser' value="<?php echo $current_twitid; ?>"><br/>
<a href="#" class="info"><?php echo _("Twitter Password: ")?><span><?php echo _("Enter your twitter.com password");?></span></a> <input type='password' name='twpass' value="<?php echo $current_twitpass; ?>"><br/>

<a href="#" class="info"><?php echo _("Outbund Trunk: ")?><span><?php echo _("The Trunk outbound calls will be made on.");?></span></a> <select name = 'trunk'>
<?php
$tresults = core_trunks_list();
//var_dump($tresults);
foreach ($tresults as $tresult) {
if ($tresilt[1] == $current_trunk ){
print('<option value="'.$tresult[1].'" SELECTED>'.$tresult[1].'</option>');
}else{
print('<option value="'.$tresult[1].'">'.$tresult[1].'</option>');	
	
}
}
?>
</select><br/>
<a href="#" class="info"><?php echo _("Blacklist: ")?><span><?php echo _("A | deliminated list of prefixes you dont want auto dilaed 011 international, 911, 411, 900 etc. ");?></span></a> <input type='text' name='blacklist' value="<?php echo $current_blacklist; ?>"><br/>
<a href="#" class="info"><?php echo _("Poll Time: ")?><span><?php echo _("How often we should check for new dm's in minutes. API Max is 100/hr per account");?></span></a> <input type='text' name='poll' value = "<?php echo $current_polltime; ?>"> (/min)<br/>
<a href="#" class="info"><?php echo _("Departments:")?><span><?php echo _("The department people will be requesting, the queue outbound calls will drop to for the given department and the weight or priority given to that call (lets it cut in line)");?></span></a><br/>

<?php
	$results = sql("SELECT * FROM `tweet2call_departments`;","getAll",DB_FETCHMODE_ASSOC);
	if (empty($results)) {
	  echo "No Current Departments";
	} else {
		print <<<HERE
<table id="tabdata0">
<thead>
<tr>
   <th><a href="#" class="info"><?php echo _("DEL")?><span><?php echo _("Check the box to delete the entry");?></span></a></th><th>Department</th><th>Queue</th><th>Weight</th>
</tr></thead><tbody>
HERE;
foreach ($results as $result) {
	echo '<tr>
	     <td><input type="checkbox" name = "del[]" value = "'.$result['department'].'"></td>
		 <td>'.$result['department'].'</td>
		 <td>'.$result['queue'].'</td>
		 <td>'.$result['weight'].'</td>
		 </tr>';
}
print("</tbody></table><br><br>");
}
?>

<table id="tabdata">
				<thead>
					<tr>
						<th>Row</th>
						<th>Department</th>
						<th>Queue</th>
						<th>Weight</th>

						<th></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><a id="addnew" href="">Add</a></td>
						<td><input id="dept" name="dept[]" type="text" /></td>
<!--						<td><input id="queue" name="queue[]" type="text" /></td> -->
						<td><select id='queue' name='queue[]'><?php echo $queueselect; ?></select></td>
						<td><input id="weight" name="weight[]" type="text" size="4" /></td>
						<td></td>
					</tr>
				</tbody>
</table>

<input type="hidden" name="action" value="update">
<input type='submit'>

</form>	
</body>
</html>
