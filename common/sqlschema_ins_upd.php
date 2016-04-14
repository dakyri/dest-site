<?php
// database call and setup for set and update
	function http_upload($upload_varname, $upload_base, $value)
	{
		global	$action_msg;
		global	$HTTP_POST_FILES;
		
		$upload_filename = $value;
//		echo $upload_varname, "<br>";
//		var_dump($HTTP_POST_FILES[$upload_varname]);
		if ($HTTP_POST_FILES[$upload_varname]) {
			$upf = &$HTTP_POST_FILES[$upload_varname];
			if (isset($upf["error"]) &&  $upf["error"] != 0) {
				if ($upf["error"] != 4) { // ignore no file error
					$action_msg .=  "File upload error ".upload_error_string($upf["error"]);
				} else {
					$upload_filename = $value;
				}
			} else {
				if (!is_dir($upload_base)) {
					if (!mkdir_hierarchy($upload_base, 0775)) {
						$action_msg .= "Base directory for upload does not exist, please contact the site administrator";
					}
				}
				if (is_dir($upload_base)) {
					$upathname = "$upload_base/".$upf["name"];
					if (@move_uploaded_file($upf["tmp_name"], $upathname)) {
						$upload_filename = $upf["name"];
					} else {
						$action_msg .=  "<b>File upload error: move_uploaded_file fails on server! ".
							"Please contact site administrator</b>";
						$upload_filename = $value;
					}
				} else {
				}
			}
		}
		return $upload_filename;
	}
	
	class MatchFillUpdate
	{
		var	$length;
		var	$table;
		var	$compare;
		var	$complete;
		function MatchFillUpdate($len, $tab, $cmpr, $cmplte)
		{
			$this->length = $len;
			$this->table = $tab;
			$this->compare = $cmpr;
			$this->complete = $cmplte;
		}
	}
	$sqlschema_match_fill_updates = array();

	if (!isset($edit_table) || !$edit_table) {
		errorpage("Sorry. No table in this schema is specified");
	}
	$tabschema = &$schema_tables[$edit_table];
	$tabschema_validated = false;
	$field_set_str = "";
	if ($tabschema->validation) {
		if ($tabschema->validation_enforced) {
			$vrx = @eval("return $tabschema->validation_enforced;");
		} else {
			$vrx = true;
		}
		if ($vrx) {
			if ($tabschema->validation_condition) {
				$tabschema_validated = @eval("return $tabschema->validation_condition;");
				if (!$tabschema_validated) {
					$action_msg = "<font color=\"#E82828\"><p>** Validation requirements have not been met! **</p></font>";
				}
			} else { 
				$vnm = "sqlschema_$tabschema->name"."_validated";
				if ($$vnm == "affirmed") {
					$tabschema_validated = true;
				} else {
					$action_msg = "<font color=\"#E82828\"><p>** Please check the validation requirements at the bottom of the page! **</p></font>";
				}
			}
		} else {
			$tabschema_validated = true;
		}
	} else {
		$tabschema_validated = true;
	}
	if ($tabschema_validated) {

		$tabschema_list_max_n_nitem_name = "sqlschema_{$tabschema->name}_list_max";
		$tabschema_list_max_n_nitem = $$tabschema_list_max_n_nitem_name;
		
		$tabschema_sel_name = "$edit_table"."_sqlschema_selected";
		$tabschema_sel = $$tabschema_sel_name;
		$tabschema_key_field = $tabschema->PrimaryKeyFieldName();
		reset($tabschema->field);
		while (list($key,$val)=each($tabschema->field)) {
			$nm = $val->name;
			$field_value = NULL;
			$force_omit = false;
			if (!$val->is_fixed || !@eval("return $val->is_fixed;")) {
//				echo $nm, "!",$$nm,"!",isset($$nm),"<br>";
				if (isset($$nm)) {
					$field_value=$$nm;
				}
			}
			if ($field_value == NULL && $val->expression) {
				$field_value = @eval("return $val->expression;");
				if ($field_value == NULL) {
					$force_omit = true;
				}
//				echo "exp val $val->expression => $field_value<br>";
			}
			if ($field_value == NULL && $val->value != NULL) {
				$field_value = $val->value;
			}
//			echo "$nm = !".$$nm."!<br>";
			if ($val->name == $tabschema_key_field) {
				$tabschema_sel = $$tabschema_key_field;
				$where = "$tabschema_key_field=$tabschema_sel";
			} else {
				switch ($val->type) {
					case "field-var":
						break;
					case "table-var":
						break;
					case "image":
					case "upload": // $$nm stores the server local name to the prior upload
						$unm = "{$nm}__";
						$base = $schema_edit_upload_base;
						if ($val->base) {
							$fb = @eval("return $val->base;");
							if ($fb) {
								$base .= "/$fb";
							}
						}
						//echo "$val->base => $base";
						$uploaded_name .= http_upload($unm, $base, $field_value);
						$field_set_str = set_item($field_set_str, $nm, $uploaded_name);
						break;
						
					case "bool":
//						if (!$force_omit) {
							$field_set_str = set_item($field_set_str, $nm, ($field_value!=0)?1:0);
//						}
						break;
						
					default:
						if (!$schema_types[$val->type]) {
							if ($val->matchFillTable) {
								$sqlschema_match_fill_updates[] = new MatchFillUpdate(
										0,
										$val->matchFillTable,
										$val->matchFillRowCompare,
										$val->matchComplete
									);										
							}
							if (!$force_omit) {
								$field_set_str = set_item($field_set_str, $nm, $field_value);
							}
						} else {
							$field_template = &$schema_types[$val->type];
							switch ($field_template->type) {
								case "field-var":
									break;
								case "table-var":
									break;
								case "enum":
									if ($field_value != NULL) {
										$field_set_str = set_item($field_set_str, $nm, $field_value);
									}
									break;
									
								case "set":
//									if ($field_value != NULL) {
										$field_set_str = set_item($field_set_str, $nm, is_array($field_value)?list_string($field_value):$field_value);
//									}
									break;
									
								case "image":
								case "upload": // $$nm stores the server local name to the prior upload
									$unm = "{$nm}__";
									$base = $schema_edit_upload_base;
									if ($val->base) {
										$fb = @eval("return $val->base;");
										if ($fb) {
											$base .= "/$fb";
										}
									}
									$uploaded_name .= http_upload($unm, $base, $field_value);
									$field_set_str = set_item($field_set_str, $nm, $uploaded_name);
									break;
									
								case "bool":
//									if ($field_value != NULL) {
										$field_set_str = set_item($field_set_str, $nm, ($field_value!=0)?1:0);
//									}
									break;
									
								case "list":
									if ($field_template->size > 0) {
										$list_length = $field_template->size;
									} elseif ($schema_edit_list_length > 0) {
										$list_length = $schema_edit_list_length;
									} else {
										$list_length = 6;
									}
									
									if ( is_array($tabschema_list_max_n_nitem) &&
										  isset($tabschema_list_max_n_nitem[$val->name]) &&
										  $tabschema_list_max_n_nitem[$val->name] >= $list_length) {
										$extend_by = ($field_template->extend_by)?$field_template->extend_by:1;
										$list_length = $tabschema_list_max_n_nitem[$val->name]+$extend_by;
										if (isset($field_template->maxlength) && $list_length > $field_template->maxlength) {
											$list_length = $field_template->maxlength;
										}
									}

									reset($field_template->value);
									while (list($ftkey,$ftval)=each($field_template->value)) {
										$ftval_value = NULL;
										if (!$ftval->is_fixed  || !@eval("return $ftval->is_fixed;")) {
											if (isset(${$ftval->name})) { 
												$ftval_value=${$ftval->name};
											}
										}
										if ($ftval_value == NULL && $ftval->expression) {
											$ftval_value = @eval("return $ftval->expression;");
										}
										if ($ftval_value == NULL && $ftval->value != NULL) {
											$ftval_value = $ftval->value;
										}
										switch ($ftval->type) {
											case "field-var":
												break;
											case "table-var":
												break;
											case "set":
												errorpage("anonymous list of set unimplemented");
												break;
											case "list":
												errorpage("anonymous list of list unimplemented");
												break;
												
											case "enum":
												errorpage("anonymous list of enum unimplemented");
												break;
												
											case "image":
											case "upload":
												$nm_urlist = "";
												$base = $schema_edit_upload_base;
												if ($ftval->base) {
													$fb = @eval("return $ftval->base;");
													if ($fb) {
														$base .= "/$fb";
													}
												}
												if ($ftval_value == NULL) {
													$ftval_value = ${$ftval->name};
												}
												for ($li=0; $li<$list_length; $li++) {
													if ($li > 0) {
														$nm_urlist .= "&";
													}
													$linm = $ftval->name."__$li";
													$nm_urlist .= http_upload($linm, $base, $ftval_value[$li]);
												}
												$field_set_str = set_item($field_set_str, $ftval->name, $nm_urlist);
												break;
												
											case "bool":
//												if ($ftval_value != NULL) {
													$field_set_str = set_item(
															$field_set_str,
															$ftval->name,
															urlencoded_bool_list_string($ftval,$list_length));
//												}
												break;
												
											default:
												if (!$schema_types[$ftval->type]) {
													if ($ftval_value != NULL) {
														if ($ftval->matchFillTable) {
															$sqlschema_match_fill_updates[] = new MatchFillUpdate(
																	$list_length,
																	$ftval->matchFillTable,
																	$ftval->matchFillRowCompare,
																	$ftval->matchComplete
																);										
														}
														$field_set_str = set_item(
																$field_set_str,
																$ftval->name,
																is_array($ftval_value)?
																		urlencoded_list_string($ftval_value,$list_length):
																		$ftval_value
																);
													}
//													echo 'set to ', $field_set_str, '<br>';
												} else {
													switch ($ftval->type) {
														case "field-var":
															break;
														case "table-var":
															break;
														case "set":
//															if ($ftval_value != NULL) {
																$field_set_str = set_item(
																		$field_set_str,
																		$ftval->name,
																		is_array($ftval_value)?
																				urlencoded_set_list_string($ftval_value,$list_length):
																				$ftval_value);
//															}
															break;
														case "list":
															errorpage("list of list unimplemented");
															break;
														case "enum":
															if ($ftval_value != NULL) {
																$field_set_str = set_item(
																		$field_set_str,
																		$ftval->name,
																		is_array($ftval_value)?
																				urlencoded_list_string($ftval_value,$list_length):$ftval_value);
															}
															break;
															
														case "image":
														case "upload":
															$nm_urlist = "";
															$base = $schema_edit_upload_base;
															if ($ftval->base) {
																$fb = @eval("return $ftval->base;");
																if ($fb) {
																	$base .= "/$fb";
																}
															}
															for ($li=0; $li<$list_length; $li++) {
																if ($li > 0) {
																	$nm_urlist .= "&";
																}
																
																$linm = $ftval->name."__$li";
																$nm_urlist .= http_upload($linm, $base, $ftval_value[$li]);
															}
															$field_set_str = set_item($field_set_str, $ftval->name, $nm_urlist);
															break;
															
														case "bool":
//															if ($ftval_value != NULL) {
																$field_set_str = set_item(
																		$field_set_str,
																		$ftval->name,
																		urlencoded_bool_list_string($ftval,$list_length));
//															}
															break;
															
														default:
															if ($ftval_value != NULL) {
																if ($ftval->matchFillTable) {
																	$sqlschema_match_fill_updates[] = new MatchFillUpdate(
																			$list_length,
																			$ftval->matchFillTable,
																			$ftval->matchFillRowCompare,
																			$ftval->matchComplete
																		);										
																}
																$field_set_str = set_item(
																		$field_set_str,
																		$ftval->name,
																		is_array($ftval_value)?
																				urlencoded_list_string($ftval_value,$list_length):
																				$ftval_value
																		);
															}
															break;
													}
												}
												break;
										}
									}
									break;
									
								default:
									if ($field_value != NULL) {
										if ($val->matchFillTable) {
											$sqlschema_match_fill_updates[] = new MatchFillUpdate(
													0,
													$val->matchFillTable,
													$val->matchFillRowCompare,
													$val->matchComplete
												);										
										}
										$field_set_str = set_item($field_set_str, $nm, $field_value);
									}
									break;
							}
						}
						break;
				}
			}
		}
		
	}
?>