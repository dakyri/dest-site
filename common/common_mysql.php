<?php

define("MYSQL_ER_SERVER", 1000);
define("MYSQL_ER_DBACCESS_DENIED", 1044);
define("MYSQL_ER_ACCESS_DENIED", 1045);
define("MYSQL_ER_NO_DB_SELECTED", 1046);
define("MYSQL_ER_NO_SUCH_TABLE", 1146);
define("MYSQL_ER_NONEXISTING_TABLE_GRANT", 1147);
define("MYSQL_ER_NOT_ALLOWED_COMMAND", 1148);
define("MYSQL_ER_SYNTAX_ERROR", 1149);
define("MYSQL_ER_DUP_KEY", 1022);
define("MYSQL_ER_DUP_FIELDNAME", 1060);
define("MYSQL_ER_DUP_KEYNAME", 1061);
define("MYSQL_ER_DUP_ENTRY", 1062);
define("MYSQL_ER_CLIENT", 2000);
define("MYSQL_ER_UNKNOWN_CLIENT_ERROR", 2000);
define("MYSQL_ER_SOCKET_CREATE", 2001);
define("MYSQL_ER_LOCAL_CONNECTION", 2002);
define("MYSQL_ER_HOST_CONNECTION", 2003);


function typelist_string($setarr)
{
	if ($setarr[0])
		$setstr = "'$setarr[0]'";
	for ($i=1; $i<sizeof($setarr); $i++) {
		$setstr.=",'$setarr[$i]'";
	}
	return $setstr;
}

// returns a comma seperated list with no spaces
function list_string($setarr)
{
	if (sizeof($setarr) == 0) {
		return "";
	}
	$setstr = "";
	while (list($k,$v) = each($setarr)) {
		if ($v) {
			if ($setstr) $setstr .= ",";
			$setstr.=$v;
		}
	}
	return $setstr;
}

// for sets and enums
function fetch_type_values($table, $field)
{
	$values = array();
	$result = mysql_query("show columns from $table like '$field'");
	if ($result > 0) {
		$row = mysql_fetch_object($result);
		$spl = split("\('?|'?\)|'?,'?|`", $row->Type);
		if ($spl[0] == "enum" || $spl[0] == "set") {
			for ($i=1; $i<sizeof($spl)-1; $i++) {
				$values[] = $spl[$i];
			}
		}
		mysql_free_result($result);
	}
	return $values;
}

function set_item($set, $field, $value)
{
//	if ($value != "") {
		if ($set) {
			$set .= ", ";
		}
		// look for unquoted apostrophes, though this isnt quite exhaustive
		if (!strstr($value,"\\'")) {
			$value = str_replace("'", "\\'", $value);
		}
		$set.= "$field = '$value'";
//	}
	return $set;
}

function eq_clause($exp, $field, $value, $conj="&&")
{
	if ($value && $field && $value != "" && $field != "" && $value != "*") {
		if ($exp) {
			$exp .= " $conj ";
		}
		// look for unquoted apostrophes, though this isnt quite exhaustive
		if (!strstr($value,"\\'")) {
			$value = str_replace("'", "\\'", $value);
		}
		$exp .= "($field='$value')";
	}
	return $exp;
}

function like_clause($exp, $field, $value, $conj="&&")
{
	if ($value && $field && $value != "" && $field != "" && $value != "*") {
		if ($exp) {
			$exp .= " $conj ";
		}
		// look for unquoted apostrophes, though this isnt quite exhaustive
		if (!strstr($value,"\\'")) {
			$value = str_replace("'", "\\'", $value);
		}
		$exp .= "($field like '$value')";
	}
	return $exp;
}

function findinset_clause($exp, $field, $value, $conj="&&")
{
	if ($value && $field && $value != "" && $field != "" && $value != "*") {
		if ($exp) {
			$exp .= " $conj ";
		}
		// look for unquoted apostrophes, though this isnt quite exhaustive
		if (!strstr($value,"\\'")) {
			$value = str_replace("'", "\\'", $value);
		}
		$exp .= "find_in_set('$value', $field)";
	}
	return $exp;
}

function get_database($database, $host, $user, $password)
{
	$mysql = @mysql_connect($host, $user, $password);
	if ($mysql > 0) {
		zotmsg("successful! mysql = $mysql");
		if (@mysql_select_db($database, $mysql)) {
			zotmsg("successful! selected database '$database' err=".mysql_errno());
		} else {
//			zoterr("failed to select database '$database': " .  mysql_errno());
//			mysql_close($mysql);
			return -mysql_errno();
		}
	} else {
		zotmsg("failed to connect to mysql on $host, user $user/$password: " . mysql_errno());
		return -mysql_errno();
	}

	return $mysql;
}

function find_objects_from_result($result, $field, $str_values)
{
	$objects = array();
	$nitems = mysql_num_rows($result);
	if ($nitems > 0) {
		mysql_data_seek($result, 0);
		for ($i=0; $i<$nitems; $i++) {
			$row = mysql_fetch_object($result);
			$fv = $row->$field;
			if (index_of("$fv", $str_values) >= 0) {
				$objects[] = $row;
			}
		}
	}
	return $objects;
}


function find_objects_from_objects($objects, $field, $str_values)
{
	$newobjects = array();
	for ($i=0; $i<count($objects); $i++) {
		$row = $objects[$i];
		$fv = $row->$field;
		if (index_of("$fv", $str_values) >= 0) {
			$newobjects[] = $row;
		}
	}
	return $newobjects;
}

function field_array_from_objects($objects, $field)
{
	$field_values = array();
	for ($i=0; $i<count($objects); $i++) {
		$field_values[] = $objects[$i]->$field;
	}
	return $field_values;
}


function quothi($desc)
{
	if (!isset($desc) || $desc == "")
		return "";
	$desc = ereg_replace("[\n\r]+", "\\n", $desc);
	$desc = ereg_replace("'", "\'", $desc);
	$desc = ereg_replace("\"", "\\\"", $desc);
	return $desc;
}

?>
