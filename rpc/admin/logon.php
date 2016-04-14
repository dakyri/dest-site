<?php

	import_request_variables("gp");
	require_once("../common/necessary.php");
	
	if (!$login_destination) {	
		$login_destination = "index.php";
	}
	if (!$cancel_destination) {	
		$cancel_destination = "index.php";
	}

	if ($login_p && $login_u) {
		$mysql = get_database(
				$database_name,
				$database_host,
				$login_u,
				$login_p);
		if ($mysql < 0) {
			$login_msg = "Please enter a valid database username/password combination";
		} else {
			setcookie("login_u", $login_u, 0, "/dest/rpc/");
			setcookie("login_p", $login_p, 0, "/dest/rpc/");
			header("Location: $login_destination");
			exit;
		}
	}
	standard_page_top("Administrative Logon",
			"../style/default.css",
			"page-noframe",
			"../images/title/database_admin.gif", 560, 72, "Logon",
			in_parent_path("/common/necessary.js"));
	if (isset($login_msg) && $login_msg != "") {
		echo "<p>$login_msg</p>";
	}
?>
<FORM ACTION="logon.php"
	METHOD=POST NAME="logonForm"
	onSubmit="return true;"
>
<?php
	hidden_field("login_destination", $login_destination);
	hidden_field("cancel_destination", $cancel_destination);
?>
<TABLE WIDTH=90% CELLSPACING=2 CELLPADDING=0>
 <TR><td width="50%" align="right">
  <FONT SIZE=+0><B>Database User Name</B></FONT>
  <td><INPUT type="text" class="form-textin" NAME="login_u" MAXLENGTH=128 SIZE=16>
 <TR><TD width="50%" align="right">
  <FONT SIZE=+0><B>Database Password</B></FONT>
  <td><INPUT type="password" class="form-textin" NAME="login_p" MAXLENGTH=128 SIZE=16>
 <TR><TD colspan="2" align="center">
  <div class="edit-controls"><INPUT TYPE=SUBMIT class="form-button" VALUE="Administrate This"></div>
</TABLE>
</FORM>
<SCRIPT LANGUAGE="javascript">
	document.logonForm.login_p.defaultValue = get_cookie("login_p"); 
	document.logonForm.login_u.value = get_cookie("login_u");
</SCRIPT>
<?php
	standard_page_bottom();
?>