<?php
error_reporting(3);
import_request_variables("gpc");
require_once("common/necessary.php");
require_once("../common/common_mysql.php");
require_once("login_chk.php");

mmtc_page_top(
	"contact",
	"RMIT MMTC (Microelectronics and Materials Technology Centre)",
	"page-main");
	
	if (!isset($mysql) || $mysql < 0) {
		$mysql = get_database(
					$schema_database_name,
					$database_host,
					$database_pleb_user,
					$database_pleb_passwd);
	}
?>
<?php
	$query = "select * from facilities where phone != ''";
	$result = mysql_query($query);
	$have_header = false;
	if ($result > 0) {
		$nitems = mysql_num_rows($result);
		for($i=0; $i < $nitems; $i++) {
			$row = mysql_fetch_object($result);
			if ($row->phone) {
				if (!$have_header) {
?>
<p><b>Contact numbers for facilities:</b><br><br>
<?php
					$have_header = true;
				}
				echo "&nbsp;&nbsp;&nbsp;<b>$row->name</b>: $row->phone<br>\n";
			}
		}
		if ($have_header) {
			echo "</p>\n";
		}
	}
	mmtc_page_bottom();
?>
