<?php 
function upload_table($mysql, $upload, $table, $fields)
{
	global	$action_msg;
	
	$dbinfo = file($upload);
	$i = 0;
	$n_fields = count($fields);
	for ($j=0; $j<$n_fields; $j++) {
		$$fields[$j] = "";
	}
	$up_cnt = 0;
	while ($i < sizeof($dbinfo)) {
		$dbline = $dbinfo[$i];
		$i++;
		$dbline = str_replace("'", "\'", $dbline);
		$dbline = str_replace("\n", "", $dbline);
		if (strlen($dbline) == 0 || $dbline[0] == " " || $i == sizeof($dbinfo)) {
			$up_cnt++;
			$set = "";
			for ($j=0; $j<$n_fields; $j++) {
				$set=set_item($set, $fields[$j], $$fields[$j]);
			}
			
			if ($set) {
				$query = "insert into $table set $set";
				$insert_result = mysql_query($query, $mysql);
				
				if (!$insert_result) {
					$err = mysql_errno();
					if ($err = MYSQL_ER_DUP_ENTRY) {
						$action_msg .= "Duplicate key on entry $up_cnt: ignored<br>";
					} else {
						errorpage("mysql error on insert: ".mysql_error());
					}
				}
			}
			for ($j=0; $j<$n_fields; $j++) {
				$$fields[$j] = "";
			}
		} else {
			for ($j=0; $j<$n_fields; $j++) {
				if ($x = match_db_item($dbline, $fields[$j])) {
					$$fields[$j] = $x;
					break;
				}
			}
		}
	}
	return true;
}


function upload_db_table($mysql, $upload, $table_name, $mvf=NULL)
{
	if ($mvf == NULL) 
		$mvf = "../temp/updb";
	if (!move_uploaded_file($upload, $mvf)) {
		errorpage("Move uploaded from '$upload' to '$mvf' is a nono");
	}

	$fields = array();
	$query = "select * from $table_name";
	$result = mysql_query($query, $mysql);
	$i = 0;
	while ($i < mysql_num_fields($result)) {
	    $meta = mysql_fetch_field($result);
	    if ($meta) {
//	        echo "No information available<br />\n";
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

	
	$ret = upload_table($mysql, $mvf/*$upload*/, $table_name, $fields);
	return $ret;
}

?>