<?php
	error_reporting(3);
	include_once("common/necessary.php");
	import_request_variables("c");
	require_once("login_chk.php");
	mmtc_page_top(
		"home",
		"RMIT MMTC (Microelectronics and Materials Technology Centre)",
		"page-main");
	require_once("../common/common_mysql.php");
	$kind_arc_max = 3;
	$front_arc_max = 4;
	if (!isset($mysql) || $mysql < 0) {
		$mysql = get_database(
					$schema_database_name,
					$database_host,
					$database_pleb_user,
					$database_pleb_passwd);
	}
?>
<p>RMIT University's <strong><em>Microelectronics and Materials Technology Centre
(</em></strong><strong><em>MMTC</em></strong><strong><em>)</em></strong> was
established in 1982 as a Commonwealth Government funded <strong><em>&ldquo;Centre
of Excellence&rdquo; </em></strong>specialising in microelectronics and
materials research. Today, the MMTC, which is located in the School of
Electrical and Computer Engineering, supports a range of major multi-disciplinary
research programs and industry related projects in integrated optics, photonic
systems, sensors, micro electromechanical systems (MEMS) and RF technology.</p>
<p>The Centre supports 10 academic and 6 full time research staff and employs
9 technical staff and project managers. The Centre also supports - 25 postgraduate
research students at Masters and PhD level and a growing number of postgraduate
coursework students <em>(</em><em>Chipskills</em><em> and Masters of </em><em>MEMS</em><em>)</em>.</p>
<p>The MMTC facilities comprise two cleanrooms, a vacuum laboratory, a packaging laboratory
and a microfluidics laboratory. In addition, there is an associated integrated
optics laboratory and sensor technology laboratory.</p>
<?php
	if ($mysql > 0) {
		require_once("common/show_mmtc_pubs.php");
		$arc_count = 0;
		$shown_head = false;
		$have_a_pub = false;
		while (list($pubk,$pubv) = each($pubdbname)) {
			$query = "select * from $pubv where find_in_set('mmtc', research_group) and material_kind like '%frontimg%' order by create_timestamp desc";
			$result = mysql_query($query);
			if ($result > 0) {
				$nitems = mysql_num_rows($result);
				$kind_arc_count = 0;
				for ($i=0; $i<$nitems; $i++) {
					$kind_arc_count++;
					$arc_count++;
					if ($kind_arc_count > $kind_arc_max || $arc_count > $front_arc_max) {
						break;
					}
					$row = mysql_fetch_object($result);
					if (!$shown_head) {
?>
<p>Highlights of our recent research publications include:</p>
<?php
						echo "<center>";
						table_header(2,2,NULL,NULL,NULL,"90%");
						$shown_head = true;
					}
					table_row();
					table_data("left","middle",NULL,1,"40%");
					show_pub_row($row, $pubv, 2, false, false);
					table_dend();
			 		$mat = array_map("rawurldecode", explode("&", $row->material));
			 		$matyp = array_map("rawurldecode", split("&", $row->material_kind));
	 				$upbase = pub_material_base($pubv)."$row->code/";
			 		reset($mat);
			 		reset($matyp);
				 	while (list($matk,$matv) = each($matyp)) {
						if ($matv=="frontimg") {
							table_data();
							echo "<a class='item-title-action' href='",
								"publications.php?publication_code={$row->code}&publication_table={$pubv}",
								"'>";
							image_tag($upbase.$mat[$matk],NULL,120);
							echo "</A>";
							table_dend();
							break;
				 		}	
					}
					table_rend();
				}
			}
		}
		if ($shown_head) {
			table_tail();
			echo "</center>";
		}
	}
	mmtc_page_bottom();
?>
