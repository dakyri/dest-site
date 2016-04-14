<?php
/////////////////////////////////////////////////////////////////////////////////////////
// database edit code from a sqlschema xml framework.
//  1st version knocked together quickly, not quite
// as flexible as it could be but ok for a generic database with a few tables
//  this file contains the code to generate a html management page,
//  other bits and pieces files beginning 'sqlschema_ ...'
// TODO:
//	 * handle tables whose fields depend on other tables' fields in a schema in a reasonably
//			transparent fashion. e.g. a catalog and a table of images referring to particular
//			entries in the catalog
//  * also be nice for a few more pseudo-types that did form checks for email etc.
//  * handle deeply nested types, currently only checks down 1 level
/////////////////////////////////////////////////////////////////////////////////////////
$php_magic_quotes_enabled = get_magic_quotes_gpc();

function submit_controls($schema, $ext)
{
// edit control buttons for this table
	global	$schema_edit_return_url;
	global	$schema_edit_delete_option;
	global	$schema_edit_modify_option;
	global	$schema_edit_insert_option;
	global	$schema_edit_supress_menu;
	
	echo "<center>";
	div("edit-controls");
	echo "\n<TABLE CELLPADDING=2 CELLSPACING=0 align=\"center\">\n";
	echo "<tr>\n";
	if ($ext == "error") {
	} elseif ($ext == "create") {
		echo "<td><B>"; submit_input("schemaEditAction", "Create table", NULL, NULL, NULL, "sqlschema_button_{$schema}_{$ext}");echo "</B></td>";
	} else {
		if (!isset($schema_edit_delete_option) || $schema_edit_delete_option) {
		 	echo "<td><B>";submit_input("schemaEditAction", $schema_edit_supress_menu?"Delete item":"Delete items", NULL, NULL, NULL, "sqlschema_delete_button_{$schema}_{$ext}"); echo "</B></td>\n";
	 	}
	 	if (!isset($schema_edit_modify_option) || $schema_edit_modify_option) {
	 		echo "<td><B>";submit_input("schemaEditAction", $schema_edit_supress_menu?"Modify entry":"Modify selected", NULL, NULL, true, "sqlschema_modify_button_{$schema}_{$ext}"); echo "</B></td>\n";
	 	}
	 	if (!isset($schema_edit_insert_option) || $schema_edit_insert_option) {
	 		echo "<td><B>";submit_input("schemaEditAction", "Insert item", NULL, NULL, NULL, "sqlschema_insert_button_{$schema}_{$ext}"); echo "</B></td>\n";
	 		echo "<td><b><input type=\"reset\" value=\"Clear Form\" onClick=\"clear_{$schema}_extras();\", ></b></td>";
	 	}
	}
 	if ($schema_edit_return_url) {
 		echo "<td><B>";button_input("schemaReturn", "Return to menu", "window.location='".htmlspecialchars($schema_edit_return_url)."'", NULL, NULL, "sqlschema_insert_button_{$schema}_{$ext}"); echo "</B></td>\n";
 	}
	echo "</tr>\n";
	echo "</TABLE>\n";
	div();
	echo "</center>";
}

function integer_input_range_check($basetype,$quals,$mid, $min, $max)
{
	if (is_numeric($min) || is_numeric($max)) {
		$minval = $min?$min:"''";
		$maxval = $max?$max:"''";
		$on_change = "value=check_int_range(value,$mid, $minval, $maxval)";
	} else {
		$on_change = "value=check_int(value,$mid)";
	}
	return $on_change;
}


function time_input_range_check($basetype,$quals,$mid, $min, $max)
{
	return "";
}

function match_complete_script(
	&$field_val, $formName, $fieldIndex, $completionClass, $completionScript)
{
	$script_str = '';
	if ($field_val->matchUrlPath
			&& $completionClass
			&& $completionScript) {
		$textinFieldName = $field_val->name;
		if ($fieldIndex == 0) {
			$script_str .= $field_val->name;
			$script_str .= "_completer=new Array();\n";
		}
		$script_str .=  "{$field_val->name}_completer";
		if ($fieldIndex >=0 ) {
			$script_str .=  "[{$fieldIndex}]";
		}
		$script_str .= "= new $completionClass(\n";
		$match_path = in_parent_path($field_val->matchUrlPath);
		$match_query = '';
		if ($field_val->matchQueryBase) {
			$xq = eval("return $field_val->matchQueryBase;");
			if ($xq) {
				$match_query .= $xq;
			}
		}
		$complete_fields = explode(",",$field_val->matchComplete);
		$compare_fields = explode(",",$field_val->matchCompare);
		if (!$complete_fields) {
			$complete_fields = array();
		}
		if (!$compare_fields) {
			$compare_fields = array();
		}
		$complete_from = array();
		for (reset($complete_fields); list($k,$v)=each($complete_fields); ) {
			$sp = explode('=', $v);
			if (count($sp) > 1) {
				$complete_fields[$k] = $sp[0];
				$complete_from[$k] = $sp[1];
				if ($sp[0] == $field_val->name) {
					$textinFieldName = $sp[1];
				}
			} else {
				$complete_from[$k] = $v;
			}
		}
		$match_fieldnm = $field_val->matchUrlMatchField;
		$fetch_fieldnm = $field_val->matchUrlFetchField;
		$value_fieldnm = $field_val->matchUrlValueField;
		if (!$match_fieldnm) $match_fieldnm = "match_field";
		if (!$fetch_fieldnm) $fetch_fieldnm = "fetch_field";
		if (!$value_fieldnm) $value_fieldnm = "match_text";
		reset($complete_from);
		while (list($k,$v)=each($complete_from)) {
			$match_query .= "&{$fetch_fieldnm}[]=$v";
		}
		reset($compare_fields);
		while (list($k,$v)=each($compare_fields)) {
			$match_query .= "&{$match_fieldnm}[]=$v";
		}
		if ($field_val->matchLabel) {
			$match_query .= "&label_expr=";
			$match_query .= urlencode($field_val->matchLabel);
		}
		$script_str .=  "'{$match_path}?{$match_query}',\n";	// the basic query
		$script_str .=  "'$value_fieldnm',\n";	// the field used to post text value to match
		if ($fieldIndex <0 ) {
			$script_str .=  "document.$formName.$field_val->name";	// the input field
		} else {
			$script_str .=  "getAnElementByName('{$field_val->name}[$fieldIndex]')";
		}
		$script_str .=  ",\n";
		$script_str .=  "'$textinFieldName',\n";	// db field correspondin to input
		$script_str .=  "[";
		reset($complete_fields);
		while (list($k,$v)=each($complete_fields)) {
			if ($k > 0) {
				$script_str .=  ",";
			}
			if ($fieldIndex <0 ) {
				$script_str .=  "document.$formName.$v";
			} else {
				$script_str .=  "getAnElementByName('$v";
				$script_str .=  "[$fieldIndex]')";
			}
		}
		$script_str .=  "],\n";
		$script_str .=  "[";
		reset($complete_from);
		while (list($k,$v)=each($complete_from)) {
			if ($k > 0) {
				$script_str .=  ",";
			}
			$script_str .=  "'$v'";
		}
		$script_str .=  "]\n);\n";
	}
	return $script_str;
}

function schema_edit_redirect(&$page, &$sqlschema, &$schema_tables, &$action_msg)
{
	$redirect_str = "Location: ".$page."?sqlschema=$sqlschema";
	if (isset($GLOBALS["schema_edit_supress_menu"])) {
		$redirect_str .= ("&schema_edit_supress_menu=".$GLOBALS["schema_edit_supress_menu"]);
	}
	if (isset($GLOBALS["schema_edit_use_selector"])) {
		$redirect_str .= ("&schema_edit_use_selector=".$GLOBALS["schema_edit_use_selector"]);
	}
	if (isset($GLOBALS["schema_edit_return_url"])) {
		$redirect_str .= ("&schema_edit_return_url=".$GLOBALS["schema_edit_return_url"]);
	}
	if (isset($GLOBALS["schema_edit_delete_option"])) {
		$redirect_str .= ("&schema_edit_delete_option=".$GLOBALS["schema_edit_delete_option"]);
	}
	if (isset($GLOBALS["schema_edit_insert_option"])) {
		$redirect_str .= ("&schema_edit_insert_option=".$GLOBALS["schema_edit_insert_option"]);
	}
	if (isset($GLOBALS["schema_edit_modify_option"])) {
		$redirect_str .= ("&schema_edit_modify_option=".$GLOBALS["schema_edit_modify_option"]);
	}
	while (list($key, $tabschema) = each($schema_tables)) {
		$tabschema_sel_name = "{$tabschema->name}_sqlschema_selected";
		$tabschema_sel = $GLOBALS[$tabschema_sel_name];
		if ($tabschema_sel >= 0) {
			$redirect_str .= "&$tabschema_sel_name=$tabschema_sel";
		}
		$tabschema_sel_pk_name = "schema_edit_select_{$tabschema->name}_primary_key";
		$pk = $GLOBALS[$tabschema_sel_pk_name];
		if ($pk != "" && $pk >= 0) {
			$redirect_str .= "&$tabschema_sel_pk_name=$pk";
		}
		$tabschema_pageno_name = "schema_edit_{$tabschema->name}_pageno";
		$pk = $GLOBALS[$tabschema_pageno_name];
		if ($pk != "" && $pk >= 0) {
			$redirect_str .= "&$tabschema_pageno_name=$pk";
		}
	}
	$redirect_str .= "&action_msg=";
	$redirect_str .= urlencode($action_msg);
	
	header($redirect_str);
	exit;
}

	$schema_edit_inserting = false;
	$schema_edit_updating = false;
	$schema_edit_scripting = false;
	if (isset($schemaEditAction)) {
		if ($schemaEditAction == "Create table") {
			require(in_parent_path($schema_edit_framework_base."sqlschema_creat.php"));
			schema_edit_redirect($_SERVER['PHP_SELF'], $sqlschema, $schema_tables, $action_msg);
		} elseif ($schemaEditAction == "Delete items" || $schemaEditAction == "Delete item") {
			if (sizeof($del) > 0) {
				if (!isset($edit_table) || !$edit_table) {
					errorpage("Sorry. No table in this schema is specified");
				}
				$tabschema = &$schema_tables[$edit_table];
				$tabschema_key_field = $tabschema->PrimaryKeyFieldName();
				
				$del_str = list_string($del);
				if ($del_str) {
					$query = "delete from $tabschema->name where $tabschema_key_field in (${del_str})";
//					echo $query;
//					var_dump($del);
					$delete_result = mysql_query($query, $mysql);
					if (!$delete_result) {
						errorpage(mysql_error());
					}
					if ($schema_edit_return_url && $schema_edit_supress_menu) {
						header("Location: ".urldecode($schema_edit_return_url)."&action_msg=Item successfully deleted...");
						exit;
					}
				} else {
					if ($schema_edit_return_url && $schema_edit_supress_menu) {
						header("Location: ".urldecode($schema_edit_return_url).
								"&action_msg=Unexpected result, no apparent item to delete ...");
						exit;
					}
				}
			}			
			schema_edit_redirect($_SERVER['PHP_SELF'], $sqlschema, $schema_tables, $action_msg);
		} elseif ($schemaEditAction=="Modify selected" || $schemaEditAction == "Modify entry") {

			$schema_edit_updating = true;
			if (!isset($edit_table) || !$edit_table) {
				errorpage("Sorry. No table in this schema is specified");
			}
			$tabschema = &$schema_tables[$edit_table];
			$tabschema_key_field = $tabschema->PrimaryKeyFieldName();
			$tabschema_sel_pk_name = "schema_edit_select_{$tabschema->name}_primary_key";
			if ($$tabschema_key_field) {
				$$tabschema_sel_pk_name = $$tabschema_key_field;
			}
			require(in_parent_path($schema_edit_framework_base."sqlschema_ins_upd.php"));
			if ($field_set_str && $where) {
				$query = "update $tabschema->name set $field_set_str where $where";
				$update_result = mysql_query($query, $mysql);
				if (!$update_result) {
					errorpage("Update error ".mysql_error());
				} else if (count($sqlschema_match_fill_updates) > 0) {
					require(in_parent_path($schema_edit_framework_base."sqlschema_match_fill_upd.php"));
				}
			}
			$schema_edit_updating = false;
			
			schema_edit_redirect($_SERVER['PHP_SELF'], $sqlschema, $schema_tables, $action_msg);

		} elseif ($schemaEditAction == "Insert item") {
			$schema_edit_inserting = true;
			
			if (!isset($edit_table) || !$edit_table) {
				errorpage("Sorry. No table in this schema is specified");
			}
			$tabschema = &$schema_tables[$edit_table];
			$tabschema_key_field = $tabschema->PrimaryKeyFieldName();
// get the auto increment value. thus we know in advance what the key of the inserted entry should be.
// we can then use this when we redirect to show the page ....
			$tabschema_locknm = "schema_lock_$tabschema->name";
			$ir_res = mysql_query("select get_lock('$tabschema_locknm', $schema_edit_insert_lock_timeout)");
			if (!$ir_res) {
				errorpage("Can't get a lock for an insert. Mysql says:<br>\n".mysql_error());
			}
			$schema_edit_auto_increment = 0;
			$ir_res = mysql_query("show table status like '$tabschema->name'");
			if ($ir_res) {
				$ir = mysql_fetch_object($ir_res);
				$schema_edit_auto_increment = $ir->Auto_increment;
			} else {
				errorpage("Can't get auto_increment value and it is required for an insert. Mysql says:<br>\n".mysql_error());
			}
			$edittab_sel_pk_name = "schema_edit_select_{$edit_table}_primary_key";
			$$edittab_sel_pk_name = $schema_edit_auto_increment;
			require(in_parent_path($schema_edit_framework_base."sqlschema_ins_upd.php"));
			if ($field_set_str) {
				$query = "insert into $tabschema->name set $field_set_str";
				$insert_result = mysql_query($query, $mysql);
				if (!$insert_result) {
					errorpage(mysql_error());
				} else if (count($sqlschema_match_fill_updates) > 0) {
					require(in_parent_path($schema_edit_framework_base."sqlschema_match_fill_upd.php"));
				}
			}
			$ir_res = mysql_query("select release_lock('$tabschema_locknm')");
			if ($schema_edit_insert_option && $schema_edit_supress_menu) {
			// if no menu, go into a standalone modify mode
				$schema_edit_insert_option = false;
				$schema_edit_modify_option = true;
			}
			$schema_edit_inserting = false;
			
			schema_edit_redirect($_SERVER['PHP_SELF'], $sqlschema, $schema_tables, $action_msg);
		} elseif ($schemaEditAction == "Modify enumerated values") {
			if (!isset($edit_table) || !$edit_table) {
				errorpage("Sorry. No table in this schema is specified");
			}
			$tabschema = $schema_tables[$edit_table];
			schema_edit_redirect($_SERVER['PHP_SELF'], $sqlschema, $schema_tables, $action_msg);
		}
	}
/////////////////////////////////////////////////////////
// page generation
////////////////////////////////////////////////////////
	schema_edit_page_top();
?>
<script type="text/javascript" LANGUAGE="javascript">
function getAnElementByName(nm)
{
	nl = document.getElementsByName(nm);
	if (nl.length > 0) {
		return nl.item(0);
	}
	return null;
}

function check_float(val, def_val)
{
	ival = parseFloat(val);
	window.alert(val);
	return isNaN(ival)?def_val:ival;
}

function check_int(val, def_val)
{
	ival = parseInt(val);

	return isNaN(ival)?def_val:ival;
}

function check_float_range(val, def_val, min_val, max_val)
{
	ival = parseFloat(val);

	if (isNaN(ival)) {
		return def_val;
	}
	if (min_val != '' && ival < min_val) {
		ival = min_val;
	} else if (max_val != '' && ival > max_val) {
		ival = max_val;
	}
	return ival;
}

function check_int_range(val, def_val, min_val, max_val)
{
	ival = parseInt(val);

	if (isNaN(ival)) {
		return def_val;
	}
	if (min_val != '' && ival < min_val) {
		ival = min_val;
	} else if (max_val != '' && ival > max_val) {
		ival = max_val;
	}
	return ival;
}

</script>
<?php
	if ($action_msg) {
		echo $action_msg;
	}
	reset($schema_tables);
	$sqlchema_completions = array();
	while (list($key, $tabschema) = each($schema_tables)) {
		if ($tabschema->completionScript) {
			$sqlschema_completions[$tabschema->name] = $tabschema->completionScript;
			$src = in_parent_path($tabschema->completionScript);
			echo "<script language=\"javascript\" src=\"$src\">","</script>\n";
		}
	}
	if ($schema_edit_preamble && function_exists("schema_edit_preamble_hook")) {
		schema_edit_preamble_hook($sqlschema);
	}
	if ($schema_edit_show_basic_instructions) {
		if ($schema_edit_global_instructions) {
			echo $schema_edit_global_instructions;
		} else {
?>
<p>To make a new entry in the database, fill in the relevant form data below, and hit the "Insert item" button below.<br>
To modify a previous entry in the database, select it with the checkbox under "select", adjust 
the relevant form data below, and hit the "Modify selected" button below.<br>
To delete entries, check the box in the "delete" column for the undesired entry(s), and hit the "Delete items" button.<br>
Blank the form with the "Clear Form" button.<br>
Fields marked <font color="red">*</font> will, as you type, match current databases,
and give you a selectable menu of results to autocomplete parts of the form.
</p>
<?php
		}
	}
//<P><B>**</b>Remember to select the validation checkbox at the bottom of the page before modifying or inserting an item.<b>**</b></p>

	if ($schema_edit_use_selector) {
		form_header($schema_edit_form_action, "schemaSelectForm", "POST", $schema_edit_form_target, "");
		echo "<b>$schema_edit_selector_text&nbsp;&nbsp;</b>";
		select_array("sqlschema",
				array_keys($sqlschema_selectable_schema), "",
				array_map("ucfirst",array_keys($sqlschema_selectable_schema)),
				"schemaSelectForm.submit();" ,$sqlschema);
		form_tail();
		br();
		br();
	}

	reset($schema_tables);
	$sqlschema_script_str = '';
	while (list($key, $tabschema) = each($schema_tables)) {
		$tabschema_sel_name = "$tabschema->name"."_sqlschema_selected";
		$tabschema_sel = $$tabschema_sel_name;
		$tabschema_sel_pk_name = "schema_edit_select_{$tabschema->name}_primary_key";
		$tabschema_pageno_name = "schema_edit_{$tabschema->name}_pageno";
		$query = "select * from $tabschema->name";
		$tabschema_where = "";
		if ($tabschema->where) {
			$tabschema_where = @eval("return $tabschema->where;");
			if ($tabschema_where) {
				$query .= " where $tabschema_where";
			}
		}
		if ($tabschema->order) {
			$query .= " order by " . $tabschema->order;
		}
		$tabschema_page_len = 0;
		if ($tabschema->editorpagesize > 0) {
			$tabschema_page_len = $tabschema->editorpagesize;
		}
		if (!$schema_edit_supress_menu) {
			if ($tabschema_page_len > 0 ) {
				if (! $$tabschema_pageno_name || $$tabschema_pageno_name < 1)
					$$tabschema_pageno_name = 1;
				$offset = $tabschema_page_len * ($$tabschema_pageno_name-1);
				$query .= " limit $offset, $tabschema_page_len ";
			}
		}
		$result = mysql_query($query);
		if ($result == 0) {
			if (mysql_errno() == MYSQL_ER_NO_SUCH_TABLE) {
				echo "<P>Rebuild the $tabschema->name table: ",
					"this action will lose data from a current table.</p>\n";
				echo "<form",
					" ACTION=\"$schema_edit_form_action\"",
					" NAME=\"createSchemaDB\" ",
					" METHOD=\"POST\"",
					">";
				submit_controls($tabschema->name, "create");
				echo "</form>\n";
				schema_edit_page_bottom();
				exit;
			} else {
				$nitems = 0;
				echo "Unexpected mysql error in query \"$query\":<br>".mysql_error();
				submit_controls($tabschema->name, "error");
				schema_edit_page_bottom();
				exit;
			}
		} else {
			$nitems = mysql_num_rows($result);
		}
	
// define some useful javascript functions ....
		$schema_edit_scripting = true;
		$list_max_n_items = array();
		
// define a javascript function to handle selections within this page
		require(in_parent_path($schema_edit_framework_base."sqlschema_edit_menu.php"));
		
// define a javascript function to clear a few bits that aren't cleared by a form reset
		require(in_parent_path($schema_edit_framework_base."sqlschema_edit_clear_extra.php"));
		$schema_edit_scripting = false;
//////////////////////////////////////////////////////////////////////////////////////////////////////
// start of the form
//////////////////////////////////////////////////////////////////////////////////////////////////////
		$primary_key_name = $tabschema->PrimaryKeyFieldName();
		$secondary_key_name = $tabschema->SecondaryKeyFieldNames();
		$secondary_key_label = $tabschema->SecondaryKeyFieldLabels();
		$secondary_key_type = $tabschema->SecondaryKeyFieldTypes();
//		var_dump($secondary_key_name);
// start of the form
		form_header($schema_edit_form_action, "{$tabschema->name}SchemaDbForm", "POST", $schema_edit_form_target, "multipart/form-data");
// basic hidden variables
//		echo "<tr><td align='center'>\n";
		reset($list_max_n_items);
		while (list($list_n_k, $list_n_v) = each($list_max_n_items)) {
			hidden_field("sqlschema_{$tabschema->name}_list_max[$list_n_k]", $list_n_v);
		}
		submit_controls($tabschema->name, "top");
		if (!$schema_edit_supress_menu) {
			echo "<center>";
			paginator(
				$tabschema->name,
				$mysql,
				$tabschema_where,
				$tabschema_order,
				$primary_key_name,
				$tabschema_page_len, $$tabschema_pageno_name,
				$schema_edit_form_action, $tabschema_pageno_name,
				"paginator","paginator-pgsel",
				"{$tabschema->label} Result Page",
				"",
				"sqlschema=$sqlschema");
			echo "</center>";
		}
		hidden_field("sqlschema", $sqlschema);echo "\n";
		hidden_field("edit_table", $tabschema->name);echo "\n";
		hidden_field($tabschema_pageno_name, $$tabschema_pageno_name);echo "\n";
		hidden_field("schema_edit_supress_menu", $schema_edit_supress_menu);echo "\n";
		hidden_field("schema_edit_use_selector", $schema_edit_use_selector);echo "\n";
		hidden_field("schema_edit_return_url", urlencode($schema_edit_return_url));echo "\n";
		if (isset($schema_edit_insert_option)) {
			hidden_field("schema_edit_insert_option", $schema_edit_insert_option);echo "\n";
		}
		if (isset($schema_edit_delete_option)) {
			hidden_field("schema_edit_delete_option", $schema_edit_delete_option);echo "\n";
		}
		if (isset($schema_edit_modify_option)) {
			hidden_field("schema_edit_modify_option", $schema_edit_modify_option);echo "\n";
		}

//////////// pop out a select menu /////////////////////
		if ($schema_edit_supress_menu) { // or not ... then we are just acting on a single entry
			if ($nitems > 0) {
				mysql_data_seek($result, 0);
			}
			$deleteable = NULL;
			for($i=0; $i < $nitems; $i++) {
				$row = mysql_fetch_object($result);
				$keyval = $row->$primary_key_name;
				if (isset($$tabschema_sel_pk_name)) {
					if ($keyval == $$tabschema_sel_pk_name) {
						$$tabschema_sel_name = $i;
						$deletable = $keyval;
						break;
					}
				} else {
					if ($i == $$tabschema_sel_name) {
						$deletable = $row->code;
						break;
					} else {
					}
				}
			}
			if ($i >= $nitems) {
				$$tabschema_sel_name = "";
			}
			hidden_field($tabschema_sel_name, $$tabschema_sel_name);
			hidden_field('del[]', $deletable);
		} else {
			table_header(0, 2, "", "", "1", "90%", "CENTER");
			echo "<TR>\n";
			echo "<th class=\"schema-del\" ALIGN=LEFT width=8%><FONT SIZE=-1>delete</FONT></TH>\n";
			echo "<th class=\"schema-sel\" ALIGN=LEFT width=8%><FONT SIZE=-1>select</FONT></TH>\n";
			while (list($k,$v) = each($secondary_key_label)) {
				echo "<TH ALIGN=LEFT><FONT SIZE=-1>$v</FONT>\n";
			}
			echo "</TR>\n";
			
			if ($nitems > 0) {
				mysql_data_seek($result, 0);
			}
			for($i=0; $i < $nitems; $i++) {
				$row = mysql_fetch_object($result);
				echo "<TR VALIGN=CENTER>\n";
				$keyval = $row->$primary_key_name;
				if (index_of($row->code, $del) >= 0) {
					$td = "<INPUT TYPE=CHECKBOX name='del[]' value=$keyval CHECKED>\n";
				} else {
					$td = "<INPUT TYPE=CHECKBOX name='del[]' value=$keyval>\n";
				}
				table_data_string($td, "LEFT", 1, "schema-del");
				$td = "<INPUT TYPE=RADIO name='$tabschema_sel_name' value=$i VALIGN=CENTER";
				if (isset($$tabschema_sel_pk_name)) {
					if ($keyval == $$tabschema_sel_pk_name) {
						$td .= " CHECKED";
						$$tabschema_sel_name = $i;
					}
				} else {
					if ($i == $$tabschema_sel_name) {
						$td .= " CHECKED";
					} else {
					}
				}
				$td .= " onClick=\"display_selected_$tabschema->name"."($i);\">\n";
				table_data_string($td, "LEFT", 1, "schema-sel");
				reset($secondary_key_name);
				while (list($sk,$sv) = each($secondary_key_name)) {
					if ($secondary_key_type[$sk] == "bool") {
				 		table_data_string(($row->$sv)?"<b>X</b>":" ", 'CENTER', 20);
					} else {
				 		table_data_string($row->$sv, "LEFT", 20);
				 	}
				}
				echo "</TR>";
			}
			echo "</TABLE>\n";
			br();
		}
/////////////////////////////// end of select menu

		if ($tabschema->documentation) {
			echo "<br><p>$tabschema->documentation</p>\n";
		}
		echo "<table cellspacing='2' cellpadding='2'>";
		reset ($tabschema->field);

		while (list($key, $val) = each($tabschema->field)) {
			if ($val->type == "table-var") {
			} elseif ($val->type == "row-var") {
			} elseif ($val->is_hidden && @eval("return $val->is_hidden;")) {
				hidden_field($val->name, $val->value);
			} else {
				if ($val->is_password) {
	//  <td><B>Password</B><BR><input type="password" name="passwd" size="20"><input type="hidden" name="enc_passwd" size="20"></td>
				} else {
	   			if ($val->is_fixed) {
	   				$fixt = @eval("return $val->is_fixed;");
	   			} else {
	   				$fixt = false;
	   			}
	//   			echo $fixt, " ", $val->is_fixed, " ", @eval("return $val->is_fixed;");
					switch ($val->type) {
						case "list":
				// don't really expect to find a list here, list type needs fields from user type structure.
				// maybe should generate an error, but will just politely ignore it
							break;
							
						case "set":
				// don't really expect to find a set here, set type needs fields from user type structure.
				// maybe should generate an error, but will just politely ignore it
	   					break;
	   					
						case "enum":
				// don't really expect to find a enum here, enum type needs fields from user type structure.
				// maybe should generate an error, but will just politely ignore it
							break;
							
						case "image":
						case "upload":
							echo "<tr><td width=\"$schema_edit_columnar_label_width\">\n";
							$nm = $val->name;
							if ($val->label) {
								echo "<B>$val->label</B><BR>\n";
							}
	   					if ($val->documentation) {
	   						echo "$val->documentation<br>\n";
	   					}
	   					if ($schema_edit_columnar) echo "</td><td>\n";
							hidden_field($nm, $field_val->value);
							if ($val->type == "image") {
								$displayer_tag = "<img name=\"__{$nm}\" id=\"__{$nm}\" target=\"_blank\" style=\"display:none\" src=\"pathtofile\">";
							} else {
								$displayer_tag = "<a name=\"__{$nm}\" id=\"__{$nm}\" target=\"_blank\" style=\"display:none\" href=\"pathtofile\">View Upload</a>";
							}
	   					if ($fixt) {
								echo $displayer_tag;
	   					} else {
								upload_input("{$nm}__", "", $val->element_class, $val->width);
								echo "<input type=\"button\" value=\"X\" onClick=\"form.elements['$nm'].value='';ank=document.getElementById('__$nm');if (ank != undefined) ank.style.display='none';\">";
								echo $displayer_tag;
//								echo "<input type=\"text\" name=\"_{$nm}\" value=\"$field_val->value\" disabled size=\"14\">";
//								echo "<input type=\"button\" value=\"X\" onClick=\"form.elements['$nm'].value='';\">";
							}
	   					echo "</td>\n";
							break;
							
						case "bool":
							echo "<tr><td width=\"$schema_edit_columnar_label_width\">\n";
							$nm = $val->name;
							if ($val->label) {
								echo "<B>$val->label</B>";
							}
	   					if ($schema_edit_columnar) {
		   					if ($val->documentation) {
		   						echo "<br>$val->documentation<br>";
		   					}
	   						echo "</td><td>\n";
	   					}
							checkbox_input($nm, "1", false, $val->element_class, $fixt);
	   					if (!$schema_edit_columnar) {
	   						if ($val->documentation) {
		   						echo "<br>\n$val->documentation\n";
		   					}
	   					}
   						echo "</td>\n";
							break;
													
						case "text":
							echo "<tr><td width=\"$schema_edit_columnar_label_width\">\n";
							$nm = $val->name;
							echo "<B>$val->label</B>";
	   					if ($val->documentation) {
	   						echo "<br>\n$val->documentation";
	   					}
	   					if ($schema_edit_columnar) echo "</td><td>\n"; else echo "<br>\n";
							text_area($nm, $val->value, $val->width, $val->height, $val->on_change, $val->element_class, $fixt);
	   					echo "</td>\n";
							break;
							
						case "time":
						case "datetime":
						case "date":
						case "timestamp":
						case "year":
							$on_change = time_input_range_check(
													$val->type,
													$val->qualifiers,
													$val->mid_value?$val->mid_value:
														($val->value?$val->value:0),
													$val->min_value,
													$val->max_value);

							if ($val->on_change) {
								$on_change .= ";$val->on_change;";
							}
							echo "<tr><td width=\"$schema_edit_columnar_label_width\">\n";
							$nm = $val->name;
							echo "<B>$val->label</B>&nbsp;";
	   					if ($val->documentation) {
	   						echo "<br>\n$val->documentation";
	   					}
   						if ($schema_edit_columnar) echo "</td><td>\n"; else echo "<br>\n";
							time_input($nm, $val->value, $val->type, $val->width, $val->maxlen, $on_change, $val->element_class, $fixt);
							echo "</td>\n";
							break;
							
						case "tinyint":
						case "mediumint":
						case "bigint":
						case "int":
						case "integer":
							$on_change = integer_input_range_check(
													$val->type,
													$val->qualifiers,
													$val->mid_value?$val->mid_value:
														($val->value?$val->value:0),
													$val->min_value,
													$val->max_value);

							if ($val->on_change) {
								$on_change .= ";$val->on_change;";
							}
							echo "<tr><td width=\"$schema_edit_columnar_label_width\">\n";
							$nm = $val->name;
							echo "<B>$val->label</B>&nbsp;";
	   					if ($val->documentation) {
	   						echo "<br>\n$val->documentation";
	   					}
   						if ($schema_edit_columnar) echo "</td><td>\n"; else echo "<br>\n";
							text_input($nm, $val->value, $val->width, $val->maxlen, $on_change, $val->element_class, $fixt);
							echo "</td>\n";
							break;
							
						case "double":
						case "real":
						case "decimal":
						case "float":
							$mid = $val->mid_value?$val->mid_value:
										($val->value?$val->value:0.0);
							if (is_numeric($val->min_value) || is_numeric($val->max_value)) {
								$min = $val->min_value?$val->min_value:"''";
								$max = $val->max_value?$val->max_value:"''";
								$on_change = "value=check_float_range(value,$mid, $min, $max)";
							} else {
								$on_change = "value=check_float(value,$mid)";
							}
							
							if ($val->on_change) {
								$on_change .= ";$val->on_change;";
							}
							echo "<tr><td width=\"$schema_edit_columnar_label_width\">\n";
							$nm = $val->name;
							echo "<B>$val->label</B>&nbsp;";
	   					if ($val->documentation) {
	   						echo "<br>\n$val->documentation";
	   					}
   						if ($schema_edit_columnar) echo "</td><td>\n"; else echo "<br>\n";
							text_input($nm, $val->value, $val->width, $val->maxlen, $on_change, $val->element_class, $fixt);
							echo "</td>\n";
							break;
							
						default:
							if (!$schema_types[$val->type]) { // a base sql type
								echo "<tr><td width=\"$schema_edit_columnar_label_width\">\n";
								$nm = $val->name;
								$field_completion_script = match_complete_script(
											$val,
											"{$tabschema->name}SchemaDbForm",
											-1,
											$val->completionClass,
											$sqlschema_completions[$tabschema->name]);
								if ($field_completion_script) {
									echo "<font color='red'>*</font><B>$val->label</B>&nbsp;";
								} else {
									echo "<B>$val->label</B>&nbsp;";
								}
		   					if ($val->documentation) {
		   						echo "<br>\n$val->documentation";
		   					}
	   						if ($schema_edit_columnar) echo "</td><td>\n"; else echo "<br>\n";
								text_input($nm, $val->value, $val->width, $val->maxlen, $val->on_change, $val->element_class, $fixt);
								$sqlschema_script_str .= $field_completion_script;
								echo "</td>\n";
							} else {
								$field_template = &$schema_types[$val->type];
								switch ($field_template->type) {
									case "set":
										echo "<tr><td width=\"$schema_edit_columnar_label_width\">\n";
						   			if ($val->is_fixed) {
						   				$fixt = @eval("return $val->is_fixed;");
						   			} else {
						   				$fixt = false;
						   			}
										$nm = $val->name."[]";
										if ($val->label) {
					   					echo "<B>$val->label</B><BR>\n";
				   					} elseif ($field_template->label) {
											echo "<B>$field_template->label</B><BR>\n";
										}
				   					if ($val->documentation) {
				   						echo "$val->documentation<br>\n";
				   					}
				   					if ($field_template->documentation) {
				   						echo "$field_template->documentation<br>\n";
				   					}
	   								if ($schema_edit_columnar) echo "</td><td>\n";
				   					if ($field_template->is_hidden && @eval("return $field_template->is_hidden;")) {
											hidden_field($nm, $val->value);
				   					} else {
					   					checkbox_array($nm, $field_template->ValueNameArray(), "", $field_template->ValueLabelArray(), $fixt);
					   				}
	   								echo "</td>\n";
										break;
										
									case "enum":
										echo "<tr><td width=\"$schema_edit_columnar_label_width\">\n";
						   			if ($val->is_fixed) {
						   				$fixt = @eval("return $val->is_fixed;");
						   			} else {
						   				$fixt = false;
						   			}
										if ($val->label) {
											echo "<B>$val->label</B>&nbsp;";
				   					} elseif ($field_template->label) {
											echo "<B>$field_template->label</B>&nbsp;";
										}
				   					if ($val->documentation) {
				   						echo "<br>\n$val->documentation";
				   					}
				   					if ($field_template->documentation) {
				   						echo "<br>\n$field_template->documentation";
				   					}
	   								if ($schema_edit_columnar) echo "</td><td>\n"; else echo "<br>\n";
				   					if ($field_template->is_hidden && @eval("return $field_template->is_hidden;")) {
											hidden_field("$val->name", $val->value);
				   					} else {
											select_array("$val->name",
													$field_template->ValueNameArray(), "", $field_template->ValueLabelArray(),
													NULL, NULL, $fixt);
										}
	   								echo "</td>\n";
										break;
									
									case "image":
									case "upload":
										echo "<tr><td width=\"$schema_edit_columnar_label_width\">\n";
						   			if ($val->is_fixed) {
						   				$fixt = @eval("return $val->is_fixed;");
						   			} else {
						   				$fixt = false;
						   			}
										$nm = $val->name;
										$unm = "{$nm}__";
										if ($val->label) {
											echo "<B>$val->label</B><BR>\n";
				   					} elseif ($field_template->label) {
											echo "<B>$field_template->label</B><BR>\n";
										}
				   					if ($val->documentation) {
				   						echo "$val->documentation<br>\n";
				   					}
				   					if ($field_template->documentation) {
				   						echo "$field_template->documentation<br>\n";
				   					}
	   								if ($schema_edit_columnar) echo "</td><td>\n";
										hidden_field($nm, $field_val->value);
										if ($field_template->type == "image") {
											$displayer_tag = "<img name=\"__{$nm}\" id=\"__{$nm}\" target=\"_blank\" style=\"display:none\" src=\"pathtofile\">";
										} else {
											$displayer_tag = "<a name=\"__{$nm}\" id=\"__{$nm}\" target=\"_blank\" style=\"display:none\" href=\"pathtofile\">View Upload</a>";
										}
				   					if ($fixt) {
											echo $displayer_tag;
				   					} else {
											upload_input($unm, "", $val->element_class, $val->width);
											echo "<input type=\"button\" value=\"X\" onClick=\"form.elements['$nm'].value='';ank=document.getElementById('__$nm');if (ank != undefined) ank.style.display='none';\">";
											echo $displayer_tag;
//											echo "<input type=\"text\" name=\"_{$nm}\" value=\"$field_val->value\" disabled size=\"14\">";
//											echo "<input type=\"button\" value=\"X\" onClick=\"form.elements['$nm'].value='';form.elements['_$nm'].value='';\">";
										}
	   								echo "</td>\n";
										break;
										
									case "bool":
										echo "<tr><td width=\"$schema_edit_columnar_label_width\">\n";
						   			if ($val->is_fixed) {
						   				$fixt = @eval("return $val->is_fixed;");
						   			} else {
						   				$fixt = false;
						   			}
										$nm = $val->name;
										if ($val->label) {
											echo "<B>$val->label</B>";
				   					} elseif ($field_template->label) {
											echo "<B>$field_template->label</B>";
										}
	   								if ($schema_edit_columnar) echo "</td><td>\n";
				   					if ($field_template->is_hidden  && @eval("return $field_template->is_hidden;")) {
											hidden_field("$nm", $val->value);
				   					} else {
											checkbox_input($nm, "1", false, $val->element_class, $fixt);
										}
										echo "<br>\n";
				   					if ($val->documentation) {
				   						echo "$val->documentation<br>\n";
				   					}
				   					if ($field_template->documentation) {
				   						echo "$field_template->documentation<br>\n";
				   					}
	   								echo "</td>\n";
										break;
						
									case "time":
									case "datetime":
									case "date":
									case "timestamp":
									case "year":
										$on_change = time_input_range_check(
																$field_template->type,
																$val->qualifiers?$val->qualifiers:$field_template->qualifiers,
																$val->mid_value?$val->mid_value:
																	($field_template->mid_value?$field_template->mid_value:
																	($val->value?$val->value:
																	($field_template->value?$field_template->value:
																	0))),
																$val->min_value?$val->min_value:
																	($field_template->min_value?$field_template->min_value:
																	NULL),
																$val->max_value?$val->max_value:
																	($field_template->max_value?$field_template->max_value:
																	NULL));
			
										if ($val->on_change) {
											$on_change .= ";$val->on_change;";
										} elseif ($field_template->on_change) {
											$on_change .= ";$field_template->on_change;";
										}
										echo "<tr><td width=\"$schema_edit_columnar_label_width\">\n";
						   			if ($val->is_fixed) {
						   				$fixt = @eval("return $val->is_fixed;");
										} elseif ($field_template->on_change) {
						   				$fixt = @eval("return $val->is_fixed;");
						   			} else {
						   				$fixt = false;
						   			}
										echo "<B>$val->label</B>";
				   					if ($val->documentation) {
				   						echo "<br>\n$val->documentation";
				   					}
				   					if ($field_template->documentation) {
				   						echo "<br>\n$field_template->documentation";
				   					}
	   								if ($schema_edit_columnar) echo "</td><td>\n"; else echo "<br>\n";
										time_input($field_template->name,
													$field_template->value,
													$field_template->type,
													$field_template->width,
													$field_template->maxlen,
													$on_change,
													$field_template->element_class,
													$fixt);
										echo "</td>\n";
										break;
										
									case "tinyint":
									case "mediumint":
									case "bigint":
									case "int":
									case "integer":
										$on_change = integer_input_range_check(
																$field_template->type,
																$val->qualifiers?$val->qualifiers:$field_template->qualifiers,
																$val->mid_value?$val->mid_value:
																	($field_template->mid_value?$field_template->mid_value:
																	($val->value?$val->value:
																	($field_template->value?$field_template->value:
																	0))),
																$val->min_value?$val->min_value:
																	($field_template->min_value?$field_template->min_value:
																	NULL),
																$val->max_value?$val->max_value:
																	($field_template->max_value?$field_template->max_value:
																	NULL));
			
										if ($val->on_change) {
											$on_change .= ";$val->on_change;";
										} elseif ($field_template->on_change) {
											$on_change .= ";$field_template->on_change;";
										}
										echo "<tr><td width=\"$schema_edit_columnar_label_width\">\n";
						   			if ($val->is_fixed) {
						   				$fixt = @eval("return $val->is_fixed;");
										} elseif ($field_template->on_change) {
						   				$fixt = @eval("return $val->is_fixed;");
						   			} else {
						   				$fixt = false;
						   			}
										echo "<B>$val->label</B>";
				   					if ($val->documentation) {
				   						echo "<br>\n$val->documentation";
				   					}
				   					if ($field_template->documentation) {
				   						echo "<br>\n$field_template->documentation";
				   					}
	   								if ($schema_edit_columnar) echo "</td><td>\n"; else echo "<br>\n";
										text_input($field_template->name,
													$field_template->value,
													$field_template->width,
													$field_template->maxlen,
													$on_change,
													$field_template->element_class,
													$fixt);
										echo "</td>";
										break;
										
									case "double":
									case "real":
									case "decimal":
									case "float":
										$mid = $val->mid_value?$val->mid_value:
															($field_template->mid_value?$field_template->mid_value:
															($val->value?$val->value:
															($field_template->value?$field_template->value:
															0.0)));
										if (is_numeric($val->min_value) ||
												is_numeric($val->max_value) ||
												is_numeric($field_template->min_value) ||
												is_numeric($field_template->max_value)) {
											$min = $val->min_value?$val->min_value:
																($field_template->min_value?$field_template->min_value:
																"''");
											$max = $val->max_value?$val->max_value:
																($field_template->min_value?$field_template->min_value:
																"''");
											$on_change = "value=check_float_range(value,$mid, $min, $max)";
										} else {
											$on_change = "value=check_float(value,$mid)";
										}
										if ($val->on_change) {
											$on_change .= ";$val->on_change;";
										} elseif ($field_template->on_change) {
											$on_change .= ";$field_template->on_change;";
										}
										echo "<tr><td width=\"$schema_edit_columnar_label_width\">\n";
						   			if ($val->is_fixed) {
						   				$fixt = @eval("return $val->is_fixed;");
										} elseif ($field_template->is_fixed) {
						   				$fixt = @eval("return $val->is_fixed;");
						   			} else {
						   				$fixt = false;
						   			}
										echo "<B>$val->label</B>";
				   					if ($val->documentation) {
				   						echo "<br>\n$val->documentation";
				   					}
				   					if ($field_template->documentation) {
				   						echo "<br>\n$field_template->documentation";
				   					}
	   								if ($schema_edit_columnar) echo "</td><td>\n"; else echo "<br>\n";
										text_input($field_template->name,
													$field_template->value,
													$field_template->width,
													$field_template->maxlen,
													$on_change,
													$field_template->element_class,
													$fixt);
										echo "</td>";
										break;
										
									case "list":
										echo "<tr><td", $schema_edit_columnar? " colspan='2'" : "" , " align='left'",",>\n";
				   					if ($val->label) {
				   						echo "<B>$val->label</B><BR>\n";
										}
				   					if ($val->documentation) {
				   						echo "$val->documentation<br>\n";
				   					}
				   					if ($field_template->documentation) {
				   						echo "$field_template->documentation<br>\n";
				   					}
				   					echo "</td></tr>";
										echo "<tr><td", $schema_edit_columnar? " colspan='2'" : "" , " align='left'",",>\n";
				   					$ft_row_cnt = 0;
										table_header(0,0, "", "", 1, "");
										table_row();
										reset ($field_template->value);
										while (list($field_key, $field_val) = each($field_template->value)) {
											if ($field_val->is_hidden && @eval("return $field_val->is_hidden;")) {
											} else {
												table_data("center", "top");
												if ($field_val->matchUrlPath) {
													echo "<font color='red'>*</font>";
												}
												if ($field_val->label) {
													echo $field_val->label;
												} else {
													echo "&nbsp;";
												}
												if ($field_val->documentation) {
													echo "<br>(",$field_val->documentation,")";
												}
												table_dend();
											}
										}
										table_rend();
										
										if ($field_template->size > 0) {
											$list_length = $field_template->size;
										} elseif ($schema_edit_list_length > 0) {
											$list_length = $schema_edit_list_length;
										} else {
											$list_length = 6;
										}
										if ($list_max_n_items[$val->name] >= $list_length) {
											$extend_by = ($field_template->extend_by)?$field_template->extend_by:1;
											$list_length = $list_max_n_items[$val->name]+$extend_by;
											if ($field_template->maxlength && ($list_length > $field_template->maxlength)) {
												$list_length = $field_template->maxlength;
											}
										}
										
										while ($ft_row_cnt < $list_length) {
											table_row();
											reset ($field_template->value);
											while (list($field_key, $field_val) = each($field_template->value)) {
												$nm = $field_val->name."[$ft_row_cnt]";
												if ($field_val->is_hidden && @eval("return $field_val->is_hidden;")) {
													hidden_field($nm,	$field_val->value);
												} else {
//													echo " ^ ", $field_val->type;
													if ($schema_types[$field_val->type]) {
														$field_val_template = &$schema_types[$field_val->type];
														table_data("left", "top");
					//									echo "$field_template->name $field_val_template->type $field_val_template->name";
										   			if ($field_val->is_fixed) {
										   				$field_fixt = @eval("return $field_val->is_fixed;");
										   			} else {
										   				$field_fixt = false;
										   			}
														switch ($field_val_template->type) {
															case "enum":
																if ($field_val->is_hidden && @eval("return $field_val->is_hidden;")) {
																	hidden_field($nm,	$field_val->value);
																} else {
																	select_array(
																			"$field_val->name"."[$ft_row_cnt]",
																			$field_val_template->ValueNameArray(),
																			"",
																			$field_val_template->ValueLabelArray(),
																			"", NULL, $field_fixt);
																}
																break;
																
															case "list":	// don't stress about this one.
																errorpage("lists of lists not currently implemented in this framework. try a pair of tables");
																break;
																
															case "set":
																if ($field_val->is_hidden  && @eval("return $field_val->is_hidden;")) {
																	hidden_field($nm,	$field_val->value);
																} else {
					   											checkbox_array(
					   													"$field_val->name"."[$ft_row_cnt][]",
					   													$field_val_template->ValueNameArray(), "",
					   													NULL, $field_fixt);
																}
																break;
																
															case "image":
															case "upload":
																$unm = "$field_val->name"."__$ft_row_cnt"; // uploads are sent as fields in arrays...
																hidden_field($nm, $field_val->value);
																if ($field_val_template->type == "image") {
																	$displayer_tag = "<img name=\"__{$nm}\" id=\"__{$nm}\" target=\"_blank\" style=\"display:none\" src=\"pathtofile\">";
																} else {
																	$displayer_tag = "<a name=\"__{$nm}\" id=\"__{$nm}\" target=\"_blank\" style=\"display:none\" href=\"pathtofile\">View Upload</a>";
																}
																if ($field_fixt) {
																	echo $displayer_tag;
																} else {
																	upload_input($unm, "",
																		$field_val->element_class?$field_val->element_class:$field_val_template->element_class,
																		$field_val->width?$field_val->width:$field_val_template->width);
																	echo "<input type=\"button\" value=\"X\" onClick=\"form.elements['$nm'].value='';ank=document.getElementById('__$nm');if (ank != undefined) ank.style.display='none';\">";
																	echo $displayer_tag;
//																	echo "<input type=\"text\" name=\"_{$nm}\" value=\"$field_val->value\" disabled size=\"14\">"; // holds current uploaded value
//																	echo "<input type=\"button\" value=\"X\" onClick=\"form.elements['$nm'].value='';form.elements['_$nm'].value='';\">";
																}
																break;
																
															case "bool":
																$nm = "$field_val->name"."[$ft_row_cnt]";
																checkbox_input($nm,
																	"1",
																	false,
																	$field_val->element_class?$field_val->element_class:$field_val_template->element_class,
																	$field_fixt);
																break;
																
															case "text":
																$nm = "$field_val->name"."[$ft_row_cnt]";
																text_area(
																	$nm,
																	$field_val->value?$field_val->value:$field_val_template->value,
																	$field_val->width?$field_val->width:$field_val_template->width,
																	$field_val->height?$field_val->height:$field_val_template->height,
																	$field_val->on_change?$field_val->on_change:$field_val_template->on_change,
																	$field_val->element_class?$field_val->element_class:$field_val_template->element_class,
																	$field_fixt);
																break;
							
															case "time":
															case "datetime":
															case "date":
															case "timestamp":
															case "year":
																$on_change = time_input_range_check(
																						$field_val_template->type,
																						$field_val->qualifiers?
																							$field_val->qualifiers:
																							$field_val_template->qualifiers,
																						$field_val->mid_value?$field_val->mid_value:
																							($field_val_template->mid_value?$field_val_template->mid_value:
																							($field_val->value?$field_val->value:
																							($field_val_template->value?$field_val_template->value:
																							0))),
																						$field_val->min_value?$field_val->min_value:
																							($field_val_template->min_value?$field_val_template->min_value:
																							NULL),
																						$field_val->max_value?$field_val->max_value:
																							($field_val_template->max_value?$field_val_template->max_value:
																							NULL));
			
									
																if ($field_val->on_change) {
																	$on_change .= ";$field_val->on_change;";
																}
																$nm = "$field_val->name"."[$ft_row_cnt]";
																time_input(
																	$nm,
																	$field_val->value?$field_val->value:$field_val_template->value,
																	$field_val_template->type,
																	$field_val->width?$field_val->width:$field_val_template->width,
																	$field_val->maxlen?$field_val->maxlen:$field_val_template->maxlen,
																	$on_change,
																	$field_val->element_class?$field_val->element_class:$field_val_template->element_class,
																	$field_fixt);
																break;
										
															case "tinyint":
															case "mediumint":
															case "bigint":
															case "int":
															case "integer":
																$on_change = integer_input_range_check(
																						$field_val_template->type,
																						$field_val->qualifiers?
																							$field_val->qualifiers:
																							$field_val_template->qualifiers,
																						$field_val->mid_value?$field_val->mid_value:
																							($field_val_template->mid_value?$field_val_template->mid_value:
																							($field_val->value?$field_val->value:
																							($field_val_template->value?$field_val_template->value:
																							0))),
																						$field_val->min_value?$field_val->min_value:
																							($field_val_template->min_value?$field_val_template->min_value:
																							NULL),
																						$field_val->max_value?$field_val->max_value:
																							($field_val_template->max_value?$field_val_template->max_value:
																							NULL));
									
																if ($field_val->on_change) {
																	$on_change .= ";$field_val->on_change;";
																}
																$nm = "$field_val->name"."[$ft_row_cnt]";
																text_input(
																	$nm,
																	$field_val->value?$field_val->value:$field_val_template->value,
																	$field_val->width?$field_val->width:$field_val_template->width,
																	$field_val->maxlen?$field_val->maxlen:$field_val_template->maxlen,
																	$on_change,
																	$field_val->element_class?$field_val->element_class:$field_val_template->element_class,
																	$field_fixt);
																break;
							
															case "double":
															case "real":
															case "decimal":
															case "float":
																$mid = $field_val->mid_value?$field_val->mid_value:
																					($field_val_template->mid_value?$field_val_template->mid_value:
																					($field_val->value?$field_val->value:
																					($field_val_template->value?$field_val_template->value:
																					0.0)));
																if (is_numeric($field_val->min_value) ||
																		is_numeric($field_val->max_value) ||
																		is_numeric($field_val_template->min_value) ||
																		is_numeric($field_val_template->max_value)) {
																	$min = $field_val->min_value?$field_val->min_value:
																						($field_val_template->min_value?$field_val_template->min_value:
																						"''");
																	$max = $field_val->max_value?$field_val->max_value:
																						($field_val_template->min_value?$field_val_template->min_value:
																						"''");
																	$on_change = "value=check_float_range(value,$mid, $min, $max)";
																} else {
																	$on_change = "value=check_float(value,$mid)";
																}
																if ($field_val->on_change) {
																	$on_change .= ";$field_val->on_change;";
																}
																$nm = "$field_val->name"."[$ft_row_cnt]";
																text_input(
																	$nm,
																	$field_val->value?$field_val->value:$field_val_template->value,
																	$field_val->width?$field_val->width:$field_val_template->width,
																	$field_val->maxlen?$field_val->maxlen:$field_val_template->maxlen,
																	$on_change,
																	$field_val->element_class?$field_val->element_class:$field_val_template->element_class,
																	$field_fixt);
																break;
							
															default:
																text_input(
																		"$field_val->name"."[$ft_row_cnt]",
																		$field_val->value?$field_val->value:$field_val_template->value,
																		$field_val->width?$field_val->width:$field_val_template->width,
																		$field_val->maxlen?$field_val->maxlen:$field_val_template->maxlen,
																		$field_val->on_change?$field_val->on_change:$field_val_template->on_change,
																		$field_val->element_class?$field_val->element_class:$field_val_template->element_class,
																		$field_fixt);
																$sqlschema_script_str .= match_complete_script(
																		$field_val,
																		"{$tabschema->name}SchemaDbForm",
																		$ft_row_cnt,
																		$field_val->completionClass,
																		$sqlschema_completions[$tabschema->name]);
																break;
														}
														table_dend();
													} else { // a list field which is a standard internal type ...
										   			if ($field_val->is_fixed) {
										   				$field_fixt = @eval("return $field_val->is_fixed;");
										   			} else {
										   				$field_fixt = false;
										   			}
														table_data("left", "top");
														switch ($field_val->type) {
															case "image":
															case "upload":
																$nm = "$field_val->name"."[$ft_row_cnt]";
																$unm = "$field_val->name"."__$ft_row_cnt"; // uploads are sent as fields in arrays...
																hidden_field($nm, $field_val->value);
																if ($field_val->type == "image") {
																	$displayer_tag = "<img name=\"__{$nm}\" id=\"__{$nm}\" target=\"_blank\" src=\"pathtofile\">";
//																	$displayer_tag = "<img name=\"__{$nm}\" id=\"__{$nm}\" target=\"_blank\" style=\"display:none\" width=100 height=100  src=\"pathtofile\">";
																} else {
																	$displayer_tag = "<a name=\"__{$nm}\" id=\"__{$nm}\" target=\"_blank\" style=\"display:none\" href=\"pathtofile\">View Upload</a>";
																}
																if ($field_fixt) {
																	echo $displayer_tag;
																} else {
																	upload_input($unm, "", $field_val->element_class, $field_val->width);
																	echo "<input type=\"button\" value=\"X\" onClick=\"form.elements['$nm'].value='';ank=document.getElementById('__$nm');ank.style.display='none';\">";
																	echo $displayer_tag;
//																	echo "<input type=\"text\" name=\"_{$nm}\" value=\"$field_val->value\" disabled size=\"14\">";
//																	echo "<input type=\"button\" value=\"X\" onClick=\"form.elements['$nm'].value='';form.elements['_$nm'].value='';\">";
																}
																break;
																
															case "bool":
																$nm = "$field_val->name"."[$ft_row_cnt]";
																checkbox_input($nm, "1", false, $field_val->element_class, $field_fixt);
																break;
																
															case "text":
																$nm = "$field_val->name"."[$ft_row_cnt]";
																text_area(
																	$nm,
																	$field_val->value,
																	$field_val->width,
																	$field_val->height,
																	$field_val->on_change,
																	$field_val->element_class,
																	$field_fixt);
																break;
																
															case "time":
															case "datetime":
															case "date":
															case "timestamp":
															case "year":
																$on_change = time_input_range_check(
																						$field_val->type,
																						$field_val->qualifiers,
																						$field_val->mid_value?$field_val->mid_value:
																							($field_val->value?$field_val->value:0),
																						$field_val->min_value?$field_val->min_value:
																							NULL,
																						$field_val->max_value?$field_val->max_value:
																							NULL);
			
									
									
																if ($field_val->on_change) {
																	$on_change .= ";$field_val->on_change;";
																}
																$nm = "$field_val->name"."[$ft_row_cnt]";
																time_input(
																	"$field_val->name"."[$ft_row_cnt]",
																	$field_val->value,
																	$field_val->type,
																	$field_val->width,
																	$field_val->maxlen,
																	$on_change,
																	$field_val->element_class,
																	$field_fixt);
																break;
										
															case "tinyint":
															case "mediumint":
															case "bigint":
															case "int":
															case "integer":
																$on_change = integer_input_range_check(
																						$field_val->type,
																						$field_val->qualifiers,
																						$field_val->mid_value?$field_val->mid_value:
																							($field_val->value?$field_val->value:0),
																						$field_val->min_value?$field_val->min_value:
																							NULL,
																						$field_val->max_value?$field_val->max_value:
																							NULL);
									
																if ($field_val->on_change) {
																	$on_change .= ";$field_val->on_change;";
																}
																$nm = "$field_val->name"."[$ft_row_cnt]";
																text_input(
																	"$field_val->name"."[$ft_row_cnt]",
																	$field_val->value,
																	$field_val->width,
																	$field_val->maxlen,
																	$on_change,
																	$field_val->element_class,
																	$field_fixt);
																break;
							
															case "double":
															case "real":
															case "decimal":
															case "float":
																if (is_numeric($field_val->min_value) || is_numeric($field_val->max_value)) {
																	$min = $field_val->min_value?$field_val->min_value:"''";
																	$max = $field_val->max_value?$field_val->max_value:"''";
																	if (is_numeric($field_val->mid_value)) {
																		$on_change = "value=check_float_range(value,$field_val->mid_value, $min, $max)";
																	} elseif (is_numeric($field_val->value)) {
																		$on_change = "value=check_float_range(value,$field_val->value, $min, $max)";
																	} else {
																		$on_change = "value=check_float_range(value,0.0, $min, $max)";
																	}
																} else {
																	if (is_numeric($field_val->mid_value)) {
																		$on_change = "value=check_float(value,$field_val->mid_value)";
																	} elseif (is_numeric($field_val->value)) {
																		$on_change = "value=check_float(value,$field_val->value)";
																	} else {
																		$on_change = "value=check_float(value,0.0)";
																	}
																}
																if ($field_val->on_change) {
																	$on_change .= ";$field_val->on_change;";
																}
																$nm = "$field_val->name"."[$ft_row_cnt]";
																text_input(
																	"$field_val->name"."[$ft_row_cnt]",
																	$field_val->value,
																	$field_val->width,
																	$field_val->maxlen,
																	$on_change,
																	$field_val->element_class,
																	$field_fixt);
																break;
							
															default:
																text_input(
																	"$field_val->name"."[$ft_row_cnt]",
																	$field_val->value,
																	$field_val->width,
																	$field_val->maxlen,
																	$field_val->on_change,
																	$field_val->element_class,
																	$field_fixt);
																$sqlschema_script_str .= match_complete_script(
																		$field_val,
																		"{$tabschema->name}SchemaDbForm",
																		$ft_row_cnt,
																		$field_val->completionClass,
																		$sqlschema_completions[$tabschema->name]);
																break;
														}
														table_dend();
													}
												}
											}
											table_rend();
											$ft_row_cnt++;
										}
										table_tail();
										echo "</td>\n";
										break;
										
																
									default:
										echo "<tr><td width=\"$schema_edit_columnar_label_width\">\n";
						   			if ($val->is_fixed) {
						   				$fixt = @eval("return $val->is_fixed;");
						   			} else {
						   				$fixt = false;
						   			}
										$field_completion_script = match_complete_script(
													$field_template,
													"{$tabschema->name}SchemaDbForm",
													-1,
													$field_template->completionClass,
													$sqlschema_completions[$tabschema->name]);
										if ($completion_script) {
											echo "<font color='red'>*</font><B>$val->label</B>&nbsp;";
										} else {
											echo "<B>$val->label</B>&nbsp;";
										}
				   					if ($val->documentation) {
				   						echo "<br>\n$val->documentation";
				   					}
				   					if ($field_template->documentation) {
				   						echo "<br>\n$field_template->documentation";
				   					}
	   								if ($schema_edit_columnar) echo "</td><td>\n"; else echo "<br>\n";
										text_input($field_template->name,
													$field_template->value,
													$field_template->width,
													$field_template->maxlen,
													$field_template->on_change,
													$field_template->element_class,
													$fixt);
										$sqlschema_script_str .= $field_completion_script;
										echo "</td>";
										break;
								}
							}
							break;
					} ////////////////////// End of main switch($val->type)
				} /////////////// End of else for not a field that isn't a password
				echo "</tr>";
			} /////////////////// End of else for a field that should be visible
		} ///////////////// End of main while
		
		if ($tabschema->validation) {
			if (!$tabschema->validation_displayed || @eval("return $tabschema->validation_displayed;")) {
				echo "<TR><TD colspan=2>\n";
				div("validation");
				echo "<p>$tabschema->validation</p>\n";
				if (!$tabschema->validation_enforced || @eval("return $tabschema->validation_enforced;")) {
					if (!$tabschema->validation_condition) {
						echo checkbox_str("sqlschema_$tabschema->name"."_validated","affirmed","","<b>I affirm that this is true</b>");
					}
				}
				div();
				echo "</td></tr>\n";
			}
		}
		echo "</table>";
		submit_controls($tabschema->name, "bot");
		echo "</FORM>\n";
		echo "<SCRIPT LANGUAGE=\"javascript\">\n";
		if ($schema_edit_supress_menu) {
			echo "\tdisplay_selected_{$tabschema->name}(",$$tabschema_sel_name,");\n";
		} else {
			echo "selfield = document.{$tabschema->name}SchemaDbForm.{$tabschema_sel_name};";
			echo "selwhich = 0;\n";
			echo "radiowhich = 0;";
			echo "if (selfield == undefined) {\n";
			echo "} else if (selfield.length == undefined) {\n"; // a single radio button
			echo "\tselfield.checked = true;\n";
			echo "\tdisplay_selected_{$tabschema->name}(0);\n";
			echo "} else {\n";
			echo "\tfor (i=0; i<selfield.length; i++) {\n";
			echo "\t\tif (selfield[i].type=='radio') {\n";// make sure we have the right one and not a hidden stray 'hidden'
			echo "\t\t\tif (selfield[i].checked) {\n";
			echo "\t\t\t\tselwhich = radiowhich;\n";
			echo "\t\t\tbreak;\n";
			echo "\t\t\t}\n";
			echo "\t\t\tradiowhich++;\n";
			echo "\t\t}\n";
			echo "\t\t}\n";
			echo "\tif (i == selfield.length || !selfield[selwhich].checked) {\n";
			echo "\t\tselfield[selwhich].checked = true;\n";
			echo "\t}\n";
			echo "\tdisplay_selected_{$tabschema->name}(selwhich);\n";
			echo "}\n";
		}
		echo "</SCRIPT>\n";
	}
	
	echo "<SCRIPT LANGUAGE=\"javascript\">\n";
	echo "window.focus();\n";
	echo "</SCRIPT>\n";
	
	reset($schema_types);
	while (list($typek, $typev) = each($schema_types)) {
//		echo $typev->extensible,":",$typev->type, "!!$typek<br>";
		if ($typev->type == "enum" && $typev->extensible) {
			$ext = @eval("return $typev->extensible;");
			if ($ext) {
				h2("Extend or modify '".ucwords(($typev->label?$typev->label:$typek))."'");
				if ($typev->documentation) {
					echo "<p>$typev->documentation</p>";
				}
				form_header($schema_edit_form_action, "modifyEnum".$typek);
				hidden_field("modify_type_k", $typek);
				hidden_field("modify_type_nm", $typev->name);
				hidden_field("sqlschema", $sqlschema);
				hidden_field("edit_table", $tabschema->name);echo "\n";
				table_header(4, 4);
				table_row();
				table_data("left", "top");
				echo "<B>Delete a field value</B><BR>\n";
				reset($typev->value);
				echo "<SELECT MULTIPLE NAME=delete[] SIZE=".sizeof($typev->value).">\n";
				while (list($k, $v) = each($typev->value)) {
					echo "<OPTION VALUE='$v->name'>",$v->label, "</option>\n";
				}
				echo "</SELECT>";
				table_dend();
				table_data("left", "top");
 				echo "<B>Add A field value</B><BR>\n";
 				table_header(0,0,1);
 				echo "<th>";
 				echo "tag value";
 				echo "</th>";
 				echo "<th>";
 				echo "visible label";
 				table_dend();
 				echo "</th>";
 				for ($i=0; $i<4; $i++) {
 					table_row();
 					table_data();
 					text_input('value[]', '', '20,1', 256);
 					table_dend();
 					table_data();
 					text_input('label[]', '', '20,1', 256);
  					table_dend();
  					table_rend();
 				}
 				table_tail();
 				table_dend();
 				table_rend();
 				table_row();
			   echo "<TD colspan=2 align='center'>\n";
   			div("edit-controls");
   			submit_input('schemaEditAction', 'Modify enumerated values');
   			div();
   			table_tail();
   			form_tail();
			}
		}
	}
	if ($sqlschema_script_str) {
		echo "<SCRIPT LANGUAGE=\"javascript\">\n";
		echo $sqlschema_script_str;
		echo "</script>\n";
	}
	mysql_free_result($result);
	schema_edit_page_bottom();
?>
