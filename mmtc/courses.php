<?php
	error_reporting(3);
	import_request_variables("gpc");
	include_once("common/necessary.php");
	include_once("../common/common_mysql.php");
	require_once("login_chk.php");
	
	mmtc_page_top(
		"courses",
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
	
	function contact_for($stnumber)
	{
		$cquery = "select * from people where stnumber='$stnumber'";
		$cresult = mysql_query($cquery);
		if ($cresult > 0) {
			$cnitems = mysql_num_rows($cresult);
			if ($cnitems > 0) {
				$crow = mysql_fetch_object($cresult);
				echo "<p>";
				echo "Contact: ";
				if ($crow->email) {
					echo anchor_str("$crow->title $crow->firstname $crow->surname", "mailto: $crow->email");
					echo "<br>";
					echo "Email: $crow->email";
				} else {
					echo "$crow->title $crow->firstname $crow->surname";
				}
				if ($crow->phone) {
					echo "<br>";
					echo "Phone: $crow->phone";
				}
				echo "</p>";
			}
		}
	}
	
	
	$inset_img_height = 120;
	if (isset($code)) {
		$query = "select * from courses where code=$code";
		$result = mysql_query($query);
		if ($result > 0) {
			$nitems = mysql_num_rows($result);
			if ($nitems > 0) {
				$row = mysql_fetch_object($result);
				div('item-head', $row->name);
				if ($eqlabels[$eqt_k]) {
					div('item-subhead', "&nbsp;&nbsp;Course Level: ",$eqlabels[$eqt_k]);
				}
				if ($row->prerequisites) {
					div('item-body', "&nbsp;&nbsp;Prerequisite: $row->prerequisites");
				}
				div('item-body');
	 			$para = array_map("rawurldecode", explode("&", $row->paragraph));
	 			$img = array_map("rawurldecode", explode("&", $row->image));
	 			$img_align = array_map("rawurldecode", explode("&", $row->image_align));
	 			$img_caption = array_map("rawurldecode", explode("&", $row->image_caption));
	 			// these are linked in a group ... the arrays will be numbered, unless something is
	 			// seriously gone titsup on the database
				$img_base = "{$upload_base}/crs{$row->code}/";
				// unindexed page just post first para and a single image
				$have_para = false;
				$have_img = false;
				$bullets_shown = false; // show after first para...
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
	 				if (!$bullets_shown) {
	 					$bullets_shown = true;
	 					if ($row->points) {
	 						$bulls = array_map("rawurldecode", explode("&", $row->points));
	 						echo "<ul>";
	 						while (list($bk, $bv)=each($bulls)) {
	 							if ($bv) {
		 							echo "<li>",$bv,"</li>";
		 						}
	 						}
	 						echo "</ul>";
	 					}
	 				}
	 			}
	 			if ($row->contact) {
	 				contact_for($row->contact);
	 			}
	 			$dox = array_map("rawurldecode", explode("&", $row->document));
	 			$doxc = array_map("rawurldecode", explode("&", $row->document_caption));
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
	 						echo anchor_str($tag, $dox_src);
	 					}
	 				}
	 			}
				div();
				exit;
			}
		}
	}
	require_once(in_parent_path("/common/sqlschema_types.php"));
	if (file_exists("$schema_base_directory/courses_types.ser")) {
		$unc = uncache_variable("$schema_base_directory/courses_types.ser");
		$eqt = $unc["course-type"];
		if ($eqt) {
			$eqtypes = $eqt->ValueNameArray();
			$eqlabels = $eqt->ValueLabelArray();
		}
	}
	if (!$eqtypes) {
		$eqtypes = array("");
		$eqlabels = array("");
	}
	$have_header = false;
	while (list($eqt_k, $eqt_v) = each($eqtypes)) {
		$query = "select * from courses";
		if ($eqt_v) {
			$query .= " where type='$eqt_v'";
		}
		$result = mysql_query($query);
		if ($result > 0) {
			$nitems = mysql_num_rows($result);
			if ($nitems > 0) {
				if (!$have_header) {
					$have_header = true;
?>
<?php
					table_header(0,0,NULL,NULL,NULL,"80%");
					table_row();
				} else {
					table_row();
				}
				if ($eqlabels[$eqt_k]) {
//					echo "<td colspan=2>";
//					div("item-head", "$eqlabels[$eqt_k] Courses");
//					echo "</td>";
//					table_rend();
//					table_row();
				}
			}
			for($i=0; $i < $nitems; $i++) {
				$row = mysql_fetch_object($result);
			
				table_data("left","top");
				echo anchor_str(
							$row->name,
							"courses.php?code={$row->code}",
							NULL, "item-head"
						);
				if ($eqlabels[$eqt_k]) {
					div('item-subhead', "&nbsp;&nbsp;Course Level: ",$eqlabels[$eqt_k]);
				}
				if ($row->prerequisites) {
					div('item-body', "&nbsp;&nbsp;Prerequisite: $row->prerequisites");
				}
				div('item-body');
	 			$para = array_map("rawurldecode", explode("&", $row->paragraph));
	 			$img = array_map("rawurldecode", explode("&", $row->image));
	 			$img_align = array_map("rawurldecode", explode("&", $row->image_align));
	 			$img_caption = array_map("rawurldecode", explode("&", $row->image_caption));
	 			// these are linked in a group ... the arrays will be numbered, unless something is
	 			// seriously gone titsup on the database
				$img_base = "{$upload_base}/crs{$row->code}/";
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
							echo "<A href=\"courses.php?code={$row->code}\" class=\"item-head\">";
	 						image_tag($img_base.$img[$k], NULL, $h, $img_caption[$k], $img_caption[$k], NULL, $img_align[$k]);
	 						echo "</A>";
						}
	 				}
	 				if ($v) {
	 					$have_para = true;
	 					echo "<p>$v</p>";
	 				}
	 				if ($have_para && $have_img) {
	 					break;
	 				}
	 			}
	 			if ($row->contact) {
	 				contact_for($row->contact);
	 			}
				div();
				table_dend();
				table_rend();
				table_row();
			}
		}
		table_rend();
	}
	if ($have_header) {
		table_rend();
		table_tail();
	} else {
?>
<p>The Centre supports 25 postgraduate
research students at Masters and PhD level and a growing number of postgraduate
coursework students <em>(</em><em>Chipskills</em><em> and Masters of </em><em>MEMS</em><em>)</em>.</p>
<?php
	}
	div();
	mmtc_page_bottom();
?>
