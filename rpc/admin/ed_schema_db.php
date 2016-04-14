<?php
//////////////////////////////////////////////////
// database edit: main wrapper.
// more general version than the specific user one
// most of the work done by schema_edit_framework
// this sets up globals for that, plus handles
// database opening/authorization
//////////////////////////////////////////////////
	error_reporting(3);
	import_request_variables("gp");
	if ($edit_pubs_for_user) {
		$posted_user = $edit_pubs_for_user;
		setcookie("edit_pubs_for_user", $edit_pubs_for_user);
	} else {
		$posted_user = false;
	}
	$return_url = "";
	import_request_variables("c");
	if ($posted_user) {
		$edit_pubs_for_user = $posted_user;
	}

	if (!isset($schema_edit_return_url)) {
		if (isset($return_rpc_url)) {
			$schema_edit_return_url = "../$return_rpc_url";
		} else {
			$schema_edit_return_url = $return_url;
		}
	}
	$action_msg = "";

	require("../common/necessary.php");
	require(in_parent_path("/common/adminlib.php"));
	require(in_parent_path("common/sqlschema_types.php"));
	
/////////////////////////////////////////////////////////
// database access
////////////////////////////////////////////////////////

	unset($login_user_row);
	if ($login_u && $login_p) {
		$mysql = get_database($database_name, $database_host, $login_u, $login_p);
		if ($mysql <= 0) {
			if ($mysql == -MYSQL_ER_ACCESS_DENIED) {
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
							if ($row->passwd == md5($login_p) && $row->kind == "admin") {
								// we're in!!!
							}
						} else {
							errorpage("No such user exists");
						}
					} else {
						errorpage("User database error".mysql_error());
					}
				} else {
					errorpage("User database error".mysql_error());
				}
			} else {
				errorpage("database $database_name on $database_host not accessible to user '$db_u' on this password\n<BR>".
					mysql_error());
			}
			$login_type = LOGIN_ADMIN;
		} else {
			$login_type = LOGIN_DBADMIN;
		}
		// we're in here !!!!
	} else {
		errorpage("Please login first!");
	}

	if ($edit_pubs_for_user) {
		$result = mysql_query("select * from people where stnumber='$edit_pubs_for_user'");
		if ($result > 0) {
			$nitems = mysql_num_rows($result);
			if ($nitems > 0) {
				$row = mysql_fetch_object($result);
				$login_user_row = $row;
				setcookie("edit_pubs_for_user", $edit_pubs_for_user);
			} else {
				$action_msg = "<b>Principal author $edit_pubs_for_user not found. Please add an entry to the user database first</b><br>";
			}
		} else {
			errorpage("Mysql error on people database: ".mysql_error());
		}
	}
	
	if (!isset($login_user_row)) {
		$result = mysql_query("select * from people where kind != 'admin'");
		if ($result > 0) {
			$nitems = mysql_num_rows($result);
			if ($nitems > 0) {
				$row = mysql_fetch_object($result);
				$login_user_row = $row;
			} else {
				errorpage("User database empty");
			}
		} else {
			errorpage("Database error searching for user: ".mysql_error());
		}
	}

	unset($row);
	$publication_entry_table = true;
	if (!isset($sqlschema) || !$sqlschema) {
		errorpage("Sorry. You can't edit a schema database without specifying a schema.");
	}
	if (file_exists("../$schema_base_directory/$pub_schema_idx_name")) {
		$sqlschema_selectable_schema = uncache_variable("../$schema_base_directory/$pub_schema_idx_name");
	}
	if (!$sqlschema_selectable_schema) {
		$sqlschema_selectable_schema = array();
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
	
	$schema_tables = uncache_variable("../$schema_base_directory/$sqlschema"."_tables.ser");
	$schema_types = uncache_variable("../$schema_base_directory/$sqlschema"."_types.ser");
//	var_dump($schema_types["conference-impact-type"]);
// global configuration variables relevant to the schema edit framework
	$schema_edit_form_action="ed_schema_db.php";
	$schema_edit_form_target="";
	$schema_edit_selector_text=$publication_entry_table?"Select publication format":"Select database";
	$schema_edit_upload_base = "../$upload_base";
	$schema_edit_framework_base = "../common/";
	$schema_edit_list_length = 6;
	$schema_edit_show_basic_instructions = true;
	$schema_edit_global_instructions = "";
	$schema_edit_insert_auto_increment_reqd = true;
	$schema_edit_insert_lock_timeout = 30;
	$schema_edit_columnar = true;	
	$schema_edit_columnar_label_width = "30%";
	
	if (!isset($schema_edit_use_selector)) {
		$schema_edit_use_selector=true;
	}
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
		$schema_edit_delete_option = false;
		$schema_edit_modify_option = false;
		$schema_edit_insert_option = true;
	} elseif ($edit_code) {
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
	} else {
		if (!isset($$sel)) {
			$$sel = 0;
		}
		if (!isset($schema_edit_supress_menu)) {
			$schema_edit_supress_menu = false;
		}
	}

// global variables used by the schema
	$edit_author_stnumber = $login_user_row->stnumber;
	$admin_edit = true;

	if ($publication_entry_table) {
		$schema_edit_preamble = true;
	} else {
		$schema_edit_preamble = false;
	}

	function schema_edit_page_top()
	{
		global	$publication_entry_table;
		standard_page_top(
				"Edit DEST Research Publications Database",
				"../style/default.css",
				"page-noframe",
				$publication_entry_table?
					"../images/title/edit_pub_entry.gif":
					"../images/title/edit_database.gif", 
				560, 72,
				$publication_entry_table?
					"Edit DEST Publication Entry":
					"Edit RPC support database",
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
		global $schema_edit_use_selector;

		if ($schema_edit_use_selector) {
			echo "<form name='pubUserForm' action=\"$schema_edit_form_action\" method=\"post\">";
			hidden_field("sqlschema",$sqlschema);
			echo "<b><font color='red'>*</font>Edit publications for (staff/student number):</b>";
			text_input("edit_pubs_for_user", $edit_pubs_for_user?$edit_pubs_for_user:$login_user_row->stnumber, 15, 15, "form.submit()", "");
?>
<script LANGUAGE="javascript">
edit_pub_for_user_completer= new jxComplete(
'../../completion.php?table=people&where_extra=kind%21%3D%27admin%27&fetch_field[]=stnumber&fetch_field[]=firstname&fetch_field[]=surname&fetch_field[]=title&fetch_field[]=gender&fetch_field[]=kind&fetch_field[]=school&match_field[]=stnumber&match_field[]=firstname&match_field[]=surname&label_expr=%24row-%3Etitle.%27+%27.%24row-%3Efirstname.%27+%27.%24row-%3Esurname',
'match_text',
pubUserForm.edit_pubs_for_user,
'stnumber',
[pubUserForm.edit_pubs_for_user],
['stnumber']
);
</script>
<?php
			echo "</form>\n";
		}
	}
	require(in_parent_path("/common/sqlschema_edit_framework.php"));
?>
