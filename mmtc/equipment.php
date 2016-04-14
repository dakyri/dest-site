<?php
	error_reporting(3);
	import_request_variables("gpc");
	include_once("common/necessary.php");
	include_once("../common/common_mysql.php");
	require_once("login_chk.php");
	
	mmtc_page_top(
		"equipment",
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
		$query = "select * from equipment where code=$code";
		$result = mysql_query($query);
		if ($result > 0) {
			$nitems = mysql_num_rows($result);
			if ($nitems > 0) {
				$row = mysql_fetch_object($result);
				div('item-head', $row->name);
				if ($row->full_name && $row->manufacturer) {
					div('item-subhead', "($row->full_name, $row->manufacturer)");
				} elseif ($row->full_name) {
					div('item-subhead', "($row->full_name)");
				} elseif ($row->manufacturer) {
					div('item-subhead', "($row->manufacturer)");
				}
				if ($row->location) {
					div('item-subhead', "Location: ".anchor_str($row->location, "facilities.php?code=$row->location_code"));
				}
				div('item-body');
	 			$para = array_map("rawurldecode", explode("&", $row->paragraph));
	 			$img = array_map("rawurldecode", explode("&", $row->image));
	 			$img_align = array_map("rawurldecode", explode("&", $row->image_align));
	 			$img_caption = array_map("rawurldecode", explode("&", $row->image_caption));
	 			// these are linked in a group ... the arrays will be numbered, unless something is
	 			// seriously gone titsup on the database
				$img_base = "{$upload_base}/equ{$row->code}/";
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
				div();
	 			if ($login_type >= LOGIN_ADMIN || 
	 					($login_type >= LOGIN_USER && $login_user_row->stnumber == $row->supervisor)) {
	 				$tr_stnum = array_map("rawurldecode", explode("&", $row->trained_stnumber));
	 				reset($tr_stnum);
	 				$n_trstnum = 0;
	 				for ($tsti=0; $tsti<count($tr_stnum); $tsti++) {
	 					if ($tr_stnum[$tsti]) {
	 						$n_trstnum++;
	 					}
	 				}
	 				if ($n_trstnum > 0) {
						div('item-subhead');
	 					echo "Trained Users:<br>";
		 				$tr_firtstnm = array_map("rawurldecode", explode("&", $row->trained_firstname));
		 				$tr_surnm = array_map("rawurldecode", explode("&", $row->trained_surname));
		 				$tr_eml = array_map("rawurldecode", explode("&", $row->trained_email));
	 					for ($tsti=0; $tsti<count($tr_stnum); $tsti++) {
		 					if ($tr_stnum[$tsti]) {
		 						$tru_name =  $tr_stnum[$tsti];
								if ($row->sup_firstname && $row->sup_surname) {
									$tru_name .= " (${tr_firtstnm[$tsti]} ${tr_surnm[$tsti]})";
								}
								if ($tr_eml[$tsti]) {
									$tru_name .= ", ${tr_eml[$tsti]}";
									echo anchor_str($tru_name, "mailto:${tr_eml[$tsti]}");
								} else {
									echo $tru_name;
								}
		 						echo "<br>";
		 					}
	 					}
	 					div();
		 			}
	 			}
				if ($row->supervisor || $row->sup_email) {
					echo "<br>";
					div('item-subhead');
					if ($row->sup_firstname && $row->sup_surname) {
						$sup_name = "$row->sup_firstname $row->sup_surname";
						if ($row->sup_email) {
							$sup_name .= " ($row->sup_email)";
						}
					} else if ($row->sup_email) {
						$sup_name = $row->sup_email;
					} else {
						$sup_name = $row->supervisor;
					}
					echo "All enquiries to: ";
					if ($row->sup_email) {
						echo anchor_str($sup_name, "mailto:$row->sup_email");
					} else {
						echo $sup_name;
					}
					echo "<br>";
					echo "<br>";
	 				div();
				}
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
	 			if ($login_type >= LOGIN_ADMIN || 
	 					($login_type >= LOGIN_USER && $login_user_row->stnumber == $row->supervisor)) {
					div('item-subhead');
					echo anchor_str("[Edit this entry]", "ed_schema_db.php?sqlschema=equipment&edit_code={$row->code}", NULL, "item-title-action");
					div();
	 			}
				exit;
			}
		}
	}
	$ncol = 2;
	require_once(in_parent_path("/common/sqlschema_types.php"));
	if (file_exists("$schema_base_directory/equipment_types.ser")) {
		$unc = uncache_variable("$schema_base_directory/equipment_types.ser");
		$eqt = $unc["equipment-type"];
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
		$query = "select * from equipment";
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
					if ($ncol >= 2) {
						table_header(0,0,NULL,NULL,NULL,"80%");
						table_row();
					}
				} else {
					if ($ncol >= 2) {
						table_row();
					}
				}
				if ($eqlabels[$eqt_k]) {
					if ($ncol >= 2) {
						echo "<td colspan=2>";
					}
					div("item-head", "$eqlabels[$eqt_k] Equipment");
					if ($ncol >= 2) {
						echo "</td>";
						table_rend();
						table_row();
					}
				}
			}
			for($i=0; $i < $nitems; $i++) {
				$row = mysql_fetch_object($result);
			
				if ($ncol >= 2) {
					table_data(($i%2==0)?"left":"right","top");
				}
				echo anchor_str(
							$row->name,
							"equipment.php?code={$row->code}",
							NULL, "item-head"
						);
				if ($row->full_name && $row->manufacturer) {
					div('item-subhead', "($row->full_name, $row->manufacturer)");
				} elseif ($row->full_name) {
					div('item-subhead', "($row->full_name)");
				} elseif ($row->manufacturer) {
					div('item-subhead', "($row->manufacturer)");
				}
				if ($row->location) {
					div('item-subhead', anchor_str($row->location, "facilities.php?code=$row->location_code"));
				}
				div('item-body');
	 			$para = array_map("rawurldecode", explode("&", $row->paragraph));
	 			$img = array_map("rawurldecode", explode("&", $row->image));
	 			$img_align = array_map("rawurldecode", explode("&", $row->image_align));
	 			$img_caption = array_map("rawurldecode", explode("&", $row->image_caption));
	 			// these are linked in a group ... the arrays will be numbered, unless something is
	 			// seriously gone titsup on the database
				$img_base = "{$upload_base}/equ{$row->code}/";
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
							echo "<A href=\"equipment.php?code={$row->code}\" class=\"item-head\">";
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
				if ($row->supervisor || $row->sup_email) {
					echo "<br>";
					if ($row->sup_firstname && $row->sup_surname) {
						$sup_name = "$row->sup_firstname $row->sup_surname";
						if ($row->sup_email) {
							$sup_name .= " ($row->sup_email)";
						}
					} else if ($row->sup_email) {
						$sup_name = $row->sup_email;
					} else {
						$sup_name = $row->supervisor;
					}
					echo "All enquiries to: ";
					if ($row->sup_email) {
						echo anchor_str($sup_name, "mailto:$row->sup_email");
					} else {
						echo $sup_name;
					}
					echo "<br>";
				}
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
		table_rend();
	}
	if ($have_header) {
		if ($ncol >= 2) {
			table_rend();
			table_tail();
		}
	} else {
?>
<p>MMTC technical resources include several mask aligners, a sputterer, an evaporation deposition
system.</p>
<?php
	}
	div();
	mmtc_page_bottom();
?>
