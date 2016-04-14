<?php
	import_request_variables("gp");
	$uri = $_SERVER['REQUEST_URI'];
	$root = $_SERVER['DOCUMENT_ROOT'];
	$this_script = $_SERVER['SCRIPT_NAME'];
	$ref_page = $_SERVER['HTTP_REFERER'];
	$urinm = split("/", $uri);
	$valuri = "/".next($urinm);
	$subsite = next($urinm);
	if (file_exists("$subsite/error.php")) {
		$base_uri = "{$valuri}/$subsite/";
		require_once("$subsite/error.php");
		exit;
	}
	$head_img = "{$valuri}/images/title/site_error.gif";
	$style_sheet = "{$valuri}/style/default.css";
	
?>
<HTML><HEAD>
<TITLE>In cyberspace, no-one can hear you scream....</TITLE>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link rel="stylesheet" href="<?php echo $style_sheet; ?>">
</HEAD>
<BODY CLASS="page-main" BGCOLOR="#ffffff" TEXT="#000000" LINK=#2222aa" VLINK="#2222aa" ALINK="#2222aa">
<img	src="<?php echo $head_img; ?>" width="560" height="72" border="0" alt="Site Error" align="top"> 
<br clear=all>
<?php
	if ($serv_error) {
		echo "<p class='title-txt'>Some kind of server error has occured .... </p>\n";
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
				"Well, you can always try again ... or contact the administrator of this server";
				"</p>\n";
	} else {
		echo "<p class='title-txt'>",
				"No error has occurred (or so it seems!) ....","<br>","</p>";
	}
?>
<BR>
</table>
</body>
</html>
