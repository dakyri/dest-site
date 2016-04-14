<?php

function dump_db_item($db_file, $row, $field)
{
	if ($field && $field != "") {
		$x = str_replace("\n", "\\n", $row->$field);
		fwrite($db_file, "$field:".$x."\n");
	}
}

function match_db_item($dbline, $field)
{
	if (ereg("^$field:", $dbline)) {
		return substr($dbline, strlen($field)+1);
	}
	return "";
}

function image_display_select($image_name, $display_name, $image_alt_label, $image_align, $image_dir, $image_list, $default_image)
{
?>
    <IMG NAME="<?php echo $display_name; ?>"
    	SRC="<?php echo $default_image; ?>"
    	BORDER="1" HSPACE="1" ALT="<?php echo $image_alt_label; ?>" ALIGN="<?php echo $image_align; ?>">
    	<BR CLEAR="<?php echo $image_align; ?>">
    <SELECT
		onChange="<?php echo $display_name; ?>.src=(options[selectedIndex].value != '')?<?php if ($image_dir) echo "'$image_dir/'"; ?>+options[selectedIndex].value:'<?php echo $default_image; ?>';"
		NAME="<?php echo $image_name; ?>" SIZE=1>
<?php
	for ($i=0; $i<sizeof($image_list); $i++) {
		echo "<OPTION value=$image_list[$i]>" . "$image_list[$i]\n";
	}
?>
	<OPTION value='' SELECTED>none
    </SELECT>
<?php
}

function image_display_text($image_name, $display_name, $image_alt_label, $image_align, $image_name_length, $default_image)
{
?><IMG NAME="<?php echo $display_name; ?>"../"." SRC="<?php echo $default_image; ?>"
	 BORDER="1" HSPACE="1" ALT="<?php echo $image_alt_label; ?>" ALIGN="<?php echo $image_align; ?>">
    <BR CLEAR="<?php echo $image_align; ?>">
    <INPUT TYPE="TEXT"
		onChange="<?php echo $display_name; ?>.src=(value != ''?value:'<?php echo $default_image; ?>');"
		NAME="<?php echo $image_name; ?>" SIZE="<?php echo $image_name_length; ?>">
<?php
}

function paginator(
		$table, $link,
		$where, $order,
		$pagination_field,
		$pagelen, $pageno,
		$fetchpage, $pagevar,
		$tabstyle, $selstyle,
		$title,
		$target="",
		$additional_href_vars=""
	)
{
	if ($pagelen > 0) {
		if (!$pageno || $pageno < 0) {
			$pageno = 0;
		}
		$query = "select $pagination_field from $table";
		if ($where) {
			$query .= " where $where";
		}
		if ($order) {
			$query .= " order by $order";
		}
		$res = mysql_query($query, $link);
		if ($res) {
			$nrows = mysql_num_rows($res);
			$npage = (int)(($nrows + ($pagelen-1))/$pagelen);
			if ($npage > 1) {
				table_header(0,2,$tabstyle, "");
				table_row();
				echo "<TD><B>$title</B></TD>\n";
				for ($i=1; $i<=$npage; $i++) {
					mysql_data_seek($res, ($i-1)*$pagelen);
					$row = mysql_fetch_object($res);
					if ($i==$pageno)
						echo "<td class=\"$selstyle\">";
					else
						echo "<td>";
					echo "<A HREF='$fetchpage?$pagevar=$i";
					if ($additional_href_vars) {
						echo "&$additional_href_vars";
					}
					echo "'";
					if ($target) {
						echo " TARGET='$target'";
					}
					echo ">", $i,"</A></TD>\n";
				}
				table_rend();
				table_tail();
				mysql_free_result($res);
			}
		} else {
			return false;
		}
	}
	return true;
}

function createtable_form($form_name, $form_action, $kind, $action_value, $submit_action="edAction", $submit_class="", $preamble_txt="")
{
	if (!$preamble_txt) {
?>
 <P>
 <B>Rebuild the <?php echo " $kind "; ?> table:</B><BR>
 <B>This action will lose data from a current table.</B><BR>
 <B>Do this only if you are sure of what you're doing.<BR>
<?php
	} else {
		echo $preamble_txt;
	}
?>
 <FORM 
<?php	echo "ACTION='$form_action' NAME='$form_name' "; ?>
	onSubmit= 'return true;' METHOD=POST>
 <TABLE CELLPADDING=2 CELLSPACING=0>
  <TR>
   <TD>
	<B><?php
	submit_input($submit_action, $action_value, "", $submit_class); ?></B>
   </TD>
  </TABLE>
 </FORM>
<?php
}


function changeproperty_form($form_name, $form_action, $table_name, $which, $vals, $form_head_class="thinfluoro", $text="")
{
?>
 <FORM
<?php	echo " ACTION='$form_action' NAME='$form_name'" ?>
	onSubmit= 'return true;' METHOD=POST>
<H2 class="<?php echo $form_head_class; ?>">Delete an existing
<?php echo " $table_name $which, "; ?>
or add a new one.</H2>
<?php echo "<p>$text</p>\n"; ?>
<TABLE CELLPADDING=2 CELLSPACING=0 align="center">
 <TR>
  <TD>
   <B>Delete a field value</B><BR>
<?php
	echo "<SELECT MULTIPLE NAME=del_property[] SIZE=".sizeof($vals).">\n";
	for ($i=0; $i<sizeof($vals); $i++) {
		echo "<OPTION VALUE=$vals[$i]>$vals[$i]\n";
	}
	echo "</SELECT>";
?>
   </TD><TD>
    <B>Add A field value</B> (may be a space sparated list)<BR>
    <INPUT TYPE=TEXT NAME='add_property' VALUE='' MAXWIDTH=256 SIZE='20,1' >
   </TD>
  <TR>
   <TD colspan=2 align="center"><div class="edit-controls">
<?php	echo "<B><INPUT TYPE=SUBMIT NAME='edAction' VALUE='Modify $which'></B>\n"; ?>
   </TD></div>
  </TABLE>
 </FORM>
<?php
}

function upload_form($form_name, $form_action, $hidden_value,
			$data_file_kind="a local text file (mysql format)",
			$submit_name="edAction", $submit_value="Upload", $form_head_class="thinfluoro",
			$upload_head="", $upload_name="upload", $hidden_name="datapage_name", $comments="")
{
?>
<P>
<H2 class="<?php echo $form_head_class; ?>"><?php
if ($upload_head) {
	echo $upload_head;
} else {
	echo "Upload $datapage_name data from $data_file_kind;";
}
?></H2>
<?php
if ($comments) {
	echo "<p>", $comments, "</p>";
}
?>
<FORM
<?php  echo " ACTION=$form_action NAME=$form_name\n"; ?>
   onSubmit= "if (upload.value == '') { alert('You need a valid file selected to perform this operation'); return false;} else  return true;"
    ENCTYPE='multipart/form-data' METHOD=POST>
 <TABLE WIDTH=100%>
  <TR>
   <TD>
    <TABLE CELLSPACING=0 CELLPADDING=2><TR VALIGN=middle>
     <TD VALIGN=middle>
      <?php
      	hidden_field($hidden_name, $hidden_value);
      	hidden_field("MAX_FILE_SIZE", "1000000");
      ?>
      <INPUT TYPE=FILE NAME="<?php echo $upload_name; ?>" VALUE="" SIZE="50,1"
	onChange = "document.<?php echo $form_name; ?>.upload_as.value = leaf(upload.value);"
	onBlur = "document.<?php echo $form_name; ?>.upload_as.value = leaf(upload.value);"
	>
    </TABLE>
  <TR>
   <TD>
   	<center><div class="edit-controls">
    <TABLE CELLSPACING=0 CELLPADDING=2><TR>
     <TD VALIGN=middle ALIGN=center>
<?php
        echo "<B><INPUT TYPE=SUBMIT NAME='$submit_name' VALUE='$submit_value' SIZE=2></B>";
?>
     </TD>
    </TABLE></div></center>
 </TABLE>
 </FORM>
<?php
}



function download_form($form_name, $form_action, $datapage_name,
			$data_file_kind="a local text file (mysql format)",
			$submit_name="edAction", $submit_value="Download", $form_head_class="thinfluoro")
{
?>
<H2 class="<?php echo $form_head_class; ?>">Download <?php echo "$datapage_name "; ?>
data to a text file</H2>
<FORM 
<?php	echo " ACTION=$form_action NAME=$form_name\n"; ?>
 onSubmit= "return true;"
 METHOD=POST>
      <INPUT TYPE=hidden name="datapage_name" value="<?php echo $datapage_name; ?>">
<center>
<div class="edit-controls">
<?php
        echo "<B><INPUT TYPE=SUBMIT NAME='$submit_name' VALUE='$submit_value' SIZE=2></B>";
?>
</div>
  </center>
 </FORM>
<?php
}

function drop_table_form($form_name, $form_action, $datapage_name,
			$submit_name="edAction", $submit_value="Download", $form_head_class="thinfluoro")
{
?>
<H2 class="<?php echo $form_head_class; ?>">Drop <?php echo "$datapage_name "; ?>
data from the site database</H2>
<FORM 
<?php	echo " ACTION=$form_action NAME=$form_name\n"; ?>
 onSubmit= "return confirm('are you sure you want to do this?');"
 METHOD=POST>
      <INPUT TYPE=hidden name="datapage_name" value="<?php echo $datapage_name; ?>">
<center>
<div class="edit-controls">
<?php
        echo "<B><INPUT TYPE=SUBMIT NAME='$submit_name' VALUE='$submit_value' SIZE=2></B>";
?>
	</div>
  </center>
 </FORM>
<?php
}



function modify_enumerated($table_nm, $column_nm,
	$enum_type, $value_list, $add_property, $del_property, $mysql=NULL)
{
	if ($add_property) {
		$newp = split("[, ]+", $add_property);
	} else {
		$newp = array();
	}
	for ($i = 0; $i<sizeof($value_list); $i++) {
		if (index_of($value_list[$i], $del_property) < 0) {
			$newp[] = $value_list[$i];
		}
	}
	if (sizeof($newp) > 0) {
		$query = "alter table $table_nm modify $column_nm $enum_type(".
				typelist_string($newp).")";
//		errorpage($query);
		if ($mysql) {
			$download_result = mysql_query($query, $mysql);
		} else {
			$download_result = mysql_query($query);
		}
		if (!$download_result) {
			errorpage("Query failed: ".mysql_error());
		}
	}
	return fetch_type_values($table_nm, $column_nm);
}

function ftp_file($dbtxtname, $ftp_u, $ftp_p, $ftp_h, $ftp_nm)
{
	echo "loading..<BR>";
	if (!($remotefile = fopen("ftp://$ftp_u:$ftp_p@$ftp_h/$ftp_nm", "w"))) {
		errorpage("Can't open remote file for output.<BR>".
			"Either the host, name, path or password are wrong,<BR>".
			"or the remote file name already exists");
	}
	if (!($dbtxtfile = fopen($dbtxtname, "r"))) {
		errorpage("Can't open temporary file for input.<BR>".
		   "Something very messy is going on and you should scream for help");
	}
	while (($str = fread($dbtxtfile, 1024)) != '') {
		echo "chunk<BR>";
		fwrite($remotefile, $str, strlen($str));
	}
	fclose($remotefile);
	echo "ftp://$ftp_u:$ftp_p@$ftp_h/$ftp_nm";
	chmod("ftp://$ftp_u:$ftp_p@$ftp_h/$ftp_nm", 0644);
}

?>