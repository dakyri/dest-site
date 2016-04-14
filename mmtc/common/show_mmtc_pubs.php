<?php
//	$publication_archive_dir = "../rpc/uploaded";
//	$publication_index_dir = "../rpc/templates";
	$publication_archive_dir = "../../destrpc/uploaded";
	$publication_index_dir = "../../destrpc/templates";
	$publication_index = "$publication_index_dir/pub_schema_idx.ser";
	$pubdbname = array("book","journal","chapter","conference");
	
	function abstract_link($code, $table)
	{
		echo "[",
			anchor_str(
				"Abstract",
				"publications.php?publication_code={$code}&publication_table={$table}",
				NULL, "item-action"),
			"]\n";
	}
	
	function download_link($kind, $object)
	{
		echo "[",
			anchor_str(
				"Download ".$kind,
				$object,
				NULL, "item-action"),
			"]\n";
	}
	
	function show_pub_row($row, $table, $link_abstract, $link_upload, $show_publisher)
	{
		if ($row->title) {
	 		$has_support_material = false;
			if ($link_abstract === 2) {
				$link_abstract = false;
				echo anchor_str(
						$row->title,
						"publications.php?publication_code={$row->code}&publication_table={$table}",
						NULL, "item-title-action"
					);
				echo "<br>";
			} else {
				echo "<b>",$row->title, "</b><br>\n";
			}
			
			$stn = split("&", $row->stnumber);
			$sura = split("&", $row->surname);
			$fura = split("&", $row->firstname);
			for ($aui=0; $aui<count($stn); $aui++) {
				if ($sura[$aui]) {
					$fnm = explode(" ", rawurldecode($fura[$aui]));
					$cnm = "";
					reset($fnm);
					while (list($k, $v) = each($fnm)) {
						if ($v  && $v{0}) {
							$cnm .= strtoupper($v{0});
							$cnm .= ". ";
						}
					}
					$cnm .= rawurldecode($sura[$aui]);
					if ($aui > 0) {
						echo ", ";
					}
					$srch_st = rawurldecode($stn[$aui]);
					if ($srch_st) {
						echo anchor_str($cnm, "publications.php?show=true&search_author=$srch_st");
					} else {
						echo $cnm;
					}
				}
			}
			echo "<br>\n";
			if ($show_publisher) {
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
							echo "$row->publisher";
						}
						if ($row->publication_place) {
							echo ", $row->publication_place";
						}
						echo " ($row->publication_year)";
						break;
				}
				echo "<br>\n";
			}			
	 		$mat = array_map("rawurldecode", explode("&", $row->material));
	 		$matyp = array_map("rawurldecode", explode("&", $row->material_kind));
	 		reset($mat);
	 		reset($matyp);
	 		for ($aui=0; $aui<count($mat); $aui++) {
	 			if ($mat[$aui] && ($matyp[$aui]=="paper" || $matyp[$aui]=="chapter")) {
	 				$has_support_material = true;
	 				break;
	 			}
	 		}
	 		if (($link_upload&&$has_support_material) || ($link_abstract&&$row->description)) {
	 			$upbase = pub_material_base($table);

				if ($link_abstract&&$row->description) {
					abstract_link($row->code, $table);
				}
				if ($link_upload&&$has_support_material) {
					download_link($matyp[$aui], "$upbase$row->code/".$mat[$aui]);
				}
				echo "<br>\n";
			}
	 		
			return true;
		}
		return false;
	}

// gives the base filename	for an archived publications directory. 
	function pub_material_base($k)
	{
		global $publication_archive_dir;
		switch ($k) {
			case "book":			$upbase = "$publication_archive_dir/bk"; break;
			case "conference":	$upbase = "$publication_archive_dir/cnf"; break;
			case "chapter":		$upbase = "$publication_archive_dir/ch"; break;
			case "journal":		$upbase = "$publication_archive_dir/jrn"; break;
			default:					$upbase = "$publication_archive_dir/dfl"; break;
		}
		return $upbase;
	}
	
	function year_query($search_year)
	{
		if ($search_year) {
			$yind = strpos($search_year, '-');
			if ($yind === false) {
				$qwh = "publication_year='$search_year'";
			} elseif ($yind == 0) {
				$years = substr($search_year, 1,4);
				$qwh = "publication_year<='$years'";
			} elseif ($yind == (strlen($search_year)-1)) {
				$years = substr($search_year, 0,4);
				$qwh = "publication_year>='$years'";
			} else {
				$years = explode('-', $search_year);
				$qwh = "(publication_year>='$years[0]') and (publication_year<='$years[1]')";
			}
			return $qwh;
		}
		return "";
	}
	
	function word_query($search_keywords)
	{
		$kw_where = "";
		$ti_where = "";
		$kw = split('[, ]', $search_keywords);
		reset($kw);
		while (list($kw_key,$kw_val) = each($kw)) {
			if ($kw_val) {
				$kw_where = like_clause($kw_where, "keywords","%$kw_val%", "&&");
			}
		}
		reset($kw);
		while (list($kw_key,$kw_val) = each($kw)) {
			if ($kw_val) {
				$ti_where = like_clause($ti_where, "title","%$kw_val%", "&&");
			}
		}
		if ($ti_where && $kw_where) {
			return "($ti_where) || ($kw_where)";
		} elseif ($ti_where) {
			return $ti_where;
		} elseif ($kw_where) {
			return $kw_where;
		}
		
		return "";
	}
	
	function author_query($search_author)
	{
		$au_where = "";
		$kw = split('[, ]', $search_author);
		reset($kw);
		while (list($kw_key,$kw_val) = each($kw)) {
			if ($kw_val) {
				$au_where = like_clause($au_where, "surname","%$kw_val%", "||");
				$au_where = like_clause($au_where, "stnumber","%$kw_val%", "||");
			}
		}
		return $au_where;
	}
?>