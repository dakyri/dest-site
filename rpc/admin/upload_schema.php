<?php
	error_reporting(3);
	import_request_variables("gpc");

	if (!isset($action_msg))
		$action_msg = "";

	require_once("../common/necessary.php");
	require_once(in_parent_path("/common/adminlib.php"));
	require_once(in_parent_path("/common/sqlschema_types.php"));
	require_once(in_parent_path("/common/parse_sqlschema.php"));

	require("admin_dblogin.php");
	$login_type = LOGIN_DBADMIN;

	require_once(in_parent_path("/common/upload_schema_funk.php"));

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
					if (move_uploaded_file($tmp_name, "../${schema_base_directory}/${org_name}")) {
						$action_msg .= "<B>Your upload of xml fragment '$org_name' appears to have been successful</B>";
					} else {
						errorpage("Upload database schema error");
					}
				} else if (upload_schema($mysql, $tmp_name, $schema_name,
						"../$schema_base_directory", $schema_database_name, $schema_idx_name,
						1, $action_msg)) {
					$action_msg .= "<B>Your upload of database schema '$org_name' appears to have been successful</B>";
				} else {
					errorpage("Upload database schema error");
				}
				break;
				
			case "refresh":
				if (upload_schema($mysql, $tmp_name, $schema_name,
						"../$schema_base_directory", $schema_database_name, $schema_idx_name,
						2, $action_msg)) {
					$update_msg .= add_to_schema_index(
									"../$schema_base_directory/$pub_schema_idx_name",
									$schema_name,
									"../$schema_base_directory/{$schema_name}_tables.ser");
					$action_msg .= "<B>Your refresh from publicatation database schema '$org_name' appears to have been successful</B>";
				} else {
					errorpage("Upload database schema error");
				}
				break;
				
			case "build":
				if (upload_schema($mysql, $tmp_name, $schema_name,
						"../$schema_base_directory", $schema_database_name, $schema_idx_name,
						3, $action_msg)) {
					$update_msg .= add_to_schema_index(
									"../$schema_base_directory/$pub_schema_idx_name",
									$schema_name,
									"../$schema_base_directory/{$schema_name}_tables.ser");
					$action_msg .= "<B>Your upload and build from publicatation database schema '$org_name' appears to have been successful</B>";
				} else {
					errorpage("Upload database schema error");
				}
				break;
				
			case "refreshdb":
				if (upload_schema($mysql, $tmp_name, $schema_name,
						"../$schema_base_directory", $schema_database_name, $schema_idx_name,
						2, $action_msg)) {
					$action_msg .= "<B>Your refresh from database schema '$org_name' appears to have been successful</B>";
				} else {
					errorpage("Upload database schema error");
				}
				break;
				
			case "builddb":
				if (upload_schema($mysql, $tmp_name, $schema_name,
						"../$schema_base_directory", $schema_database_name, $schema_idx_name,
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
	standard_page_top("Upload database schema from local xml files",
				 "../style/default.css", "page-noframe",
				 "../images/title/upload_schema.gif", 560, 72, "Upload database schema",
				 "../common/necessary.js");
	br("all");
	echo $action_msg;
?>
<P>
This option is used for uploading new database template schema files, or refreshing caches associated with uploaded schemas.
These are XML files defining the basic database layout, edit text, and usage information for tables in this database.
</p>
<p>Templates uploaded as publication database schema are made directly available to users of the
DEST RPC database. Other databases maintained and created via this page are typically support databases,
and will not be directly accessible by regular users.</p>
<?php
	require("admin_nav.php");
	div("short-form");
	upload_form("rebuildPubDbSchemaForm", "upload_schema.php", "build", "a local xml file", "schemaAction", "Build", "blackvinyl",
			"Upload xml publication database schema, completely building required tables", "upload", "uploadMode",
			"Completely regenerating the database form requires a certain amount of caution. Also ensure the database is backed up, clear the current database, regenerate it, and then reload the old data");
	div();
	br();
	br();
	div("short-form");
	upload_form("refreshPubDbSchemaForm", "upload_schema.php", "refresh", "a local xml file", "schemaAction", "Refresh", "blackvinyl",
			"Upload xml publication database schema, refreshing cache variables only", "upload", "uploadMode",
			"This is the option typically necessary for changing comments and embedded php actions. Use only when not modifying the database per se");
	div();
	br();
	br();
	div("short-form");
	upload_form("rebuildDbSchemaForm", "upload_schema.php", "builddb", "a local xml file", "schemaAction", "Build", "blackvinyl",
			"Upload xml database schema, completely building required tables", "upload", "uploadMode",
			"Completely regenerating the database form requires a certain amount of caution. Also ensure the database is backed up, clear the current database, regenerate it, and then reload the old data");
	div();
	br();
	br();
	div("short-form");
	upload_form("refreshDbSchemaForm", "upload_schema.php", "refreshdb", "a local xml file", "schemaAction", "Refresh", "blackvinyl",
			"Upload xml database schema, refreshing cache variables only", "upload", "uploadMode",
			"This is the option typically necessary for changing comments and embedded php actions. Use only when not modifying the database per se");
	div();
	br();
	br();
	div("short-form");
	upload_form("uploadSchemaForm", "upload_schema.php", "upload", "a local xml file", "schemaAction", "Upload", "blackvinyl",
			"Upload xml database schema data to the site", "upload", "uploadMode",
			"This places a support file (such as xml definitions) in the appropriate place in the server. To be used these definitions still have to be processed by one of the options above");
	div();
	standard_page_bottom();
?>
