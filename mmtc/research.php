<?php
	error_reporting(3);
	import_request_variables("gpc");
	include_once("common/necessary.php");
	require_once("login_chk.php");
	include_once("../common/common_mysql.php");
	mmtc_page_top(
		"research",
		"RMIT MMTC (Microelectronics and Materials Technology Centre)",
		"page-main");
	div('wide-margin');
	if (!isset($mysql) || $mysql < 0) {
		$mysql = get_database(
					$schema_database_name,
					$database_host,
					$database_pleb_user,
					$database_pleb_passwd);
	}
	
?>
<p>The MMTC supports a range of major multi-disciplinary
research programs and industry related projects in integrated optics, photonic
systems, sensors, micro electromechanical systems (MEMS) and RF technology.</p>
<?php
	$query = "select * from research_project where find_in_set('mmtc',research_group)";
	$result = mysql_query($query, $mysql);
	$have_header = false;
	$inset_img_height = 220;
	if ($result > 0) {
		$nitems = mysql_num_rows($result);
		for($i=0; $i < $nitems; $i++) {
			$row = mysql_fetch_object($result);
			if (!$have_header) {
				$have_header = true;
?>
<p>Current major projects of interest include...</p>
<?php
			}
		
			div("item-head", $row->name);
			div('item-body');
 			$para = array_map("rawurldecode", explode("&", $row->paragraph));
 			$img = array_map("rawurldecode", explode("&", $row->image));
 			$img_align = array_map("rawurldecode", explode("&", $row->image_align));
 			$img_caption = array_map("rawurldecode", explode("&", $row->image_caption));
 			// these are linked in a group ... the arrays will be numbered, unless something is
 			// seriously gone titsup on the database
			$img_base = "../../destrpc/uploaded/rch{$row->code}/";
			// unindexed page just post first para and a single image
			$have_para = false;
			$have_img = false;
 			while (list($k,$v) = each($para)) {
 				if ($img[$k]) {
 					$img_src = $img_base.$img[$k];
 					if (file_exists($img_src)) {
 						$have_img = true;
 						$dims = getimagesize($img_src);
 						$h = ($dims[1] > $inset_img_height)? $inset_img_height: $dims[1];
						image_tag($img_base.$img[$k], NULL, $h, $img_caption[$k], $img_caption[$k], NULL, $img_align[$k]);
 					}
 				}
 				if ($v) {
 					$have_para = true;
 					echo "<p>$v</p>";
 				}
 			}
 			
			
			div();
 			$dox = array_map("rawurldecode", explode("&", $row->document));
 			$doxc = array_map("rawurldecode", explode("&", $row->document_caption));
			div('item-subhead');
 			while (list($k,$v) = each($dox)) {
 				if ($v) {
 					$dox_src = $img_base.$v;
 					if (file_exists($dox_src)) {
 						$tag = "Download ";
 						if ($doxc[$k]) {
 							$tag .= $doxc[$k];
 						} else {
 							$tag .= " documentation";
 						}
 						echo anchor_str($tag, $dox_src),"<br>";
 					}
 				}
 			}
			div();
			echo "<br clear='all'>";
		}
	}
	if ($have_header) {
	}
div();
mmtc_page_bottom();
?>
