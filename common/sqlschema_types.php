<?php
//////////////////////////////////////////////////////////
// class definitions to describe an sql table
//////////////////////////////////////////////////////////
	class SQLField
	{
		var	$name;
		var	$type;
		var	$label;
		var	$is_hidden;
		var	$is_password;
		var	$is_fixed;
		var	$key_value;
		var	$qualifiers;
		var	$width;
		var	$height;
		var	$maxlen;
		var	$on_change;
		var	$element_class;
		var	$value;
		var	$expression;
		var	$documentation;
		var	$base;
		
		function SQLField($name, $type, $hidden=false, $passwd=false,
						 $keyval="", $quals="", $val=NULL, $wid=NULL, $hyt=NULL, $maxlen=NULL,
						 $ec=NULL, $oc=NULL, $isfx=NULL, $expr=NULL, $base=NULL)
		{
			$this->name = $name;
			$this->type = $type;
			$this->label = "";
			$this->is_hidden = $hidden;
			$this->is_password = $passwd;
			$this->is_fixed = $isfx;
			$this->key_value = $keyval;
			$this->qualifiers = $quals;
			$this->width = $wid;
			$this->height = $hyt;
			$this->maxlen = $maxlen;
			$this->on_change = $oc;
			$this->element_class = $ec;
			$this->value = $val;
			$this->base = $base;
			$this->expression = $expr;
			$this->documentation = "";
		}
	}
	
	class SQLTable
	{
		var	$field;
		var	$name;
		var	$label;
		var	$where;
		var	$order;
		var	$documentation;
		var	$validation;
		var	$validation_displayed;
		var	$validation_enforced;
		var	$validation_condition;
		
		function SQLTable($name, $where=NULL, $order=NULL, $label=NULL, $vd=NULL, $ve=NULL, $vc=NULL)
		{
			$this->name = $name;
			$this->label = $label;
			$this->field = array();
			$this->where = $where;
			$this->order = $order;
			$this->documentation = "";
			$this->validation = "";
			$this->validation_displayed = $vd;
			$this->validation_enforced = $ve;
			$this->validation_condition = $vc;
		}
		
		function AddField($field)
		{
			$this->field[] = $field;
		}
		
		function PrimaryKeyFieldName()
		{
			reset($this->field);
			while (list($key, $val) = each($this->field)) {
				if ($val->key_value == "primary") {
					return $val->name;
				}
			}
		}
		
		function SecondaryKeyFieldNames()
		{
			reset($this->field);
			$keynames = array();
			while (list($key, $val) = each($this->field)) {
				if ($val->key_value == "secondary") {
					$keynames[] = $val->name;
				}
			}
			return $keynames;
		}
		
		function SecondaryKeyFieldLabels()
		{
			reset($this->field);
			$keynames = array();
			while (list($key, $val) = each($this->field)) {
				if ($val->key_value == "secondary") {
					$keynames[] = $val->label;
				}
			}
			return $keynames;
		}
		
		function SecondaryKeyFieldTypes()
		{
			reset($this->field);
			$keynames = array();
			while (list($key, $val) = each($this->field)) {
				if ($val->key_value == "secondary") {
					$keynames[] = $val->type;
				}
			}
			return $keynames;
		}
	}

	class SQLUserType
	{
		var	$name;
		var	$type;
		var	$value;
		var	$documentation;
		var	$label;
		var	$size;
		
		function SQLUserType($name, $type, $size=NULL)
		{
			$this->name = $name;
			$this->type = $type;
			$this->size = $size;
			$this->value = array();
		}
		
		function Add($value) // either a field or a value ...
		{
			$this->value[] = $value;
		}
		
		function ValueNameArray()
		{
			reset($this->value);
			$va = array();
			while (list($k,$v) = each($this->value)) {
				if ($v->type == "category") {
					$va2 = $v->ValueNameArray();
					reset($va2);
					while (list($k2,$v2) = each($va2)) {
						$va[] = $v2;
					}
				} else {
					$va[] = $v->name;
				}
			}
			return $va;
		}
		
		function ValueLabelArray()
		{
			reset($this->value);
			$va = array();
			while (list($k,$v) = each($this->value)) {
				if ($v->type == "category") {
					$va2 = $v->ValueLabelArray();
					reset($va2);
					while (list($k2,$v2) = each($va2)) {
						$va[] = $v2;
					}
				} else {
					$va[] = $v->label;
				}
			}
			return $va;
		}
		function Label($value)
		{
			reset($this->value);
			$va = array();
			while (list($k,$v) = each($this->value)) {
				if ($v->type == "category") {
					$cv = $v->Label($value);
					if ($cv)
						return $cv;
				} else {
					if ($v->name == $value) {
						return $v->label;
					}
				}
			}
			return "";
		}
	}

	class SQLValue
	{
		var	$name;
		var	$value;
		var	$documentation;
		var	$label;
		
		function SQLValue($name, $value="")
		{
			$this->name = $name;
			$this->value = $value;
			$this->documentation = "";
			$this->label = "";
		}
	}
	
	function sqlschema_field_values_string($utype)
	{
		reset($utype->value);
		$field_values = "";
		while (list($key, $val) = each($utype->value)) {
			if ($field_values) {
				$field_values .= ",";
			}
			if ($val->type == "category") { // will be null if this is a pure value object or another piece of fluff
				$field_values .= sqlschema_field_values_string($val);
			} else {
				$field_values .= "'$val->name'";
			}
		}
		return $field_values;
	}
	
	function sqlschema_list_field_create_string($utype)
	{
		reset($utype->value);
		$field_values = "";
		while (list($key, $val) = each($utype->value)) {
			if ($field_values) {
				$field_values .= ",";
			}
			if ($val->type == "list") { // will be null if this is a pure value object or another piece of fluff
				$field_values .= sqlschema_field_values_string($val);
			} else {	// all lists are stored in text fields as pieces of urlencoded text separated by '&'
				$field_values .= "$val->name text";
			}
		}
		return $field_values;
	}
	
	function urlencoded_list_string($fva,$sqlschema_list_length)
	{
		$list_val_str = "";
		for ($lki=0; $lki <$sqlschema_list_length; $lki++) {
			$fv = $fva[$lki];
			if ($lki > 0) {
				$list_val_str .= "&";
			}
			if ($fv) {
				$list_val_str .= rawurlencode($fv);
			}
		}
		return $list_val_str;
	}
	
	function urlencoded_set_list_string($fva,$sqlschema_list_length)
	{
		$list_val_str = "";
		for ($lki=0; $lki <$sqlschema_list_length; $lki++) {
			$fv = $fva[$lki];
			if ($lki > 0) {
				$list_val_str .= "&";
			}
			if ($fv) {
				if (is_array($fv)) { // should be unless somethings badly wrong
					$list_val_str .= rawurlencode(list_string($fv));
				}
			}
		}
		return $list_val_str;
	}
	
	function urlencoded_bool_list_string($fva,$sqlschema_list_length)
	{
		$list_val_str = "";
		for ($lki=0; $lki <$sqlschema_list_length; $lki++) {
			$fv = $fva[$lki];
			if ($lki > 0) {
				$list_val_str .= "&";
			}
//			if ($fv) {
				$list_val_str .= (($fv != 0)?1:0);
//			}
		}
		return $list_val_str;
	}
	
	function sqlschema_list_to_array(&$field)
	{
		return array_map("rawurldecode", explode("&", $field));
	}
	
?>