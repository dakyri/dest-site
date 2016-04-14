<?php
/////////////////////////////////////////////////////////////////////////////////////////
// database edit code from a sqlschema xml framework.
//  1st version knocked together quickly, not quite
// as generic as it could be but ok for most database jobs
//  this file contains the code to generate a html management page,
//  database manipulation is in sqlschema_ins_upd.php
// TODO:
//  * lots of testing and revision! esp on generated javascript db reflection
//  * size attribute of a list? what happens when too small for number of db  entries in a list
//	 * handle tables whose fields depend on other tables' fields in a schema in a reasonably
//			transparent fashion. e.g. a catalog and a table of images referring to particular
//			entries in the catalog
//  * form reset. needs a generated javascript function
//	 * perhaps make the "is_hidden" attribute an evaluated php expression, so
//			different pages off the same schema could see different fields
//  * twoud be nice to generate checks for numeric fields with generated javascript
//  * also be nice for a few more pseudo-types that did form checks for email etc.
//  * handle deeply nested types, currently only checks down 1 level
/////////////////////////////////////////////////////////////////////////////////////////
function submit_controls()
{
// edit control buttons for this table
	div("edit-controls");
	echo "\n<TABLE CELLPADDING=2 CELLSPACING=0 align=\"center\">\n";
	echo "<tr>\n";
 	echo "<td><B>";submit_input("edAction","Delete items"); echo "</B></td>\n";
 	echo "<td><B>";submit_input("edAction", "Modify selected"); echo "</B></td>\n";
 	echo "<td><B>";submit_input("edAction", "Insert item"); echo "</B></td>\n";
 	echo "<td><b><input type=\"reset\" value=\"Clear Form\"></b></td>";
	echo "</tr>\n";
	echo "</TABLE>\n";
	div();
	
}


function schema_edit_redirect(&$page, &$sqlschema, &$schema_tables, &$action_msg)
{
	$redirect_str = "Location: ".$page."?sqlschema=$sqlschema";
	while (list($key, $tabschema) = each($schema_tables)) {
		$tabschema_sel_name = "$tabschema->name"."_sqlschema_selected";
		$tabschema_sel = $GLOBALS[$tabschema_sel_name];
		if ($tabschema_sel >= 0) {
			$redirect_str .= "&$tabschema_sel_name=$tabschema_sel";
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

	if (isset($edAction)) {
		if ($edAction == "Create table") {
			require($schema_edit_framework_base."sqlschema_creat.php");
			schema_edit_redirect($_SERVER['PHP_SELF'], $sqlschema, $schema_tables, $action_msg);
		} elseif ($edAction == "Delete items" && sizeof($del) > 0) {
		
			if (!isset($edit_table) || !$edit_table) {
				errorpage("Sorry. No table in this schema is specified");
			}
			$tabschema = &$schema_tables[$edit_table];
			$tabschema_key_field = $tabschema->PrimaryKeyFieldName();
			
			$query = "delete from $tabschema->name where $tabschema_key_field in (".list_string($del).")";
			$delete_result = mysql_query($query, $mysql);
			if (!$delete_result) {
				errorpage(mysql_error());
			}
			
			schema_edit_redirect($_SERVER['PHP_SELF'], $sqlschema, $schema_tables, $action_msg);
		} elseif ($edAction=="Modify selected") {
			$schema_edit_updating = true;
			require($schema_edit_framework_base."sqlschema_ins_upd.php");
			if ($field_set_str && $where) {
				$query = "update $tabschema->name set $field_set_str where $where";
				$update_result = mysql_query($query, $mysql);
				if (!$update_result) {
					errorpage("Update error ".mysql_error());
				}
			}
			$schema_edit_updating = false;
			
			schema_edit_redirect($_SERVER['PHP_SELF'], $sqlschema, $schema_tables, $action_msg);

		} elseif ($edAction == "Insert item") {
			$schema_edit_inserting = true;
			
			if (!isset($edit_table) || !$edit_table) {
				errorpage("Sorry. No table in this schema is specified");
			}
			$tabschema = &$schema_tables[$edit_table];
			$tabschema_key_field = $tabschema->PrimaryKeyFieldName();
			if ($schema_edit_insert_auto_increment_reqd) {
	// something in the insert requires preknowledge of the auto_increment value ...
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
			}
			require($schema_edit_framework_base."sqlschema_ins_upd.php");
			if ($field_set_str) {
				$query = "insert into $tabschema->name set $field_set_str";
				$insert_result = mysql_query($query, $mysql);
				if (!$insert_result) {
					errorpage(mysql_error());
				}
			}
			if ($schema_edit_insert_auto_increment_reqd) {
				$ir_res = mysql_query("select release_lock('$tabschema_locknm')");
			}
			$schema_edit_inserting = false;
			
			schema_edit_redirect($_SERVER['PHP_SELF'], $sqlschema, $schema_tables, $action_msg);

		} elseif ($edAction == "Modify enum") {
			if (!isset($edit_table) || !$edit_table) {
				errorpage("Sorry. No table in this schema is specified");
			}
			$tabschema = $schema_tables[$edit_table];
			$kind_values = modify_enumerated(
					$tabschema->name, $schema_edit_modify_enum_name, $schema_edit_modify_enum_type,
					$kind_values, $add_property, $del_property);
		}
	}
/////////////////////////////////////////////////////////
// page generation
////////////////////////////////////////////////////////
	standard_page_top(
			$schema_edit_page_title,
			$schema_edit_stylesheet,
			$schema_edit_body_style,
			$schema_edit_title_img, $schema_edit_title_img_w, $schema_edit_title_img_h, $schema_edit_title_img_alt,
			$schema_edit_common_js);
			
	br("all");
	if ($action_msg) {
		echo $action_msg;
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
</p>

<?php
		}
	}
//<P><B>**</b>Remember to select the validation checkbox at the bottom of the page before modifying or inserting an item.<b>**</b></p>
	
	if ($schema_edit_use_selector) {
		form_header($schema_edit_form_action, "schemaSelectForm", "POST", $schema_edit_form_target, "");
		echo "<b>$schema_edit_selector_text&nbsp;&nbsp;</b>";
		select_array("sqlschema", array_keys($uploaded_schema), "", array_map("ucfirst",array_keys($uploaded_schema)), "schemaSelectForm.submit();" ,$sqlschema);
		form_tail();
		br();
		br();
	}

	while (list($key, $tabschema) = each($schema_tables)) {
		$tabschema_sel_name = "$tabschema->name"."_sqlschema_selected";
		$tabschema_sel = $$tabschema_sel_name;
		$query = "select * from $tabschema->name";
		if ($tabschema->where) {
			$whex = @eval("return $tabschema->where;");
			if ($whex) {
				$query .= " where $whex";
			}
		}
		if ($tabschema->order) {
			$query .= " order by " . $tabschema->order;
		}
	
		if ($g_page_len > 0) {
			if (! $pageno || $pageno < 1)
				$pageno = 1;
			$offset = $g_page_len * ($pageno-1);
			$query .= " limit $offset, $g_page_len ";
		}
	
		$result = mysql_query($query);
		if ($result == 0) {
			if (mysql_errno() == MYSQL_ER_NO_SUCH_TABLE) {
				createtable_form("createSchemaDb", $schema_edit_form_action, $schema_table, "Create table");
				page_bottom_menu();
				standard_page_bottom();
				exit;
			} else {
				$nitems = 0;
				echo "Unexpected mysql error in query \"$query\":<br>".mysql_error();
				page_bottom_menu();
				standard_page_bottom();
				exit;
			}
		} else {
			$nitems = mysql_num_rows($result);
		}
// define a javascript function to handle selections within this page
	
		$schema_edit_scripting = true;
		
		echo "<SCRIPT LANGUAGE=\"javascript\">\n";
// reflect the table in javascript	
		echo "{$tabschema->name}_data = [\n";
		if ($nitems > 0)
			mysql_data_seek($result, 0);
		for($i=0; $i < $nitems; $i++) {
			$row = mysql_fetch_object($result);
			reset ($tabschema->field);
			echo "[";
			while (list($key, $val) = each($tabschema->field)) {
				$f_nm = $val->name;
				if ($val->type == "table-var") {
				} elseif ($val->type == "row-var") {
//				} elseif (0) { // was is_hidden some of these maybe should be reflected, most should ???
				} elseif ($schema_types[$val->type]) {
					$field_template = &$schema_types[$val->type];
					switch ($field_template->type) {
						case "enum":
							$v = index_of($row->$f_nm, $field_template->ValueNameArray());
							echo "'$v',";
							break;
						case "list":
							reset ($field_template->value);
							while (list($field_key, $field_val) = each($field_template->value)) {
								$flnm = $field_val->name;
								$v = $row->$flnm;	// a list!!
								echo "'$v',";
								switch($field_val->type) {
									case "image":
									case "upload":
										$base = $schema_edit_upload_base;
										if ($field_val->base) {
											$fb = @eval("return $field_val->base;");
											if ($fb) {
												$base .= "/$fb";
											}
										}
										echo "'$base',";
										break;
								}
							}
							break;
							
						case "image":
						case "upload":
							$v = quothi($row->$f_nm);
							echo "'$v',";
							$base = $schema_edit_upload_base;
							if ($val->base) {
								$fb = @eval("return $val->base;");
								if ($fb) {
									$base .= "/$fb";
								}
							}
							echo "'$base',";
							break;
							
						case "set":
							$v = quothi($row->$f_nm);
							echo "'$v',";
							break;
						default:
							$v = quothi($row->$f_nm);
							echo "'$v',";
							break;
					}
				} else {
					switch ($val->type) {
						case "enum":
							$v = index_of($row->$f_nm, $val->value);
							echo "'$v',";
							break;
						case "list": // extremely suss, but not likely called!!!!????
							reset ($tabschema->value);
							while (list($field_key, $field_val) = each($tabschema->value)) {
								$flnm = $field_val->name;
								$v = $row->$flnm;	// a list!!
								echo "'$v',";
								switch($field_val->type) {
									case "image":
									case "upload":
										$base = $schema_edit_upload_base;
										if ($field_val->base) {
											$fb = @eval("return $field_val->base;");
											if ($fb) {
												$base .= "/$fb";
											}
										}
										echo "'$base',";
										break;
								}
							}
							break;
							
						case "image":
						case "upload":
							$v = quothi($row->$f_nm);
							echo "'$v',";
							$base = $schema_edit_upload_base;
							if ($val->base) {
								$fb = @eval("return $val->base;");
								if ($fb) {
									$base .= "/$fb";
								}
							}
							echo "'$base',";
							break;
							
						case "set":
							$v = quothi($row->$f_nm);
							echo "'$v',";
							break;
						default:
							$v = quothi($row->$f_nm);
							echo "'$v',";
							break;
					}
				}
			}
			echo "],\n";
		}
		echo "];\n";
		
		echo "function display_selected_{$tabschema->name}(sel)\n";
		echo "{\n";

		$field_count = 0;
		$tabschema_key_field = $tabschema->PrimaryKeyFieldName();
//		echo "\tif (sel < 0 ) {\n\t\tsel = 0;\n\t\tdocument.{$tabschema_sel_name}.value = 0;\n\t} ",
//				"else if (sel >= {$tabschema->name}_data.length) {\n\t\tsel = {$tabschema->name}_data.length-1;\n\t\tdocument.{$tabschema_sel_name}.value=sel;\n\t}\n";
//		echo "\tif (sel < 0 ) {\n\t\tsel = 0;\n\t} ",
//				"else if (sel >= {$tabschema->name}_data.length) {\n\t\tsel = {$tabschema->name}_data.length-1;\n\t}\n";
//		echo "window.alert(sel);";
		reset($tabschema->field);
		while (list($key, $val) = each($tabschema->field)) {
			if ($val->type == "table-var") {
			} elseif ($val->type == "row-var") {
//			} elseif (0) { // was is_hidden some of these maybe should be reflected, most should ???
			} else {
				if ($schema_types[$val->type]) {
					$field_template = &$schema_types[$val->type];
					switch ($field_template->type) {
						case "set":
							echo "\ttypz = {$tabschema->name}_data[sel][$field_count].split(',');\n";
							echo "\tobj = document.{$tabschema->name}SchemaDbForm;\n";
							echo "\tfor (i=0; i<obj.elements.length; i++) {\n";
								echo "\t\tif (obj.elements[i].name == '$val->name\[\]') {\n";
									echo "\t\t\tobj.elements[i].checked = false;\n";
									echo "\t\t\tfor (j=0; j<typz.length; j++) {\n";
										echo "\t\t\t\tif(obj.elements[i].value == typz[j]) {\n";
											echo "\t\t\t\t\tobj.elements[i].checked = true;\n";
										echo "\t\t\t\t}\n";
									echo "\t\t\t}\n";
								echo "\t}\n";
							echo "\t}\n";
							$field_count++;
							break;
							
						case "enum":
							echo "\tif (document.{$tabschema->name}SchemaDbForm.$val->name != undefined) ";
							echo "document.{$tabschema->name}SchemaDbForm.$val->name.selectedIndex = {$tabschema->name}_data[sel][$field_count];\n";
							$field_count++;
							break;
							
						case "list":
							if ($field_template->size > 0) {
								$list_length = $field_template->size;
							} elseif ($schema_edit_list_length > 0) {
								$list_length = $schema_edit_list_length;
							} else {
								$list_length = 6;
							}
							reset ($field_template->value);
							while (list($field_key, $field_val) = each($field_template->value)) {
								echo "\tlistelz = {$tabschema->name}_data[sel][$field_count].split('&');\n";
								echo "\tobj = document.{$tabschema->name}SchemaDbForm.elements;\n";
								echo "\tfor (i=0; i<{$list_length}; i++) {\n";
									echo "\t\tctlobj = obj['$field_val->name['+i+']'];\n";
									echo "\t\tif (ctlobj != undefined) {\n";
//									echo "window.alert(ctlobj.value+'!!!!')";
									switch ($field_val->type) {
										case "bool":
											echo "\t\t\tv = unescape(listelz[i]);\n";
											echo "\t\t\tctlobj.checked=(listelz[i] != undefined)?(v!=0 && v!='0'):false;\n";
											break;
										case "image":
										case "upload":
											$field_count++;
											echo "\t\t\tlistev = (listelz[i] != undefined)?unescape(listelz[i]):'';\n";
											echo "\t\t\tctlobj.value=listev;\n";
											echo "\t\t\tdfobj = obj['_$field_val->name['+i+']'];\n";
											echo "\t\t\tif (dfobj != undefined) {\n";
											echo "\t\t\t\tdfobj.value = ctlobj.value;\n";
											echo "\t\t\t}\n";
											echo "\t\t\tankobj = document.anchors['__$field_val->name['+i+']'];\n";
											echo "\t\t\tif (ankobj == undefined)\n";
											echo "\t\t\t\tankobj = document.getElementById('__$field_val->name['+i+']');\n"; // for explorer
											echo "\t\t\tif (ankobj != undefined) {\n";
											echo "\t\t\t\tif (listev) {\n";
											echo "\t\t\t\t\tankobj.style.visibility = 'visible';\n";
											echo "\t\t\t\t\tpathname = {$tabschema->name}_data[sel][$field_count];\n";
											echo "\t\t\t\t\tankobj.href = pathname+'/'+listev;\n";
											echo "\t\t\t\t} else {\n";
											echo "\t\t\t\t\tankobj.style.visibility = 'hidden';\n";
											echo "\t\t\t\t}\n";
											echo "\t\t\t}\n";
											break;
										default:
											echo "\t\t\tctlobj.value=(listelz[i] != undefined)?unescape(listelz[i]):'';\n";
											break;
									}
									echo "\t\t}\n";
								echo "\t}\n";
								$field_count++;
							}
							break;
							
						case "bool":
							echo "\tv = ({$tabschema->name}_data[sel][$field_count]);\n";
							echo "\tif (document.{$tabschema->name}SchemaDbForm.$val->name != undefined) ";
							echo "document.{$tabschema->name}SchemaDbForm.$val->name.checked = (v!=undefined && v!=0 && v!='0');\n";
							break;
							
						case "image":
						case "upload": 
							echo "\tleafname = {$tabschema->name}_data[sel][$field_count];";
							$field_count++;
							echo "\tif (document.{$tabschema->name}SchemaDbForm.$val->name != undefined) ";
							echo "document.{$tabschema->name}SchemaDbForm.$val->name.value = leafname;\n";// the hidden current val
							echo "\ttxtobj=document.{$tabschema->name}SchemaDbForm._$val->name;";
							echo "\tif (txtobj != undefined) txtobj.value = leafname;\n";
							echo "\tpathname = {$tabschema->name}_data[sel][$field_count];\n";
							echo "\tankobj=document.anchors['__$val->name'];";
							echo "\tif (leafname) {\n";
							echo "\t\tif (ankobj != undefined) ankobj.href = pathname+'/'+leafname;\n";
							echo "\t} else {\n";
							echo "\t\tif (ankobj != undefined) ankobj.style.visibility = 'hidden';\n";
							echo "\t}\n";
							break;
							
						default:
							echo "\tif (document.{$tabschema->name}SchemaDbForm.$val->name != undefined) ";
							echo "document.{$tabschema->name}SchemaDbForm.$val->name.value = {$tabschema->name}_data[sel][$field_count];\n";
							$field_count++;
							break;
					}
				} else {
					switch ($val->type) {
						case "enum":
							break;
						case "set":
							break;
						case "bool":
							echo "\tv = ({$tabschema->name}_data[sel][$field_count]);\n";
							echo "\tif (document.{$tabschema->name}SchemaDbForm.$val->name != undefined) ";
							echo "document.{$tabschema->name}SchemaDbForm.$val->name.checked = (v!=undefined && v!=0 && v!='0');\n";
							break;
						case "image":
						case "upload":
							echo "\tleafname = {$tabschema->name}_data[sel][$field_count];";
							$field_count++;
							echo "\tif (document.{$tabschema->name}SchemaDbForm.$val->name != undefined) ";
							echo "document.{$tabschema->name}SchemaDbForm.$val->name.value = leafname;\n";// the hidden current val
							echo "\ttxtobj=document.{$tabschema->name}SchemaDbForm._$val->name;";
							echo "\tif (txtobj != undefined) txtobj.value = leafname;\n";
							echo "\tpathname = {$tabschema->name}_data[sel][$field_count];\n";
							echo "\tankobj=document.anchors['__$val->name'];";
							echo "\tif (leafname) {\n";
							echo "\t\tif (ankobj != undefined) {ankobj.href = pathname+'/'+leafname;\n}";
							echo "\t} else {\n";
							echo "\t\tif (ankobj != undefined) {ankobj.style.visibility = 'hidden';}\n";
							echo "\t}\n";
							break;
						default:
							echo "\tif (document.{$tabschema->name}SchemaDbForm.$val->name != undefined) ";
							echo "document.{$tabschema->name}SchemaDbForm.$val->name.value = {$tabschema->name}_data[sel][$field_count];\n";
							break;
					}
					$field_count++;
				}
			}
		}

		echo "}\n"; // end of function

		echo "window.focus();\n";
		echo "</SCRIPT>\n";
		$schema_edit_scripting = false;
		
//////////////////////////////////////////////////////////////////////////////////////////////////////
// start of the form
//////////////////////////////////////////////////////////////////////////////////////////////////////
		$primary_key_name = $tabschema->PrimaryKeyFieldName();
		$secondary_key_name = $tabschema->SecondaryKeyFieldNames();
		$secondary_key_label = $tabschema->SecondaryKeyFieldLabels();
		$secondary_key_type = $tabschema->SecondaryKeyFieldTypes();
//		var_dump($secondary_key_name);
		paginator($tabschema->name, $mysql, $g_page_len, $pageno,
				$schema_edit_form_action, "pageno", "#44ff44", "#4444ff", "title", "","sqlschema=$sqlschema");
// start of the form
		form_header($schema_edit_form_action, "$tabschema->name"."SchemaDbForm", "POST", $schema_edit_form_target, "multipart/form-data");
// basic hidden variables
//		echo "<tr><td align='center'>\n";
		submit_controls();
//		echo "</td></tr>";
		hidden_field("sqlschema", $sqlschema);echo "\n";
		hidden_field("edit_table", $tabschema->name);echo "\n";
//		hidden_field($tabschema_sel_name, $$tabschema_sel_name);echo "\n";

		table_header(0, 2, "", "", "0", "90%", "CENTER");
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
			if ($i == $$tabschema_sel_name) {
				 $td .= " CHECKED";
			} else {
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
//		echo "<TABLE>\n";
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
	   					if ($fixt) {
								echo "<a name=\"__{$nm}\" id=\"__{$nm}\" target=\"_blank\" style=\"visibility:hidden\" href=\"pathtofile\">View Upload</a>";
	   					} else {
								upload_input("{$nm}__", "", $val->element_class, $val->width);
								echo "<input type=\"button\" value=\"X\" onClick=\"form.elements['$nm'].value='';ank=document.getElementById('__$nm');if (ank != undefined) ank.style.visibility='hidden';\">";
								echo "<a name=\"__{$nm}\" id=\"__{$nm}\" target=\"_blank\" style=\"visibility:hidden\" href=\"pathtofile\">View Upload</a>";
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
							
						default:
							if (!$schema_types[$val->type]) {
								echo "<tr><td width=\"$schema_edit_columnar_label_width\">\n";
								$nm = $val->name;
								echo "<B>$val->label</B>&nbsp;";
		   					if ($val->documentation) {
		   						echo "<br>\n$val->documentation";
		   					}
	   						if ($schema_edit_columnar) echo "</td><td>\n"; else echo "<br>\n";
								text_input($nm, $val->value, $val->width, $val->maxlen, $val->on_change, $val->element_class, $fixt);
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
				   					if ($fixt) {
											echo "<a name=\"__{$nm}\" id=\"__{$nm}\" target=\"_blank\" style=\"visibility:hidden\" href=\"pathtofile\">View Upload</a>";
				   					} else {
											upload_input($unm, "", $val->element_class, $val->width);
											echo "<input type=\"button\" value=\"X\" onClick=\"form.elements['$nm'].value='';ank=document.getElementById('__$nm');if (ank != undefined) ank.style.visibility='hidden';\">";
											echo "<a name=\"__{$nm}\" id=\"__{$nm}\" target=\"_blank\" style=\"visibility:hidden\" href=\"pathtofile\">View Upload</a>";
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
										
									case "list":
										echo "<tr><td", $schema_edit_columnar? " colspan='2'" : "" , ",>\n";
				   					if ($val->label) {
				   						echo "<B>$val->label</B><BR>\n";
										}
				   					if ($val->documentation) {
				   						echo "$val->documentation<br>\n";
				   					}
				   					if ($field_template->documentation) {
				   						echo "$field_template->documentation<br>\n";
				   					}
										$ft_row_cnt = 0;
										table_header(0,0, "", "", 1, "");
										table_row();
										reset ($field_template->value);
										while (list($field_key, $field_val) = each($field_template->value)) {
											if ($field_val->is_hidden && @eval("return $field_val->is_hidden;")) {
											} else {
												table_data("center", "top");
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
											
										while ($ft_row_cnt < $list_length) {
											table_row();
											reset ($field_template->value);
											while (list($field_key, $field_val) = each($field_template->value)) {
												$nm = $field_val->name."[$ft_row_cnt]";
												if ($field_val->is_hidden && @eval("return $field_val->is_hidden;")) {
													hidden_field($nm,	$field_val->value);
												} else {
													if ($schema_types[$field_val->type]) {
														$field_val_template = &$schema_types[$field_val->type];
														table_data("center", "top");
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
																if ($field_fixt) {
																	echo "<a name=\"__{$nm}\" id=\"__{$nm}\" target=\"_blank\" style=\"visibility:hidden\" href=\"pathtofile\">View Upload</a>";
																} else {
																	upload_input($unm, "",
																		$field_val->element_class?$field_val->element_class:$field_val_template->element_class,
																		$field_val->width?$field_val->width:$field_val_template->width);
																	echo "<input type=\"button\" value=\"X\" onClick=\"form.elements['$nm'].value='';ank=document.getElementById('__$nm');if (ank != undefined) ank.style.visibility='hidden';\">";
																	echo "<a name=\"__{$nm}\" id=\"__{$nm}\" target=\"_blank\" style=\"visibility:hidden\" href=\"pathtofile\">View Upload</a>";
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
																
															default:
																text_input(
																	"$field_val->name"."[$ft_row_cnt]",
																	$field_val->value?$field_val->value:$field_val_template->value,
																	$field_val->width?$field_val->width:$field_val_template->width,
																	$field_val->maxlen?$field_val->maxlen:$field_val_template->maxlen,
																	$field_val->on_change?$field_val->on_change:$field_val_template->on_change,
																	$field_val->element_class?$field_val->element_class:$field_val_template->element_class,
																	$field_fixt);
																break;
														}
														table_dend();
													} else { // a list field which is a standard internal type ...
										   			if ($field_val->is_fixed) {
										   				$field_fixt = @eval("return $field_val->is_fixed;");
										   			} else {
										   				$field_fixt = false;
										   			}
														table_data("center", "top");
														switch ($field_val->type) {
															case "image":
															case "upload":
																$nm = "$field_val->name"."[$ft_row_cnt]";
																$unm = "$field_val->name"."__$ft_row_cnt"; // uploads are sent as fields in arrays...
																hidden_field($nm, $field_val->value);
																if ($field_fixt) {
																	echo "<a name=\"__{$nm}\" id=\"__{$nm}\" target=\"_blank\" style=\"visibility:hidden\" href=\"pathtofile\">View Upload</a>";
																} else {
																	upload_input($unm, "", $field_val->element_class, $field_val->width);
																	echo "<input type=\"button\" value=\"X\" onClick=\"form.elements['$nm'].value='';ank=document.getElementById('__$nm');ank.style.visibility='hidden';\">";
																	echo "<a name=\"__{$nm}\" id=\"__{$nm}\" target=\"_blank\" style=\"visibility:hidden\" href=\"pathtofile\">View Upload</a>";
//																	echo "<input type=\"text\" name=\"_{$nm}\" value=\"$field_val->value\" disabled size=\"14\">";
//																	echo "<input type=\"button\" value=\"X\" onClick=\"form.elements['$nm'].value='';form.elements['_$nm'].value='';\">";
																}
																break;
																
															case "bool":
																$nm = "$field_val->name"."[$ft_row_cnt]";
																checkbox_input($nm, "1", false, $field_val->element_class, $field_fixt);
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
													$field_template->on_change,
													$field_template->element_class,
													$fixt);
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
		submit_controls();
//		echo "</td></tr><tr><td>";
		echo "</FORM>\n";
		echo "<SCRIPT LANGUAGE=\"javascript\">\n";
		echo "selfield = document.{$tabschema->name}SchemaDbForm.{$tabschema_sel_name};";
//		echo "window.alert(selfield.length);";
//		echo "window.alert(selfield.value);";
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
//		echo "window.alert(selfield);\n";
//		echo "window.alert(selwhich+' '+i+' '+selfield.length);\n";
		echo "\tdisplay_selected_{$tabschema->name}(selwhich);\n";
		echo "}\n";
		echo "</SCRIPT>\n";
	}
	mysql_free_result($result);
	page_bottom_menu();
	standard_page_bottom();
?>