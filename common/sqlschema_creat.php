<?php
	$tables_present = array();
	$tables_present_result = mysql_list_tables($schema_database,$mysql);
	if (!$tables_present_result) {
		errorpage("Can't read tables for database '$schema_database'");
	}
	$i = 0;
	$nt = mysql_num_rows($tables_present_result);
	while ($i<$nt && ($tprow = mysql_tablename($tables_present_result, $i++))) {
		$tables_present[] = $tprow[0];
	}
	
	reset($schema_tables);
	while (list($schkey, $tabschema) = each($schema_tables)) {
		if (index_of($tabschema, $tables_present) < 0) {
			$field_create_str = "";
			while (list($key,$val)=each($tabschema->field)) {
				$field_str = "";
				switch ($val->type) {
					case "field-var":
						break;
					case "table-var":
						break;
					default:
						if ($schema_types[$val->type]) {
							$field_template = &$schema_types[$val->type];
							switch ($field_template->type) {
								case "field-var":
									break;
								case "table-var":
									break;
								case "list":
									$field_str = sqlschema_list_field_create_string($field_template);
									break;
								case "enum":
									$field_str = "$val->name enum(";
									$field_str .= sqlschema_field_values_string($field_template);
									$field_str .= ")";
									if ($val->qualifiers) {
										$field_str .= " $val->qualifiers";
									}
									if ($field_template->qualifiers) {
										$field_str .= $field_template->qualifiers;
									}
									break;
								case "set":
									$field_str = "$val->name set(";
									$field_str .= sqlschema_field_values_string($field_template);
									$field_str .= ")";
									if ($val->qualifiers) {
										$field_str .= " $val->qualifiers";
									}
									if ($field_template->qualifiers) {
										$field_str .= $field_template->qualifiers;
									}
									break;
									
								case "upload":
								case "image":
									$field_str = "$val->name text";
									if ($val->qualifiers) {
										$field_str .= " $val->qualifiers";
									}
									if ($field_template->qualifiers) {
										$field_str .= $field_template->qualifiers;
									}
									break;
									
								default:
									$field_str = "$val->name $field_template->type";
									if ($val->qualifiers) {
										$field_str .= " $val->qualifiers";
									}
									if ($field_template->qualifiers) {
										$field_str .= $field_template->qualifiers;
									}
									break;
							}
						} else {
							switch ($val->type) {
								case "upload":
								case "image":
									$field_str = "$val->name text";
									if ($val->qualifiers) {
										$field_str .= " $val->qualifiers";
									}
									break;
								default:
									$field_str = "$val->name $val->type";
									if ($val->qualifiers) {
										$field_str .= " $val->qualifiers";
									}
									break;
							}
						}
						break;
				}
				if ($field_str) {
					if ($field_create_str) {
						$field_create_str .= ", ";
					}
					$field_create_str .= $field_str;
				}
			}

			$query = "create table $tabschema->name ($field_create_str)";
			$create_result = mysql_query($query, $mysql);
			if (!$create_result) {
				errorpage("create error ".mysql_error()
						."<br>in<br>"."<font color=\"#ff6699\">$query</font>"
					);
			}
			$update_msg .= "<p>Created table $tabschema->name</p>";
		} else {
			$update_msg .= "<p>Table $tabschema->name already exists</p>";
		}
	}
?>