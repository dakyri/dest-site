<?php
error_reporting(3);
import_request_variables("gpc");
require_once("common/necessary.php");
require_once("login_chk.php");

mmtc_page_top(
	"staff",
	"RMIT MMTC (Microelectronics and Materials Technology Centre)",
	"page-main");
?>
<p>The Centre supports 10 academic and 6 full time research staff and employs
9 technical staff and project managers. The Centre also supports - 25 postgraduate
research students at Masters and PhD level and a growing number of postgraduate
coursework students <em>(</em><em>Chipskills</em><em> and Masters of </em><em>MEMS</em><em>)</em>.</p>
<?php
	$query = "select * from people where (find_in_set('mmtc', properties))";
	$result = mysql_query($query);
	if ($result > 0) {
		$nitems = mysql_num_rows($result);
		if ($nitems > 0) {
?>
<?php
			table_header(2,2);
			for ($i=0; $i<$nitems; $i++) {
				$row = mysql_fetch_object($result);	
				if ($row->kind=='staff') {
					$nm = "";
					if ($row->title) {
						$nm = "$row->title";
					}
					if ($row->firstname) {
						$nm .= " $row->firstname";
					}
					if ($row->surname) {
						$nm .= " $row->surname";
					}
					echo "<tr>";
					echo "<td>";
					div("item-subhead");
					echo $nm;
					div();
					echo "</td>";
					if ($row->email) {
						echo "<td>";
						div("item-subhead");
						echo anchor_str($row->email, "mailto:$row->email");
						div();
						echo "</td>";
					}
					echo "</tr>";
					if ($row->description) {
						echo "<tr><td colspan='2'>";
						div("item-subhead");
						echo $row->description;
						div();
						echo "</td></tr>";
					}
				}
			}
			table_tail();
		} else {
		}
	}
	
	mmtc_page_bottom();
?>
