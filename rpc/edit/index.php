<?php
	import_request_variables("gp");
	require_once("../common/necessary.php");
	error_reporting(0);
	if (!$login_destination) {
		$login_destination = "user_ed_schema_db.php";
	}
	if (!$logout && $login_p && $login_u) {
		setcookie("login_u", $login_u, 0, "/dest/rpc/");
		setcookie("login_p", $login_p, 0, "/dest/rpc/");
		header("Location: $login_destination");
		exit();
	}
	import_request_variables("c");
	if ($logout) {
		setcookie("login_u", "", 0, "/dest/rpc/");
		setcookie("login_p", "", 0, "/dest/rpc/");
		header("Location: ../index.php");
	} elseif ($login_p && $login_u) {
		header("Location: $login_destination");
		exit();
	}
	header("Location: logon.php");
	exit;
?>
