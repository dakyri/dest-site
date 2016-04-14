<?php
// site specific necessities. keep "necessary.php" as generic as possible
$thumbnail_width = 130;
$thumbnail_height = 130;
$image_width = 300;
$image_height = 300;

$email_webadmin = "";

//$upload_base = "uploaded";
$upload_base = "../../destrpc/uploaded";
$default_image = "images/img-dflt.gif";
$default_thumb_image = "images/img-dflt.gif";

define("LOGIN_NONE", 0);
define("LOGIN_USER", 1);
define("LOGIN_ADMIN", 2);
define("LOGIN_DBADMIN", 3);

$schema_base_directory = "templates";
$schema_idx_name = "schema_idx.ser";
$pub_schema_idx_name = "pub_schema_idx.ser";

$logo_movies = array("flashing", "flashing2", "hummer");
$swf_animation_name="logos";
$movie_obj_nm = "logos";
$use_flash_logo = true;
$g_page_len = 0;	// number of entries in a page of results, 0 turns off paging
$d_page_len = 0;

// used by dynamic pages not generated off the sqlschema_edit_framework setup
// needs to be extended whenever a new document type/table is added, or the
// base tag is changed in the xml schema file....
function upload_base($k)
{
	global $upload_base;
	switch ($k) {
		case "book":			$upbase = "$upload_base/bk"; break;
		case "conference":	$upbase = "$upload_base/cnf"; break;
		case "chapter":		$upbase = "$upload_base/ch"; break;
		case "journal":		$upbase = "$upload_base/jrn"; break;
		default:					$upbase = "$upload_base/dfl"; break;
	}
	return $upbase;
}

function setup_swf_hook()
{
	global $swf_hook;
	global $logo_movies;
?>
<SCRIPT LANGUAGE="javascript">
function getFlashMovieObject(movieName)
{
	  if (top.topFrame.document[movieName]) {
//	  		alert(top.topFrame.document[movieName]);
	      return top.topFrame.document[movieName];
	  }
	  
	  if (navigator.appName.indexOf("Microsoft Internet")==-1)
	  {
	    if (top.topFrame.document.embeds) {
	      if ( top.topFrame.document.embeds[movieName]) {
//	      	alert(top.topFrame.document.embeds[movieName]);
	      	return top.topFrame.document.embeds[movieName]; 
	      }
	    }
	  }
	  else // if (navigator.appName.indexOf("Microsoft Internet")!=-1)
	  {
	    return document.getElementById(movieName);
	  }
}
logos = getFlashMovieObject('logos');
</SCRIPT>
<?php
	for ($i=0; $i<count($logo_movies); $i++) {
		$swf_hook[$i] = "logos.TGotoLabel('/$logo_movies[$i]',2);logos.TPlay('/$logo_movies[$i]');";
	}
}

function rand_hook()
{
	global $swf_hook;
	return $swf_hook[rand(0,count($swf_hook)-1)];
}

function seq_hook($n)
{
	global $swf_hook;
	return $swf_hook[$n%count($swf_hook)];
}

function cat_idx_image_mouse_hook($n)
{
	return rand_hook();
}

function cat_main_image_mouse_hook($n)
{
	return rand_hook();
}

// do the passwords as php4 seshion vars?
// session_name("sesh");
// session_register("db_user");
// session_register("db_passwd");

function standard_page_top($title, $stylesheet, $body_class, $header_img_src="", $header_img_w="", $header_img_h="", $header_img_alt="", $javascript_src="necessary.js")
{
?>
<HTML><HEAD>
<TITLE><?php echo $title;?></TITLE>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<meta NAME="description"
  CONTENT="RMIT University DEST Research Publications Collection">
<meta NAME="keywords"
  CONTENT="RMIT University, DEST, Research, Publications,Collection">
<link rel="stylesheet" href="<?php echo $stylesheet; ?>">
</HEAD>
<BODY CLASS="<?php echo $body_class; ?>" BGCOLOR="#ffffff" TEXT="#000000" LINK=#2222aa" VLINK="#2222aa" ALINK="#2222aa">
<font face="Arial, Helvetica, sans-serif">
<?php if ($header_img_src): ?>
<img src="<?php echo $header_img_src; ?>" width="<?php echo $header_img_w; ?>" height="<?php echo $header_img_h; ?>" border="0" alt="<?php echo $header_img_alt; ?>" align="top"> 
<?php endif; ?>
<SCRIPT LANGUAGE="javascript" SRC="<?php echo $javascript_src; ?>"></SCRIPT>
<?php
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

function page_bottom_menu()
{
	global	$login_type;
	global	$login_u;
	global	$login_p;
	
	$uri = $_SERVER['REQUEST_URI'];
	$urinm = split("/", $uri);
	if (index_of("admin", $urinm) >= 0 || index_of("edit", $urinm) >= 0) {
		$reloff = "../";
	} else {
		$reloff = "";
	}
?>
<BR>
<CENTER>
<TABLE CLASS="nav-grid" BORDER=0 CELLSPACING=0 CELLPADDING=0>
<TR>
<TD WIDTH=45 VALIGN=top ALIGN=center>
<A HREF="#top" CLASS="img-button" onClick="history.back(); return false;"><B>Back</B></A>
<TD WIDTH=45 VALIGN=top ALIGN=center>
<A HREF="#top" CLASS="img-button" onClick="history.forward(); return false;"><B>Forward</B></A>
<TD WIDTH=45 VALIGN=top ALIGN=center>
<A HREF="<?php echo $reloff; ?>index.php" CLASS="img-button"><B>DEST RPC Home</B></A>
<?php if ($login_u && $login_p): ?>
<?php if ($login_type >= LOGIN_ADMIN): ?>
<TD WIDTH=45 VALIGN=top ALIGN=center>
<A HREF="<?php echo $reloff; ?>admin/index.php" CLASS="img-button"><B>Admin</B></A>
<TD WIDTH=45 VALIGN=top ALIGN=center>
<A HREF="<?php echo $reloff; ?>admin/index.php?logout=admin" CLASS="img-button"><B>Logout Admin</B></A>
<?php elseif ($login_type == LOGIN_USER): ?>
<TD WIDTH=45 VALIGN=top ALIGN=center>
<A HREF="<?php echo $reloff; ?>edit/index.php" CLASS="img-button"><B>Edit Publications</B></A>
<TD WIDTH=45 VALIGN=top ALIGN=center>
<A HREF="<?php echo $reloff; ?>admin/ed_people.php" CLASS="img-button"><B>Edit Personal Info</B></A>
<TD WIDTH=45 VALIGN=top ALIGN=center>
<A HREF="<?php echo $reloff; ?>edit/index.php?logout=user" CLASS="img-button"><B>Logout <?php echo $usr_u; ?></B></A>
<?php endif; ?>
<?php else: ?>
<TD WIDTH=45 VALIGN=top ALIGN=center>
<A HREF="<?php echo $reloff; ?>logon.php" CLASS="img-button"><B>User Logon</B></A>
<?php endif; ?>
<TD WIDTH=45 VALIGN=top ALIGN=center>
<A HREF="#top" CLASS="img-button" onMouseUp="window.print(); return false;"><B>Print</B></A>
</CENTER>
</TABLE>
<FONT SIZE=1>
<DIV CLASS="nowrap" ALIGN=right> <font face="Arial, Helvetica, sans-serif"></font></DIV>
</FONT>
<?php
}

function standard_page_bottom()
{
?>
</FONT> 
</BODY>
</HTML>
<?php
}

function errorpage($error_message, $error_style="")
{
	standard_page_top("In cyberspace, no-one can hear you scream....",
		$error_style?$error_style:in_parent_path("style/default.css"),
		"page-main",
		in_parent_path("images/title/site_error.gif"),
		560, 72,
		"Site Error");
	echo "<br clear=all>";
	if ($error_message) {
		echo "<B>$error_message</B><BR>";
	}
?>
<BR>
<CENTER>
<a width="40" height="40" border="0" title="Return to previous page"  class="img-button" onclick="history.back()">Go Back</A>
</CENTER>
<?php
	exit;
}

?>
