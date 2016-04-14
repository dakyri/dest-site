<?php
	error_reporting(3);
	import_request_variables("gpc");

	if (!isset($action_msg))
		$action_msg = "";

	require_once("common/necessary.php");
	require_once("login_chk.php");

	if (!isset($login_type)) {
		header("Location: logon.php?login_msg=Invalid+login...&login_destination=upload_schema.php");
		exit();
	}
	if ($login_type < LOGIN_DBADMIN) {
		header("Location: logon.php?login_msg=Database+Admin+login+required...&login_destination=upload_schema.php");
		exit();
	}

	require_once("../common/adminlib.php");
	require_once("../common/sqlschema_types.php");
	require_once("../common/parse_sqlschema.php");
	require_once("../common/upload_schema_funk.php");

	if (isset($schemaAction) && $schemaAction) {
		$tmp_name = $HTTP_POST_FILES["upload"]["tmp_name"];
		$org_name = $HTTP_POST_FILES["upload"]["name"];
		$typ_name = $HTTP_POST_FILES["upload"]["type"];
		$ulerrc = $HTTP_POST_FILES["upload"]["error"];
		$size = $HTTP_POST_FILES["upload"]["size"];
		$schema_name = basename($org_name, ".xsql");
		switch($uploadMode) {
			case "upload":
				if (ereg('.xml$', $org_name)) { // a regular xml fragment just move it in place
					if (move_uploaded_file($tmp_name, "${schema_base_directory}/${org_name}")) {
						$action_msg .= "<B>Your upload of xml fragment '$org_name' appears to have been successful</B>";
					} else {
						errorpage("Upload database schema error");
					}
				} else if (upload_schema($mysql, $tmp_name, $schema_name,
						"$schema_base_directory", $schema_database_name, $schema_idx_name,
						1, $action_msg)) {
					$action_msg .= "<B>Your upload of database schema '$org_name' appears to have been successful</B>";
				} else {
					errorpage("Upload database schema error");
				}
				break;
				
			case "refreshdb":
				if (upload_schema($mysql, $tmp_name, $schema_name,
						"$schema_base_directory", $schema_database_name, $schema_idx_name,
						2, $action_msg)) {
					$action_msg .= "<B>Your refresh from database schema '$org_name' appears to have been successful</B>";
				} else {
					errorpage("Upload database schema error");
				}
				break;
				
			case "builddb":
				if (upload_schema($mysql, $tmp_name, $schema_name,
						"$schema_base_directory", $schema_database_name, $schema_idx_name,
						3, $action_msg)) {
					$action_msg .= "<B>Your upload and build from database schema '$org_name' appears to have been successful</B>";
				} else {
					errorpage("Upload database schema error");
				}
				break;
				
			default:
				$action_msg = "Unusual request $uploadMode";
				break;
		}
	} 
	mmtc_page_top(
		"admin",
		"MMTC: Schema Admistrative Controls",
		"page-main",
		NULL,NULL,
		"../common/necessary.js");
	div('wide-margin');
	echo $action_msg;
?>
<P>
This option is used for uploading new database template schema files, or refreshing caches associated with uploaded schemas.
These are XML files defining the basic database layout, edit text, and usage information for tables in this database.
</p>
<?php
	table_header(0,0);
	echo "<tr><td>";
	upload_form("rebuildDbSchemaForm", "upload_schema.php", "builddb", "a local xml file", "schemaAction", "Build", "blackvinyl",
			"Upload xml database schema, completely building required tables", "upload", "uploadMode",
			"Completely regenerating the database form requires a certain amount of caution. Also ensure the database is backed up, clear the current database, regenerate it, and then reload the old data");
	echo "</td></tr>";
	echo "<tr><td>";
	upload_form("refreshDbSchemaForm", "upload_schema.php", "refreshdb", "a local xml file", "schemaAction", "Refresh", "blackvinyl",
			"Upload xml database schema, refreshing cache variables only", "upload", "uploadMode",
			"This is the option typically necessary for changing comments and embedded php actions. Use only when not modifying the database per se");
	echo "</td></tr>";
	echo "<tr><td>";
	upload_form("uploadSchemaForm", "upload_schema.php", "upload", "a local xml file", "schemaAction", "Upload", "blackvinyl",
			"Upload xml database schema data to the site", "upload", "uploadMode",
			"This places a support file (such as xml definitions) in the appropriate place in the server. To be used these definitions still have to be processed by one of the options above");
	echo "</td></tr>";
	table_tail();
	div();
	mmtc_page_bottom();
?>
