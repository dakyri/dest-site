<?php
	if (isset($login_u) && $login_u != "") {
		$mysql = get_database($database_name, $database_host, $login_u, $login_p);
		if ($mysql <= 0) {
			header("Location: logon.php?login_msg=database $database_name on $database_host not accessible to user $login_u on this password");
			exit;
		}
	} else {
		header("Location: logon.php?login_msg=Please login to use this function");
		exit;
	}
?>