<?php
	import_request_variables("gpc");
	error_reporting(3);
	require_once("common/local.php");
	require_once("common/common_mysql.php");
	require_once("common/common_funk.php");

	$xml_response = true;
	function fatal_error($str)
	{
		global	$xml_response;
		if ($xml_response) {
			echo "<completion><error>",htmlspecialchars($str),"</error></completion>";
		}
		exit;
	}
	
	if ($xml_response) {
		header('Content-Type: text/xml');
		echo "<?xml version = '1.0' standalone='yes'?>\n";
		echo "<!DOCTYPE completion []>\n";
	} else {
		header('Content-Type: text/plain');
	}

	if (!$table) {
		fatal_error("missing table specifier");
	}
	if (!$match_field) {
		fatal_error("missing field specifier");
	}
	$mysql = get_database(
					$database_name,
					$database_host,
					$database_pleb_user,
					$database_pleb_passwd);
	if ($mysql < 0) {
		fatal_error(mysql_error());
	}
	
	$completion_query = "select ";
	if (is_array($fetch_field)) {
		reset($fetch_field);
		list($k,$v) = each($fetch_field);
		$completion_query .= $v;
		while (list($k,$v)=each($fetch_field)) {
			$completion_query .= ",$v";
		}
	} else {
		$completion_query .= $fetch_field;
	}
	$completion_query .= " from $table";
		// ?????????? need to add slashes elsewhere rather than have them gratis here
		// via magic quotes ... oops!!! fix later !!!! 
	$where = stripslashes($where_extra);
	$label_expr = stripslashes($label_expr);
	if ($match_field && $match_text) {
		$match_text_a = split('[ ,.]', $match_text);
		reset($match_text_a);
		while (list($a_k, $match_str) = each($match_text_a)) {
			if ($match_str) {
				if ($where) {
					$where .= " && ";
				}
				if (is_array($match_field)) {
					reset($match_field);
					$fwhere = "";
					while (list($k,$v)=each($match_field)) {
						if ($fwhere) {
								$fwhere .= "|| ";
						}
						$fwhere .= "$v like '%$match_str%'";
					}
					$where .=  "($fwhere)";
				} else {
					$where .= "$match_field like '%$match_str%'";
				}
			}
		}
	}
	if ($where) {
		$completion_query .= " where $where";
	}
	$result = mysql_query($completion_query);
	if ($result <= 0) {
		fatal_error('mysql error: '.mysql_error());
	}
	$nitems = mysql_num_rows($result);
	if ($xml_response) {
		echo "<completion>";
	}
	if ($nitems > 0) {
		for ($i=0; $i<$nitems; $i++) {
			if ($xml_response) {
				echo "<item>\n";
			}
			$row = mysql_fetch_object($result);
			if ($label_expr) {
				$label = eval("return $label_expr;");
				if ($label) {
					$label = htmlspecialchars(str_replace(" ", "&nbsp;", $label));
					if ($xml_response) {
						echo "<label>",$label,"</label>\n";
					}
				}
			}
			$row_arr = get_object_vars($row);
			reset($row_arr);
			while(list($k,$v)=each($row_arr)) {
				if ($xml_response) {
					echo "<value name='$k'>",htmlspecialchars($v),"</value>\n";
				}
			}
			if ($xml_response) {
				echo "</item>\n";
			}
		}
	} else {
		echo "<error>No matching items found</error>\n";
	}
	if ($xml_response) {
		echo "</completion>\n";
	}
?>