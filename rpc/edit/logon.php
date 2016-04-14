<?php

	import_request_variables("gp");
	require_once("../common/necessary.php");
	if (!$login_destination) {
		$login_destination = "user_ed_schema_db.php";
	}
	if (!$cancel_destination) {
		$cancel_destination = "../index.php";
	}
	if ($login_p && $login_u) {
		$mysql = @get_database(
				$database_name,
				$database_host,
				$login_u,
				$login_p);
		if ($mysql < 0) {
			if ($mysql == -MYSQL_ER_ACCESS_DENIED) {
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
							if ($row->passwd == md5($login_p)) {
			// yay... someone's on our team
								setcookie("login_u", $login_u, 0, "/dest/rpc/");
								setcookie("login_p", $login_p, 0, "/dest/rpc/");
								header("Location: $login_destination");
								exit();
							} else {
								$login_msg = "Password for user '$login_u' is incorrect...";
							}
						} else {
							$login_msg = "User '$login_u' does not exist...";
						}
					} else {
						$login_msg = "Can't access user database: ".mysql_error();
					}
				} else {
					$login_msg = "Can't access user database: ".mysql_error();
				}
			} else {	// db admin login attempt failed due to some error
				$login_msg = "Can't access database: ".mysql_error();
			}
		} else {	// successful db admin login
			setcookie("login_u", $login_u, 0, "/dest/rpc/");
			setcookie("login_p", $login_p, 0, "/dest/rpc/");
			header("Location: $login_destination");
			exit();
		}
	} else { // no user pass/combo input
		;
	}
	import_request_variables("c");
	standard_page_top(
			"DEST Research Publication Collection Logon",
			in_parent_path("style/default.css"),
			"page-noframe",
			in_parent_path("images/title/logon.gif"),
			560, 72, "DEST RPC Logon",
			in_parent_path("common/necessary.js"));
	if (isset($login_msg) && $login_msg != "") {
		echo "<p>$login_msg</p>";
	}
?>
<form action="logon.php" method="POST"  name="logonForm" onsubmit="return true;">
<table cellpadding="2" cellspacing="2" border="0" width="90%">
 <TR><td width="50%" align="right">
  <FONT SIZE=+0><B>User Name</B></FONT></td><td>
  <INPUT type="text" class="form-textin" NAME="login_u" MAXLENGTH=128 SIZE=16></td>
 <TR><td align="right">
  <FONT SIZE=+0><B>Password</B></FONT></td><td>
  <INPUT type="password" class="form-textin" NAME="login_p" MAXLENGTH=128 SIZE=16></td>
  <tr ><td colspan="2" align="center">
<?php
	hidden_field("login_destination", $login_destination);
	hidden_field("cancel_destination", $cancel_destination);
	div("edit-controls");
	echo "<table CELLPADDING=2 CELLSPACING=0 align=\"center\">\n", "<tr>\n";
	echo "<td>";submit_input("rpcMode", "Login");	echo "</td>";
	echo "<td>";button_input("rpcMode", "Cancel", "location.href='$cancel_destination';");	echo "</td>";
	echo "</table>\n";
	div();
?>
	</td></tr>
</TABLE>
</FORM>
<SCRIPT LANGUAGE="javascript">
	document.logonForm.login_p.defaultValue = get_cookie("login_p"); 
	document.logonForm.login_u.value = get_cookie("login_u");
</SCRIPT>
<?php
	standard_page_bottom();
?>