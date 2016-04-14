<?php
/////////////////////////////////////////////////////////
// XML parser bits
/////////////////////////////////////////////////////////
//	var	$xmlparse_element_stack;
//	var	$xmlparse_current_table;
//	var	$xmlparse_current_cdata;
//	var	$xmlparse_current_documentation;
//	var	$xmlparse_current_validation;
//	var	$xmlparse_current_field;
//	var	$schema_tables;
//	var	$xmlparse_current_utype;
//	var	$xmlparse_utype_stack;
//	var	$schema_types;
//	var	$xmlparse_parser;
	
	$xmlparse_element_stack = array();
	$xmlparse_current_table = NULL;
	$xmlparse_current_cdata = NULL;
	$xmlparse_current_documentation = NULL;
	$xmlparse_current_validation = NULL;
	$xmlparse_current_field = NULL;
	$xmlparse_current_value = NULL;
	$xmlparse_current_utype = NULL;
	$xmlparse_utype_stack = array();
	$schema_types = array();
	$schema_tables = array();
	

	

// Element events are issued whenever the XML parser encounters start or end tags. There are separate handlers for start tags and end tags. 
	function start_element_handler( $parser, $tag_name, $attribs)
	{
//		echo "start $tag_name<br>";
		global 	$xmlparse_element_stack;
		global 	$xmlparse_utype_stack;
		global	$xmlparse_current_cdata;
		global	$xmlparse_current_documentation;
		global	$xmlparse_current_validation;
		global	$xmlparse_current_field;
		global	$xmlparse_current_value;
		global	$xmlparse_current_utype;
		global	$schema_tables;
		global	$xmlparse_current_table;
		global	$schema_types;
		
		$tos = end($xmlparse_element_stack);
		$tos_2 = prev($xmlparse_element_stack);
		$xmlparse_current_cdata = "";
				
		if ($tos == "validation") {
//			echo "->validation<br>";
			$attrib_string = attrib_string($attribs);
			if ($attrib_string) {
				$xmlparse_current_validation .= "<$tag_name $attrib_string>";
			} else {
				$xmlparse_current_validation .= "<$tag_name>";
			}
			return;
		}
		if ($tos == "documentation") {
//			echo "->documentation<br>";
			$attrib_string = attrib_string($attribs);
			if ($attrib_string) {
				$xmlparse_current_documentation .= "<$tag_name $attrib_string>";
			} else {
				$xmlparse_current_documentation .= "<$tag_name>";
			}
			return;
		}
		
		$name = "";
		$type = "";
		$label = NULL;
		$quals = NULL;
		$f_width = NULL;
		$f_height = NULL;
		$f_maxlen = NULL;
		$f_class = NULL;
		$f_onchange = NULL;
		$field_value = NULL;
		$min_value = NULL;
		$mid_value = NULL;
		$max_value = NULL;
		$is_hidden = NULL;
		$is_password = NULL;
		$is_fixed = NULL;
		$is_extensible = NULL;
		$is_key = NULL;
		$expr = NULL;
		$tab_where = NULL;
		$tab_order = NULL;
		$t_size = NULL;
		$f_base = NULL;
		$l_extend_by = NULL;
		$t_validation_displayed = NULL;
		$t_validation_enforced = NULL;
		$t_validation_condition = NULL;
		$matchQueryBase=NULL;
		$matchCompare=NULL;
		$matchComplete=NULL;
		$matchUrlPath=NULL;
		$matchUrlMatchField=NULL;
		$matchUrlFetchField=NULL;
		$matchUrlValueField=NULL;
		$matchFillTable=NULL;
		$matchFillRowCompare=NULL;
		$completionClass = NULL;
		$matchLabel = NULL;
		$completionScript = NULL;
		
		$f_editorpagesize = NULL;
		
		
		reset($attribs);
		switch ($tag_name) {
			case "sqlschema":
				while (list($key, $value)=each($attribs)) {
					switch($key) {
						case "name":
							$name = $value;
							break;
						default:
							break;
					}
				}
				$schema_database = $name;
				break;
				
			case "table":
				while (list($key, $value)=each($attribs)) {
					switch($key) {
						case "label":
							$label = $value;
							break;
						case "name":
							$name = $value;
							break;
						case "where":
							$tab_where = $value;
							break;
						case "order":
							$tab_order = $value;
							break;
						case "validation-displayed":
							$t_validation_displayed = $value;
							break;
						case "validation-enforced":
							$t_validation_enforced = $value;
							break;
						case "validation-condition":
							$t_validation_condition = $value;
							break;
						case "editorpagesize":
							$f_editorpagesize = $value;
							break;
						case "completionScript":
							$completionScript = $value;
							break;
						default:
							break;
					}
				}
				$xmlparse_current_table = new SQLTable($name, $tab_where, $tab_order, $label, $t_validation_displayed, $t_validation_enforced, $t_validation_condition);
				if ($f_editorpagesize) {
					$xmlparse_current_table->editorpagesize = $f_editorpagesize;
				}
				if ($completionScript) {
					$xmlparse_current_table->completionScript = $completionScript;
				}
				break;
				
			case "row-var":
				while (list($key, $value)=each($attribs)) {
					switch($key) {
						case "name":
							$name = $value;
							break;
						case "value":
							$field_value = $value;
							break;
					}
				}
				$xmlparse_current_field = new SQLField($name, "row-var", $is_hidden, $is_password, $is_key,
																	$quals, $field_value, $f_width, $f_height, $f_maxlen,
																	$f_class, $f_onchange, $is_fixed, $expr, $f_base);
				break;
				
			case "table-var":
				while (list($key, $value)=each($attribs)) {
					switch($key) {
						case "name":
							$name = $value;
							break;
						case "value":
							$field_value = $value;
							break;
					}
				}
				$xmlparse_current_field = new SQLField($name, "table-var", $is_hidden, $is_password, $is_key,
																	$quals, $field_value, $f_width, $f_height, $f_maxlen,
																	$f_class, $f_onchange, $is_fixed, $expr, $f_base);
				break;
				
			case "field":
				while (list($key, $value)=each($attribs)) {
					switch($key) {
						case "name":
							$name = $value;
							break;
						case "type":
							switch ($value) {	// standard mysql types
								case "integer":
								case "int":
								case "bigint":
								case "mediumint":
								case "smallint":
								case "tinyint":
								case "bit":
								case "bool":
								case "float":
								case "double":
								case "real":
								case "decimal":
								case "numeric":
								
								case "date":
								case "datetime":
								case "timestamp":
								case "time":
								case "year":
								
								case "char":
								case "varchar":
								case "tinyblob":
								case "tinytext":
								case "blob":
								case "text":
								case "mediumblob":
								case "mediumtext":
								case "longblob":
								case "longtext":
								
								case "enum":
								case "set":
									$type = $value;
									break;
								case "upload":	// a http file upload
								case "image":	// a http file upload interpreted as an image
									$type = $value;
									break;
								default:	// named type defined within the schema
									$type = $value;
									break;
							}
							break;
						case "qualifiers":
							$quals = $value;
							break;
						case "value":
							$field_value = $value;
							break;
						case "minval":
							$min_value = $value;
							break;
						case "midval":
							$mid_value = $value;
							break;
						case "maxval":
							$max_value = $value;
							break;
						case "height":
							$f_height = $value;
							break;
						case "width":
							$f_width = $value;
							break;
						case "onChange":
							$f_onchange = $value;
							break;
						case "class":
							$f_class = $value;
							break;
						case "maxlength":
							$f_maxlen = $value;
							break;
						case "key":
							$is_key = $value;
							break;
						case "expression":
							$expr = $value;
							break;
						case "base":
							$f_base = $value;
							break;
						case "hidden":
							$is_hidden = $value;
							break;
						case "password":
							if ($value == "true") {
								$is_password = true;
							}
							break;
						case "fixed":
							$is_fixed = $value;
							break;
						case "matchUrlPath":
							$matchUrlPath = $value;
							break;
						case "matchQueryBase":
							$matchQueryBase = $value;
							break;
						case "matchComplete":
							$matchComplete = $value;
							break;
						case "matchCompare":
							$matchCompare = $value;
							break;
						case "matchUrlFetchField":
							$matchUrlFetchField = $value;
							break;
						case "matchUrlValueField":
							$matchUrlValueField = $value;
							break;
						case "matchUrlMatchField":
							$matchUrlMatchField = $value;
							break;
						case "matchFillTable":
							$matchFillTable = $value;
							break;
						case "matchFillRowCompare":
							$matchFillRowCompare = $value;
							break;
						case "matchLabel":
							$matchLabel = $value;
							break;
						case "completionClass":
							$completionClass = $value;
							break;
						default:
							break;
					}
				}
				$xmlparse_current_field = new SQLField($name, $type, $is_hidden, $is_password, $is_key,
																	$quals, $field_value, $f_width, $f_height, $f_maxlen,
																	$f_class, $f_onchange, $is_fixed, $expr, $f_base);
				if ($min_value != NULL) {
					$xmlparse_current_field->min_value = $min_value;
				}
				if ($mid_value != NULL) {
					$xmlparse_current_field->mid_value = $mid_value;
				}
				if ($max_value != NULL) {
					$xmlparse_current_field->max_value = $max_value;
				}
				if ($matchQueryBase != NULL) {
					$xmlparse_current_field->matchQueryBase = $matchQueryBase;
				}
				if ($matchComplete != NULL) {
					$xmlparse_current_field->matchComplete = $matchComplete;
				}
				if ($matchCompare != NULL) {
					$xmlparse_current_field->matchCompare = $matchCompare;
				}
				if ($matchUrlPath != NULL) {
					$xmlparse_current_field->matchUrlPath = $matchUrlPath;
				}
				if ($matchUrlFetchField != NULL) {
					$xmlparse_current_field->matchUrlFetchField = $matchUrlFetchField;
				}
				if ($matchUrlMatchField != NULL) {
					$xmlparse_current_field->matchUrlMatchField = $matchUrlMatchField;
				}
				if ($matchUrlValueField != NULL) {
					$xmlparse_current_field->matchUrlValueField = $matchUrlValueField;
				}
				if ($matchFillTable != NULL) {
					$xmlparse_current_field->matchFillTable = $matchFillTable;
				}
				if ($matchFillRowCompare != NULL) {
					$xmlparse_current_field->matchFillRowCompare = $matchFillRowCompare;
				}
				if ($completionClass != NULL) {
					$xmlparse_current_field->completionClass = $completionClass;
				}
				if ($matchLabel != NULL) {
					$xmlparse_current_field->matchLabel = $matchLabel;
				}
				break;
				
			case "list-type":
				while (list($key, $value)=each($attribs)) {
					switch($key) {
						case "name":
							$name = $value;
							break;
						case "size":
							$t_size = $value;
							break;
						case "extend-by":
							$l_extend_by = $value;
							break;
						case "maxlength":
							$l_maxlength = $value;
							break;
						default:
							break;
					}
				}
				$xmlparse_current_utype = new SQLUserType($name, "list", $t_size);
				if ($l_extend_by) {
					$xmlparse_current_utype->extend_by = $l_extend_by;
				}
				if ($l_maxlength) {
					$xmlparse_current_utype->maxlength = $l_maxlength;
				}
				break;
				
			case "enum-type":
				while (list($key, $value)=each($attribs)) {
					switch($key) {
						case "size":
							$t_size = $value;
							break;
						case "name":
							$name = $value;
							break;
						case "extensible":
							$is_extensible = $value;
							break;
						case "label":
							$label = $value;
							break;
						default:
							break;
					}
				}
				$xmlparse_current_utype = new SQLUserType($name, "enum");
				if ($is_extensible != NULL) {
					$xmlparse_current_utype->extensible = $is_extensible;
				}
				if ($label) {
					$xmlparse_current_utype->label = $label;
				}
				break;
				
			case "set-type":
				while (list($key, $value)=each($attribs)) {
					switch($key) {
						case "size":
							$t_size = $value;
							break;
						case "name":
							$name = $value;
							break;
						default:
							break;
					}
				}
				$xmlparse_current_utype = new SQLUserType($name, "set");
				break;
				
			case "simple-type":
				while (list($key, $value)=each($attribs)) {
					switch($key) {
						case "name":
							$name = $value;
							break;
						case "type":
							$field_type = $value;
							break;
						default:
							break;
					}
				}
				$xmlparse_current_utype = new SQLUserType($name, $field_type);
				break;
				
			case "verification":
				$current_verification = "";
				break;
				
			case "documentation":
				$xmlparse_current_documentation = "";
				break;
				
			case "category":
				while (list($key, $value)=each($attribs)) {
					switch($key) {
						case "label":
							$label = $value;
							break;
						case "name":
							$name = $value;
							break;
						default:
							break;	
					}
				}
				array_push($xmlparse_utype_stack, $xmlparse_current_utype);
				$xmlparse_current_utype = new SQLUserType($name, "category");
				$xmlparse_current_utype->label = $label;
				break;
				
			case "value":
				while (list($key, $value)=each($attribs)) {
					switch($key) {
						case "name":
							$name = $value;
							break;
						default:
							break;
					}
				}
				$xmlparse_current_value = new SQLValue($name);
				break;
				
			case "list":
				while (list($key, $value)=each($attribs)) {
					switch($key) {
						case "label":
							$label = $value;
							break;
						case "name":
							$name = $value;
							break;
						default:
							break;	
					}
				}
				array_push($xmlparse_utype_stack, $xmlparse_current_utype);
				$xmlparse_current_utype = new SQLUserType($name, "list");
				break;
				
			default:
				break;
		}
		array_push($xmlparse_element_stack, $tag_name);
	}

	function end_element_handler( $parser, $tag_name)
	{
		global 	$xmlparse_element_stack;
		global 	$xmlparse_utype_stack;
		global	$xmlparse_current_cdata;
		global	$xmlparse_current_documentation;
		global	$xmlparse_current_validation;
		global	$xmlparse_current_field;
		global	$xmlparse_current_value;
		global	$xmlparse_current_utype;
		global	$schema_tables;
		global	$xmlparse_current_table;
		global	$schema_types;
		
		$tos = end($xmlparse_element_stack);
		
//		echo "end $tag_name tos=$tos<br>";
		
		if ($tos == "validation") {
			if ($tag_name != "validation") {
				$xmlparse_current_validation .= "</$tag_name>";
			} else {
				array_pop($xmlparse_element_stack);
				$tos = end($xmlparse_element_stack);
				switch ($tos) {
					case "field":
						$xmlparse_current_field->validation = $xmlparse_current_validation;
						break;
					case "table":
						$xmlparse_current_table->validation = $xmlparse_current_validation;
						break;
					case "category":
					case "list":
					case "enum-type":
					case "list-type":
					case "set-type":
						$xmlparse_current_utype->validation = $xmlparse_current_validation;
						break;
				}
				$xmlparse_current_validation = "";
			}
			return;
		}
		
		if ($tos == "documentation") {
			if ($tag_name != "documentation") {
				$xmlparse_current_documentation .= "</$tag_name>";
			} else {
				array_pop($xmlparse_element_stack);
				$tos = end($xmlparse_element_stack);
				switch ($tos) {
					case "field":
						$xmlparse_current_field->documentation = $xmlparse_current_documentation;
						break;
					case "table":
						$xmlparse_current_table->documentation = $xmlparse_current_documentation;
						break;
					case "category":
					case "list":
					case "enum-type":
					case "list-type":
					case "set-type":
						$xmlparse_current_utype->documentation = $xmlparse_current_documentation;
						break;
				}
				$xmlparse_current_documentation = "";
			}
			return;
		}
		
		$expected_name = array_pop($xmlparse_element_stack);	// expect "$expected_name == $tag_name" but not worth forcing the issue
		$tos = end($xmlparse_element_stack);
		
//		echo "'$tag_name' tag name, tos now $tos<br>";
		switch ($tag_name) {
			case "sqlschema":
				break;
				
			case "table":
				if ($xmlparse_current_table) {
					$schema_tables[$xmlparse_current_table->name] = $xmlparse_current_table;
					$xmlparse_current_table = NULL;
				}
				break;
				
			case "row-var":
			case "table-var":
			case "field":
				if ($xmlparse_current_field) {
					if ($tos == "list-type") {
						if ($xmlparse_current_utype) {
							$xmlparse_current_utype->Add($xmlparse_current_field);
						}
					} elseif ($tos == "table") {
						if ($xmlparse_current_table) {
							$xmlparse_current_table->AddField($xmlparse_current_field);
						}
					}
					$xmlparse_current_field = NULL;
				}			
				break;
				
			case "list-type":
				if ($xmlparse_current_utype) {
					$schema_types[$xmlparse_current_utype->name] = $xmlparse_current_utype;
					$xmlparse_current_utype = NULL;
				}
				break;
			case "enum-type":
				if ($xmlparse_current_utype) {
					$schema_types[$xmlparse_current_utype->name] = $xmlparse_current_utype;
					$xmlparse_current_utype = NULL;
				}
				break;
			case "set-type":
				if ($xmlparse_current_utype) {
					$schema_types[$xmlparse_current_utype->name] = $xmlparse_current_utype;
					$xmlparse_current_utype = NULL;
				}
				break;
			case "simple-type":
				if ($xmlparse_current_utype) {
					$schema_types[$xmlparse_current_utype->name] = $xmlparse_current_utype;
					$xmlparse_current_utype = NULL;
				}
				break;
			case "category":
				if ($xmlparse_current_utype) {
					$cat = $xmlparse_current_utype;
					$xmlparse_current_utype = array_pop($xmlparse_utype_stack);
					$xmlparse_current_utype->Add($cat);
				}
				break;
			case "value":
				if ($xmlparse_current_value) {
					$xmlparse_current_value->label = $xmlparse_current_cdata;
					if ($xmlparse_current_utype) {
						$xmlparse_current_utype->Add($xmlparse_current_value);
					}
					$xmlparse_current_value = NULL;
				}
				break;
			case "list":
				if ($xmlparse_current_utype) {
					$lis = $xmlparse_current_utype;
					$xmlparse_current_utype = array_pop($xmlparse_utype_stack);
					$xmlparse_current_utype->Add($lis);
				}
				break;
			default:
				break;
		}
	}

	function character_data_handler($parser, $data) 
//Character data is roughly all the non-markup contents of XML documents, including whitespace between tags. Note that the XML parser does not add or remove any whitespace, it is up to the application (you) to decide whether whitespace is significant. 
	{
//		echo "cdata [$data]<br>";
		global 	$xmlparse_element_stack;
		global 	$xmlparse_utype_stack;
		global	$xmlparse_current_cdata;
		global	$xmlparse_current_documentation;
		global	$xmlparse_current_validation;
		global	$xmlparse_current_field;
		global	$xmlparse_current_value;
		global	$xmlparse_current_utype;
		global	$schema_tables;
		global	$xmlparse_current_table;
		global	$schema_types;
		
		$tos = end($xmlparse_element_stack);
		
		if ($tos == "validation") {
			$xmlparse_current_validation .= $data;
			return;
		}
		if ($tos == "documentation") {
			$xmlparse_current_documentation .= $data;
			return;
		}
		if ($xmlparse_current_field) {
			$xmlparse_current_field->label .= $data;
		}
		$xmlparse_current_cdata .= $data;
	}

	function processing_instruction_handler($parser, $target, $data) 
// PHP programmers should be familiar with processing instructions (PIs) already.  is a processing instruction, where php is called the "PI target". The handling of these are application-specific, except that all PI targets starting with "XML" are reserved. 
	{
//		echo "pi $target $data<br>";
		global 	$xmlparse_element_stack;
		global 	$xmlparse_utype_stack;
		global	$xmlparse_current_cdata;
		global	$xmlparse_current_documentation;
		global	$xmlparse_current_validation;
		global	$xmlparse_current_field;
		global	$xmlparse_current_value;
		global	$xmlparse_current_utype;
		global	$schema_tables;
		global	$xmlparse_current_table;
		global	$schema_types;
		
		$tos = end($xmlparse_element_stack);
		
		if ($tos == "validation") {
			$xmlparse_current_validation .= "\<\?$target $data\?\>";
			return;
		}
		if ($tos == "documentation") {
			$xmlparse_current_documentation .= "\<\?$target $data\?\>";
			return;
		}
	}


	function default_handler($parser, $data)
// What goes not to another handler goes to the default handler. You will get things like the XML and document type declarations in the default handler. 
	{
//		echo "default: |$data|<br>";
		global 	$xmlparse_element_stack;
		global 	$xmlparse_utype_stack;
		global	$xmlparse_current_cdata;
		global	$xmlparse_current_documentation;
		global	$xmlparse_current_validation;
		global	$xmlparse_current_field;
		global	$xmlparse_current_value;
		global	$xmlparse_current_utype;
		global	$schema_tables;
		global	$xmlparse_current_table;
		global	$schema_types;
		
		$tos = end($xmlparse_element_stack);
		
		if ($tos == "validation") {
			$xmlparse_current_validation .= $data;
			return;
		}
		if ($tos == "documentation") {
			$xmlparse_current_documentation .= $data;
			return;
		}
		$xmlparse_current_cdata .= $data;
	}


	function unparsed_entity_decl_handler($parser, $entity_name, $base, $system_id, $public_id, $notation_name) 
// This handler will be called for declaration of an unparsed (NDATA) entity. 
// entity_name: The name of the entity that is about to be defined. 
// base: This is the base for resolving the system identifier (systemId) of the external entity. Currently this parameter will always be set to an empty string. 
// system_id: System identifier for the external entity. 
// public_id: Public identifier for the external entity. 
// notation_name: Name of the notation of this entity (see xml_set_notation_decl_handler()). 
	{
//		echo "unparsed entity $notation_name: $base, $system_id, $public_id<br>";
		global 	$xmlparse_element_stack;
		global 	$xmlparse_utype_stack;
		global	$xmlparse_current_cdata;
		global	$xmlparse_current_documentation;
		global	$xmlparse_current_validation;
		global	$xmlparse_current_field;
		global	$xmlparse_current_value;
		global	$xmlparse_current_utype;
		global	$schema_tables;
		global	$xmlparse_current_table;
		global	$schema_types;
		
		$tos = end($xmlparse_element_stack);
		
		if ($tos == "validation") {
			$str = "<!ENTITY $entity_name";
			$str .= ($public_id?" PUBLIC $public_id": " $system_id");
			$str .= " NDATA $notation_name>";
			$xmlparse_current_validation .= $str;
			return;
		}
		if ($tos == "documentation") {
			$str = "<!ENTITY $entity_name";
			$str .= ($public_id?" PUBLIC $public_id": " $system_id");
			$str .= " NDATA $notation_name>";
			$xmlparse_current_documentation .= $str;
			return;
		}
 	}

	function notation_decl_handler($parser, $notation_name, $base, $system_id, $public_id) 
// This handler is called for declaration of a notation. 
// notation_name: This is the notation's name, as per the notation format described above. 
// base: This is the base for resolving the system identifier (system_id) of the notation declaration. Currently this parameter will always be set to an empty string. 
// system_id: System identifier of the external notation declaration. 
// public_id: Public identifier of the external notation declaration. 
	{
//		echo "notation decl: $base, $system_id, $public_id<br>";
		global 	$xmlparse_element_stack;
		global 	$xmlparse_utype_stack;
		global	$xmlparse_current_cdata;
		global	$xmlparse_current_documentation;
		global	$xmlparse_current_validation;
		global	$xmlparse_current_value;
		global	$xmlparse_current_field;
		global	$xmlparse_current_utype;
		global	$schema_tables;
		global	$xmlparse_current_table;
		global	$schema_types;
		
		$tos = end($xmlparse_element_stack);
		
		if ($tos == "validation") {
			$str = "<!NOTATION $notation_name";
			$str .= ($public_id?" PUBLIC $public_id": " $system_id");
			$str .= ">";
			$xmlparse_current_validation .= $str;
			return;
		}
		if ($tos == "documentation") {
			$str = "<!NOTATION $notation_name";
			$str .= ($public_id?" PUBLIC $public_id": " $system_id");
			$str .= ">";
			$xmlparse_current_documentation .= $str;
			return;
		}

	}

	function external_entity_ref_handler($parser, $open_entity_names, $base, $system_id, $public_id) 
// open_entity_names: The second parameter, open_entity_names, is a space-separated list of the names of the entities that are open for the parse of this entity (including the name of the referenced entity). 
// base: This is the base for resolving the system identifier (system_id) of the external entity. Currently this parameter will always be set to an empty string. 
// system_id: The fourth parameter, system_id, is the system identifier as specified in the entity declaration. 
// public_id: The fifth parameter, public_id, is the public identifier as specified in the entity declaration, or an empty string if none was specified; the whitespace in the public identifier will have been normalized as required by the XML spec. 
	{
		global 	$xmlparse_element_stack;
		global 	$xmlparse_utype_stack;
		global	$xmlparse_current_cdata;
		global	$xmlparse_current_documentation;
		global	$xmlparse_current_validation;
		global	$xmlparse_current_field;
		global	$xmlparse_current_value;
		global	$xmlparse_current_utype;
		global	$schema_tables;
		global	$xmlparse_current_table;
		global	$schema_types;
		
		global	$xmlparse_external_base;
		
		$tos = end($xmlparse_element_stack);
//		echo "external entity: $base, $system_id, $public_id";
		if ($tos == "validation") {
			$str = "<!ENTITY $entity_name";
			$str .= ($public_id?" PUBLIC $public_id": " SYSTEM $system_id");
			$str .= " NDATA $notation_name>";
			$xmlparse_current_validation .= $str;
			return;
		}
		if ($tos == "documentation") {
			$str = "<!ENTITY $entity_name";
			$str .= ($public_id?" PUBLIC $public_id": " SYSTEM $system_id");
			$str .= " NDATA $notation_name>";
			$xmlparse_current_documentation .= $str;
			return;
		}
		
		if ($system_id) {
			if (!($fp = fopen("$xmlparse_external_base/$system_id", "r"))) {
				echo "failed to open $xmlparse_external_base/$system_id";
				return false;
			}
			if (!($xparser = new_sqlschema_parser($xmlparse_external_base))) {
	            printf("Could not open entity %s at %s\n", $open_entity_names,
	                   $system_id);
	            return false;
	      }
	      while ($data = fread($fp, 4096)) {
	         if (!xml_parse($xparser, $data, feof($fp))) {
	                printf("XML error: %s at line %d while parsing entity %s\n",
	                       xml_error_string(xml_get_error_code($xparser)),
	                       xml_get_current_line_number($xparser), $open_entity_names);
	                xml_parser_free($xparser);
	                return false;
				}
	      }
	      xml_parser_free($xparser);
	      return true;
	   }
	   return false;
	}
	
	function new_sqlschema_parser($base)
	{
		global	$xmlparse_external_base;
		
		$xmlparse_parser = xml_parser_create();
		$xmlparse_external_base = $base;
		if (!xml_set_element_handler($xmlparse_parser, "start_element_handler", "end_element_handler")) return false;
		if (!xml_set_character_data_handler($xmlparse_parser, "character_data_handler")) return false;
		if (!xml_set_processing_instruction_handler($xmlparse_parser, "processing_instruction_handler")) return false ;
		if (!xml_set_default_handler($xmlparse_parser, "default_handler")) return false;
		if (!xml_set_unparsed_entity_decl_handler($xmlparse_parser, "unparsed_entity_decl__handler")) return false;
		if (!xml_set_notation_decl_handler($xmlparse_parser, "notation_decl_handler")) return false;
		if (!xml_set_external_entity_ref_handler($xmlparse_parser, "external_entity_ref_handler")) return false;
		if (!xml_parser_set_option($xmlparse_parser, XML_OPTION_CASE_FOLDING, false)) return false;
		return $xmlparse_parser;
	}
	
	function parse_sqlschema($parser, $fp)
	{
		while ($data = fread($fp, 4096)) {
			if (!xml_parse($parser, $data)) {
   	     return (sprintf("XML error: %s at line %d in main file",
   	                 xml_error_string(xml_get_error_code($parser)),
   	                 xml_get_current_line_number($parser)));
			}
		}
		return false;
	}
	

?>