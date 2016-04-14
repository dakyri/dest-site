<?php
	$field_count = 0;
	$tabschema_key_field = $tabschema->PrimaryKeyFieldName();
	div("edit-controls");
	echo "Select by ..."
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
//						echo "\ttypz = {$tabschema->name}_data[sel][$field_count].split(',');\n";
//						echo "\tobj = document.{$tabschema->name}SchemaDbForm;\n";
//						echo "\tfor (i=0; i<obj.elements.length; i++) {\n";
	//						echo "\t\tif (obj.elements[i].name == '$val->name\[\]') {\n";
	//							echo "\t\t\tobj.elements[i].checked = false;\n";
	//							echo "\t\t\tfor (j=0; j<typz.length; j++) {\n";
	//								echo "\t\t\t\tif(obj.elements[i].value == typz[j]) {\n";
	//									echo "\t\t\t\t\tobj.elements[i].checked = true;\n";
	//								echo "\t\t\t\t}\n";
	//							echo "\t\t\t}\n";
	//						echo "\t}\n";
	//					echo "\t}\n";
						$field_count++;
						break;
						
					case "enum":
					// a selector or an array of checkboxes
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
						
					case "image":						echo "\tif (document.{$tabschema->name}SchemaDbForm.$val->name != undefined) {\n";
						echo "\t\tdocument.{$tabschema->name}SchemaDbForm.$val->name.value = {$tabschema->name}_data[sel][$field_count];\n";
						echo "\t}\n";

					case "upload": // dunno how these are searchable, ... yet
						$field_count++;
						break;
						
					default:
						echo "\tif (document.{$tabschema->name}SchemaDbForm.$val->name != undefined) ";
						echo "document.{$tabschema->name}SchemaDbForm.$val->name.value = {$tabschema->name}_data[sel][$field_count];\n";
						$field_count++;
						break;
				}
			} else {
				switch ($val->type) {
					case "enum": // shouldnt exist
						break;
					case "set": // shouldnt exist
						break;
					case "bool":
					// checkbox
						break;
					case "image": // dunno how these are searchable, ... yet
					case "upload":
						$field_count++;
						break;
					default:
					// text field
						break;
				}
				$field_count++;
			}
		}
	}
	
	div();

?>