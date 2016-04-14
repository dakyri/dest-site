<?php
// site specific necessities. keep "necessary.php" as generic as possible
$thumbnail_width = 130;
$thumbnail_height = 130;
$image_width = 300;
$image_height = 300;

$email_webadmin = "";

$default_image = "images/img-dflt.gif";
$default_thumb_image = "images/img-dflt.gif";
$upload_base = "uploaded";

define("LOGIN_NONE", 0);
define("LOGIN_USER", 1);
define("LOGIN_ADMIN", 2);
define("LOGIN_DBADMIN", 3);

$schema_base_directory = "templates";
$schema_idx_name = "schema_idx.ser";
$pub_schema_idx_name = "pub_schema_idx.ser";

$g_page_len = 0;	// number of entries in a page of results, 0 turns off paging
$d_page_len = 0;


function side_nav($page, $base_uri="")
{
	global	$login_type;
	div("nav");
	echo anchor_str("Home",
			"{$base_uri}index.php", NULL,
			($page=="home")?"current":NULL, NULL);
	echo anchor_str("Research",
			"{$base_uri}research.php", NULL,
			($page=="research")?"current":NULL, NULL);
	echo anchor_str("Courses",
			"{$base_uri}courses.php", NULL,
			($page=="courses")?"current":NULL, NULL);
	echo anchor_str("Facilities",
			"{$base_uri}facilities.php", NULL,
			($page=="facilities")?"current":NULL, NULL);
	echo anchor_str("Equipment",
			"{$base_uri}equipment.php", NULL,
			($page=="equipment")?"current":NULL, NULL);
	if ($login_type >= LOGIN_USER) {
		echo anchor_str("Booking Sheet",
				"{$base_uri}booking.php", NULL,
				($page=="booking")?"current":NULL, NULL);
	}
	echo anchor_str("Publications",
			"{$base_uri}publications.php", NULL,
			($page=="publications")?"current":NULL, NULL);
//	echo anchor_str("Contact",
//			"{$base_uri}contact.php", NULL,
//			($page=="contact")?"current":NULL, NULL);
	echo anchor_str("Staff",
			"{$base_uri}staff.php", NULL,
			($page=="staff")?"current":NULL, NULL);
	if ($login_type >= LOGIN_USER) {
		if ($login_type >= LOGIN_ADMIN) {
			echo anchor_str("Admin",
				"{$base_uri}admin.php", NULL,
				($page=="admin")?"current":NULL, NULL);
		}
		echo anchor_str("Logout",
				"{$base_uri}logon.php?loginAction=Logout&login_destination=index.php", NULL,
				($page=="login")?"current":NULL, NULL);
	} else {
		echo anchor_str("Login",
				"{$base_uri}logon.php", NULL,
				($page=="login")?"current":NULL, NULL);
	}
	div();
}

function mmtc_page_top($page, $title, $body_class, $top_banner=NULL, $base_uri="", $common_javascript_src=NULL)
{
	if (!$top_banner) {
		$top_banner = "images/title/mmtc_top-w.jpg";
	}
	$stylesheet = "style/default.css";
	if ($base_uri) {
		$top_banner = "{$base_uri}$top_banner";
		$stylesheet = "{$base_uri}$stylesheet";
	}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<meta NAME="description"
  CONTENT="RMIT University Microelectronics and Materials Technology Centre">
<meta NAME="keywords"
  CONTENT="RMIT University, MMTC, Microelectronics, Materials Technology">
<title><?php echo $title;?></title>
<link href="<?php echo $stylesheet; ?>" rel="stylesheet" type="text/css">
</head>
<body class="<?php echo $body_class; ?>">
<?php if ($common_javascript_src): ?>
<SCRIPT type="text/javascript" SRC="<?php echo $common_javascript_src; ?>"></SCRIPT>
<?php	endif; ?>
<img src="<?php echo $top_banner; ?>" width="630" height="95" class="page-top">
<?php
	side_nav($page, $base_uri);
}


function verify_user($mysql, $usr_u, $usr_p_enc, &$usr_row)
{
	$user_authenticated = false;
	if ($mysql > 0 && $usr_u && $usr_p_enc) {
		$query = "select * from people where name='$usr_u'";
		$result = mysql_query($query);
		if ($result > 0) {
			$nitems = mysql_num_rows($result);
			if ($nitems > 0) {
				$row = mysql_fetch_object($result);
				if ($row->passwd == $usr_p_enc) {
					$user_authenticated = $row->kind;
					$usr_row = $row; 
				}
			}
		}
	}
	return $user_authenticated;
}


function mmtc_page_bottom()
{
?>
</body>
</html>
<?php
}

function errorpage($error_message, $error_style="")
{
	mmtc_page_top(
		NULL,
		"RMIT MMTC (Microelectronics and Materials Technology Centre)",
		"page-main");
	if ($error_message) {
		echo "<B>$error_message</B><BR>";
	}
?>
<BR>
<CENTER>
<a width="40" height="40" border="0" title="Return to previous page"  class="img-button"
	onclick="history.back()">Go Back</A>
</CENTER>
<?php
	mmtc_page_bottom();
	exit;
}

?>
