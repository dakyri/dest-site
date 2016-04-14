<?php
// original handcoded page...
// debate slotting into same framework as other databases, but there's a fair whack of necessarily
// adhoc stuff, and it provides security/options so mucking with it is messy ????
	error_reporting(3);
	import_request_variables("gpc");
	require("../common/necessary.php");
	require(in_parent_path("/common/adminlib.php"));
	require(in_parent_path("/common/sqlschema_types.php"));

function edit_controls()
{
	global	$login_type;
	global	$return_rpc_url;
?>
 <center><div class="edit-controls">
 <TABLE ALIGN="center" CELLPADDING=2 CELLSPACING=0>
  <TR>
<?php if ($login_type != LOGIN_USER): ?>
   <TD><B><INPUT TYPE='SUBMIT' NAME="edAction" VALUE="Delete items"></B></td>
   <TD><B><INPUT
   	TYPE='SUBMIT' NAME="edAction" VALUE="Modify selected"
   	ID='modBut' DISABLED='true'></B></td>
   <TD><B><INPUT TYPE='SUBMIT' NAME="edAction" VALUE="Insert item"></B></td>
<?php else: ?>
   <TD><B><INPUT TYPE='SUBMIT' NAME="edAction" VALUE="Submit changes"></b></td>
<?php endif; ?>
<?php if ($login_type != LOGIN_USER): ?>
   <TD><B><INPUT
   	TYPE='RESET' NAME="edAction" VALUE="Clear fields" ID='clearBut'
   	onClick="disable_modify(true);"></b></td>
<?php else: ?>
<?php endif;?>
   <TD><B><input type="button" name="edAction" value="Return to menu"
     onclick="window.location='<?php
     	echo ($login_type >= LOGIN_ADMIN)?"index.php":
     			($return_rpc_url?"../$return_rpc_url":"../index.php"); ?>';"></b></td>
  </TABLE>
  </div></center>
<?php
}
	$schema_types = uncache_variable(in_parent_path("$schema_base_directory/journal_types.ser"));
	$rsc_type = &$schema_types["rsc-type"];

	$action_msg = "";
	$login_user_row = NULL;
	$login_type = LOGIN_NONE;
	if ($login_u && $login_p) {
		$mysql = get_database($database_name, $database_host, $login_u, $login_p);
		if ($mysql <= 0) {
			$mysql_errno = -$mysql;
			if ($mysql_errno == MYSQL_ER_ACCESS_DENIED) {
				$mysql = get_database(
						$database_name,
						$database_host,
						$database_pleb_user,
						$database_pleb_passwd);
				if ($mysql > 0) {
					$query = "select * from people where stnumber='$login_u'";
					$result = mysql_query($query);
					if ($result > 0) {
						$nitems = mysql_num_rows($result);
						if ($nitems > 0) {
							$row = mysql_fetch_object($result);
							if ($row->passwd == md5($login_p)) { // cookies should be set then
								require("../private/local.php");
								$login_user_row = $row;
								if ($row->kind == "admin") {
									$login_type = LOGIN_ADMIN;
								} else {
									$login_type = LOGIN_USER;
								}
								mysql_close($mysql);
								$mysql = get_database(
												$database_name,
												$database_host,
												$database_mod_user,
												$database_mod_passwd);
								if (!$mysql) {
									errorpage("Database error. ".mysql_error());
								}
							} else {
								errorpage("Not permitted. Password for $login_u is incorrect");
							}
						} else {
							errorpage("Not permitted. User $login_u doesn't exist in this system");
						}
						mysql_free_result($result);
					} else {
						errorpage("Not permitted. User $login_u doesn't exist in this system");
					}
				} else {
					errorpage("System not accessible: database error:<br>".mysql_error());
				}
			} else {
				errorpage("Not permitted. Please log on with the correct username and password!");
			}
		} else { // database login to mysql
			$login_type = LOGIN_DBADMIN;
		}
	} else {
		header("Location: logon.php?login_destination=ed_people.php");
		exit();
	}
	
	if ($login_type == LOGIN_USER) {
		if ($login_user_row) {
			$key = $login_user_row->code;
		} else {
			if ($return_url) {
				header("Location: ".urldecode($return_url));
			} else {
				header("Location: ../index.php");
			}
			exit();
		}
	}
	
	$kind_values = fetch_type_values("people", "kind");
	$gender_values = fetch_type_values("people", "gender");
	$properties_values = fetch_type_values("people", "properties");

	if (isset($edAction)) {
		if ($edAction == "Create people") {
			$query = "create table people (".
					"code int not null auto_increment primary key,".
					"stnumber tinytext,".
					"passwd tinytext,".
					"surname tinytext,".
					"firstname tinytext,".
					"title tinytext,".
					"school tinytext,".
					"kind enum('staff','student','admin'),".
					"gender enum('M','F'),".
					"properties set('author','mmtc'),".
					"location tinytext,".
					"address text,".
					"city tinytext,".
					"postcode tinytext,".
					"phone tinytext,".
					"mobile tinytext,".
					"fax tinytext,".
					"web tinytext,".
					"email tinytext,".
					"description text)";
			$create_result = mysql_query($query);
			if (!$create_result) {
				errorpage("create error ".mysql_error());
			}
			$kind_values = fetch_type_values("people", "kind");
			$gender_values = fetch_type_values("people", "gender");
			$properties_values = fetch_type_values("people", "properties");
			header("Location: ed_people.php");
			exit;
		} elseif ($edAction == "Delete items" && sizeof($del) > 0) {
			$query = "delete from people where code in (".list_string($del).")";
			$delete_result = mysql_query($query);
			if (!$delete_result) {
				errorpage(mysql_error());
			}
			header("Location: ed_people.php?selected=$selected");
			exit;
		} elseif ($edAction=="Modify selected" || $edAction=="Submit changes") {
			$set = "";
			if ($stnumber) $set = set_item($set, "stnumber", strtolower($stnumber));
			$set = set_item($set, "surname", $surname);
			$set = set_item($set, "firstname", $firstname);
			$set = set_item($set, "title", $title);
			if (isset($passwd)) {
				if ($passwd != "") {
					$set = set_item($set, "passwd", md5($passwd));
				}
			} else if (isset($enc_passwd)) {
				if ($enc_passwd != "") {
					$set = set_item($set, "passwd", $enc_passwd);
				}
			}
			if ($kind) $set = set_item($set, "kind", $kind);
			$set = set_item($set, "school", $school);
			$set = set_item($set, "gender", $gender);
			$set = set_item($set, "location", $location);
			$set = set_item($set, "address", $address);
			$set = set_item($set, "city", $city);
			$set = set_item($set, "postcode", $postcode);
			$set = set_item($set, "phone", $phone);
			$set = set_item($set, "mobile", $mobile);
			$set = set_item($set, "fax", $fax);
			$set = set_item($set, "web", $web);
			$set = set_item($set, "email", $email);
			$set = set_item($set, "properties", list_string($property));
			$set = set_item($set, "description", $description);
			$where = "code = $key";
			if ($set && $where) {
				$query = "update people set $set where $where";
//				echo "modify = $query<BR>";
				$update_result = mysql_query($query);
				if (!$update_result) {
					errorpage("Update error ".mysql_error());
				}
			}
			header("Location: ed_people.php?selected=$selected");
			exit;
		} elseif ($edAction == "Insert item") {
			$set = "";
			if ($stnumber) $set = set_item($set, "stnumber", strtolower($stnumber));
			$set = set_item($set, "surname", $surname);
			$set = set_item($set, "firstname", $firstname);
			$set = set_item($set, "title", $title);
			if (isset($passwd)) {
				if ($passwd != "") {
					$set = set_item($set, "passwd", md5($passwd));
				}
			} else if (isset($enc_passwd)) {
				if ($enc_passwd != "") {
					$set = set_item($set, "passwd", $enc_passwd);
				}
			}
			if ($kind) $set = set_item($set, "kind", $kind);
			$set = set_item($set, "school", $school);
			$set = set_item($set, "gender", $gender);
			$set = set_item($set, "location", $location);
			$set = set_item($set, "address", $address);
			$set = set_item($set, "city", $city);
			$set = set_item($set, "postcode", $postcode);
			$set = set_item($set, "phone", $phone);
			$set = set_item($set, "mobile", $mobile);
			$set = set_item($set, "fax", $fax);
			$set = set_item($set, "web", $web);
			$set = set_item($set, "email", $email);
			$set = set_item($set, "properties", list_string($property));
			$set = set_item($set, "description", $description);
			$where = "code = $key";
			if ($set) {
				$query = "insert into people set $set";
//				echo "insert = $query<BR>";
				$insert_result = mysql_query($query);
				if (!$insert_result) {
					errorpage(mysql_error());
				}
			}
			header("Location: ed_people.php?selected=$selected");
			exit;
		} elseif ($edAction == "Modify kind") {
			$kind_values = modify_enumerated(
					"people", "kind", "enum",
					$kind_values, $add_property, $del_property);
			header("Location: ed_people.php?selected=$selected");
			exit;
		} elseif ($edAction == "Modify property") {
			$properties_values = modify_enumerated(
					"people", "properties", "set",
					$properties_values, $add_property, $del_property);
		}
		header("Location: ed_people.php?selected=$selected");
		exit;
	}
	standard_page_top($login_user_code?"Edit personal details":"Edit The People List", "../style/default.css", "page-noframe",
					$login_user_code?"../images/title/edit_personal.gif":"../images/title/edit_people.gif", 560, 72,
					$login_user_code?"Edit Personal Details":"Edit the People List",
					in_parent_path("common/necessary.js"));
	if ($action_msg)
		echo $action_msg;

	if ($login_type == LOGIN_USER && $login_user_row) {
		$query = "select * from people where code='$login_user_row->code'";
	} else {
		$query = "select * from people";
		$order = "kind,surname,firstname";
		if ($order) {
			$query .= " order by " . $order;
		}
		if ($g_page_len > 0) {
			if (! $pageno || $pageno < 1)
				$pageno = 1;
			$offset = $g_page_len * ($pageno-1);
			$query .= " limit $offset, $g_page_len ";
		}
	}


	$result = mysql_query($query);
	if ($result == 0) {
		if (mysql_errno() == MYSQL_ER_NO_SUCH_TABLE) {
			createtable_form("createPeople", "ed_people.php", "user list", "Create people", "edAction", "", "");
			page_bottom_menu();
			standard_page_bottom();
			exit;
		} else {
			echo "<br>Database Error: ",mysql_error(), "<br>";
			$nitems = 0;
		}
	} else {
		$nitems = mysql_num_rows($result);
	}
?>

<SCRIPT LANGUAGE="javascript">
function disable_modify(val)
{
	mb = document.getElementsByName('edAction');
	if (mb) {
		for (i=0; i<mb.length; i++) {
			bt = mb.item(i);
			if (bt.value == 'Modify selected') {
				bt.disabled = val;
			}
		}
	}
}

function display_selected(sel)
{
	if (sel >= catalog_data.length) {
		sel = catalog_data.length-1;
	}
	if (sel < 0) {
		sel = 0;
	}
	
	disable_modify(false);
	document.edPeopleForm.key.value = catalog_data[sel][0];

<?php if ($login_type != LOGIN_USER): ?>
	document.edPeopleForm.stnumber.value = catalog_data[sel][1];
<?php endif; ?>
	document.edPeopleForm.enc_passwd.value = catalog_data[sel][2];
	document.edPeopleForm.firstname.value = catalog_data[sel][3];
	document.edPeopleForm.surname.value = catalog_data[sel][4];
	document.edPeopleForm.title.value = catalog_data[sel][5];
<?php if ($login_type != LOGIN_USER): ?>
	document.edPeopleForm.kind.selectedIndex = catalog_data[sel][6];
//	if (document.edPeopleForm.kind.value == 'student') {
//		show_spans('student-specific', true);
//	} else {
//		show_spans('student-specific', false);
//	}
<?php endif; ?>
	document.edPeopleForm.gender.selectedIndex = catalog_data[sel][7];
// properties 3
<?php if ($login_type != LOGIN_USER): ?>
	typz = catalog_data[sel][8].split(",");
	obj = document.edPeopleForm;
	for (i=0; i<obj.elements.length; i++) {
		if (obj.elements[i].name == "property[]") {
			obj.elements[i].checked = false;
			for (j=0; j<typz.length; j++) {
				if (obj.elements[i].value == typz[j]) {
					obj.elements[i].checked = true;
				}
			}
		}
	}
<?php endif; ?>
	document.edPeopleForm.location.value = catalog_data[sel][9];
	document.edPeopleForm.address.value = catalog_data[sel][10];
	document.edPeopleForm.phone.value = catalog_data[sel][11];
	document.edPeopleForm.fax.value = catalog_data[sel][12];
	document.edPeopleForm.mobile.value = catalog_data[sel][13];
	document.edPeopleForm.email.value = catalog_data[sel][14];
	document.edPeopleForm.web.value = catalog_data[sel][15];
	document.edPeopleForm.city.value = catalog_data[sel][16];
	document.edPeopleForm.postcode.value = catalog_data[sel][17];

	document.edPeopleForm.description.value = catalog_data[sel][18];
	document.edPeopleForm.school.value = catalog_data[sel][19];
}

// reflect the database...
// obviously, for big db's, we'll only reflect the current window's worth
catalog_data = [
<?php
	if ($nitems > 0)
		mysql_data_seek($result, 0);
	for($i=0; $i < $nitems; $i++) {
		$row = mysql_fetch_object($result);
		echo "['$row->code',",
			"'",quothi($row->stnumber),"',",
			"'",quothi($row->passwd),"',",
			"'",quothi($row->firstname),"',",
			"'",quothi($row->surname),"',",
			"'",quothi($row->title),"',",
			index_of($row->kind, $kind_values),",",
			index_of($row->gender, $gender_values),",",
			"'$row->properties',",
			"'",quothi($row->location),"',",
			"'",quothi($row->address),"',",
			" '$row->phone', '$row->fax', '$row->mobile',",
			" '$row->email', '$row->web',",
			" '$row->city', '$row->postcode',",
			"'", quothi($row->description),"',",
			"'", quothi($row->school),"'",
			" ]";
			if ($i < $nitems-1) {
				echo ",\n";
			}
	}
?>
];
window.focus();

</SCRIPT>

<?php
	if ($login_type != LOGIN_USER) {
		paginator(
				"people",
				$mysql,
				"",
				$order,
				"code",
				$g_page_len, $pageno,
				"ed_people.php", "pageno",
				"paginator","paginator-pgsel",
				"Result Page",
				"",
				"");
	}
?>
<FORM ACTION="ed_people.php" NAME="edPeopleForm" METHOD="POST">
<?php
	edit_controls();
?>
<?php if ($login_type != LOGIN_USER): ?>
<table cellpadding="0" cellspacing="0" border="1" align="CENTER" width="90%">
<TR BGCOLOR="#ffffff">
<TH ALIGN="LEFT" class="schema-del"><FONT SIZE="-1">Delete</FONT>
<TH ALIGN="LEFT" class="schema-sel"><FONT SIZE="-1">Select</FONT>
<TH ALIGN="LEFT" WIDTH="15%"><FONT SIZE="-1">Staff/student number</FONT>
<TH ALIGN="LEFT"><FONT SIZE="-1">Name</FONT>
<TH ALIGN="LEFT"><FONT SIZE="-1">kind</FONT>
<TH ALIGN="LEFT"><FONT SIZE="-1">email</FONT>
</TR>
<?php
	if ($nitems > 0)
		mysql_data_seek($result, 0);
	if (!isset($selected)) {
		$selected = 0;
	}
	for($i=0; $i < $nitems; $i++) {
		$row = mysql_fetch_object($result);
		echo "<TR VALIGN='CENTER'>\n";
		if (index_of($row->code, $del) >= 0) {
			$td = "<INPUT TYPE='CHECKBOX' name='del[]' value='$row->code' CHECKED>\n";
		} else {
			$td = "<INPUT TYPE='CHECKBOX' name='del[]' value=$row->code>\n";
		}
		table_data_string($td, "LEFT", 1, "schema-del");
		$td = "<INPUT TYPE='RADIO' name='selected' value='$i' autoComplete='off'";
		if ($i == $selected) {
			 $td .= " CHECKED";
		} else {
		}
		$td .= " onClick=\"display_selected($i);\">\n";
		table_data_string($td, "LEFT", 1, "schema-sel");

  		table_data_string($row->stnumber, "LEFT", 10);
		table_data_string("$row->title $row->firstname $row->surname", "LEFT", 20);
		table_data_string($row->kind, "LEFT", 10);
    	table_data_string($row->email, "LEFT");
		echo "</TR>";
	}
?>
</TABLE><BR>
<?php	endif; ?>
<table>
<tr><td>
 <TABLE><TR>
<?php if ($login_type != LOGIN_USER): ?>
   <TD><B>Staff/Student Number</B><BR><INPUT TYPE=TEXT NAME="stnumber"  MAXLENGTH='48' SIZE='20'></TD>
<? endif; ?>
   <td><B>Title</B><BR><input type="text" name="title" size="5"></td>
   <td><B>First Name(s)</B><BR><input type="text" name="firstname" size="30"></td>
   <td><B>Surname</B><BR><input type="text" name="surname" size="30"></td>
 </TABLE>
<tr><td><B>Password</B><BR>
  The password is an arbitrary length string of printable characters.
  Leave this field blank unless you wish to change it.<BR>
   <input type="password" name="passwd" size="20">
   <input type="hidden" name="enc_passwd" size="20"></td>
<tr><td>
	<table><tr>
	<td><b>School</b><br>
	<?php
		select_array("school", $rsc_type->ValueNameArray(), "", $rsc_type->ValueLabelArray());
	?>
	</td>
   <TD><B>Gender</B><BR><?php	select_array("gender", $gender_values); ?></TD>
<?php if ($login_type != LOGIN_USER): ?>
   <TD><B>Kind</B><BR><?php
   	select_array("kind",
   		$kind_values,
   		NULL,
   		NULL,
   		NULL, // "if (value == 'student') show_spans('student-specific',true); else show_spans('student-specific',false); ",
			"staff");
?></TD>
<?php else: ?>
	<td></td>
<?php endif; ?>
   <td><B>RMIT Location</B><BR><input type="text" name="location" size="30"></td>
   </tr></table>
  <tr>
   <TD ><B>Postal Address</B><BR>
   <TEXTAREA NAME="address"  COLS='50' ROWS='5'></TEXTAREA></TD>
  <tr><td>
   <table><tr>
    <td><B>City</B><BR><input type="text" name="city" size="30"></td>
    <td><B>Postcode</B><BR><input type="text" name="postcode" size="20"></td>
   </tr></table>
<TR>
 <TABLE>
  <TR>
   <TD><B>Phone</B><BR><INPUT TYPE=TEXT NAME="phone"  MAXLENGTH='32' SIZE='16'></TD>
   <TD><B>Fax</B><BR><INPUT TYPE=TEXT NAME="fax"  MAXLENGTH='32' SIZE='16'></TD>
   <TD><B>Mobile</B><BR><INPUT TYPE=TEXT NAME="mobile"  MAXLENGTH='32' SIZE='16'></TD>
  <TR>
   <TD colspan="2"><B>Email</B><BR><INPUT TYPE=TEXT NAME="email"  MAXLENGTH='64' SIZE='32'></TD>
   <TD><B>Web</B><BR><INPUT TYPE=TEXT NAME="web"  MAXLENGTH='64' SIZE='40'></TD>
 </TABLE>
<TR>
<TD>
 <TABLE CELLPADDING=0 CELLSPACING=0>
  <TR>
   <TD WIDTH='60%'><B>Description</B><BR><TEXTAREA NAME="description"  COLS='50' ROWS='5'></TEXTAREA>
<?php if ($login_type != LOGIN_USER):?>
   <TD WIDTH='15%' ALIGN='center' VALIGN='top'><B>Properties</B><BR><?php checkbox_array("property[]", $properties_values); ?>
<?php endif; ?>
 </TABLE>
<TR><TD>
<INPUT TYPE=HIDDEN NAME="key" VALUE="">
</TABLE>
<?php	edit_controls(); ?>
</FORM>
<?php
	if ($login_type != LOGIN_USER) {
		br();br();
		changeproperty_form("changeKindForm", "ed_people.php", "person", "kind", $kind_values, "whitevinyl",
			"This facility allows you to extend the values that the 'kind' field may take in the site user database. This allows the site to be substantially modified without impacting directly on the database behind it. This may be useful if you are adding new web services that use this as a user database.");
		changeproperty_form("changePropForm", "ed_people.php", "person", "property", $properties_values, "whitevinyl",
			"This facility allows you to extend the range of values that the 'property' set field in the site user database may take. This allows the site to be substantially modified without impacting directly on the database behind it. This may be useful if you are adding new web services that use this as a user database.");

	}
	if ($login_type == LOGIN_USER):
?>
<SCRIPT LANGUAGE="javascript">
	display_selected(0);
</SCRIPT>
<?php
	else:
?>
<script language="javascript" src="../../common/jx_complete.js"></script>
<SCRIPT LANGUAGE="javascript">
//	test_completer = new jxComplete(
//		'../completion.php?table=people'+
//			'&fetch_field[]=stnumber&fetch_field[]=surname&fetch_field[]=firstname'+
//			'&match_field[]=stnumber&match_field[]=surname&match_field[]=firstname'+
//			'&where_extra='+escapeURI("kind='staff'"),
//		'match_text',
//		edPeopleForm.supervisor,
//		'stnumber',
//		null,
//		null,
//		null);
	edPeopleForm.selected.value = <?php echo $selected?$selected:0; ?>;
	display_selected(edPeopleForm.selected.value);
</SCRIPT>
<?php
	endif;
	mysql_free_result($result);
	standard_page_bottom();
?>
