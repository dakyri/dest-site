<?php
// database checks for checking match - auto complete databases based on current submission
// cloned holus bolus from sqlschema_ins_upd
function check_update_autocomplete_db(
		$auto_db_table,
		&$fill_compare_fields, &$fill_compare_val_nm, &$field_val,
		&$complete_fields, &$complete_val_nm, &$complete_val)
{
// fill_compare_fields, fill_compare_val_nm, and field_val are arrays of same length (the number
//    of fields which would distinguish a row in a support automcomplete db as unique, plus the
//    correspponding field in the db just editted, and the respective values
// complete_fields, complete_val_nm, and complete_val are arrays of same length (the number
//    of fields which are the significant values of the fields used
	global	$mysql;
	global	$login_type;
	
	if (!$auto_db_table) {
		return false;
	}
// make a db test. does this item exist? we expect a match on all the compare fields
// to be a match, and we should correct the given entry ???? maybe.
	
	$select_str = "";
	reset($fill_compare_fields);
	while (list($k,$v)=each($fill_compare_fields)) {
		$select_str = eq_clause($select_str, $v, $field_val[$k]);
	}
	if (!$select_str) { // nothing to do
		return false;
	}
	$query = "select * from $auto_db_table where ${select_str}";
	$fetch_result = mysql_query($query, $mysql);
	if (!$fetch_result) {
		return false;
	}
	$num_matching = mysql_num_rows($fetch_result);
	if ($num_matching == 0) {
		reset($complete_fields);
		$field_set_str = "";
		while (list($k,$v)=each($complete_fields)) {
			if ($complete_val[$k]) {
				$field_set_str = set_item($field_set_str, $v, $complete_val[$k]);
			}
		}
		if ($field_set_str) {
			$query = "insert into ${auto_db_table} set ${field_set_str}";
			$insert_result = mysql_query($query, $mysql);
			if (!$insert_result) {
//				errorpage(mysql_error()); // perhaps we should be quiet about things like this
				return false;
			}
		}
	} else {
// possibly try and correct inconsistencies and errors, or supply missing information.
		if ($num_matching == 1) {
			$row = mysql_fetch_object($fetch_result);
			reset($complete_fields);
			$field_set_str = "";
			while (list($k,$v)=each($complete_fields)) {
				if (isset($row->$v)) { 
					$chk_val = $row->$v;
					if (!$chk_val || ($login_type >= LOGIN_ADMIN && $chk_val != $complete_val[$k])) {
						$field_set_str = set_item($field_set_str, $v, $complete_val[$k]);
					}
				} else {
// else there is an inconsistency in this subsystem's settings. a field should be fetched even if
// in which case it would be a really bad idea to set this
				}
			}
			if ($field_set_str) {
				$query = "update ${auto_db_table} set ${field_set_str} where ${select_str}";
				$update_result = mysql_query($query, $mysql);
				if (!$update_result) {
	//				errorpage(mysql_error()); // perhaps we should be quiet about things like this
					return false;
				}
			}
		} else {
// this is getting into the can'o'worms territory. do we make changes to every matching
// entry? or if so, who should commit such wholesale slaugher....
		}
	}
	return true;
}

	if (!isset($edit_table) || !$edit_table) {
		errorpage("Sorry. No table in this schema is specified");
	}
	$tabschema = &$schema_tables[$edit_table];
	$field_set_str = "";
	if (count($sqlschema_match_fill_updates)>0) {

		$tabschema_list_max_n_nitem_name = "sqlschema_{$tabschema->name}_list_max";
		$tabschema_list_max_n_nitem = $$tabschema_list_max_n_nitem_name;
		
		$tabschema_sel_name = "$edit_table"."_sqlschema_selected";
		$tabschema_sel = $$tabschema_sel_name;
		$tabschema_key_field = $tabschema->PrimaryKeyFieldName();
		reset($sqlschema_match_fill_updates);
		while (list($key,$val)=each($sqlschema_match_fill_updates)) {
// of form "a[=b],c,d[=e] etc, the first field is the field in the editted db, the optional second
// is the name of the field used in the autocomplete db			
			$fnm = explode(",",$val->compare);
			reset($fnm);
			$fill_compare_fields = array();
			$fill_compare_val_nm = array();
			while (list($fnmk,$fnmv)=each($fnm)) {
				$expfnm = explode("=", $fnmv);
				if (count($expfnm) <= 1) {
					$fill_compare_fields[$fnmk] = $expfnm[0];
					$fill_compare_val_nm[$fnmk] = $expfnm[0];
				} else {
					$fill_compare_fields[$fnmk] = $expfnm[1];
					$fill_compare_val_nm[$fnmk] = $expfnm[0];
				}
			}

// of form "a[=b],c,d[=e] etc, the first field is the field in the editted db, the optional second
// is the name of the field used in the autocomplete db			
			$cnm = explode(",",$val->complete);
			reset($cnm);
			$complete_fields = array();
			$complete_val_nm = array();
			while (list($fnmk,$fnmv)=each($cnm)) {
				$expfnm = explode("=", $fnmv);
				if (count($expfnm) <= 1) {
					$complete_fields[$fnmk] = $expfnm[0];
					$complete_val_nm[$fnmk] = $expfnm[0];
				} else {
					$complete_fields[$fnmk] = $expfnm[1];
					$complete_val_nm[$fnmk] = $expfnm[0];
				}
			}
			if ($val->length > 0) {
				for ($i=0; $i<$val->length; $i++) {
					$field_val = array();

					reset($fill_compare_val_nm);
					while (list($fcvnmk,$fcvnmv)=each($fill_compare_val_nm)) {
						$field_val[$fcvnmk] = ${$fcvnmv}[$i]; // should be a $val->length array
					}
					$complete_val = array();
					reset($complete_val_nm);
					while (list($fcvnmk,$fcvnmv)=each($complete_val_nm)) {
						$complete_val[$fcvnmk] = ${$fcvnmv}[$i]; // ... a $val->length array
					}
					check_update_autocomplete_db(
							$val->table,
							$fill_compare_fields, $fill_compare_val_nm, $field_val,
							$complete_fields, $complete_val_nm, $complete_val);
				}
			} else {
				$field_val = array();
				reset($fill_compare_val_nm);
				while (list($fcvnmk,$fcvnmv)=each($fill_compare_val_nm)) {
					$field_val[$fcvnmk] = $$fcvnmv; // should be a single value
				}
				$complete_val = array();
				reset($complete_val_nm);
				while (list($fcvnmk,$fcvnmv)=each($complete_val_nm)) {
					$complete_val[$fcvnmk] = $$fcvnmv; // should be a single value
				}
				check_update_autocomplete_db(
						$val->table,
						$fill_compare_fields, $fill_compare_val_nm, $field_val,
						$complete_fields, $complete_val_nm, $complete_val);
			}
		}
	}
?>