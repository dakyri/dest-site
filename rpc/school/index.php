<?php

	import_request_variables("gp");
	require_once("../common/necessary.php");
	error_reporting(0);
	
	$logged_in = false;
	if (!$logout && $sch_p) {
		$mysql = get_database($database_name, $database_host, $sch_u, $sch_p);
		if ($mysql <= 0) {
			$sch_p = NULL;
			$mysql = -$mysql;
			switch ($mysql) {
				case MYSQL_ER_LOCAL_CONNECTION:
				case MYSQL_ER_HOST_CONNECTION: {
					$login_msg = "Can't connect to database server. Try again later, and if problems persist, contact the site administrator";
					break;
				}
				
				case MYSQL_ER_DBACCESS_DENIED:
				case MYSQL_ER_ACCESS_DENIED: {
					$login_msg = "Access denied. Please enter a valid password";
					break;
				}
				
				default: {
					if ($mysql >= MYSQL_ER_CLIENT) {
						$login_msg = "Unexpected Mysql client error '".mysql_error()."': try again later, and if problems persist contact, the site administrator";
					} else {
						$login_msg = "Unexpected Mysql server error '".mysql_error()."': try again later, and if problems persist contact, the site administrator";
					}
					break;
				}
			}
		} else {
			setcookie("sch_u", $sch_u, 0, "/dest/");
			setcookie("sch_p", $sch_p, 0, "/dest/");
			$logged_in = true;
		}
	}
	import_request_variables("c");
	if ($logout) {
		setcookie("sch_u", "", 0, "/dest/");
		setcookie("sch_p", "", 0, "/dest/");
		unset($sch_u);
		unset($sch_p);
		unset($sch_p_e);
	} elseif ($sch_p) {
		$logged_in = true;
	}
	if ($logged_in) {
//		header("Location: schoolview.php");
//		exit();
		standard_page_top("School Administrator's menu", "../style/default.css", "page-noframe",
					"../images/title/database_admin.gif", 560, 72, "School Logon", "../common/necessary.js");
?>
<p>Welcome to the school administrator's view of the DEST Research Publications Database</p>
<ul>
<li><a href="schoolview.php?show_pubs_select=unchecked_only">View/Approve currently unapproved publications</a></li>
<li><a href="schoolview.php?show_pubs_select=checked_only">View/Edit currently approved publications</a></li>
<li><a href="schoolview.php?show_pubs_select=all_pubs">View/Edit all approved and unapproved publications that have passed the primary check</a></li>
<li><a href="schoolview.php?show_pubs_select=all_pubs_regardless">View/Edit all approved and unapproved publications</a></li>
<li><a href="schoolview.php?show_pubs_select=rmit_authors">View/Edit RMIT author details</a></li>
</ul>
<?php
//<li><a href="quantaview.php?authors=%">Track research quanta</a></li>
		standard_page_bottom();
	} else {
		standard_page_top("Administrative Logon", "../style/default.css", "page-noframe",
					"../images/title/database_admin.gif", 560, 72, "School Logon", "../common/necessary.js");
		if (isset($login_msg) && $login_msg != "") {
			echo "<p><b>$login_msg</b></p>";
		} else {
			echo "<p>Welcome to the school administrator's view of the DEST Research Publications Database</p>";
		}
		form_header("index.php", "logonForm")
?>
<TABLE WIDTH=90% CELLSPACING=2 CELLPADDING=0>
<TR><TD>
<FONT SIZE=+0><B>Please enter the school administrators password</B></FONT>
</td><td>
<input type="hidden" name="sch_u" value="<?php echo $dest_school_admin; ?>">
<INPUT type="password" class="form-textin" NAME="sch_p" MAXLENGTH=128 SIZE=16>
</td>
</TABLE>
</FORM>
<?php
		standard_page_bottom();
	}
?>

