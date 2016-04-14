<?php
function add_to_schema_index($indexname, $schemaname, $schemacache)
{
	$update_msg = "";
	if (file_exists($indexname)) {
		$uploaded_schema = uncache_variable($indexname);
	} else {
		$update_msg .=  "<p>Schema master index $indexname doesn't exist</p>";
	}
	
	if (!$uploaded_schema) {
		$uploaded_schema = array();
	}
	$uploaded_schema[$schemaname] = $schemacache;
	if (cache_variable($uploaded_schema, $indexname)) {
		$update_msg .= "<p>Added '$schemaname' to master schema index</p>";
	} else {
		$update_msg .= "<p>Failed to add '$schemaname' to master schema index</p>";
	}
	return $update_msg;
}

function upload_schema(
		$mysql, $filename,
		$schemaname, $schemabase, $schema_database, $schema_master,
		$mode, &$update_msg)
{
	global	$schema_tables;	// built by the parser
	global	$schema_types;		// .... ditto
	
	if (!($fp = fopen($filename, "r"))) {
		errorpage("Can't open '$filename' for input");
	}
	if (!($parser = new_sqlschema_parser($schemabase))) {
		errorpage("Can't create parser");
	}
	if ($err=parse_sqlschema($parser, $fp)) {	// at least try and parse it
		errorpage($err);
	}
	
	if ($mode>=1) {	// do an xml upload.
		// save uploaded file to templates
		$schema_xml_filename = "$schemabase/$schemaname.xsql";
		if (!($xmlsave_fp = @fopen($schema_xml_filename, "w"))) {
			errorpage("Can't create template file '$schema_xml_filename'");
		}
		fseek($fp, 0);
		while (($str = fread($fp, 1024)) != '') {
			fwrite($xmlsave_fp, $str, strlen($str));
		}
		fclose($xmlsave_fp);
		@chmod($schema_xml_filename, 0664);
		$update_msg .= "<p>Saved schema '$schema_xml_filename'</p>";
		if ($mode >= 2) {	// regenerate variables associated with this schema
			if (cache_variable($schema_tables, "$schemabase/$schemaname"."_tables.ser")) {
				$update_msg .= "<p>Cached schema table variables, $schemabase/$schemaname"."_tables.ser</p>";
			} else {
				$update_msg .= "<p>Failed to cache schema table variables</p>";
			}
			if (cache_variable($schema_types, "$schemabase/$schemaname"."_types.ser")) {
				$update_msg .= "<p>Cached schema type variables, $schemabase/$schemaname"."_types.ser</p>";
			} else {
				$update_msg .= "<p>Failed to cache schema type variables</p>";
			}
			
			$update_msg .= add_to_schema_index(
									"$schemabase/$schema_master",
									$schemaname,
									"$schemabase/$schemaname"."_tables.ser");
			
			if ($mode >= 3) {	// create associated tables
				require(in_parent_path("/common/sqlschema_creat.php"));			
			} else {
// at least try and check out all associated enum and set are still aok
// 
//				require("../common/sqlschema_mod_set_enum.php");			
			}
				
		}
	}
	
	return true;
}
?>
