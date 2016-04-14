<?php
	error_reporting(3);
	import_request_variables("gpc");
	require_once("../common/necessary.php");
	require_once(in_parent_path("/common/adminlib.php"));
	require_once(in_parent_path("common/sqlschema_types.php"));

	require("admin_dblogin.php");
	$login_type = LOGIN_DBADMIN;
		
	require(in_parent_path("/common/upload_table_funk.php"));

	if ($edAction == "Upload") {
		if (upload_db_table($mysql, $HTTP_POST_FILES["upload"]["tmp_name"], $datapage_name)) {
			$action_msg .= "<B>Your upload of backup data to database '$database_name' appears to have been successful</B>";
		} else {
			errorpage("Upload client database error");
		}
	} 
	standard_page_top("Upload Databases from local text files", "../style/default.css", "page-noframe", "../images/title/upload_database_backup.gif", 560, 72, "Upload table data", "../common/necessary.js");
	br("all");
	echo $action_msg;
	
	if (file_exists("../$schema_base_directory/$schema_idx_name")) {
		$uploaded_schema = uncache_variable("../$schema_base_directory/$schema_idx_name");
	}
	if (!$uploaded_schema) {
		$uploaded_schema = array();
	}
?>
<p>
This option is used principally for installing backups, or moving the site.
</p>
<?php
	require("admin_nav.php");
	reset($uploaded_schema);
	while (list($key,$val) = each($uploaded_schema)) {
		$unc = uncache_variable("../$schema_base_directory/$key"."_tables.ser");
		if ($unc) {
			reset($unc);
			while (list($ukey, $uval) = each($unc)) {
				div("short-form");
				upload_form("uploadDataForm", "upload_table.php", $uval->name, "a local textfile", "edAction", "Upload", "blackvinyl",
					"Upload a text file of data into the $key database", "upload", "datapage_name");
				div();
				br();
				br();
			}
		}
  	}
	div("short-form");
	upload_form("uploadDataForm", "upload_table.php", people, "a local textfile", "edAction", "Upload", "blackvinyl",
					"Upload a text file of data into the people database", "upload", "datapage_name");
	div();
	standard_page_bottom();
?>
