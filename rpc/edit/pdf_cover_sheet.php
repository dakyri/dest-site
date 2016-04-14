<?php
	error_reporting(3);
	import_request_variables("gp");

	require_once("../common/necessary.php");
	require_once(in_parent_path("/common/sqlschema_types.php"));
	
	if (!$table || !$code || !$stnumber) {
		errorpage("To generate a cover sheet, you must supply an rpc publication table name, code and user name");
	}
	$mysql = get_database(
					$database_name,
					$database_host,
					$database_pleb_user,
					$database_pleb_passwd);
	if ($mysql <= 0) {
		errorpage("Can't access mysql on standard user: ".mysql_error());
	}
	$pdfschema_params = array();
	$pdfschema_params["table"] = $table;
	$pdfschema_params["code"] = $code;
	$pdfschema_params["user"] = $stnumber;
	$GLOBALS["pdfschema_params"] = $pdfschema_params;
	
	switch($table) {
		case "book":
			$xml_template = in_parent_path("template_master/book_cover.xpdf");
			break;
		case "chapter":
			$xml_template = in_parent_path("template_master/chapter_cover.xpdf");
			break;
		case "journal":
			$xml_template = in_parent_path("template_master/journal_cover.xpdf");
			break;
		case "conference":
			$xml_template = in_parent_path("template_master/conference_cover.xpdf");
			break;
		default:
			$xml_template = in_parent_path("template_master/cover_sheet.xpdf");
			break;
	}
	$schema_types = uncache_variable("../$schema_base_directory/$table"."_types.ser");
	$vrii_type = &$schema_types["vrii-type"];
	$rsc_type = &$schema_types["rsc-type"];

	require_once(in_parent_path("common/pdfschema.php"));
	if (!generate_schema_pdf(
			$xml_template,
			"cover_{$stnumber}_{$table}_{$code}.pdf",
			$pdfschema_params)) {
		errorpage($pdfschema_error_msg);
	}
	exit;
?>