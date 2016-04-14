<?php
	import_request_variables("gpc");
	error_reporting(0);
	require_once("../common/necessary.php");
	if ($logout) {
		unset($login_u);
		unset($login_p);
		setcookie("login_u", "", 0, "/dest/rpc/");
		setcookie("login_p", "", 0, "/dest/rpc/");
		if (isset($return_rpc_url)) {
			header("Location: ../$return_rpc_url");
		} else {
			header("Location: ../index.php");
		}
		exit();
	}
	if (!$login_u || !$login_p) {
		header("Location: logon.php");
		exit();
	}
	$mysql = get_database($database_name, $database_host, $login_u, $login_p);
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
							$mysql = get_database(
								$database_name,
								$database_host,
								$database_mod_user,
								$database_mod_passwd);
						} else {
							$mysql = -1;
						}
					}
				}
			}
		}
	}
	if ($mysql <= 0) {
		header("Location: logon.php?login_type=admin&login_msg=Invalid+login...");
		exit();
	}

	standard_page_top("DEST RPC: Admistrative Controls", "../style/default.css", "page-noframe", "../images/title/database_admin.gif", 560, 72, "DEST RPC Admin Zone", "../common/necessary.js");
	if (file_exists("../$schema_base_directory/$schema_idx_name")) {
		$uploaded_schema = uncache_variable("../$schema_base_directory/$schema_idx_name");
	}
	if (!$uploaded_schema) {
		$uploaded_schema = array();
	}
?>
<font size="3"><P>This page offers links to fundamental administrative tools for the all the RPC
 databases, access to the support databases, and 'first check' edits on the publication databases.</p>
<?php
	echo "<center>";
	div("edit-controls");
	echo "<table><tr>";
 	echo "<td><b>"; button_input("rpcReturn", "Return to RPC main",
 				$return_rpc_url?
 					"window.location='../$return_rpc_url'":
 					"window.location='../index.php'",
 				NULL, NULL, "nav but"); echo "</B></td>\n";
 	echo "<td><b>"; button_input("indReturn", "Logout", "window.location='index.php?logout=true'", NULL, NULL, "nav but"); echo "</B></td>\n";
	echo "</tr></table>";
	div();
	echo "</center>";
?>
<ul><b>Publication and Support Databases</b>:
<?php
// !!!! important to note here that the field names of these databases are
// idententical to those in the corresponding publication db's. no reason that this
// is the case other than that there isn't a pressing reason to allow for it just now.
// the worst that would happen to allow it would be a minor extension required on the sync_table script
// which is not major. the normal db autocomplete routines do allow for this and deal with it....
	reset($uploaded_schema);
	while (list($key,$val) = each($uploaded_schema)) {
		$kl = ucwords(str_replace('_', ' ', $key));
  		echo "<li><a href=\"ed_schema_db.php?sqlschema=$key&schema_edit_return_url=index.php\">Modify $kl database</a></li>";
  	}
	$sync_conference_field = array(
  		"conference_name",
  		"conference_date",
  		"conference_location",
  		"publication_title",
  		"publisher",
  		"publication_place",
  		"publication_year",
  		"publication_month",
  		"editor",
  		"isbn");
	$sync_conference_field_param = "";
  	reset($sync_conference_field);
  	while (list($k,$v) = each($sync_conference_field)) {
  		$sync_conference_field_param .= "&sync_field[]=$v";
  	}
  	
	$sync_journal_field = array(
  		"journal_name",
  		"publisher",
  		"publication_place",
  		"issn");
	$sync_journal_field_param = "";
  	reset($sync_journal_field);
  	while (list($k,$v) = each($sync_journal_field)) {
  		$sync_journal_field_param .= "&sync_field[]=$v";
  	}
  	
	$sync_author_field = array(
  		"surname",
  		"firstname",
  		"stnumber",
  		"author_title",
  		"gender",
  		"type",
  		"school_code",
  		"school_org_name"
  	);
	$sync_author_field_param = "&schema_list_src=true";
  	reset($sync_author_field);
  	while (list($k,$v) = each($sync_author_field)) {
  		$sync_author_field_param .= "&sync_field[]=$v";
  	}
?>
  <li><a href="ed_people.php">Modify Login Accounts database</a></li>
 </ul>
<?php	if ($login_type == LOGIN_DBADMIN):	?>
 <ul><b>General Database/System Admin Tools</b>:
  <li><a href="upload_schema.php">Upload a new database schema</a>
  	<br>&nbsp;&nbsp;(create a new database, modify structure or edit behaviour of an existing one) </li>
  <li><a href="download_table.php">Download backup files from a database</a>
  	<br>&nbsp;&nbsp;(makes a backup of selected databases to a file on your local hard-drive)</li>
  <li><a href="upload_table.php">Upload backup files to a database</a>
  	<br>&nbsp;&nbsp;(inserts a prior backup from a file on your local hard-drive to selected databases)</li>
  <li><a href="sync_table.php?from_table=conference&to_table=conference_info<?php echo $sync_conference_field_param; ?>">Synchronise conference info/autocompleter table</a>
  	<br>&nbsp;&nbsp;(inserts details on any conferences with submissions in the RPC to the 'Conference Info' database)</li>
  <li><a href="sync_table.php?from_table=journal&to_table=journal_info<?php echo $sync_journal_field_param; ?>">Synchronise journal info/autocompleter table</a>
  	<br>&nbsp;&nbsp;(uploads any journals with submissions in the RPC to the 'Journal Info' database)</li>
  <li><a href="sync_table.php?from_table=journal,conference,chapter,book&to_table=authors<?php echo $sync_author_field_param; ?>">Synchronise author info/autocompleter table</a>
  	<br>&nbsp;&nbsp;(uploads any authors with submissions in the RPC to the 'Authors' database)</li>
  <li><a href="clear_table.php">Clear database tables</a>
   <br>&nbsp;&nbsp;(drops selected databases, and removes them from the hierarchy of installed databases)</li>
 </ul>
<?php	endif; ?>
<?php
	standard_page_bottom();
?>
