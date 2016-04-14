<?php
	import_request_variables("gpc");
	error_reporting(3);

	if (!isset($action_msg))
		$action_msg = "";

	require_once("common/necessary.php");
	require_once("login_chk.php");

	if (!isset($login_type)) {
		header("Location: logon.php?login_msg=Invalid+login...&login_destination=download_table.php");
		exit();
	}
	if ($login_type < LOGIN_DBADMIN) {
		header("Location: logon.php?login_msg=Database+Admin+login+required...&login_destination=download_table.php");
		exit();
	}

	require_once("../common/adminlib.php");
	require_once("../common/sqlschema_types.php");
	require_once("../common/dump_table_funk.php");

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
	mmtc_page_top(
		"admin",
		"MMTC: Schema Admistrative Controls",
		"page-main",
		NULL,NULL,
		"../common/necessary.js");
	div('wide-margin');
	echo $action_msg;
	
	if (file_exists("$schema_base_directory/$schema_idx_name")) {
		$uploaded_schema = uncache_variable("$schema_base_directory/$schema_idx_name");
	}
	if (!$uploaded_schema) {
		$uploaded_schema = array();
	}
?>
<P>This option is used principally for making backups, or moving the site.</p>
<?php
	reset($uploaded_schema);
	table_header(0,0);
	while (list($key,$val) = each($uploaded_schema)) {
		$unc = uncache_variable("$schema_base_directory/$key"."_tables.ser");
		if ($unc) {
			reset($unc);
			while (list($ukey, $uval) = each($unc)) {
				$download_nm = "$uval->name-".date("d-m-y");
				echo "<tr><td>";
				download_form("download$uval->name"."Form", "download_table.php/$download_nm?", $uval->name,
						 "a local textfile", "edAction", "Download", "blackvinyl");
				echo "</td></tr>";
			}
		}
  	}
  	table_tail();
  	div();
	mmtc_page_bottom();
?>
