<?php
	import_request_variables("gp");
	include_once("common/necessary.php");
	standard_page_top(
		"In cyberspace, no-one can hear you scream....",
		isset($base_uri)?"{$base_uri}style/default.css":in_parent_path("style/default.css"),
		"page-main",
		isset($base_uri)?"{$base_uri}images/title/site_error.gif":in_parent_path("images/title/site_error.gif"),
		560, 72,
		"Site Error");
	echo "<br clear=all>";
	if ($serv_error) {
		echo "<p class='title-txt'>Some kind of error has occured .... </p>\n";
		switch ($serv_error) {
			case 400:
				echo "<p class='title-txt'>400: Bad Request.",
						"Don't see many of those, do you?<br>",
						"Something must have gone wrong with a script or the server.<br>",
						"</p>\n";
				break;
			case 401:
				echo "<p class='title-txt'>401: Unauthorized.",
						"Maybe you need a correct password or something like that ...<br>",
						"</p>\n";
				break;
			case 403:
				echo "<p class='title-txt'>403: Forbidden.",
						"You just shouldn't be here, should you?<br>",
						"</p>\n";
				break;
			case 404:
				echo "<p class='title-txt'>Server error .... 404, Page not found.<br>",
						"You've probably misspelt a page name, or bookmarked the wrong thing.<br>",
						"</p>\n";
				break;
			default:
				break;
		}
		echo "<p class='title-txt'>",
				"Well, you can always try again ... or contact the administrator of this site";
				"</p>\n";
	} else {
		echo "<p class='title-txt'>",
				"No error has occurred (or so it seems!) ....","<br>","</p>";
	}
?>
<BR>
<center>
<table cellpadding="0" cellspacing="0" border="0" align="center" class="nav-grid">
<TR>
<TD WIDTH=45 VALIGN=top ALIGN=center>
<a href="/dest/rpc/index.php" width="40" height="40" border="0" title="Go to main DEST RPC"  class="img-button"><center>Go to<br>RPC</center></A>
</td>
<TD WIDTH=45 VALIGN=top ALIGN=center>
<a href="/dest/rpc/edit/" width="40" height="40" border="0" title="Go to main DEST RPC edit"  class="img-button"><center>Go to<br>RPC<br>Edit</center></A>
</td>
<TD WIDTH=45 VALIGN=top ALIGN=center>
<a href="/dest/rpc/school/" width="40" height="40" border="0" title="Go to main DEST RPC school view"  class="img-button"><center>Go to<br>RPC<br>School View</center></A>
</td>
<TD WIDTH=45 VALIGN=top ALIGN=center>
<a href="/dest/rpc/admin/" width="40" height="40" border="0" title="Go to main DEST RPC school view"  class="img-button"><center>Go to<br>RPC<br>Admin</center></A>
</td>
</CENTER>
</table>
</body>
</html>
