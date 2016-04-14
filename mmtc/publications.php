<?php
error_reporting(3);
import_request_variables("gpc");
require_once("common/necessary.php");
require_once("common/show_mmtc_pubs.php");
require_once("../common/common_mysql.php");
require_once("login_chk.php");

	mmtc_page_top(
		"publications",
		"RMIT MMTC (Microelectronics and Materials Technology Centre)",
		"page-main");
	if (!isset($mysql) || $mysql < 0) {
		$mysql = get_database(
					$schema_database_name,
					$database_host,
					$database_pleb_user,
					$database_pleb_passwd);
	}
	if ($mysql < 0) {
		echo "<P>Publication database is temporarily off-line. Please try later.</p>";
		mmtc_page_bottom();
		exit;
	}
	
	if (isset($publication_code) && isset($publication_table)) {
		$query = "select * from $publication_table where code='$publication_code'";
		$result = mysql_query($query);
		if ($result <= 0) {
			echo "<P>Publication database is temporarily off-line. Please try later.</p>";
			echo mysql_error();
			mmtc_page_bottom();
			exit;
		}
		$nitems = mysql_num_rows($result);
		if ($nitems <= 0) {
?>
<p>I'm sorry, but that particular article doesn't seem to be referenced in our database.
</p>
<?php
			mmtc_page_bottom();
			exit;
		}
		$row = mysql_fetch_object($result);
?>
<p>The following article is copyright &copy; by the respective authors and publisher,
and may be downloaded for personal use only. Any other use requires prior permission of the author and the respective publisher.
</p>
<?php
		div("pub-abstract-head");
		show_pub_row($row, $publication_table, false, false, true);
		div();
		div("pub-abstract-body");
		if ($row->description) {
			echo $row->description;
		}
 		$mat = array_map("rawurldecode", explode("&", $row->material));
 		$matyp = array_map("rawurldecode", explode("&", $row->material_kind));
	 	$download_k = -1;
	 	$frontimg_k = -1;
	 	$img_count = 0;
	 	$media_count = 0;
	 	reset($mat);
	 	reset($matyp);
	 	while (list($k,$v) = each($matyp)) {
	 		if ($v=="paper" || $v=="chapter") {
			 	$is_download = (($download_k<0) && ($mat[$k]));
		 		if ($is_download) {
		 			$download_k = $k;
		 		}
		 	} elseif ($v=="img" || $v=="frontimg") {
		 		$img_count++;
		 	} elseif ($v=="media") {
		 		$media_count++;
		 	}
	 	}
	 	$upbase = pub_material_base($publication_table);
		if ($download_k >= 0) {
			echo "<br>";
			echo "<br>";
			download_link($matyp[$download_k], "$upbase$row->code/".$mat[$download_k]);
			echo "<br>\n";
		}
		div();
		if ($img_count > 0) {
			echo "<center>";
			table_header(0,0);
			reset($matyp);
		 	while (list($k,$v) = each($matyp)) {
				if ($v=="frontimg") {
					echo "<tr><td align='center' colspan=2>";
					image_tag("$upbase$row->code/".$mat[$k]);
					echo "</td></tr>";
		 		}	
			}
			reset($matyp);
			$img_count = 0;
			echo "<tr>";
		 	while (list($k,$v) = each($matyp)) {
				if ($v=="img") {
					table_data();
					$image_count++;
					image_tag("$upbase$row->code/".$mat[$k]);
					table_dend();
					if ($image_count%2 == 0) {
						echo "</tr><tr>";
					}
		 		}	
			}
			table_tail();
			echo "</center>";
		}
		mmtc_page_bottom();
		exit;
	}
?>
<p>Show MMTC publications for ...</p>
<?php
	form_header("publications.php", "searchForm", "POST", "", "");
	table_header(2,2);
	echo "<tr><td><b>Year<br>(or range of years)</b></td><td>";
	text_input("search_year", $search_year, 10, 10);
	echo "</td></tr>";
	echo "<tr><td><b>Keywords or<br>words in title</b></td><td>";
	text_input("search_keywords", $search_keywords, 60, 200);
	echo "</td></tr>";
	echo "<tr><td><b>Name of author(s)</b></td><td>";
	text_input("search_author", $search_author, 60, 200);
	echo "</td></tr>";
	echo "<td colspan='2' align='center'>";
	div('edit-controls');submit_input("rpcMode", "Search");	div();echo "</td>";
	table_tail();
	table_tail();
	form_tail();

	$where = "find_in_set('mmtc', research_group)";
	$kw_where = word_query($search_keywords);
	if ($kw_where) {
		$where .= "&& ($kw_where)";
	}
	$kw_where = year_query($search_year);
	if ($kw_where) {
		$where .= "&& ($kw_where)";
	}
	$kw_where = author_query($search_author);
	if ($kw_where) {
		$where .= "&& ($kw_where)";
	}
	
	$arc_count = 1;
	$have_a_pub = false;
	while (list($pubk,$pubv) = each($pubdbname)) {
		$query = "select * from $pubv where $where";
		$result = mysql_query($query);
		if ($result <= 0) {
			echo "<P>Publication database is temporarily off-line. Please try later.</p>";
			echo mysql_error();
			mmtc_page_bottom();
			exit;
		}
		$nitems = mysql_num_rows($result);
		if ($nitems > 0) {
			if (!$have_a_pub) {
?>
<p>The following articles are copyright &copy; by the respective authors and publishers,
and may be downloaded for personal use only. Any other use requires prior permission of the author and the respective publisher.
</p>
<?php
				echo "<center>\n";
				table_header(0,0);
				$have_a_pub = true;
			}
			for($i=0; $i < $nitems; $i++) {
				$row = mysql_fetch_object($result);
				if ($row->title) {
					table_row();
					table_data("left", "top");
					echo $arc_count, ". ";
					table_dend();
					table_data("left","top");
					show_pub_row($row, $pubv, true, true, true);
					echo "<br>";
					table_dend();
					table_rend();
					$arc_count++;
				}
			}
		}
	}
	
	if ($have_a_pub) {
		table_tail();
		echo "</center>\n";
	} else {
?>
<p>Sorry, but we haven't published anything that matches your criteria.</p>
<?php
	}
	mmtc_page_bottom();
?>
