<?php
	import_request_variables("gpc");
	error_reporting(3);

	if (!isset($action_msg))
		$action_msg = "";

	require_once("common/necessary.php");
	require_once("login_chk.php");

	if (!isset($login_type)) {
		header("Location: logon.php?login_msg=Invalid+login...&login_destination=upload_table.php");
		exit();
	}
	if ($login_type < LOGIN_DBADMIN) {
		header("Location: logon.php?login_msg=Database+Admin+login+required...&login_destination=upload_table.php");
		exit();
	}
		
	require_once("../common/adminlib.php");
	require_once("../common/sqlschema_types.php");
	require_once("../common/upload_table_funk.php");

	if ($edAction == "Upload") {
		if (upload_db_table($mysql, $HTTP_POST_FILES["upload"]["tmp_name"], $datapage_name, "temp/updb")) {
			$action_msg .= "<B>Your upload of backup data to database '$database_name' appears to have been successful</B>";
		} else {
			errorpage("Upload client database error");
		}
	} 
	
	mmtc_page_top(
		"admin",
		"MMTC: Site Database Upload",
		"page-main",
		NULL,NULL,
		"../common/necessary.js");
	echo $action_msg;
	
	if (file_exists("$schema_base_directory/$schema_idx_name")) {
		$uploaded_schema = uncache_variable("$schema_base_directory/$schema_idx_name");
	}
	if (!$uploaded_schema) {
		$uploaded_schema = array();
	}
?>
<p>
This option is used principally for installing backups, or moving the site.
</p>
<?php
	table_header(0,0);
	reset($uploaded_schema);
	while (list($key,$val) = each($uploaded_schema)) {
		$unc = uncache_variable("$schema_base_directory/$key"."_tables.ser");
		if ($unc) {
			reset($unc);
			while (list($ukey, $uval) = each($unc)) {
				echo "<tr><td>";
				upload_form("uploadDataForm", "upload_table.php", $uval->name, "a local textfile", "edAction", "Upload", "blackvinyl",
					"Upload a text file of data into the $key database", "upload", "datapage_name");
				echo "</td></tr>";
			}
		}
  	}
  	table_tail();
	mmtc_page_bottom();
?>
