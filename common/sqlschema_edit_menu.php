<?php
	echo "<SCRIPT type=\"text/javascript\" LANGUAGE=\"javascript\">\n";
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
						if (!isset($list_max_n_items[$f_nm])) {
							$list_max_n_items[$f_nm] = 0;
						}
						while (list($field_key, $field_val) = each($field_template->value)) {
							$flnm = $field_val->name;
							$v = $row->$flnm;	// a list!!
							// calculate maximum length of the list ... but don't check enums or types of enums
							$do_max_check = true;
							if (isset($schema_types[$field_val->type]) && $schema_types[$field_val->type]->type == "enum") {
								$do_max_check = false;
							} elseif ($field_val->type == "enum") {
								$do_max_check = false;
							}
							if ($do_max_check) {
								$vels = explode("&", $v);
								$v_n_items = 0;
								while (list($vels_k, $vels_v) = each($vels)) {
									if ($vels_v) {
//											$v_n_items++;
										$v_n_items = $vels_k+1;
									}
								}
								if ($v_n_items > $list_max_n_items[$f_nm]) {
									$list_max_n_items[$f_nm] = $v_n_items;
								}
							}
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
						if (!isset($list_max_n_items[$f_nm])) {
							$list_max_n_items[$f_nm] = 0;
						}
						while (list($field_key, $field_val) = each($tabschema->value)) {
							$flnm = $field_val->name;
							$v = $row->$flnm;	// a list!!
							// calculate maximum length of the list ... but don't check enums or types of enums
							$do_max_check = true;
							if (isset($schema_types[$field_val->type]) && $schema_types[$field_val->type]->type == "enum") {
								$do_max_check = false;
							} elseif ($field_val->type == "enum") {
								$do_max_check = false;
							}
							if ($do_max_check) {
								$vels = explode("&", $v);
								$v_n_items = 0;
								while (list($vels_k, $vels_v) = each($vels)) {
									if ($vels_v) {
//											$v_n_items++;
										$v_n_items = $vels_k;
									}
								}
								if ($v_n_items > $list_max_n_items[$f_nm]) {
									$list_max_n_items[$f_nm] = $v_n_items;
								}
							}
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
						if ($list_max_n_items[$val->name] >= $list_length) {
							$extend_by = ($field_template->extend_by)?$field_template->extend_by:1;
							$list_length = $list_max_n_items[$val->name]+$extend_by;
							if (isset($field_template->maxlength) && ($list_length > $field_template->maxlength)) {
								$list_length = $field_template->maxlength;
							}
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
									case "time":
									case "datetime":
									case "date":
									case "year":
										break;
										
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
										
//										echo "\t\t\tankobj = document.anchors['__$field_val->name['+i+']'];\n";
//										echo "\t\t\tif (ankobj == undefined)\n";
										echo "\t\t\tankobj = document.getElementById('__$field_val->name['+i+']');\n"; // for explorer

										echo "\t\t\tif (ankobj != undefined) {\n";
										echo "\t\t\t\tif (listev) {\n";
										echo "\t\t\t\t\tpathname = {$tabschema->name}_data[sel][$field_count];\n";
										if ($field_val->type == "image") {
//											echo "img = new Image();img.src = pathname+'/'+listev;ankobj.src = img.src;";
											echo "\t\t\t\t\tankobj.src = pathname+'/'+listev;\n";
										} else {
											echo "\t\t\t\t\tankobj.href = pathname+'/'+listev;\n";
										}
//										echo "alert(ankobj.src);";
										echo "\t\t\t\t\tankobj.style.display = 'block';\n";
										echo "\t\t\t\t} else {\n";
										echo "\t\t\t\t\tankobj.style.display = 'none';\n";
										echo "\t\t\t\t}\n";
//										echo "alert(ankobj.style.display);";
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
						
					case "time":
					case "datetime":
					case "date":
					case "year":
						break;
						
					case "bool":
						echo "\tv = ({$tabschema->name}_data[sel][$field_count]);\n";
						echo "\tif (document.{$tabschema->name}SchemaDbForm.$val->name != undefined) ";
						echo "document.{$tabschema->name}SchemaDbForm.$val->name.checked = (v!=undefined && v!=0 && v!='0');\n";
						break;
						
					case "image":
					case "upload": 
						echo "\tleafname = {$tabschema->name}_data[sel][$field_count];\n";
						$field_count++;
						echo "\tif (document.{$tabschema->name}SchemaDbForm.$val->name != undefined)\n";
						echo "\t\tdocument.{$tabschema->name}SchemaDbForm.$val->name.value = leafname;\n";// the hidden current val
						echo "\ttxtobj=document.{$tabschema->name}SchemaDbForm._$val->name;\n";
						echo "\tif (txtobj != undefined) txtobj.value = leafname;\n";
						echo "\tpathname = {$tabschema->name}_data[sel][$field_count];\n";
						
//						echo "\tankobj=document.anchors['__$val->name'];";
						echo "\tankobj=document.getElementById['__$val->name'];";
						
						echo "\tif (leafname) {\n";
						echo "\t\tif (ankobj != undefined) {\n";
						if ($field_template->type == "image") {
							echo "\t\t\tankobj.src = pathname+'/'+leafname;\n";
						} else {
							echo "\t\t\tankobj.href = pathname+'/'+leafname;\n";
						}
						echo "\t\t\tankobj.style.display = 'block';\n";
						echo "\t\t}\n";
						echo "\t} else {\n";
						echo "\t\tif (ankobj != undefined) ankobj.style.display = 'none';\n";
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

					case "time":
						echo "\tv = ({$tabschema->name}_data[sel][$field_count]);\n";
						echo "\tif (document.{$tabschema->name}SchemaDbForm.$val->name != undefined) ";
						echo "document.{$tabschema->name}SchemaDbForm.$val->name.value = v;\n";
						echo "\tsetmysqltime(v",
								",document.{$tabschema->name}SchemaDbForm._h_$val->name",
								",document.{$tabschema->name}SchemaDbForm._mi_$val->name",
								",document.{$tabschema->name}SchemaDbForm._s_$val->name",
								");";
						break;
						
					case "datetime":
						echo "\tv = ({$tabschema->name}_data[sel][$field_count]);\n";
						echo "\tif (document.{$tabschema->name}SchemaDbForm.$val->name != undefined) ";
						echo "document.{$tabschema->name}SchemaDbForm.$val->name.value = v;\n";
						echo "\tsetmysqldatetime(v",
								",document.{$tabschema->name}SchemaDbForm._y_$val->name",
								",document.{$tabschema->name}SchemaDbForm._mo_$val->name",
								",document.{$tabschema->name}SchemaDbForm._d_$val->name",
								",document.{$tabschema->name}SchemaDbForm._h_$val->name",
								",document.{$tabschema->name}SchemaDbForm._mi_$val->name",
								",document.{$tabschema->name}SchemaDbForm._s_$val->name",
								");";
						break;
						
					case "date":
						echo "\tv = ({$tabschema->name}_data[sel][$field_count]);\n";
						echo "\tif (document.{$tabschema->name}SchemaDbForm.$val->name != undefined) ";
						echo "document.{$tabschema->name}SchemaDbForm.$val->name.value = v;\n";
						echo "\tsetmysqldatetime(v",
								",document.{$tabschema->name}SchemaDbForm._y_$val->name",
								",document.{$tabschema->name}SchemaDbForm._mo_$val->name",
								",document.{$tabschema->name}SchemaDbForm._d_$val->name",
								");";
						break;
						
					case "year":
						echo "\tv = ({$tabschema->name}_data[sel][$field_count]);\n";
						echo "\tif (document.{$tabschema->name}SchemaDbForm.$val->name != undefined) ";
						echo "document.{$tabschema->name}SchemaDbForm.$val->name.value = v;\n";
						break;
						
					case "bool":
						echo "\tv = ({$tabschema->name}_data[sel][$field_count]);\n";
						echo "\tif (document.{$tabschema->name}SchemaDbForm.$val->name != undefined) ";
						echo "document.{$tabschema->name}SchemaDbForm.$val->name.checked = (v!=undefined && v!=0 && v!='0');\n";
						break;
					case "image":
					case "upload":
						echo "\tleafname = {$tabschema->name}_data[sel][$field_count];\n";
						$field_count++;
						echo "\tif (document.{$tabschema->name}SchemaDbForm.$val->name != undefined)\n";
						echo "\t\tdocument.{$tabschema->name}SchemaDbForm.$val->name.value = leafname;\n";// the hidden current val
						echo "\ttxtobj=document.{$tabschema->name}SchemaDbForm._$val->name;\n";
						echo "\tif (txtobj != undefined) txtobj.value = leafname;\n";
						echo "\tpathname = {$tabschema->name}_data[sel][$field_count];\n";
//						echo "\tankobj=document.anchors['__$val->name'];\n";
						echo "\tankobj=document.getElementById('__$val->name');\n";
						echo "\tif (leafname) {\n";
						echo "\t\tif (ankobj != undefined) {\n";
						if ($val->type == "image") {
							echo "\t\t\tankobj.src = pathname+'/'+leafname;\n";
						} else {
							echo "\t\t\tankobj.href = pathname+'/'+leafname;\n";
						}
						echo "\t\t\tankobj.style.display = 'block';\n";
						echo "\t\t}\n";
						echo "\t} else {\n";
						echo "\t\tif (ankobj != undefined) {ankobj.style.display = 'none';}\n";
						echo "\t}\n";
						break;
					default:
						echo "\tif (document.{$tabschema->name}SchemaDbForm.$val->name != undefined) {\n";
						echo "\t\tdocument.{$tabschema->name}SchemaDbForm.$val->name.value = {$tabschema->name}_data[sel][$field_count];\n";
						echo "\t}\n";
						break;
				}
				$field_count++;
			}
		}
	}

	echo "\tmodtop=document.{$tabschema->name}SchemaDbForm.sqlschema_modify_button_{$tabschema->name}_top;\n";
	echo "\tmodbot=document.{$tabschema->name}SchemaDbForm.sqlschema_modify_button_{$tabschema->name}_bot;\n";
//		echo "\tif (modtop != undefined) modtop.style.visibility='visible'\n";
//		echo "\tif (modbot != undefined) modbot.style.visibility='visible'\n";
	echo "\tif (modtop != undefined) modtop.disabled=false\n";
	echo "\tif (modbot != undefined) modbot.disabled=false\n";
	echo "}\n"; // end of function

	
	echo "</SCRIPT>\n";
?>