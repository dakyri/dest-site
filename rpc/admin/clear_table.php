<?php
	import_request_variables("gpc");
	require_once("../common/necessary.php");
	require_once(in_parent_path("/common/adminlib.php"));
	require_once(in_parent_path("/common/sqlschema_types.php"));
	
	require("admin_dblogin.php");
	$login_type = LOGIN_DBADMIN;
	
function drop_table($mysql, $table)
{
	global	$action_msg;
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
			$uploaded_schema = uncache_variable("../$schema_base_directory/$schema_idx_name");
		}
		if (!$uploaded_schema) {
			$uploaded_schema = array();
		}
		unset($uploaded_schema[$datapage_name]);
		if (cache_variable($uploaded_schema, "../$schema_base_directory/$schema_idx_name")) {
		} else {
		}
	}
	standard_page_top("Drop tables from the site database", "../style/default.css", "page-noframe", "../images/title/clear_database.gif", 560, 72, "Drop table data", "../common/necessary.js");
	br("all");
	if ($action_msg) {
		echo "<font size=+0><B>$action_msg</b></font>";
	}
	if (file_exists("../$schema_base_directory/$schema_idx_name")) {
		$uploaded_schema = uncache_variable("../$schema_base_directory/$schema_idx_name");
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
	require("admin_nav.php");
	reset($uploaded_schema);
	while (list($key,$val) = each($uploaded_schema)) {
		$unc = uncache_variable("../$schema_base_directory/$key"."_tables.ser");
		if ($unc) {
			reset($unc);
			while (list($ukey, $uval) = each($unc)) {
				div("short-form");
				drop_table_form("drop$ukey"."Form", "clear_table.php", $uval->name, "edAction", "Drop", "blackvinyl");
				div();
				br();
				br();
			}
		}
  	}
	div("short-form");
	drop_table_form("dropPeopleForm", "clear_table.php", "people", "edAction", "Drop", "blackvinyl");
	div();
	br();
	br();
	standard_page_bottom();
?>
