<?php
	import_request_variables("gp");
	include_once("common/necessary.php");
	mmtc_page_top(
		NULL,
		"In cyberspace, no-one can hear you scream....",
		"page-main",
		NULL,
		$base_uri);
	if ($serv_error) {
		echo "<p class='title-txt'>Some kind of error has occured .... </p>\n";
		switch ($serv_error) {
			case 400:
				echo "<p class='title-txt'>400: Bad Request.",
						"Don't see many of those, do you?<br>",
						"Something must have gone wrong with a script, or the server.<br>",
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
	mmtc_page_bottom();
?>
