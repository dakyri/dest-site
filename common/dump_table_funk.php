<?php

function dump_table($mysql, $db_file, $table, $order, $fields)
{
	$query = "select * from $table";
	if ($order) {
		$query .= " order by " . $order;
	}

	$result = mysql_query($query, $mysql);
	if ($result == 0) {
		errorpage(mysql_error());
	} else {
		$nitems = mysql_num_rows($result);
	}
	$n_fields = count($fields);
	for($i=0; $i < $nitems; $i++) {
		$row = mysql_fetch_object($result);
		for ($j=0; $j<$n_fields; $j++) {
			dump_db_item($db_file, $row, $fields[$j]);
		}
		fwrite($db_file, "\n");
	}
	return true;
}

function dump_db_table($mysql, $db_file, $table_name, $order_str)
{
	$fields = array();
	$query = "select * from $table_name";
	$result = mysql_query($query, $mysql);
	$i = 0;
	while ($i < mysql_num_fields($result)) {
	    $meta = mysql_fetch_field($result);
	    if ($meta) {
				$fields[] = $meta->name;
//blob:         $meta->blob
//max_length:   $meta->max_length
//multiple_key: $meta->multiple_key
//name:         $meta->name
//not_null:     $meta->not_null
//numeric:      $meta->numeric
//primary_key:  $meta->primary_key
//table:        $meta->table
//type:         $meta->type
//unique_key:   $meta->unique_key
//unsigned:     $meta->unsigned
//zerofill:     $meta->zerofill";
	    }
	    $i++;
	}

	dump_table($mysql, $db_file, $table_name, $order_str, $fields);
	return true;
}
?>