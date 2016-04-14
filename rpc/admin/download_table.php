<?php
	import_request_variables("gpc");
	error_reporting(3);
	require_once("../common/necessary.php");
	require_once(in_parent_path("/common/adminlib.php"));
	require_once(in_parent_path("/common/sqlschema_types.php"));

	require("admin_dblogin.php");
	$login_type = LOGIN_DBADMIN;

	require(in_parent_path("/common/dump_table_funk.php"));

	if ($edAction == "Download") {
		$dbtxtname = tempnam("/tmp", "shop-");
		if (!($db_file = fopen($dbtxtname, "w+"))) {
			errorpage("Can't open temporary file '$dbtxtname' for output.<BR>");
		}
		switch($datapage_name) {
			case "people":

				if (!dump_db_table($mysql, $db_file, "people", "stnumber")) {
					errorpage("Download wierdass error");
				}
				break;
			default:
				if (!dump_db_table($mysql, $db_file, $datapage_name, "")) {
					errorpage("Download wierdass error");
				}
				break;
		}

		rewind($db_file);
		
		header("Content-Type: application/octet-stream");
		fpassthru($db_file);
		fclose($db_file);
		exit;
	}
	standard_page_top("Download databases from local text files", "../style/default.css", "page-noframe", "../images/title/make_database_backup.gif", 560, 72, "Download table data", "../common/necessary.js");
	br("all");
	
	if (file_exists("../$schema_base_directory/$schema_idx_name")) {
		$uploaded_schema = uncache_variable("../$schema_base_directory/$schema_idx_name");
	}
	if (!$uploaded_schema) {
		$uploaded_schema = array();
	}
?>
<P>This option is used principally for making backups, or moving the site.</p>
<?php
	require("admin_nav.php");
	reset($uploaded_schema);
	while (list($key,$val) = each($uploaded_schema)) {
		$unc = uncache_variable("../$schema_base_directory/$key"."_tables.ser");
		if ($unc) {
			reset($unc);
			while (list($ukey, $uval) = each($unc)) {
				$download_nm = "$uval->name-".date("d-m-y");
				div("short-form");
				download_form("download$uval->name"."Form", "download_table.php/$download_nm?", $uval->name,
						 "a local textfile", "edAction", "Download", "blackvinyl");
				div();
				br();
				br();
			}
		}
  	}
	$download_nm = "people-".date("d-m-y");
	div("short-form");
	download_form("downloadPeopleForm", "download_table.php/$download_nm?", "people",
			 "a local textfile", "edAction", "Download", "blackvinyl");
	div();
	br();
	br();
	standard_page_bottom();
?>
