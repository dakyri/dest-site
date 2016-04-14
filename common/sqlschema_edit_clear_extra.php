<?php
	echo "<script type=\"text/javascript\" LANGUAGE=\"javascript\">\n";
	echo "function clear_{$tabschema->name}_extras()\n";
	echo "{\n";
	echo "\tmodtop=document.{$tabschema->name}SchemaDbForm.sqlschema_modify_button_{$tabschema->name}_top;\n";
	echo "\tmodbot=document.{$tabschema->name}SchemaDbForm.sqlschema_modify_button_{$tabschema->name}_bot;\n";
//		echo "\tif (modtop != undefined) modtop.style.visibility='hidden'\n";
//		echo "\tif (modbot != undefined) modbot.style.visibility='hidden'\n";
	echo "\tif (modtop != undefined) modtop.disabled=true\n";
	echo "\tif (modbot != undefined) modbot.disabled=true\n";

	reset($tabschema->field);
	while (list($key, $val) = each($tabschema->field)) {
		if ($val->type == "table-var") {
		} elseif ($val->type == "row-var") {
		} else {
			if ($schema_types[$val->type]) {
				$field_template = &$schema_types[$val->type];
				switch ($field_template->type) {
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
//									echo "window.alert(ctlobj.value+'!!!!')";
								switch ($field_val->type) {
									case "time":
									case "datetime":
									case "date":
										break;
						
									case "image":
									case "upload":
										echo "\tobj = document.{$tabschema->name}SchemaDbForm.elements;\n";
										echo "\tfor (i=0; i<{$list_length}; i++) {\n";
										echo "\t\tctlobj = obj['$field_val->name['+i+']'];\n";
										echo "\t\tif (ctlobj != undefined) {\n";
										echo "\t\t\tctlobj.value='';\n";
										echo "\t\t\tdfobj = obj['_$field_val->name['+i+']'];\n";
										echo "\t\t\tif (dfobj != undefined) {\n";
										echo "\t\t\t\tdfobj.value = ctlobj.value;\n";
										echo "\t\t\t}\n";
										echo "\t\t\tankobj = document.anchors['__$field_val->name['+i+']'];\n";
										echo "\t\t\tif (ankobj == undefined)\n";
										echo "\t\t\t\tankobj = document.getElementById('__$field_val->name['+i+']');\n"; // for explorer
										echo "\t\t\tif (ankobj != undefined) {\n";
										echo "\t\t\t\tankobj.style.display = 'none';\n";
										echo "\t\t\t}\n";
										echo "\t\t}\n";
										echo "\t}\n";
										break;
									case "bool":
									default:
										break;
								}
						}
						break;
						
					case "time":
					case "datetime":
					case "date":
						break;
						
					case "image":
					case "upload": 
						echo "\tif (document.{$tabschema->name}SchemaDbForm.$val->name != undefined)\n";
						echo "\t\tdocument.{$tabschema->name}SchemaDbForm.$val->name.value = '';\n";// the hidden current val
						echo "\ttxtobj=document.{$tabschema->name}SchemaDbForm._$val->name;\n";
						echo "\tif (txtobj != undefined) txtobj.value = '';\n";
						echo "\tankobj=document.anchors['__$val->name'];";
						echo "\tif (ankobj != undefined) ankobj.style.display = 'none';\n";
						break;
						
					case "bool":
					case "set":
					case "enum":
					default:
						break;
				}
			} else {
				switch ($val->type) {
					case "image":
					case "upload":
						echo "\tif (document.{$tabschema->name}SchemaDbForm.{$val->name} != undefined)\n";
						echo "\t\tdocument.{$tabschema->name}SchemaDbForm.{$val->name}.value = '';\n";// the hidden current val
						echo "\ttxtobj=document.{$tabschema->name}SchemaDbForm._{$val->name};\n";
						echo "\tif (txtobj != undefined) txtobj.value = '';\n";
						echo "\tankobj=document.anchors['__$val->name'];\n";
						echo "\tif (ankobj != undefined) {ankobj.style.display = 'none';}\n";
						break;
						
					case "time":
					case "datetime":
					case "date":
						break;
						
					case "enum":
					case "set":
					case "bool":
					default:
						break;
				}
				$field_count++;
			}
		}
	}
	echo "}\n";
	echo "</script>\n";
?>
