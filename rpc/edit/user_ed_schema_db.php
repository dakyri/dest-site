<?php
///////////////////////////////////////////////
// database edit: main wrapper.
// most of the work done by schema_edit_framework
// this sets up globals for that, plus handles
// database opening/authorization
//////////////////////////////////////////////////
	error_reporting(3);
	import_request_variables("gp");
	if ($edit_pubs_for_user) {
		$form_edit_pubs_for_user = $edit_pubs_for_user;
	}
	$return_url = "";
	import_request_variables("c");
	$edit_pubs_for_user = $form_edit_pubs_for_user;
	if (!isset($action_msg)) {
		$action_msg = "";
	}
	if (!isset($schema_edit_return_url)) {
		if (isset($return_rpc_url)) {
			$schema_edit_return_url = "../$return_rpc_url";
		} else {
			$schema_edit_return_url = $return_url;
		}
	}

	require("../common/necessary.php");
	require(in_parent_path("/common/adminlib.php"));
	require(in_parent_path("/common/sqlschema_types.php"));
	require(in_parent_path("/private/local.php"));
	
/////////////////////////////////////////////////////////
// database access
////////////////////////////////////////////////////////
	unset($login_user_code);
	unset($login_user_row);
	if (isset($login_u) && $login_u != "" && isset($login_p) && $login_p != "") {
		$mysql = get_database(
						$database_name,
						$database_host,
						$database_pleb_user,
						$database_pleb_passwd);
		if ($mysql > 0) {
			$query = "select * from people where stnumber='$login_u'";
			$result = mysql_query($query);
			if ($result > 0) {
				$nitems = mysql_num_rows($result);
				if ($nitems > 0) {
					$row = mysql_fetch_object($result);
					if ($row->passwd == md5($login_p)) {
						setcookie("login_u", $login_u, 0, "/dest/rpc/");
						setcookie("login_p", $login_p, 0, "/dest/rpc/");
						$login_user_row = $row;
					}
				}
			}
		}
	}

	if (!isset($login_user_row)) {
		header("Location: logon.php");
		exit();
	}
	$publication_entry_table = true;
	if ($database_mod_user != "") {
		mysql_close($mysql);
		$mysql = get_database($database_name, $database_host, $database_mod_user, $database_mod_passwd);
		if ($mysql <= 0) {
			errorpage("database $database_name on $database_host not accessible to user '$db_u' on this password\n<BR>".
			mysql_error());
		}
	} else {
		errorpage("No no no. Not Allowed!");
	}
	if (file_exists("../$schema_base_directory/$pub_schema_idx_name")) {
		$sqlschema_selectable_schema = uncache_variable("../$schema_base_directory/$pub_schema_idx_name");
	}
	if (!$sqlschema_selectable_schema) {
		$sqlschema_selectable_schema = array();
	}

	if (!isset($sqlschema) || !$sqlschema) {
		reset($sqlschema_selectable_schema);
		list($sqlschema,$schemaregfile) = each($sqlschema_selectable_schema);
		if (!$sqlschema) {
			errorpage("It appears no publication formats have been uploaded.<br>Please contact the administrator of this system.");
		}
	}
	if (!$sqlschema_selectable_schema[$sqlschema]) {
		$publication_entry_table = false;
		$pub_schema = $sqlschema_selectable_schema;
		if (file_exists("../$schema_base_directory/$schema_idx_name")) {
			$sqlschema_selectable_schema = uncache_variable("../$schema_base_directory/$schema_idx_name");
		}
		if (!$sqlschema_selectable_schema[$sqlschema]) {
			errorpage("Sorry. Schema '$sqlschema' is not registered with this system.");
		}
		reset($pub_schema);
		while (list($k,$v) = each($pub_schema)) {
			if ($sqlschema_selectable_schema[$k]) {
				unset($sqlschema_selectable_schema[$k]);
			}
		}
		unset($pub_schema);
	}
	
	unset($row); // important for php expression in setting base

	$schema_tables = uncache_variable("../{$schema_base_directory}/{$sqlschema}_tables.ser");
	$schema_types = uncache_variable("../{$schema_base_directory}/{$sqlschema}_types.ser");
	
	$schema_edit_form_action="user_ed_schema_db.php";
	$schema_edit_form_target="";
	$schema_edit_selector_text=$publication_entry_table?"Select publication format":"Select database";
	$schema_edit_upload_base = "../$upload_base/";
	$schema_edit_framework_base = "../common/";
	$schema_edit_list_length = 6;
	$schema_edit_show_basic_instructions = true;
	$schema_edit_global_instructions = "";
	$schema_edit_insert_auto_increment_reqd = true;
	$schema_edit_insert_lock_timeout = 30; //  only needed if need the auto_increment value
	$schema_edit_columnar = true;
	$schema_edit_columnar_label_width = "30%";
	
	if (isset($edit_table)) {
		$sel = "schema_edit_select_{$edit_table}_primary_key";
	} else {
		$sel = "schema_edit_select_{$sqlschema}_primary_key";
	}
	if ($delete_single) {
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
	} elseif ($insert_single) {
		$schema_edit_use_selector=false;
		if (!isset($schema_edit_supress_menu)) {
			$schema_edit_supress_menu = true;
		}
		$schema_edit_insert_option = true;
		$schema_edit_delete_option = false;
		$schema_edit_modify_option = false;
	} elseif ($edit_code) {
		$schema_edit_use_selector=false;
		if (!isset($$sel)) {
			$$sel = $edit_code;
		}
		if (!isset($schema_edit_supress_menu)) {
			$schema_edit_supress_menu = true;
		}
		$schema_edit_insert_option = false;
		$schema_edit_delete_option = false;
	} else {
		if (!isset($$sel)) {
			$$sel = 0;
		}
		if (!isset($schema_edit_supress_menu)) {
			$schema_edit_supress_menu = false;
		}
	}
	if (!isset($schema_edit_use_selector)) {
		$schema_edit_use_selector=true;
	}
// global variables used by the schema	$edit_author_stnumber = $login_user_row->stnumber;	// set automatically as an expression attr in sqlschema
	$edit_author_row = $login_user_row;
	$schema_edit_preamble = true;
	
function schema_edit_page_top()
{
	standard_page_top(
			"Edit DEST Research Publications Database",
			"../style/default.css",
			"page-noframe",
			"../images/title/edit_pub_entry.gif", 560, 72, "Edit DEST Publication Entry",
			in_parent_path("/common/necessary.js"));
	br("all");
}

function schema_edit_page_bottom()
{
	standard_page_bottom();
}

function schema_edit_preamble_hook($sqlschema)
{
	global $login_user_row;
	global $edit_pubs_for_user;
	global $admin_edit;
	
	if ($login_user_row) {
		echo "Currently logged in as <b>",
				"$login_user_row->stnumber ($login_user_row->firstname $login_user_row->surname)",
				"</b>...<br>";
	}
	if ($admin_edit) {
		echo "<form action=\"$schema_edit_form_action\" method=\"post\">";
		hidden_field("sqlschema",$sqlschema);
		echo "<b>Edit publications for (staff/student number):</b>";
		text_input(
			"edit_pubs_for_user",
			$edit_pubs_for_user?$edit_pubs_for_user:$login_user_row->stnumber,
			15, 15, "form.submit()",
			"");
		echo "</form>";
	}
}

	if ($login_user_row->kind == "admin") {
		$admin_adit = true;
		$login_type = LOGIN_ADMIN;
	} else {
		$admin_edit = false;
		$login_type = LOGIN_USER;
	}
	
	if ($admin_edit) {
		if ($edit_pubs_for_user) {
			$result = mysql_query("select * from people where stnumber='$edit_pubs_for_user'");
			if ($result > 0) {
				$nitems = mysql_num_rows($result);
				if ($nitems > 0) {
					$u_row = mysql_fetch_object($result);
					$edit_author_row = $u_row;
					setcookie("edit_pubs_for_user", $edit_pubs_for_user);
				} else {
					$action_msg = "$edit_pubs_for_user not found<br>";
				}
			} else {
				errorpage("Mysql error on people database: ".mysql_error());
			}
		} else {
			$action_msg = "user not set<br>";
		}

		$edit_author_stnumber = $edit_author_row->stnumber;
	}
	require(in_parent_path("common/sqlschema_edit_framework.php"));
?>