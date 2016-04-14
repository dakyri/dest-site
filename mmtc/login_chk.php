<?php
	$login_user_row = NULL;
	$login_type = LOGIN_NONE;
	if ($login_p && $login_u) {
		require_once("../common/common_mysql.php");
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
				$query = "select * from people where stnumber='$login_u' and (find_in_set('mmtc', properties) or (kind='admin'))";
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