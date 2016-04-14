<?php
	import_request_variables("gpc");
	error_reporting(3);

	if (!isset($action_msg))
		$action_msg = "";

	require_once("common/necessary.php");
	require_once("login_chk.php");

	if (!isset($login_type)) {
		header("Location: logon.php?login_msg=Invalid+login...&login_destination=clear_table.php");
		exit();
	}
	if ($login_type < LOGIN_DBADMIN) {
		header("Location: logon.php?login_msg=Database+Admin+login+required...&login_destination=clear_table.php");
		exit();
	}
	
	require_once("../common/adminlib.php");
	require_once("../common/sqlschema_types.php");
	
function drop_table($mysql, $table)
{
	global	$action_msg;
	global	$schema_base;
	
	$action_msg .= "Table to go is '$table'...<br>";
	$query = "drop table if exists $table";
	$result = mysql_query($query, $mysql);
	return $result;
}
	$action_msg = "";
	if ($edAction == "Drop") {
		if (!drop_table($mysql, $datapage_name)) {
			errorpage("Drop $datapage_name table error".mysql_error());
		}
		$action_msg .= "Looks like you've dropped the $datapage_name table ok";
// now delete from cache master if its there
		if (file_exists("$schema_base/$schema_master")) {
			$uploaded_schema = uncache_variable("$schema_base_directory/$schema_idx_name");
		}
		if (!$uploaded_schema) {
			$uploaded_schema = array();
		}
		unset($uploaded_schema[$datapage_name]);
		
		if (cache_variable($uploaded_schema, "$schema_base_directory/$schema_idx_name")) {
		} else {
		}
	}
	mmtc_page_top(
		"admin",
		"MMTC: Site Database Upload",
		"page-main",
		NULL,NULL,
		in_parent_path("/common/necessary.js"));
	div('wide-margin');
	echo $action_msg;
	if (file_exists("$schema_base_directory/$schema_idx_name")) {
		$uploaded_schema = uncache_variable("$schema_base_directory/$schema_idx_name");
	}
	if (!$uploaded_schema) {
		$uploaded_schema = array();
	}
?>
<P>
This option is used principally to remove badly corrupted tables so they can be regenerated, and removing
older versions of tables during site upgrades.
<P>This process loses all data from the tables. Please back them up first, or satisfy yourself that you
don't need the current data.</p>
<?php
	table_header(0,0);
	reset($uploaded_schema);
	while (list($key,$val) = each($uploaded_schema)) {
		$unc = uncache_variable("$schema_base_directory/$key"."_tables.ser");
		if ($unc) {
			reset($unc);
			while (list($ukey, $uval) = each($unc)) {
				echo "<tr><td>";
				drop_table_form("drop$ukey"."Form", "clear_table.php", $uval->name, "edAction", "Drop", "blackvinyl");
				echo "</td></tr>";
			}
		}
  	}
  	table_tail();
  	div();
	mmtc_page_bottom();
?>
