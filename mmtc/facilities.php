<?php
	error_reporting(3);
	import_request_variables("gpc");
	include_once("common/necessary.php");
	include_once("../common/common_mysql.php");
	require_once("login_chk.php");
	
	mmtc_page_top(
		"facilities",
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
	
	$inset_img_height = 120;
	
	if (isset($code)) {
		$query = "select * from facilities where code=$code";
		$result = mysql_query($query, $mysql);
		if ($result > 0) {
			$nitems = mysql_num_rows($result);
			if ($nitems > 0) {
				$row = mysql_fetch_object($result);
				div('item-head', $row->name);
				if ($row->location) {
					div('item-subhead', "Location: $row->location");
				}
				div('item-body');
	 			$para = array_map("rawurldecode", explode("&", $row->paragraph));
	 			$img = array_map("rawurldecode", explode("&", $row->image));
	 			$img_align = array_map("rawurldecode", explode("&", $row->image_align));
	 			$img_caption = array_map("rawurldecode", explode("&", $row->image_caption));
	 			// these are linked in a group ... the arrays will be numbered, unless something is
	 			// seriously gone titsup on the database
				$img_base = "{$upload_base}/fac{$row->code}/";
				// unindexed page just post first para and a single image
				$have_para = false;
				$have_img = false;
	 			while (list($k,$v) = each($para)) {
	 				if ($img[$k]) {
	 					$img_src = $img_base.$img[$k];
	 					if (file_exists($img_src)) {
	 						image_tag($img_base.$img[$k], NULL, NULL, $img_caption[$k], $img_caption[$k], NULL, $img_align[$k]);
						}
	 				}
	 				if ($v) {
	 					echo "<p>$v</p>";
	 				}
	 			}
				$eq_query = "select * from equipment where location_code='$row->code'";
				$eq_result = mysql_query($eq_query, $mysql);
				$n_eq_items = mysql_num_rows($eq_result);
				echo "<div style='margin:20px'>";
				echo "Key pieces of equipment kept here include:<br>";
				for($ei=0; $ei < $n_eq_items; $ei++) {
					$eqrow = mysql_fetch_object($eq_result);
					echo "<a href=\"equipment.php?code={$eqrow->code}\" style='margin:10px'>";
					echo $eqrow->name, " ($eqrow->full_name",")";
					echo "</a>";
					echo "<br>";
				}
				echo "</div>";
				div();
				exit;
			}
		}
	}
	$ncol = 2;
	$query = "select * from facilities";
	$result = mysql_query($query, $mysql);
	$have_header = false;
	if ($result > 0) {
		$nitems = mysql_num_rows($result);
		for($i=0; $i < $nitems; $i++) {
			$row = mysql_fetch_object($result);
			if (!$have_header) {
				$have_header = true;
?>
<p>The MMTC facilities comprise several specialised areas across the campus...</p>
<?php
				if ($ncol >= 2) {
					table_header(0,0,NULL,NULL,NULL,"80%");
					table_row();
				}
			}
		
			if ($ncol >= 2) {
				table_data(($i%2==0)?"left":"right","top");
			}
			echo anchor_str(
						$row->name,
						"facilities.php?code={$row->code}",
						NULL, "item-head"
					);
			if ($row->location) {
				div('item-subhead', "$row->location");
			}
			div('item-body');
 			$para = array_map("rawurldecode", explode("&", $row->paragraph));
 			$img = array_map("rawurldecode", explode("&", $row->image));
 			$img_align = array_map("rawurldecode", explode("&", $row->image_align));
 			$img_caption = array_map("rawurldecode", explode("&", $row->image_caption));
 			// these are linked in a group ... the arrays will be numbered, unless something is
 			// seriously gone titsup on the database
			$img_base = "{$upload_base}/fac{$row->code}/";
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
						echo "<A href=\"facilities.php?code={$row->code}\" class=\"item-head\">";
 						image_tag($img_base.$img[$k], NULL, $h, $img_caption[$k], $img_caption[$k], NULL, $ncol >= 2?NULL:$img_align[$k]);
 						echo "</A>";
					}
 				}
 				if ($v) {
 					if ($ncol >= 2) {
 						echo "<br clear='all'>";
 					}
 					$have_para = true;
 					echo "<p>$v</p>";
 				}
 				if ($have_para && $have_img) {
 					break;
 				}
 			}
 			
			$eq_query = "select * from equipment where location_code='$row->code'";
			$eq_result = mysql_query($eq_query, $mysql);
			$n_eq_items = mysql_num_rows($eq_result);
			echo "<div style='margin:20px'>";
			for($ei=0; $ei < $n_eq_items; $ei++) {
				$eqrow = mysql_fetch_object($eq_result);
				echo "<a href=\"equipment.php?code={$eqrow->code}\">";
				echo $eqrow->name;
				echo "</a>";
				echo "<br>";
			}
			echo "</div>";
			
			div();
			if ($ncol >= 2) {
				table_dend();
				if (($i+1)%$ncol == 0) {
					table_rend();
					table_row();
				}
			}
		}
	}
	if ($have_header) {
		if ($ncol >= 2) {
			table_rend();
			table_tail();
		}
	} else {
?>
<p>The MMTC facilities comprise two cleanrooms, a vacuum laboratory, a packaging laboratory
and a microfluidics laboratory. In addition, there is an associated integrated
optics laboratory and sensor technology laboratory.</p>
<?php
	}
	div();
	mmtc_page_bottom();
?>
