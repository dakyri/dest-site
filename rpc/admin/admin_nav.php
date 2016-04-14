<?php
	echo "<center>";
	div("edit-controls");
	echo "<table><tr>";
 	echo "<td><b>"; button_input("indReturn", "Return to admin menu", "window.location='index.php'", NULL, NULL, "nav but"); echo "</B></td>\n";
 	echo "<td><b>"; button_input("rpcReturn", "Return to RPC main", "window.location='../index.php'", NULL, NULL, "nav but"); echo "</B></td>\n";
	echo "</tr></table>";
	div();
	echo "</center>";
?>