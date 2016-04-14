<?php

//$upload_base = "uploaded";
$upload_base = "../destrpc/uploaded";
$default_image = "images/img-dflt.gif";
$default_thumb_image = "images/img-dflt.gif";

$schema_base_directory = "templates";
$schema_idx_name = "schema.ser";

$g_page_len = 0;	// number of entries in a page of results, 0 turns off paging
$d_page_len = 0;

// used by dynamic pages not generated off the sqlschema_edit_framework setup
// needs to be extended whenever a new document type/table is added, or the
// base tag is changed in the xml schema file....
function upload_base($k)
{
	global $upload_base;
	switch ($k) {
		case "book":			$upbase = "$upload_base/bk"; break;
		case "conference":	$upbase = "$upload_base/cnf"; break;
		case "chapter":		$upbase = "$upload_base/ch"; break;
		case "journal":		$upbase = "$upload_base/jrn"; break;
		default:					$upbase = "$upload_base/dfl"; break;
	}
	return $upbase;
}



function verify_user($mysql, $usr_u, $usr_p_enc, &$usr_row)
{
	$user_authenticated = false;
	if ($mysql > 0 && $usr_u && $usr_p_enc) {
		$query = "select * from people where name='$usr_u'";
		$result = mysql_query($query);
		if ($result > 0) {
			$nitems = mysql_num_rows($result);
			if ($nitems > 0) {
				$row = mysql_fetch_object($result);
				if ($row->passwd == $usr_p_enc) {
					$user_authenticated = $row->kind;
					$usr_row = $row; 
				}
			}
		}
	}
	return $user_authenticated;
}


?>
