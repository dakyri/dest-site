<?php
	import_request_variables("gpc");
	error_reporting(3);
	require_once("common/necessary.php");
	require_once("../common/common_mysql.php");
	require_once("../common/adminlib.php");
	require_once("login_chk.php");
	
	if (!isset($login_type)) {
		header("Location: logon.php?login_msg=Invalid+login...");
		exit();
	}
	if ($login_type < LOGIN_ADMIN) {
		header("Location: logon.php?login_msg=Administrative+login+required...");
		exit();
	}

	mmtc_page_top(
		"admin",
		"MMTC: Site Admistrative Controls",
		"page-main",
		NULL,NULL,
		"../common/necessary.js");
	div('wide-margin');
	if (file_exists("$schema_base_directory/$schema_idx_name")) {
		$uploaded_schema = uncache_variable("$schema_base_directory/$schema_idx_name");
	}
	if (!$uploaded_schema) {
		$uploaded_schema = array();
	}
?>
<P>This page offers links to fundamental administrative tools for the all the MMTC
support databases, and content management for this site.</p>
<p>Control of the publication databases is via the admin facilities of
the <a href="../rpc/admin/index.php">Research Publication Collection</a> site.</p>
<?php
	//echo "<center>";
	//div("edit-controls");
	//echo "<table><tr>";
 	//echo "<td><b>"; button_input("rpcReturn", "Return to RPC main",
 	//			$return_rpc_url?
 	//				"window.location='../$return_rpc_url'":
 	//				"window.location='../index.php'",
 	//			NULL, NULL, "nav but"); echo "</B></td>\n";
 	//echo "<td><b>"; button_input("indReturn", "Logout", "window.location='index.php?logout=true'", NULL, NULL, "nav but"); echo "</B></td>\n";
	//echo "</tr></table>";
	//div();
	//echo "</center>";
?>
<ul><b>Support Databases</b>:
<?php
	reset($uploaded_schema);
	while (list($key,$val) = each($uploaded_schema)) {
		$kl = ucwords(str_replace('_', ' ', $key));
  		echo "<li><a href=\"ed_schema_db.php?sqlschema=$key&schema_edit_return_url=index.php\">Modify $kl database</a></li>";
  	}
?>
 <li><a href="ed_people.php">Modify user account database</a></li>
 </ul>
<?php	if ($login_type == LOGIN_DBADMIN):	?>
 <ul><b>General Database/System Admin Tools</b>:
  <li><a href="upload_schema.php">Upload a new database schema</a>
  	<br>&nbsp;&nbsp;(create a new database, modify structure or edit behaviour of an existing one) </li>
  <li><a href="download_table.php">Download backup files from a database</a>
  	<br>&nbsp;&nbsp;(makes a backup of selected databases to a file on your local hard-drive)</li>
  <li><a href="upload_table.php">Upload backup files to a database</a>
  	<br>&nbsp;&nbsp;(inserts a prior backup from a file on your local hard-drive to selected databases)</li>
  <li><a href="clear_table.php">Clear database tables</a>
   <br>&nbsp;&nbsp;(drops selected databases, and removes them from the hierarchy of installed databases)</li>
 </ul>
<?php	else: ?>
<p>Full site control requires a database login.</p>
<?php	endif; ?>
<?php
	div();
	mmtc_page_bottom();
?>
