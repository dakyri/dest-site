<?php
	import_request_variables("gpc");
	error_reporting(3);

	if (!isset($action_msg))
		$action_msg = "";

	require_once("common/necessary.php");
	require_once("login_chk.php");

	if (isset($edit_table)) {
		$sel = "schema_edit_select_{$edit_table}_primary_key";
	} else {
		$sel = "schema_edit_select_{$sqlschema}_primary_key";
	}
	if (!isset($login_type)) {
		header("Location: logon.php?login_msg=Invalid+login...&login_destination=".
				urlencode("ed_schema_db.php?sqlschema=$sqlschema".($edit_code?"&edit_code=$edit_code":""))
			);
		exit();
	}
	$authorized_mod_user = false;
	if ($login_type < LOGIN_ADMIN) {
//		echo "!!!", isset($edit_code), "  $sqlschema xx $sel x ",$code, "###", isset($code);
		if ($login_type == LOGIN_USER && $sqlschema == "equipment" &&
				 (isset($edit_code)||isset($code)||isset($$sel))) {
			$kode = isset($edit_code)?$edit_code:(isset($code)?$code:$$sel);
			$eq_res = mysql_query("select supervisor from equipment where code=$kode");
			if ($eq_res > 0) {
				$n_eq = mysql_num_rows($eq_res);
//			echo $n_eq, "!!!", $login_user_row->stnumber,"!";
				if ($n_eq > 0) {
					$eqr = mysql_fetch_object($eq_res);
					if ($login_user_row->stnumber && $eqr->supervisor && $eqr->supervisor == $login_user_row->stnumber) {
						$authorized_mod_user = true;
					}
				}
			}				
		}
		if (!$authorized_mod_user) {
			header("Location: logon.php?login_msg=Database+Admin+login+required...&login_destination=".
				urlencode("ed_schema_db.php?sqlschema=$sqlschema".($edit_code?"&edit_code=$edit_code":""))
			);
			exit();
		}
	}
	if ($login_type == LOGIN_ADMIN) {
		$authorized_mod_user = true;
	}
	if ($authorized_mod_user) {
		require_once("private/local.php");
		$mysql = get_database(
				$database_name,
				$database_host,
				$database_mod_user,
				$database_mod_passwd);
	}
	if ($mysql < 0) {
		errorpage("Mysql Error".mysql_error());
	}

	if (!isset($schema_edit_return_url)) {
		$schema_edit_return_url = "admin.php";
	}
	$action_msg = "";

	require_once("../common/adminlib.php");
	require_once("../common/sqlschema_types.php");
	
	if (!isset($sqlschema) || !$sqlschema) {
		errorpage("Sorry. You can't edit a schema database without specifying a schema.");
	}
	if (file_exists("$schema_base_directory/$schema_idx_name")) {
		$sqlschema_selectable_schema = uncache_variable("$schema_base_directory/$schema_idx_name");
	}
	if (!$sqlschema_selectable_schema) {
		$sqlschema_selectable_schema = array();
	}
	
	$schema_tables = uncache_variable("$schema_base_directory/$sqlschema"."_tables.ser");
	$schema_types = uncache_variable("$schema_base_directory/$sqlschema"."_types.ser");
//	var_dump($schema_types["conference-impact-type"]);
// global configuration variables relevant to the schema edit framework
	$schema_edit_form_action="ed_schema_db.php";
	$schema_edit_form_target="";
	$schema_edit_selector_text="Select database";
	$schema_edit_upload_base = "uploaded";
	$schema_edit_framework_base = "../common/";
	$schema_edit_list_length = 6;
	$schema_edit_show_basic_instructions = true;
	$schema_edit_global_instructions = "";
	$schema_edit_insert_auto_increment_reqd = true;
	$schema_edit_insert_lock_timeout = 30;
	$schema_edit_columnar = true;	
	$schema_edit_columnar_label_width = "30%";
	$schema_edit_preamble = true;
	
	if (!isset($schema_edit_use_selector)) {
		$schema_edit_use_selector=true;
	}
	if ($delete_single) {
		$schema_edit_show_basic_instructions = false;
		$schema_edit_use_selector=false;
		if (!isset($schema_edit_supress_menu)) {
			$schema_edit_supress_menu = true;
		}
		if (!isset($$sel)) {
			$$sel = $edit_code;
		}
		$schema_edit_delete_option = true;
		$schema_edit_modify_option = false;
		$schema_edit_insert_option = false;
		if ($sqlschema == "equipment") {
			$schema_edit_return_url = "equipment.php?code=$edit_code";
		}
	} elseif ($insert_single) {
		$schema_edit_show_basic_instructions = false;
		$schema_edit_use_selector=false;
		if (!isset($schema_edit_supress_menu)) {
			$schema_edit_supress_menu = true;
		}
		$schema_edit_delete_option = false;
		$schema_edit_modify_option = false;
		$schema_edit_insert_option = true;
		if ($sqlschema == "equipment") {
			$schema_edit_return_url = "equipment.php?code=$edit_code";
		}
	} elseif ($edit_code) {
		$schema_edit_show_basic_instructions = false;
		$schema_edit_use_selector=false;
		if (!isset($$sel)) {
			$$sel = $edit_code;
		}
		if (!isset($schema_edit_supress_menu)) {
			$schema_edit_supress_menu = true;
		}
		$schema_edit_insert_option = false;
		$schema_edit_modify_option = true;
		$schema_edit_delete_option = false;
		if ($sqlschema == "equipment") {
			$schema_edit_return_url = "equipment.php?code=$edit_code";
		}
	} else {
		if (!isset($$sel)) {
			$$sel = 0;
		}
		if (!isset($schema_edit_supress_menu)) {
			$schema_edit_supress_menu = false;
		}
	}

	function schema_edit_page_top()
	{
		mmtc_page_top(
			"admin",
			"MMTC: Edit Site Database",
			"page-main",
			NULL,NULL,
			"../common/necessary.js");
		div("wide-margin");
	}
	
	function schema_edit_page_bottom()
	{
		div();
		mmtc_page_bottom();
	}
	
	function schema_edit_preamble_hook($sqlschema)
	{
		global $login_user_row;
		global $edit_pubs_for_user;
		global $admin_edit;
		global $schema_edit_use_selector;
	}
	require("../common/sqlschema_edit_framework.php");
?>
