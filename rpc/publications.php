<?php
	import_request_variables("gpc");
	error_reporting(0);
	require_once("common/necessary.php");
	require_once("../common/sqlschema_types.php");
	
	require_once("searchparams.php");
	require_once("login_chk.php");
	
	$front_img_height = 200;
//echo "@",$search_stnumber,"@",$search_rmit_author;
//	function sqlschema_list_to_array(&$field)
//	{
//		return array_map("rawurldecode", explode("&", $field));
//	}
	setcookie("return_rpc_url", "publications.php?".search_param_url());
	$show_arc = true;
	function supporting_materials($row, $base)
	{
		if ($row->description) {
			echo "<p>$row->description</p>\n";
		}
		if ($row->web) {
			echo "<br>Web site: ";
			echo anchor_str($row->web, $row->web, "_blank");
			echo "\n";
		}
 		$mat = split("&", $row->material);
 		$matyp = split("&", $row->material_kind);
 		$has_sm = false;
 		for ($aui=0; $aui<count($mat); $aui++) {
 			if ($mat[$aui]) {
 				$has_sm = true;
 				break;
 			}
 		}
 		if ($has_sm) {
			echo "<BR>&nbsp;&nbsp;Supporting materials:<br>";
			echo "\n";
			table_header(1, 1, "", "", 0);
			echo "\n";
	 		for ($aui=0; $aui<count($mat); $aui++) {
	 			if ($mat[$aui]) {
		 			table_row();
					echo "\n";
		 			$nm = rawurldecode($mat[$aui]);
		 			table_data_string("&nbsp;&nbsp;");
					echo "\n";
	 				table_data_string(anchor_str($nm, $base.$nm, "", ""));
					echo "\n";
	 				table_data_string("[".rawurldecode($matyp[$aui])."]");
					echo "\n";
	 				table_rend();
					echo "\n";
	 			}
	 		}
			table_tail();
			echo "\n";
		}
	}

	function subject_breakdown($row, $show_subjects)
	{
		if ($show_subjects && $show_subjects != "none") {
			global $rfcd_type;
			table_header(1, 1, "", "", 1);
			echo "\n";
			$stn = split("&", $row->rfcd_code);
			$sura = split("&", $row->rfcd_split);
			for ($aui=0; $aui<count($stn); $aui++) {
				if ($sura[$aui]) {
					table_row();
					$subjn = "";
					switch ($show_subjects) {
						case "rfc":
							$subjn = rawurldecode($stn[$aui]);
							break;
						case "both":
							$subjn = $rfcd_type->Label(rawurldecode($stn[$aui]))." (".rawurldecode($stn[$aui]).")";
							break;
						case "name":
						default:
							$subjn = $rfcd_type->Label(rawurldecode($stn[$aui]));
							break;
					}
					
					table_data_string($subjn);
					echo "\n";
					table_data_string(rawurldecode($sura[$aui])."%");
					echo "\n";
					table_rend();
					echo "\n";
				}
			}
			table_tail();
			echo "\n";
		}
	}
	
	function author_list($row)
	{
		global $rsc_type;
	
		table_header(1, 1, "", "", 1);
		$stn = split("&", $row->stnumber);
		$sura = split("&", $row->surname);
		$fura = split("&", $row->firstname);
		$ata= split("&", $row->author_title);
		$typa = split("&", $row->type);
		$sca= split("&", $row->school_code);
		$son = split("&", $row->school_org_name);
		for ($aui=0; $aui<count($stn); $aui++) {
			if ($sura[$aui]) {
				table_row();
				echo "\n";
				echo "<td>",rawurldecode($ata[$aui]), " ",rawurldecode($fura[$aui]), " ", rawurldecode($sura[$aui]),"</td>";
				table_data_string(rawurldecode($stn[$aui]));
				echo "\n";
				$rmit_school_nm = $rsc_type->Label(rawurldecode($sca[$aui]));
				table_data_string($sca[$aui] >= 0?("$rmit_school_nm (RMIT)"):rawurldecode($son[$aui]));
				echo "\n";
				table_rend();
				echo "\n";
			}
		}
		table_tail();
	}
	
	function scholar_searches($row, $auth=NULL, $dobr=false)
	{
		if ($auth) {
			echo "[";
			echo anchor_str(
				"Author/Title search on Google Scholar",
				"http://scholar.google.com/scholar?hl=en&lr=&q=".urlencode("author:$auth \"$row->title\""),
				NULL, "item-edit", "");
			echo "]";
		}
		if ($dobr) {
			br();
			echo "\n";
		}
		echo "[";
		echo anchor_str(
				"Title search on Google Scholar",
				"http://scholar.google.com/scholar?hl=en&lr=&q=".urlencode("\"$row->title\""),
				NULL, "item-edit", "");
		echo "]";
		if ($dobr) {
			br();
			echo "\n";
		}
		echo "[";
		echo anchor_str(
				"Keyword search on Google Scholar",
				"http://scholar.google.com/scholar?hl=en&lr=&q=".urlencode("$row->keywords"),
				NULL, "item-edit", "");
		echo "]";
		if ($dobr) {
			br();
			echo "\n";
		}
	}
	
	function publication_publisher_details(&$table, &$row)
	{
		switch ($table) {
			case "book":
				if ($row->publisher) {
					echo "$row->publisher";
					if ($row->publication_place) {
						echo ", $row->publication_place";
					}
				}
				echo " ($row->publication_year)";
				break;
			case "chapter":
				echo "$row->book_title";
				if ($row->start_page && $row->end_page) {
					echo ", $row->start_page-$row->end_page";
				}
				if ($row->publisher) {
					echo ", $row->publisher";
				}
				if ($row->publication_place) {
					echo ", $row->publication_place";
				}
				echo " ($row->publication_year)";
				break;
			case "journal":
				echo "$row->journal_name";
				if ($row->volume) {
					echo "<b> $row->volume</b>";
				}
				if ($row->edition && ereg("[[:digit:]]+",$row->editon)) {
					echo ", $row->edition";
				}
				if ($row->start_page && $row->end_page) {
					echo ", $row->start_page-$row->end_page";
				}
				echo " ($row->publication_year)";
				break;
			case "conference":
				echo "$row->publication_title";
				if ($row->editor) {
					echo ", (Ed:$row->editor)";
				}
				if ($row->start_page && $row->end_page) {
					echo ", $row->start_page-$row->end_page";
				}
				if ($row->publisher) {
					echo ", $row->publisher";
				}
				if ($row->publication_place) {
					echo ", $row->publication_place";
				}
				echo " ($row->publication_year)";
				break;
			default:
				if ($row->publisher) {
					echo ", $row->publisher";
				}
				if ($row->publication_place) {
					echo ", $row->publication_place";
				}
				echo " ($row->publication_year)";
				break;
		}
	}
	
	function arc_format($row, $table, $item_count)
	{
		global	$login_user_row;
		global	$login_type;
		global	$show_citations;
		global	$show_timestamp;
		global	$show_detailed_listing;
		global	$front_img_height;
		global	$show_subjects;
		global	$rfcd_type;

		if ($row->title) {
	 		$upbase = upload_base($table);

	 		$has_support_material = false;
	 		if ($login_type >= LOGIN_ADMIN || 
	 				(  $login_user_row &&
	 					$row->first_author_stnumber == $login_user_row->stnumber)) {
	 			$editable_publication = true;
	 		} else {
		 		$editable_publication = false;
			}
			table_row();
			table_data("left", "top");
			if (is_numeric($item_count)) {
				echo $item_count, ". ";
			}
			table_dend();
			
			table_data("left", "top");
			echo "<b>",$row->title, "</b>\n";
			table_dend();
			table_rend();
			
			$stn = sqlschema_list_to_array($row->stnumber);
			$sura = sqlschema_list_to_array($row->surname);
			$fura = sqlschema_list_to_array($row->firstname);
			echo "<tr><td>&nbsp;</td><td>\n";
			for ($aui=0; $aui<count($stn); $aui++) {
				if ($sura[$aui] || $stn[$aui] || $fura[$aui]) {
					$fnm = explode(" ", $fura[$aui]);
					$cnm = "";
					reset($fnm);
					while (list($k, $v) = each($fnm)) {
						if ($v  && $v{0}) {
							$cnm .= strtoupper($v{0});
							$cnm .= ". ";
						}
					}
					$cnm .= $sura[$aui];
					if ($aui > 0) {
						echo ", ";
					}
					$srch_st = $stn[$aui];
					if ($srch_st) {
						if ($login_user_row && $login_user_row->stnumber == $srch_st) {
							$editable_publication = true;
						}
						echo anchor_str($cnm, "publications.php?".search_param_url($srch_st, NULL, "publications"));
					} else {
						echo anchor_str($cnm, "publications.php?".search_param_url(NULL, $sura[$aui], "publications"));
//						echo $cnm;
					}
				}
			}
			echo "</td></tr>\n";
			echo "<tr><td>&nbsp;</td><td>\n";
			publication_publisher_details($table, $row);
			echo "</td></tr>\n";
			
	 		$mat = split("&", $row->material);
	 		$matyp = split("&", $row->material_kind);
	 		
			if ($show_subjects && $show_subjects != "none") {
				$rfcd_codes = sqlschema_list_to_array($row->rfcd_code);
				$rfcd_splits = sqlschema_list_to_array($row->rfcd_split);
				$shown_tb = false;
				for ($aui=0; $aui< count($rfcd_codes); $aui++) {
					if ($rfcd_codes[$aui] && $rfcd_codes[$aui] > 0) {
						if (!$shown_tb) {
							$shown_tb = true;
							echo "<tr><td>&nbsp;</td><td>\n";
							echo "<b>Subjects: </b>";
						} else {
							echo ", ";
						}
						switch ($show_subjects) {
							case "rfc":
								$subjn = $rfcd_codes[$aui];
								break;
							case "both":
								$subjn = $rfcd_type->Label($rfcd_codes[$aui])." (".$rfcd_codes[$aui].")";
								break;
							case "name":
							default:
								$subjn = $rfcd_type->Label($rfcd_codes[$aui]);
								break;
						}
						echo $subjn;
					}
				}
				if ($shown_tb) {
					echo "</td></tr>\n";
				}
			}
			if ($show_detailed_listing) {
				if ($row->keywords) {
					echo "<tr><td>&nbsp;</td><td>\n";
					echo "<b>Keywords: </b>", $row->keywords;
					echo "</td></tr>\n";
				}
				reset($matyp);
			 	while (list($k,$v) = each($matyp)) {
					if ($v=="frontimg") {
						echo "<tr><td>&nbsp;</td><td>\n";
						$img_src = "$upbase$row->code/".$mat[$k];
 						$dims = getimagesize($img_src);
  						$h = ($dims[1] > $front_img_height)? $front_img_height: $dims[1];
 						image_tag($img_src, NULL, $h, NULL, NULL, NULL, "left");
						echo "</td></tr>";
			 		}	
				}
			}
			
	 		for ($aui=0; $aui<count($mat); $aui++) {
	 			if ($mat[$aui] && ($matyp[$aui]=="paper" || $matyp[$aui]=="chapter")) {
	 				$has_support_material = true;
	 				break;
	 			}
	 		}
	 		if ($has_support_material || $editable_publication) {
				echo "<tr><td>&nbsp;</td><td>\n";
				if ($row->description) {
					echo "[",
						anchor_str(
							"View Abstract",
							"publications.php?".search_param_url()."&view_abstract_code={$row->code}&view_abstract_table={$table}",
							NULL, "item-action"),
							"]\n";
				}
				if ($has_support_material) {
					echo "[",
						anchor_str(
							"Download ".ucfirst($matyp[$aui]),
							"$upbase$row->code/".$mat[$aui],
							NULL, "item-action"),
						"]\n";
				}
				if ($editable_publication) {
					if ($login_type >= LOGIN_ADMIN) {
						echo "[",
							anchor_str(
								"Edit",
								"admin/ed_schema_db.php?sqlschema=$table&edit_pubs_for_user={$row->first_author_stnumber}&edit_code={$row->code}",
								NULL, "item-edit"),
							"]\n";
						echo "[",
							anchor_str(
								"Delete",
								"admin/ed_schema_db.php?sqlschema=$table&delete_single=true&edit_pubs_for_user={$row->first_author_stnumber}&edit_code={$row->code}",
								NULL, "item-edit"),
							"]\n";
					} else {
						echo "[",
							anchor_str(
								"Edit",
								"edit/user_ed_schema_db.php?sqlschema=$table&edit_pubs_for_user={$login_user_row->stnumber}&edit_code={$row->code}",
								NULL, "item-edit"),
							"]\n";
						echo "[",
							anchor_str(
								"Delete",
								"edit/user_ed_schema_db.php?sqlschema=$table&delete_single=true&edit_pubs_for_user={$login_user_row->stnumber}&edit_code={$row->code}",
								NULL, "item-edit"),
							"]\n";
					}
					echo "[",
							anchor_str(
								"Generate PDF Cover Sheet",
								"edit/pdf_cover_sheet.php?stnumber={$row->first_author_stnumber}&table={$table}&code={$row->code}",
								NULL, "item-edit"),
							"]\n";
				}
				echo "</td></tr>\n";
				echo "<tr><td>&nbsp;</td><td>\n";
				if ($show_citations) {
					scholar_searches($row);
				}
				if ($show_timestamp) {
					timestamp_display($row);
				}
				echo "</td></tr>\n";
			}
	 		
			table_row();
			echo "<td>&nbsp;</td>\n";
			table_rend();
			
			return true;
		}
		return false;
	}
	
	function parc_format($row, $item_count)
	{
		global	$login_user_row;
		global	$login_type;
		global	$show_citations;
		global	$show_timestamp;
		global	$show_detailed_listing;
		
		if ($row->name) {
	 		if ($login_type >= LOGIN_ADMIN || 
	 				(  $login_user_row &&
	 					$row->owning_researcher_stnumber == $login_user_row->stnumber)) {
	 			$editable_publication = true;
	 		} else {
		 		$editable_publication = false;
			}
			table_row();
			table_data("left", "top");
			if (is_numeric($item_count)) {
				echo $item_count, ". ";
			}
			table_dend();
			
			table_data("left", "top");
			echo "<b>",$row->name, "</b>\n";
			table_dend();
			table_rend();
			
			if ($show_detailed_listing && $row->description) {
				echo "<tr><td>&nbsp;</td><td>";
				table_header(2,2);
				echo "<tr><td>&nbsp;&nbsp;</td><td>$row->description</td></tr>";
				table_tail();
			}
			if ($row->owning_researcher_stnumber) {
				$au_query = "select * from people";
				$au_query .= " where (stnumber='$row->owning_researcher_stnumber')";
//				echo $au_query;
				$au_result = mysql_query($au_query);
				if (!$au_result) {
					echo "Database error: ", mysql_error();
				} else {
					$n_au_found = mysql_num_rows($au_result);
					if ($n_au_found >0) {
						$au_row = mysql_fetch_object($au_result);
						echo "<tr><td>&nbsp;</td><td>";
						echo "Principal Researcher:&nbsp;",
							$au_row->title?"$au_row->title ":"",
							"$au_row->firstname $au_row->surname";
						echo "</td></tr>";
					}
				}				
			}
			
			$stn = sqlschema_list_to_array($row->stnumber);
			$sura = sqlschema_list_to_array($row->surname);
			$fura = sqlschema_list_to_array($row->firstname);
			echo "<tr><td>&nbsp;</td><td>Chief Investigators: \n";
			for ($aui=0; $aui<count($stn); $aui++) {
				if ($sura[$aui]) {
					$fnm = explode(" ", $fura[$aui]);
					$cnm = "";
					reset($fnm);
					while (list($k, $v) = each($fnm)) {
						if ($v  && $v{0}) {
							$cnm .= strtoupper($v{0});
							$cnm .= ". ";
						}
					}
					$cnm .= $sura[$aui];
					if ($aui > 0) {
						echo ", ";
					}
					$srch_st = $stn[$aui];
					if ($srch_st) {
						if ($login_user_row && $login_user_row->stnumber == $srch_st) {
							$editable_publication = true;
						}
						echo anchor_str($cnm, "publications.php?".search_param_url($srch_st, NULL, "publications"));
					} else {
						echo $cnm;
					}
				}
			}
			echo "</td></tr>\n";
			if ($show_detailed_listing && $row->web) {
				$url = ereg('^http://', $row->web)?$row->web:"http://$row->web";
				echo "<tr><td>&nbsp;</td><td>Web: \n",
					anchor_str(
						"$row->web",
						$url,
						NULL, "item-edit"),"</td></tr>";
			}
			if ($row->start_year) {
				if ($row->end_year) {
					echo "<tr><td>&nbsp;</td><td>Project running from years: $row->start_year-$row->end_year</td>";
				} else {
					echo "<tr><td>&nbsp;</td><td>Commencing year: $row->start_year</td>";
				}
			}
			if ($show_detailed_listing && $row->keywords) {
				echo "<tr><td>&nbsp;</td><td>Keywords: $row->keywords</td>";
			}
			$kw = split('[, ]', $search_keywords);
			$au_where = "";
			reset($kw);
			while (list($au_key,$au_val) = each($kw)) {
				if ($au_val) {
					$au_where = like_clause($au_where, "keywords","%$au_val%", "||");
				}
			}
			
			$stn = sqlschema_list_to_array($row->income_year);
			$sura = sqlschema_list_to_array($row->income_amount);
			$has_income = false;
			for ($aui=0; $aui<count($stn); $aui++) {
				if ($sura[$aui] > 0) {
					$has_income = true;
				}
			}
			if ($has_income) {
				echo "<tr><td>&nbsp;</td><td><br>\n";
				table_header(2,2,NULL, NULL, 1);
				echo "<tr><td>Year</td><td>Research Income</td></tr>";
				for ($aui=0; $aui<count($stn); $aui++) {
					if ($sura[$aui] > 0) {
						$cnm = $sura[$aui];
						$srch_st = $stn[$aui];
						if ($cnm && $srch_st) {
							echo "<tr><td>$srch_st</td><td>\$$cnm</td></tr>";
						}
					}
				}
				table_tail();
				echo "</td></tr>\n";
			}
			
	 		if ($editable_publication) {
	 			$upbase = upload_base($table);

				echo "<tr><td>&nbsp;</td><td>\n";
				if ($editable_publication) {
					if ($login_type >= LOGIN_ADMIN) {
						echo "[",
							anchor_str(
								"Edit",
								"admin/ed_schema_db.php?sqlschema=research_project&edit_pubs_for_user={$row->owning_researcher_stnumber}&edit_code={$row->code}",
								NULL, "item-edit"),
							"]\n";
						echo "[",
							anchor_str(
								"Delete",
								"admin/ed_schema_db.php?sqlschema=research_project&delete_single=true&edit_pubs_for_user={$row->owning_researcher_stnumber}&edit_code={$row->code}",
								NULL, "item-edit"),
							"]\n";
					} else {
						echo "[",
							anchor_str(
								"Edit",
								"edit/user_ed_schema_db.php?sqlschema=research_project&edit_pubs_for_user={$login_user_row->stnumber}&edit_code={$row->code}",
								NULL, "item-edit"),
							"]\n";
						echo "[",
							anchor_str(
								"Delete",
								"edit/user_ed_schema_db.php?sqlschema=research_project&delete_single=true&edit_pubs_for_user={$login_user_row->stnumber}&edit_code={$row->code}",
								NULL, "item-edit"),
							"]\n";
					}
// we might conceivably want to do this ... but not right now 
//					echo "[",
//							anchor_str(
//								"Generate PDF Cover Sheet",
//								"edit/pdf_cover_sheet.php?stnumber={$row->first_author_stnumber}&table=research_project&code={$row->code}",
//								NULL, "item-edit"),
//							"]\n";
				}
				echo "</td></tr>\n";
				echo "<tr><td>&nbsp;</td><td>\n";
				if ($show_timestamp) {
					timestamp_display($row);
				}
				echo "</td></tr>\n";
			}
	 		
			table_row();
			echo "<td>&nbsp;</td>\n";
			table_rend();
			
			return true;
		}
		return false;
	}

	function aurc_format($row, $item_count)
	{
		global	$login_user_row;
		global	$login_type;
		global	$show_citations;
		global	$show_timestamp;
		global	$rsc_type;
		global	$show_detailed_listing;
		
 		if ($login_type >= LOGIN_ADMIN || 
 				(  $login_user_row &&
 					$row->owning_researcher_stnumber == $login_user_row->stnumber)) {
 			$editable_publication = true;
 		} else {
	 		$editable_publication = false;
		}
		$stn = sqlschema_list_to_array();
		if ($row->surname) {
			if ($row->author_title) {
				$cnm = "$row->author_title ";
			} else {
				$cnm = "";
			}
			$cnm .= "$row->firstname $row->surname";
// possible but debatable ... maybe this should be a fixed. people shouldn't in any case be able to change pwi
//				if ($login_user_row && $login_user_row->stnumber == $row->stnumber) {
//					$editable_publication = true;
//				}
			echo "<tr><td>$item_count.</td><td>";
			if ($row->stnumber) {
				echo anchor_str($cnm, "publications.php?".search_param_url($row->stnumber, NULL, "publications"));
				echo "<td>RMIT <u>$row->stnumber</u>";
				if ($row->school_code) {
					echo ", ", $rsc_type->Label($row->school_code);
				}
				echo "</td>";
			} else {
				echo anchor_str($cnm, "publications.php?".search_param_url(NULL, $row->surname, "publications"));
				echo "<td>";
				if ($row->school_org_name) {
					echo "$row->school_org_name</td>";
				}
				echo "</td>";
			}
			echo "<td>";
			if ($row->author_code) {
				echo "&nbsp;&nbsp;&nbsp;&nbsp;<b>Author Code&nbsp;$row->author_code</b>&nbsp;&nbsp;&nbsp;&nbsp;";
			}
			echo "</td>";
			if ($show_timestamp) {
				echo "<td>";
				if ($row->create_timestamp || $row->edit_timestamp) {
					echo "Timestamp ";
					if ($row->create_timestamp) {
						echo $row->create_timestamp;
					}
					echo "&nbsp;/&nbsp;";
					if ($row->edit_timestamp) {
						echo $row->edit_timestamp;
					}
				}
				echo "</td>";
			}
	 		if ($editable_publication) {
	 			echo "<td>";
				if ($login_type >= LOGIN_ADMIN) {
					echo "[",
						anchor_str(
							"Edit",
							"admin/ed_schema_db.php?sqlschema=authors&edit_code={$row->code}",
							NULL, "item-edit"),
						"]";
					echo "[",
						anchor_str(
							"Delete",
							"admin/ed_schema_db.php?sqlschema=authors&delete_single=true&edit_code={$row->code}",
							NULL, "item-edit"),
						"]\n";
				} else {
					echo "[",
						anchor_str(
							"Edit",
							"edit/user_ed_schema_db.php?sqlschema=authors&edit_code={$row->code}",
							NULL, "item-edit"),
						"]";
					echo "[",
						anchor_str(
							"Delete",
							"edit/user_ed_schema_db.php?sqlschema=authors&delete_single=true&edit_code={$row->code}",
							NULL, "item-edit"),
						"]\n";
				}
	 			echo "</td>";
			}
			echo "</td>";
			return true;
		}
	 	return false;	
	}

	function starc_format($row, $item_count)
	{
		global	$login_user_row;
		global	$login_type;
		global	$show_citations;
		global	$show_timestamp;
		global	$rsc_type;
		global	$show_detailed_listing;

		if ($login_type >= LOGIN_ADMIN || 
 				(  $login_user_row &&
 					$row->supervisor == $login_user_row->stnumber)) {
 			$editable_publication = true;
 		} else {
	 		$editable_publication = false;
		}
		$stn = sqlschema_list_to_array();
		if ($row->surname) {
			if ($row->title) {
				$cnm = "$row->title ";
			} else {
				$cnm = "";
			}
			$cnm .= "$row->firstname $row->surname";
// possible but debatable ... maybe this should be a fixed. people shouldn't in any case be able to change pwi
//				if ($login_user_row && $login_user_row->stnumber == $row->stnumber) {
//					$editable_publication = true;
//				}
			echo "<tr><td>$item_count.</td><td>";
			if ($row->stnumber) {
				echo anchor_str($cnm, "publications.php?".search_param_url($row->stnumber, NULL, "publications"));
				echo "<td>RMIT <u>$row->stnumber</u>";
				if ($row->school_code) {
					echo ", ", $rsc_type->Label($row->school_code);
				}
				echo "</td>";
			}
			if ($row->commence_year && $row->commence_year != 'none') {
	 			echo "<tr><td>&nbsp;</td><td></td><td>Commenced:&nbsp;$row->commence_year</td></tr>";
	 		}
			if ($row->completion_year && $row->completion_year != 'none') {
	 			echo "<tr><td>&nbsp;</td><td></td><td>Completed:&nbsp;$row->completion_year</td></tr>";
	 		}
			if ($row->supervisor) {
	 			echo "<tr><td>&nbsp;</td><td></td><td>";
				if ($row->title) {
					$cnm = "$row->sup_title ";
				} else {
					$cnm = "";
				}
				$cnm .= "$row->sup_firstname $row->sup_surname";
				echo "Supervisor:&nbsp;", anchor_str($cnm, "publications.php?".search_param_url($row->supervisor, NULL, "publications"));
	 			echo "</td></tr>";
			}
			if ($row->project && $show_detailed_listing) {
	 			echo "<tr><td>&nbsp;</td><td></td><td>";
	 			echo "<br>&nbsp;&nbsp;$row->project<br>&nbsp;";
	 			echo "</td></tr>";
			}
			if ($show_timestamp) {
	 			echo "<tr><td>&nbsp;</td><td></td><td>";
				if ($row->create_timestamp || $row->edit_timestamp) {
					echo "Timestamp ";
					if ($row->create_timestamp) {
						echo $row->create_timestamp;
					}
					echo "&nbsp;/&nbsp;";
					if ($row->edit_timestamp) {
						echo $row->edit_timestamp;
					}
				}
	 			echo "</td></tr>";
			}
	 		if ($editable_publication) {
	 			echo "<tr><td>&nbsp;</td><td></td><td>";
				if ($login_type >= LOGIN_ADMIN) {
					echo "[",
						anchor_str(
							"Edit",
							"admin/ed_schema_db.php?sqlschema=students&edit_code={$row->code}",
							NULL, "item-edit"),
						"]";
					echo "[",
						anchor_str(
							"Delete",
							"admin/ed_schema_db.php?sqlschema=students&delete_single=true&edit_code={$row->code}",
							NULL, "item-edit"),
						"]\n";
				} else {
					echo "[",
						anchor_str(
							"Edit",
							"edit/user_ed_schema_db.php?sqlschema=students&edit_code={$row->code}",
							NULL, "item-edit"),
						"]";
					echo "[",
						anchor_str(
							"Delete",
							"edit/user_ed_schema_db.php?sqlschema=students&delete_single=true&edit_code={$row->code}",
							NULL, "item-edit"),
						"]\n";
				}
	 			echo "</td></tr>";
			}
			echo "<tr><td>&nbsp;</td></tr>";
			return true;
		}
	 	return false;	
	}

	function publication_details($row)
	{
		echo "Published: $row->publisher, $row->publication_place, $row->publication_year";
		echo "\n";
	}
	
	function timestamp_display($row)
	{
		if ($row->create_timestamp) {
			echo "&nbsp;&nbsp;Entry created on $row->create_timestamp<br>";
			echo "\n";
		}
		if ($row->edit_timestamp) {
			echo "&nbsp;&nbsp;Last modified on $row->edit_timestamp<br>";
			echo "\n";
		}
	}
	
	function affiliation_breakdown($row)
	{
		global	$vrii_type;
		global	$rg_type;
		if ($row->vrii) {
			if ($row->vrii != "unaligned") {
				if ($vrii_type) {
					$vrnm = $vrii_type->Label($row->vrii);
				} else {
					$vrnm = ucfirst($row->vrii);
				}
				if ($vrnm) {
					echo "&nbsp;&nbsp;<b>VRII</b> : $vrnm<br>";
					echo "\n";
				}
			}
		}
		
		if ($row->research_group) {
			$rg = explode(",",$row->research_group);
			if ($vrii_type) {
				$vrnm = $rg_type->Label($rg[0]);
			} else {
				$vrnm = ucfirst($rg[0]);
			}
			echo "&nbsp;&nbsp;<b>Research Group: $vrnm</b>";
			for ($i=1; $i<count($rg); $i++) {
				if ($vrii_type) {
					$vrnm = $vrii_type->Label($rg[$i]);
				} else {
					$vrnm = ucfirst($rg[$i]);
				}
				echo ", ",$vrnm;
			}
			br();
			echo "\n";
		}
		if ($row->vrii || $row->research_group) {
//			br();
		}
	}
	
	
///////////////////////////////////////////
// get database and table formats
///////////////////////////////////////////
	if (!isset($mysql) || $mysql < 0) {
		$mysql = get_database(
					$schema_database_name,
					$database_host,
					$database_pleb_user,
					$database_pleb_passwd);
	}
	if ($mysql < 0) {
		errorpage("RPC database is offline. Please contact the site administrator.");
	}
	
	$schema_idx_path = "$schema_base_directory/$pub_schema_idx_name";
	if (file_exists($schema_idx_path)) {
		$uploaded_schema = uncache_variable($schema_idx_path);
	} else {
		errorpage("publication format index '$schema_idx_path' does not exist");
	}
	if (!$uploaded_schema) {
		errorpage("publication format index '$schema_idx_path' is empty");
	}
	while (list($k,$v)=each($uploaded_schema)) {
		$scv = uncache_variable("$schema_base_directory/$k"."_tables.ser");
		$schema_table[$k] = reset($scv);
	}
	reset($schema_table);
	$v=current($schema_table);
	$schema_types = uncache_variable("$schema_base_directory/$v->name"."_types.ser");
	$rsc_type = &$schema_types["rsc-type"];
	$rfcd_type = &$schema_types["rfcd-type"];
	$vrii_type = &$schema_types["vrii-type"];
	$rg_type = &$schema_types["research-group-type"];
	
	if ($login_type == LOGIN_USER) {
		if (!$search_title &&
				!$search_rmit_author &&
				!$search_stnumber &&
				!$search_author) {
			if ($search_mode == "publications" || $search_mode == "students") {
				$search_stnumber = $login_u;
			}
		}
	}
///////////////////////////////////////////////////////////////
// top of the page
///////////////////////////////////////////////////////////////
	standard_page_top("DEST Research Publications Database", "style/default.css", "page-noframe", "images/title/dest_rpc.gif", 700, 72, "DEST Research Publication Collection", "common/necessary.js");
	br("all");
	if ($action_msg) {
		echo "<br><b>$action_msg</b><br>\n";
	}
	require("searchform.php");
	br();br();br();
	
	
	if ($search_mode == "projects") {
		if ($search_rmit_author) {
// here we should do a search on any partial matches on surname or firstname. assume for moment we have stnumber only
			$kra = split('[, ]', $search_rmit_author);
		}
		$kt = split('[, ]', $search_title);
		$ka = split('[, ]', $search_author);
		$ku = split('[, ]', $search_stnumber);
		$kw = split('[, ]', $search_keywords);
		$au_where = "";
		reset($kra);
		while (list($au_key,$au_val) = each($kra)) {
			if ($au_val) {
				$au_where = like_clause($au_where, "owning_researcher_stnumber","$au_val", "||");
			}
		}
		reset($kw);
		while (list($au_key,$au_val) = each($kw)) {
			if ($au_val) {
				$au_where = like_clause($au_where, "keywords","%$au_val%", "||");
			}
		}
		reset($ka);
		while (list($au_key,$au_val) = each($ka)) {
			if ($au_val) {
				$au_where = like_clause($au_where, "surname","%$au_val%", "||");
			}
		}
		reset($ku);
		while (list($au_key,$au_val) = each($ku)) {
			if ($au_val) {
				$au_where = like_clause($au_where, "stnumber","%$au_val%", "||");
			}
		}
		reset($kt);
		while (list($au_key,$au_val) = each($kt)) {
			if ($au_val) {
				$au_where = like_clause($au_where, "name","%$au_val%", "||");
			}
		}
		if (!$au_where) {
			$au_where = "name like '%'";
		}
		if ($au_where) {
			table_header(0,0);
			
			$upbase = upload_base($k);
			$qwh = $au_where;
			if ($search_year) {
				$yind = strpos($search_year, '-');
				if ($yind === false) {
					$qwh = "$qwh and (start_year<='$search_year') and (end_year>='$search_year')";
				} elseif ($yind == 0) {
					$years = substr($search_year, 1,4);
					$qwh = "$qwh and (start_year<='$years')";
				} elseif ($yind == (strlen($search_year)-1)) {
					$years = substr($search_year, 0,4);
					$qwh = "$qwh and (end_year>='$years')";
				} else {
					$years = explode('-', $search_year);
					$qwh = "$qwh and (end_year>='$years[0]') and (start_year<='$years[1]')";
				}
			}
			$query = "select * from research_project where $qwh";
//				echo $query."<br>";
			$result = mysql_query($query);
			if ($result > 0) {
				$nitems = mysql_num_rows($result);
				if ($nitems > 0) {
					table_row();
					table_data("left", "top", 2);
					div("result-title",	"<b>$nitems Research Project",(($nitems > 1)? "s":""), " found matching these criteria", "</b>");
					table_dend();
					table_rend();
					$arc_count = 1;
					for($i=0; $i < $nitems; $i++) {
						$row = mysql_fetch_object($result);
						if (parc_format($row, $arc_count)) {
							$arc_count++;
						}
					}
				} else {
					div("result-title",	"<b>No Research Project found matching these criteria</b>");
				}
			} else {
				echo "Mysql error searching Research Projects:<br>".mysql_error(), "<br>query:",$query;
			}

			table_tail();
			br();br();
		}
		table_tail();
	} elseif ($search_mode == "authors") {
		$kt = split('[, ]', $search_title);
		$ka = split('[, ]', $search_author);
		$ku = split('[, ]', $search_stnumber);
		$au_where = "";
		reset($ka);
		while (list($au_key,$au_val) = each($ka)) {
			if ($au_val) {
				$au_where = like_clause($au_where, "surname","%$au_val%", "||");
			}
		}
		reset($ku);
		while (list($au_key,$au_val) = each($ku)) {
			if ($au_val) {
				$au_where = like_clause($au_where, "stnumber","%$au_val%", "||");
			}
		}
		if ($search_status && $search_status != 'any') {
			switch ($search_status) {
			case 'checked': 
				if ($au_where) $au_where = "($au_where) and ";
				$au_where .= "(author_code != '')";
				break;
			case 'unchecked': 
				if ($au_where) $au_where = "($au_where) and ";
				$au_where .= "(length(author_code)<=>NULL || length(author_code)=0)";
				break;
			}
		}
		table_header(0,0);
			
		if ($au_where) {
			$query = "select * from authors where $au_where";
		} else {
			$query = "select * from authors";
		}
		$query .= " order by surname, firstname";
//		echo $query;
		$result = mysql_query($query);
		if ($result > 0) {
			$nitems = mysql_num_rows($result);
			if ($nitems > 0) {
				table_row();
				table_data("left", "top", 2);
				div("result-title",	"<b>$nitems Author",(($nitems > 1)? "s":""), " found matching these criteria", "</b>");
				table_dend();
				table_rend();
				$arc_count = 1;
				for($i=0; $i < $nitems; $i++) {
					$row = mysql_fetch_object($result);
					if (aurc_format($row, $arc_count)) {
						$arc_count++;
					}
				}
			} else {
				div("result-title", "<b>No authors found matching this query</b>");
			}

			table_tail();
			br();br();
		} else {
			echo "Mysql error searching Authors:<br>".mysql_error(), "<br>query:",$query;
		}
		table_tail();
	} elseif ($search_mode == "students") {
		$ka = split('[, ]', $search_author);
		$ku = split('[, ]', $search_stnumber);
		$au_where = "";
		reset($ka);
		while (list($au_key,$au_val) = each($ka)) {
			if ($au_val) {
				$au_where = like_clause($au_where, "surname","%$au_val%", "||");
				$au_where = like_clause($au_where, "firstname","%$au_val%", "||");
				$au_where = like_clause($au_where, "stnumber","%$au_val%", "||");
			}
		}
		reset($ku);
		while (list($au_key,$au_val) = each($ku)) {
			if ($au_val) {
				$au_where = like_clause($au_where, "supervisor","%$au_val%", "||");
				$au_where = like_clause($au_where, "sup_firstname","%$au_val%", "||");
				$au_where = like_clause($au_where, "sup_surname","%$au_val%", "||");
			}
		}
		if ($search_status && $search_status != 'any') {
			switch ($search_status) {
			case 'completed': 
				if ($au_where) $au_where = "($au_where) and ";
				$au_where .= "(length(completion_year)>0 and completion_year != 'none')";
				break;
			case 'uncompleted': 
				if ($au_where) $au_where = "($au_where) and ";
				$au_where .= "(length(completion_year)<=>NULL || length(completion_year)=0 || completion_year='none')";
				break;
			}
		}
		if ($search_year) {
			$yind = strpos($search_year, '-');
			if ($yind === false) {
				if ($au_where) $au_where = "($au_where) and ";
				$au_where .= "(commence_year<='$search_year') and ((completion_year>='$search_year')or(completion_year='none'))";
			} elseif ($yind == 0) {
				$years = substr($search_year, 1,4);
				if ($au_where) $au_where = "($au_where) and ";
				$au_where .= "(commence_year<='$years')";
			} elseif ($yind == (strlen($search_year)-1)) {
				$years = substr($search_year, 0,4);
				if ($au_where) $au_where = "($au_where) and ";
				$au_where .= "((completion_year>='$years')or(completion_year='none'))";
			} else {
				$years = explode('-', $search_year);
				if ($au_where) $au_where = "($au_where) and ";
				$au_where .= "((completion_year>='$years[0]')||(completion_year='none')) and (commence_year<='$years[1]')";
			}
		}
			
		table_header(0,0);
			
		if ($au_where) {
			$query = "select * from students where $au_where";
		} else {
			$query = "select * from students";
		}
		$query .= " order by supervisor, surname, firstname";
		$result = mysql_query($query);
		if ($result > 0) {
			$nitems = mysql_num_rows($result);
			if ($nitems > 0) {
				table_row();
				table_data("left", "top", 2);
				div("result-title",	"<b>$nitems Student",(($nitems > 1)? "s":""), " found matching these criteria", "</b>");
				table_dend();
				table_rend();
				$arc_count = 1;
				for($i=0; $i < $nitems; $i++) {
					$row = mysql_fetch_object($result);
					if (starc_format($row, $arc_count)) {
						$arc_count++;
					}
				}
			} else {
				div("result-title",	"<b>No Students found for this query</b>");
			}

			table_tail();
			br();br();
		} else {
			echo "Mysql error searching Students:<br>".mysql_error(), "<br>query:",$query;
		}
		table_tail();
	} else { // if ($search_mode == "publications")
		if ($view_abstract_code && $view_abstract_table) {
			$query = "select * from ${view_abstract_table} where code=${view_abstract_code}";
			$result = mysql_query($query);
			if ($result > 0) {
				$nitems = mysql_num_rows($result);
				if ($nitems != 1) {
					echo "Could not find item ${view_abstract_code} in table '${view_abstract_table}' while looking for abstract";
				} else {
					$row = mysql_fetch_object($result);
					div("result-title", "<b>$row->title</b>");
					div("result-body");
					$stn = sqlschema_list_to_array($row->stnumber);
					$sura = sqlschema_list_to_array($row->surname);
					$fura = sqlschema_list_to_array($row->firstname);
					for ($aui=0; $aui<count($stn); $aui++) {
						if ($sura[$aui] || $stn[$aui] || $fura[$aui]) {
							$fnm = explode(" ", $fura[$aui]);
							$cnm = "";
							reset($fnm);
							while (list($k, $v) = each($fnm)) {
								if ($v  && $v{0}) {
									$cnm .= strtoupper($v{0});
									$cnm .= ". ";
								}
							}
							$cnm .= $sura[$aui];
							if ($aui > 0) {
								echo ", ";
							}
							$srch_st = $stn[$aui];
							if ($srch_st) {
								if ($login_user_row && $login_user_row->stnumber == $srch_st) {
									$editable_publication = true;
								}
								echo anchor_str($cnm, "publications.php?".search_param_url($srch_st, NULL, "publications"));
							} else {
								echo $cnm;
							}
						}
					}
					div();
					div("result-body");
					publication_publisher_details($view_abstract_table, $row);
					div();
					div("item-detail-txt");
					if ($row->keywords) {
						echo "<br><b>Keywords: </b>", $row->keywords;
					}
					$rfcd_codes = sqlschema_list_to_array($row->rfcd_code);
					$rfcd_splits = sqlschema_list_to_array($row->rfcd_split);
					$shown_tb = false;
					for ($aui=0; $aui< count($rfcd_codes); $aui++) {
						if ($rfcd_codes[$aui] && $rfcd_codes[$aui] > 0) {
							if (!$shown_tb) {
								$shown_tb = true;
								echo "<br><b>Subjects: </b>";
							} else {
								echo ", ";
							}
							$subjn = $rfcd_type->Label($rfcd_codes[$aui]);
							echo $subjn, " (",$rfcd_splits[$aui],"%)";
						}
					}
					echo "<p>$row->description</p>";
					div();
					div("result-body");
			 		$mat = sqlschema_list_to_array($row->material);
			 		$matyp = sqlschema_list_to_array($row->material_kind);
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
	 				$upbase = upload_base($view_abstract_table);
					if ($download_k >= 0) {
						echo "<br>";
						echo "<br>";
						echo "[",
							anchor_str(
								"Download ".$matyp[$download_k],
								"$upbase$row->code/".$mat[$download_k],
								NULL, "item-action"),
							"]\n";
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
					echo "<br><br>";
				}
			}
		} else {
			$where = "";
			$au_where = "";
			if ($search_rmit_author) {
				$authors = split('[, ]', $search_rmit_author);
				$au_where = "";
				reset($authors);
				while (list($au_key,$au_val) = each($authors)) {
					if ($au_val) {
						$au_where = like_clause($au_where, "stnumber",$au_val, "||");
						$au_where = like_clause($au_where, "firstname",$au_val, "||");
						$au_where = like_clause($au_where, "surname", $au_val,"||");
					}
				}
				$au_query = "select * from people";
				$au_query .= " where ($au_where) and (kind != 'admin')";
				if ($au_order) {
					$au_query .= " order $au_order";
				}
				$au_result = mysql_query($au_query);
				if (!$au_result) {
					echo "Database error: ", mysql_error();
				} else {
					$n_au_found = mysql_num_rows($au_result);
					if ($n_au_found >0) {
						table_header(0,0);
						for($ai=0; $ai < $n_au_found; $ai++) {
							$au_row = mysql_fetch_object($au_result);
							table_row();
							table_data("left", "top", 2);
							div("result-head", "Publications for $au_row->stnumber, $au_row->title $au_row->firstname $au_row->surname");
							table_dend();
							table_rend();
							
							reset($schema_table);
							while (list($k,$v)=each($schema_table)) {
								$upbase = upload_base($k);
								$qwh = "first_author_stnumber='$au_row->stnumber'";
								if ($search_status && $search_status != 'any') {
									switch ($search_status) {
									case 'primary': 
										$qwh = "($qwh) and primary_checked";
										break;
									case 'nprimary': 
										$qwh = "($qwh) and not primary_checked";
										break;
									case 'school': 
										$qwh = "($qwh) and school_checked";
										break;
									case 'portfolio': 
										$qwh = "($qwh) and portfolio_checked";
										break;
									case 'nschool': 
										$qwh = "($qwh) and not school_checked";
										break;
									case 'nportfolio': 
										$qwh = "($qwh) and not portfolio_checked";
										break;
									}
								}
								if ($search_year) {
									$yind = strpos($search_year, '-');
									if ($yind === false) {
										$qwh = "$qwh and publication_year='$search_year'";
									} elseif ($yind == 0) {
										$years = substr($search_year, 1,4);
										$qwh = "$qwh and publication_year<='$years'";
									} elseif ($yind == (strlen($search_year)-1)) {
										$years = substr($search_year, 0,4);
										$qwh = "$qwh and publication_year>='$years'";
									} else {
										$years = explode('-', $search_year);
										$qwh = "$qwh and (publication_year>='$years[0]') and (publication_year<='$years[1]')";
									}
								}
								$query = "select * from $v->name where $qwh";
								$result = mysql_query($query);
								if ($result > 0) {
									$nitems = mysql_num_rows($result);
									if ($nitems > 0) {
										table_row();
										table_data("left", "top", 2);
										div("result-title",	$v->label?"<B>$nitems $v->label":"<b>$nitems".ucfirst($v->name),
													(($nitems > 1)? "s</b>":"</B>"));
										table_dend();
										table_rend();
										$arc_count = 1;
										for($i=0; $i < $nitems; $i++) {
											if ($show_arc) {
												$row = mysql_fetch_object($result);
												if (arc_format($row, $v->name, $arc_count)) {
													$arc_count++;
												}
											} else {
												table_row();
												echo "<td align=\"left\">";
												
												$row = mysql_fetch_object($result);
												div("result-title", ($i+1).". <b>\"$row->title\"</b>");
												div("result-body");
												publication_details($row);
												if ($show_authors) {
													author_list($row);
												}
												if ($show_subjects) {
													subject_breakdown($row, $show_subjects);
												}
												if ($show_detailed_listing) {
													affiliation_breakdown($row);
												}
												if ($show_timestamp) {
													timestamp_display($row);
												}
												if ($show_supporting) {
													supporting_materials($row, "$upbase$row->code/");
												}
												div();
												br();
												
												table_dend();
												table_data("right", "top", 1, "", "30%");
												scholar_searches($row, $au_row->surname, true);
												table_dend();
												table_rend();
											}
										}
									}
								} else {
									echo "Mysql error searching database $v->name:<br>",mysql_error(),"<br>Query:",$query,"<br>";
								}
							}
						}
						table_tail();
						br();br();
					} else {
						div("There are no RMIT principal authors in this collection matching your request.", "result-head");
					}
				}
			} else {
				$kt = split('[, ]', $search_title);
				$ka = split('[, ]', $search_author);
				$ku = split('[, ]', $search_stnumber);
				$kw = split('[, ]', $search_keywords);
				$au_where = "";
				reset($kw);
				while (list($au_key,$au_val) = each($kw)) {
					if ($au_val) {
						$au_where = like_clause($au_where, "keywords","%$au_val%", "||");
					}
				}
				reset($ka);
				while (list($au_key,$au_val) = each($ka)) {
					if ($au_val) {
						$au_where = like_clause($au_where, "surname","%$au_val%", "||");
					}
				}
				reset($ku);
				while (list($au_key,$au_val) = each($ku)) {
					if ($au_val) {
						$au_where = like_clause($au_where, "stnumber","%$au_val%", "||");
					}
				}
				reset($kt);
				while (list($au_key,$au_val) = each($kt)) {
					if ($au_val) {
						$au_where = like_clause($au_where, "title","%$au_val%", "||");
					}
				}
				if ($au_where) {
					div("result-head", "Publications found matching the given criteria");
					table_header(0,0);
					reset($schema_table);
					while (list($k,$v)=each($schema_table)) {
						$upbase = upload_base($k);
						$qwh = $au_where;
						if ($search_status && $search_status != 'any') {
							switch ($search_status) {
								case 'primary': 
									$qwh = "($qwh) and primary_checked";
									break;
								case 'nprimary': 
									$qwh = "($qwh) and not primary_checked";
									break;
								case 'school': 
									$qwh = "($qwh) and school_checked";
									break;
								case 'portfolio': 
									$qwh = "($qwh) and portfolio_checked";
									break;
								case 'nschool': 
									$qwh = "($qwh) and not school_checked";
									break;
								case 'nportfolio': 
									$qwh = "($qwh) and not portfolio_checked";
									break;
							}
						}
						if ($search_year) {
							$yind = strpos($search_year, '-');
							if ($yind === false) {
								$qwh = "$qwh and publication_year='$search_year'";
							} elseif ($yind == 0) {
								$years = substr($search_year, 1,4);
								$qwh = "$qwh and publication_year<='$years'";
							} elseif ($yind == (strlen($search_year)-1)) {
								$years = substr($search_year, 0,4);
								$qwh = "$qwh and publication_year>='$years'";
							} else {
								$years = explode('-', $search_year);
								$qwh = "$qwh and (publication_year>='$years[0]') and (publication_year<='$years[1]')";
							}
						}
						$query = "select * from $v->name where $qwh";
		//				echo $query."<br>";
						$result = mysql_query($query);
						if ($result > 0) {
							$nitems = mysql_num_rows($result);
							if ($nitems > 0) {
								table_row();
								table_data("left", "top", 2);
								div("result-title",	$v->label?"<B>$nitems $v->label":"<b>$nitems".ucfirst($v->name),
										(($nitems > 1)? "s</b>":"</B>"));
								table_dend();
								table_rend();
								$arc_count = 1;
								for($i=0; $i < $nitems; $i++) {
									if ($show_arc) {
										$row = mysql_fetch_object($result);
										if (arc_format($row, $v->name, $arc_count)) {
											$arc_count++;
										}
									} else {
										table_row();
										echo "<td align=\"left\">";
										
										$row = mysql_fetch_object($result);
										div("result-title",	($i+1).". <b>\"$row->title\"</b>");
										div("result-body");
										publication_details($row);
										if ($show_authors) {
											author_list($row);
										}
										if ($show_subjects) {
											subject_breakdown($row, $show_subjects);
										}
										if ($show_detailed_listing) {
											affiliation_breakdown($row);
										}
										if ($show_timestamp) {
											timestamp_display($row);
										}
										if ($show_supporting) {
											$authname_query = "select * from people where stnumber='$row->first_author_stnumber'";
											$auresult = mysql_query($authname_query);
											if ($auresult > 0) {
												$nauitems = mysql_num_rows($auresult);
												if ($nauitems > 0) {
													$authname_row = mysql_fetch_object($auresult);
													supporting_materials($row, "$upbase$row->code/");
												}
											}
										}
										div();
										br();
										
										table_dend();
										$ska = split('&', $row->surname);
										table_data("right", "top", 1, "", "30%");
										scholar_searches($row, $ska[0], true);
										table_dend();
										table_rend();
									}
								}
							}
						} else {
							echo "Mysql error searching database $v->name:<br>".mysql_error(), "<br>query:",$query;
						}
					}
					table_tail();
					br();br();
				}
				table_tail();
			}
		}
	}
	if ($login_type == LOGIN_USER) {
		echo "<p>To change your password, or modify contact details, ";
		echo anchor_str("follow this link", "admin/ed_people.php");
		echo ".</p>\n";
	}
	standard_page_bottom();
?>
