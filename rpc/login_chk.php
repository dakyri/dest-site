<?php
	if ($rpcMode == "Login") {
		$search_params = search_param_url();
		if (!isset($login_destination)) {
			$login_destination = urlencode("publications.php?".$search_params);
		}
		if (!isset($cancel_destination)) {
			$cancel_destination = urlencode("publications.php?".$search_params);
		}
		header("Location: logon.php?login_destination={$login_destination}&cancel_destination={$cancel_destination}");
		exit;
	} else if ($rpcMode == "Logout") {
		unset($login_u);
		unset($login_p);
		setcookie("login_u", "");
		setcookie("login_p", "");
	} else {
	}
	
	$login_user_row = NULL;
	$login_type = LOGIN_NONE;
	if ($login_p && $login_u) {
		$mysql = get_database(
				$database_name,
				$database_host,
				$login_u,
				$login_p);
		if ($mysql > 0) {
			$login_type = LOGIN_DBADMIN;
		} elseif (-$mysql == MYSQL_ER_ACCESS_DENIED) {
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
							$login_user_row = $row;
							if ($login_user_row->kind == "admin") {
								$login_type = LOGIN_ADMIN;
							} else {
								$login_type = LOGIN_USER;
							}
						}
					}
				}
			}
		}
	}	
?>