<?php
	error_reporting(3);
	import_request_variables("gp");
	require_once("common/necessary.php");
	require_once("../common/common_mysql.php");
	import_request_variables("c");
	
	if (!$login_destination) {
		$login_destination = "index.php";
	}
	if (!$cancel_destination) {
		$cancel_destination = "index.php";
	}
	if ($loginAction == "Logout") {
		unset($login_u);
		unset($login_p);
		setcookie("login_u", "");
		setcookie("login_p", "");
		header("Location: $login_destination");
	} elseif ($loginAction=="Login" && $login_p && $login_u) {
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
					$query = "select * from people where stnumber='$login_u' and (find_in_set('mmtc', properties) or (kind='admin'))";
					$result = mysql_query($query);
					if ($result > 0) {
						$nitems = mysql_num_rows($result);
						if ($nitems > 0) {
							$row = mysql_fetch_object($result);				
							if ($row->passwd == md5($login_p)) {
			// yay... someone's on our team
								setcookie("login_u", $login_u, 0);
								setcookie("login_p", $login_p, 0);
								header("Location: $login_destination");
								exit();
							} else {
								$login_msg = "Password for user '$login_u' is incorrect...";
							}
						} else {
							$login_msg = "User '$login_u' does not exist, or does not have permission to log into this site...";
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
			setcookie("login_u", $login_u, 0);
			setcookie("login_p", $login_p, 0);
			header("Location: $login_destination");
			exit();
		}
	} else { // no user pass/combo input
		;
	}
	import_request_variables("c");
	mmtc_page_top(
			"login",
			"RMIT MMTC (Microelectronics and Materials Technology Centre)",
			"page-main",
			NULL, NULL,
			"../common/necessary.js");
	if (isset($login_msg) && $login_msg != "") {
		echo "<p>$login_msg</p>";
	}
?>
<br><br>
<form action="logon.php" method="POST"  name="logonForm" onsubmit="return true;">
<table cellpadding="2" cellspacing="2" border="0" width="60%">
 <TR><td align="right">
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
	echo "<td>";submit_input("loginAction", "Login"); echo "</td>";
	echo "<td>";button_input("loginAction", "Cancel", "location.href='$cancel_destination';"); echo "</td>";
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
	mmtc_page_bottom();
?>