<?php
// display routines for school view and edit
	function supporting_materials($row, $base, $k)
	{
		global	$mat_type;
				table_row();
				table_data_string("&nbsp;");
				table_rend();
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
 			table_row();
			table_data_string("<b>Supporting materials</b>");
			table_rend();
			echo "\n";
	 		for ($aui=0; $aui<count($mat); $aui++) {
	 			if ($mat[$aui]) {
		 			table_row();
					echo "\n";
		 			$nm = rawurldecode($mat[$aui]);
		 			table_data_string("&nbsp;&nbsp;");
					echo "\n";
					$typ = $mat_type[$k]->Label(rawurldecode($matyp[$aui]));
	 				table_data_string($typ?$typ:"(Unspecified kind of material)");
					echo "\n";
	 				table_data_string(anchor_str($nm, $base.$nm, "", ""));
					echo "\n";
	 				table_rend();
					echo "\n";
	 			}
	 		}
			echo "\n";
		}
	}

	function subject_breakdown($row, $show_subjects)
	{
		if ($show_subjects && $show_subjects != "none") {
			global $rfcd_type;
			$stn = split("&", $row->rfcd_code);
			$sura = split("&", $row->rfcd_split);
			for ($aui=0; $aui<count($stn); $aui++) {
				if ($sura[$aui]) {
					table_row();
					table_data_string("&nbsp;");
					table_rend();
					table_row();
					
					table_data_string("<b>Subject ".($aui+1)."</b>");
				
					$subjn = $rfcd_type->Label(rawurldecode($stn[$aui]));
					
					table_data_string("RFCD code");
					table_data_string($stn[$aui]);
					table_rend();
					echo "\n";
					
					table_row();
					table_data_string("&nbsp;");
					table_data_string("Subject Name");
					table_data_string($subjn?$subjn:"Not a valid RFCD code");
					table_rend();
					echo "\n";
					
					table_row();
					table_data_string("&nbsp;");
					table_data_string("% Split");
					table_data_string(rawurldecode($sura[$aui])."%");
					echo "\n";
					table_rend();
					echo "\n";
				}
			}
		}
	}
	
	function author_list($row)
	{
		global $rsc_type;
		echo "\n";
		$stn = split("&", $row->stnumber);
		$sura = split("&", $row->surname);
		$fura = split("&", $row->firstname);
		$ata= split("&", $row->author_title);
		$typa = split("&", $row->type);
		$sca= split("&", $row->school_code);
		$gra= split("&", $row->gender);
		$son = split("&", $row->school_org_name);
		for ($aui=0; $aui<count($stn); $aui++) {
			if ($sura[$aui]) {
				table_row();
				table_data_string("&nbsp;");
				table_rend();
				
				table_row();
				table_data_string("<b>Author ".($aui+1)."</b>");
				table_data_string("E/S Number");
				table_data_string($stn[$aui]?rawurldecode($stn[$aui]):"n/a");
				table_rend();
				echo "\n";
				table_row();
				table_data_string("&nbsp;");
				table_data_string("Surname");
				table_data_string(rawurldecode($sura[$aui]));
				table_rend();
				echo "\n";
				table_row();
				table_data_string("&nbsp;");
				table_data_string("Firstname");
				table_data_string(rawurldecode($fura[$aui]));
				table_rend();
				echo "\n";
				table_row();
				table_data_string("&nbsp;");
				table_data_string("Title");
				table_data_string(rawurldecode($ata[$aui]));
				table_rend();
				echo "\n";
				table_row();
				table_data_string("&nbsp;");
				table_data_string("Type");
				table_data_string(rawurldecode($typa[$aui]));
				table_rend();
				echo "\n";
				table_row();
				table_data_string("&nbsp;");
				table_data_string("Gender");
				table_data_string(rawurldecode($gra[$aui]));
				table_rend();
				echo "\n";
				if ($sca[$aui] >= 0) {
					$rmit_school_nm = $rsc_type->Label(rawurldecode($sca[$aui]));
					table_row();
					table_data_string("&nbsp;");
					table_data_string("School code");
					table_data_string(rawurldecode($typa[$aui]));
					table_rend();
					echo "\n";
					table_row();
					table_data_string("&nbsp;");
					table_data_string("RMIT School name&nbsp;");
					table_data_string($rmit_school_nm);
					table_rend();
					echo "\n";
				} else {
					table_row();
					table_data_string("&nbsp;");
					table_data_string("External Org name&nbsp;");
					table_data_string(rawurldecode($son[$aui]));
					table_rend();
					echo "\n";
				}
			}
		}
		echo "\n";
	}
	
	function publication_details($row, $k)
	{
		switch ($k) {
			case "journal":
				table_row();
				table_data_string("<b>Journal Name</b>","left", 30);
				table_data_string("&nbsp;");
				table_data_string($row->journal_name,"left", 30);
				table_rend();
				echo "\n";
				table_row();
				table_data_string("<b>Volume</b>","left", 30);
				table_data_string("&nbsp;");
				table_data_string($row->volume,"left", 30);
				table_rend();
				echo "\n";
				table_row();
				table_data_string("<b>Edition</b>","left", 30);
				table_data_string("&nbsp;");
				table_data_string($row->edition,"left", 30);
				table_rend();
				echo "\n";
				table_row();
				table_data_string("<b>First page</b>","left", 30);
				table_data_string("&nbsp;");
				table_data_string($row->start_page,"left", 30);
				table_rend();
				echo "\n";
				table_row();
				table_data_string("<b>Last page</b>","left", 30);
				table_data_string("&nbsp;");
				table_data_string($row->end_page,"left", 30);
				table_rend();
				echo "\n";
				break;
			case "conference":
				table_row();
				table_data_string("<b>Conference Name</b>","left", 30);
				table_data_string("&nbsp;");
				table_data_string($row->conference_name,"left", 30);
				table_rend();
				echo "\n";
				table_row();
				table_data_string("<b>Conference Date</b>","left", 30);
				table_data_string("&nbsp;");
				table_data_string($row->conference_date,"left", 30);
				table_rend();
				echo "\n";
				table_row();
				table_data_string("<b>Conference Location</b>","left", 30);
				table_data_string("&nbsp;");
				table_data_string($row->conference_location,"left", 30);
				table_rend();
				echo "\n";
				table_row();
				table_data_string("<b>Publication Title</b>","left", 30);
				table_data_string("&nbsp;");
				table_data_string($row->publication_title,"left", 30);
				table_rend();
				echo "\n";
				table_row();
				table_data_string("<b>Editor</b>","left", 30);
				table_data_string("&nbsp;");
				table_data_string($row->editor,"left", 30);
				table_rend();
				echo "\n";
				break;
		}
		table_row();
		echo "\n";
		table_data_string("<b>Publisher</b>","left", 30);
		table_data_string("&nbsp;");
		echo "\n";
		table_data_string($row->publisher,"left", 30);
		table_data_string("&nbsp;");
		echo "\n";
		table_rend();
		echo "\n";
		table_row();
		echo "\n";
		table_data_string("<b>Publication Place</b>","left", 30);
		table_data_string("&nbsp;");
		echo "\n";
		table_data_string($row->publication_place,"left", 30);
		echo "\n";
		table_rend();
		echo "\n";
		table_row();
		table_data_string("<b>Publication Year</b>","left", 30);
		table_data_string("&nbsp;");
		table_data_string($row->publication_year,"left", 30);
		table_rend();
		echo "\n";
		switch ($k) {
			case "book":
				break;
			case "conference":
				table_row();
				table_data_string("<b>ISBN</b>","left", 30);
				table_data_string("&nbsp;");
				table_data_string($row->isbn,"left", 30);
				table_rend();
				echo "\n";
				break;
			case "chapter":
				table_row();
				table_data_string("<b>First page</b>","left", 30);
				table_data_string("&nbsp;");
				table_data_string($row->start_page,"left", 30);
				table_rend();
				echo "\n";
				table_row();
				table_data_string("<b>Last page</b>","left", 30);
				table_data_string("&nbsp;");
				table_data_string($row->end_page,"left", 30);
				table_rend();
				break;
			case "journal":
				table_row();
				table_data_string("<b>ISSN</b>","left", 30);
				table_data_string("&nbsp;");
				table_data_string($row->issn,"left", 30);
				table_rend();
				echo "\n";
				break;
		}
	}
	
	function stripspace($str)
	{
		for ($i=0; $i<strlen($str); $i++) {
			if (ord($str{$i})<ord(' ')) {
				$str{$i} = " ";
			}
		}
		return str_replace(" ", "", $str);
	}
	
	function timestamp_display($row)
	{
		if ($row->create_timestamp) {
			table_row();
			table_data_string("<b>Entry created</b>","left", 30);
			table_data_string("&nbsp;");
			table_data_string($row->create_timestamp,"left", 30);
			table_rend();
		}
		if ($row->edit_timestamp) {
			table_row();
			table_data_string("<b>Last modified</b>","left", 30);
			table_data_string("&nbsp;");
			table_data_string($row->edit_timestamp,"left", 30);
			table_rend();
		}
	}
	
	function affiliation_breakdown($row)
	{
		global	$vrii_type;
		global	$rg_type;
		if ($row->vrii) {
			if ($vrii_type) {
				$vrnm = $vrii_type->Label($row->vrii);
			} else {
				$vrnm = ucfirst($row->vrii);
			}
			table_row();
			table_data_string("&nbsp;");
			table_rend();
			table_row();
			echo "\n";
			table_data_string("<b>VRII</b>","left", 30);
			table_data_string("&nbsp;");
			table_data_string($vrnm);
			table_rend();
			echo "\n";
		}
	}

	function school_inputs($row, $table, $type, $view_mode, $do_edit, $au_stnumber,
			$edit_entry_label, $submit_edit_label, $other_action_labels=NULL)
	{
		global	$show_pubs_select;
		
//		form_header("schoolview.php#$table$row->code", "schoolInputForm", "post");
		form_header("schoolview.php", "schoolInputForm", "post");
		table_row();
		table_data("left", null, 3, "result-input");
		echo "&nbsp;";
		table_dend();
		table_rend();
		table_row();
		table_data("left", null, 1, "result-input");
		echo "<b>PWI number</b>";
		table_dend();
		table_data("left", null, 2, "result-input");
		if ($do_edit) {
			text_input(
					"edit_pwi_code",
					$row->pwi_code,
					10,10
				);
		} else {
			echo $row->pwi_code?$row->pwi_code:"none";
		}
		table_dend();
		table_rend();
		echo "\n";

		table_row();
		table_data("left", null, 1, "result-input");
		echo "<b>School Check</b>";
		table_dend();
		table_data("left", null, 2, "result-input");
		$v = $row->school_checked;
		if ($do_edit) {
			checkbox_input(
					"edit_school_check",
					"true",
					($v&&$v!="0"), "", false
				);
		} else {
			if ($v && $v != "0") {
				echo "yes";
			} else {
				echo "no";
			}
		}
		table_dend();
		table_rend();
		echo "\n";
		
		table_row();
		table_data("left", null, 1, "result-input");
		echo "<b>School Comments</b>";
		table_dend();
		table_data("left", null, 2, "result-input");
		if ($do_edit) {
			text_area("edit_school_comment",
					$row->school_comment,
					70, 10
				);
		} else {
			echo $row->school_comment;
		}
		table_dend();
		table_rend();
		
		table_row();
		table_data("left", null, 1, "result-input");
		table_dend();
		table_data("left", null, 2, "result-input");
		div("edit-controls");
		hidden_field("edit_pub_table", $table);
		hidden_field("edit_pub_code", $row->code);
		hidden_field("edit_pub_stnumber", $au_stnumber);
		hidden_field("show_pubs_select", $show_pubs_select);
		if ($do_edit) {
			submit_input("schoolAction", $submit_edit_label);
		} else {
			submit_input("schoolAction", $edit_entry_label);
		}
		if ($other_action_labels != NULL) {
			if (is_array($other_action_labels)) {
				reset($other_action_labels);
				while (list($k,$v) = each($other_action_labels)) {
					submit_input("schoolAction", $v);
				}
			} else {
				submit_input("schoolAction", $other_action_labels);
			}
		}
		div();
		form_tail();
		table_dend();
		table_rend();
		
		table_row();
		table_data("left", null, 3, "result-input");
		echo "&nbsp;";
		table_dend();
		table_rend();
		echo "\n";
	}
?>