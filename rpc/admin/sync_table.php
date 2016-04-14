<?php
	import_request_variables("gpc");
	require("../common/necessary.php");

	require("admin_dblogin.php");
	
	function sqlschema_list_to_array(&$field)
	{
		return array_map("rawurldecode", explode("&", $field));
	}
	
	$login_type = LOGIN_DBADMIN;
	
	$action_msg = "";
	if (!$from_table) {
		errorpage("Sync_table: No source table given");
	}
	if (!$to_table) {
		errorpage("Sync_table: No source table given");
	}
	if (!$sync_field || !is_array($sync_field) || count($sync_field) < 1) {
		errorpage("Sync_table: No source fields given");
	}
	if ($syncAction == "Synchronize") {
		$src_table = explode(",",$from_table);
		reset($src_table);
		while (list($tabk, $tabv) = each($src_table)) {
	 	 	$from_fields = list_string($sync_field);
		  	$from_query = "select $from_fields from $tabv";
			$from_result = mysql_query($from_query);
			$from_nitems = 0;
			if ($from_result > 0) {
				$from_nitems = mysql_num_rows($from_result);
				if ($from_nitems > 0) {
					$action_msg .= "<b>Synchronization from '$tabv' to '$to_table'<br>";
					$i = 0;
					while ($i < $from_nitems) {
						$row = mysql_fetch_object($from_result);
						
						reset($sync_field);
						$maxlistind = 0;
						while (list($k, $v) = each($sync_field)) {
							$sync_val_arr[$v] = sqlschema_list_to_array($row->$v);
							if (count($sync_val_arr[$v]) > $maxlen) {
								$maxlistind = count($sync_val_arr[$v]);
							}
						}
						for ($listind=0; $listind<$maxlistind; $listind++) {
							$cond = "";
							reset($sync_field);
							while (list($k, $v) = each($sync_field)) {
								if ($v) {
									$cond = eq_clause($cond, $v, $sync_val_arr[$v][$listind]);
								}
							}
							$isinto_query = "select * from $to_table";
							if ($cond) {
								$isinto_query .= " where $cond";
							}
							$isinto_result = mysql_query($isinto_query);
							if ($isinto_result <= 0) {
								errorpage("Mysql error, checking for presence in $to_table, query \"$isinto_query\": ".mysql_error());
							}
							$isinto_nitems = mysql_num_rows($isinto_result);
							if ($isinto_nitems == 0) { // ie we only insert if there is no match
								$set = "";
								reset($sync_field);
								while (list($k, $v) = each($sync_field)) {
									$set=set_item($set, $v, $sync_val_arr[$v][$listind]);
								}
							
								if ($set) {
									$query = "insert into $to_table set $set";
									$insert_result = mysql_query($query, $mysql);
									if (!$insert_result) {
										errorpage("mysql error on insert into destination $to_table: ".mysql_error());
									}
									reset($sync_field);
									$mess = "";
									while (list($sfk,$sfv)=each($sync_field)) {
										$mess .= "&nbsp;$sfv <= ".$sync_val_arr[$sfv][$listind].",";
									}
									$action_msg .= "$mess<br>";
								}
							}
						}
						$i++;
					}
					$action_msg .= "<br> appears to be successful</b>";
				} else {
					$action_msg .= "No data found in table '$tabv'<br>";
				}
				mysql_free_result($from_result);
			} else {
				errorpage("Database error searching source table $tabv: ".mysql_error());
			}
		}
	}
	
	standard_page_top(
		"Synchronize $to_table Table",
		"../style/default.css",
		"page-noframe",
		"../images/title/sync_db.gif", 560, 72,
		"Synchronize table $to_table",
		"../common/necessary.js");
	br();
	if ($action_msg)
		echo $action_msg;
?>
<p>This option will attempt to extract fields in the RPC publication databases
not listed in the RPC support databases, and add them to the latter.</p>
<?php
	require("admin_nav.php");
	div("short-form");
	echo "<P>Synchronize fields ";
	reset($sync_field);
	list($k, $v) = each($sync_field);
	echo $v;
	while (list($k, $v) = each($sync_field)) {
		echo ", ",$v;
	}
	echo " from the $from_table table(s) to the $to_table table.</p>\n";
	echo "<form  action=\"sync_table.php\" $form_action name=\"syncForm\" onSubmit= \"return confirm('are you sure you want to do this?');\" METHOD=POST>\n";
	hidden_field("from_table", $from_table);
	hidden_field("to_table", $to_table);
	hidden_field("schema_list_src", $schema_list_src);
	reset($sync_field);
	while (list($k, $v) = each($sync_field)) {
		hidden_field("sync_field[]", $v);
	}
	echo "<center>";
	div("edit-controls");
	echo "<table><tr>";
	echo "<td><b>"; echo "<INPUT TYPE=SUBMIT NAME='syncAction' VALUE='Synchronize' SIZE=2>"; echo "</b></td>";
	echo "</tr></table>";
 	div();
	echo "</center>";
	echo "</form>\n";
	div();
	
	standard_page_bottom();
?>