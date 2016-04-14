<?php
	import_request_variables("gpc");
	include_once("common/necessary.php");
	include_once("$site_base/common/sqlschema_types.php");
	require_once("searchparams.php");
	require_once("login_chk.php");
	standard_page_top("DEST Research Publications Database", "style/default.css", "page-noframe", "images/title/dest_rpc.gif", 700, 72, "DEST Research Publication Collection", "common/necessary.js");
	br("all");
	search_param_reset();
	require_once("searchform.php");
	br();br();

?>
<font face="Arial, Helvetica, sans-serif">
<font SIZE=2><P></p>This site is best viewed in Firefox 1.0 or greater, Mozilla, Netscape 6 or greater,
Internet Explorer 6 or greater, Safari, or Opera. Netscape 4.7 is not supported.
It is also best viewed with Javascript enabled, though this is not a necessity. Browser masquerading may lead to
unnusual behaviours.</font> 
<?php
//<font SIZE=1> 
//<P ALIGN=right> &copy; 2005. Designed and maintained by <A HREF="http://www.mayaswell.com">Maya Swell</A><BR>
//</P>
//</font>
	standard_page_bottom();
?>