<?php
	error_reporting(0);
	import_request_variables("gp");
	
	if ($edit_pubs_for_year) {
		$posted_year = $edit_pubs_for_year;
		setcookie("edit_pubs_for_user", $edit_pubs_for_year);
	} else {
		$posted_year = false;
	}
	import_request_variables("c");
	if ($posted_year) {
		$edit_pubs_for_year = $posted_year;
	}		
	
	require_once("../common/necessary.php");
	require_once("../../common/sqlschema_types.php");
	require_once("../../common/adminlib.php");
	require_once("schoolfunc.php");
	
/////////////////////////////////////////////////////////
// database access specific to needs of school admin
//   identical to main view script, except for format
////////////////////////////////////////////////////////
	if (isset($sch_p) && $sch_p != "") {
		$mysql = get_database($database_name, $database_host, $sch_u, $sch_p);
		if ($mysql <= 0) {
			header("Location: index.php?login_msg=".urlencode("Please enter a valid password"));
			exit();
		}
		setcookie("sch_u", $sch_u);
		setcookie("sch_p", $sch_p);
	} else {
		header("Location: index.php?login_msg=".urlencode("Please enter a valid password"));
		exit();
	}
	
	$submit_changes_label = "update rpc database";
	if ($schoolAction == $submit_changes_label) { // change to a button, or auto form 
		$action_msg = "";
		reset($_school_comment);
		while (list($k,$v)=each($_school_comment)) {
			$ti = explode(" ", $k);
			if ($v) {
				if ($v == ' ') {
					$query = "update $ti[0] set school_comment='' where code='$ti[1]'";
				} else {
					$query = "update $ti[0] set school_comment='".quothi($v)."' where code='$ti[1]'";
				}
				if (!mysql_query($query)) {
					$action_msg = "Database update fails, ".mysql_error()."<br>";
				}
			}
		}
		reset($_pwi_number);
		while (list($k,$v)=each($_pwi_number)) {
			$ti = explode(" ", $k);
			if ($v) {
				if ($v == ' ') {
					$query = "update $ti[0] set pwi_code='' where code='$ti[1]'";
				} else {
					$query = "update $ti[0] set pwi_code='".quothi($v)."' where code='$ti[1]'";
				}
				if (!mysql_query($query)) {
					$action_msg = "Database update fails, ".mysql_error()."<br>";
				}
			}
		}
		reset($_school_check);
		while (list($k,$v)=each($_school_check)) {
			$ti = explode(" ", $k);
			if ($v != '') {
				if ($v == '1' || $v > 0 || $v=="true") {
					$query = "update $ti[0] set school_checked=1 where code='$ti[1]'";
				} else {
					$query = "update $ti[0] set school_checked=0 where code='$ti[1]'";
				}
				if (!mysql_query($query)) {
					$action_msg = "Database update fails, ".mysql_error()."<br>";
				}
			}
		}
		$red_string = "Location: schoolview.php?";
		if ($action_msg) {
			$red_string .= "action_msg=$action_msg";
		}
		header($red_string);
	}
	
	if (!isset($action_msg)) {
		$action_msg = "";
	}

///////////////////////////////////////////
// get database and table formats
///////////////////////////////////////////
	$mysql = get_database(
					$schema_database_name,
					$database_host,
					$database_pleb_user,
					$database_pleb_passwd);

	if ($mysql < 0) {
		errorpage("Can't open database:".mysql_error());
	}
	$schema_idx_path = "../$schema_base_directory/$pub_schema_idx_name";
	if (file_exists($schema_idx_path)) {
		$uploaded_schema = uncache_variable($schema_idx_path);
	} else {
		errorpage("publication format index '$schema_idx_path' does not exist");
	}
	if (!$uploaded_schema) {
		errorpage("publication format index '$schema_idx_path' is empty");
	}
	while (list($k,$v)=each($uploaded_schema)) {
		$scv = uncache_variable("../$schema_base_directory/$k"."_tables.ser");
		$schema_table[$k] = reset($scv);
		$st = &$schema_table[$k];
		$vnm = $st->name;
		$schema_types = uncache_variable("../$schema_base_directory/$vnm"."_types.ser");
		$mat_type[$vnm] = $schema_types["$vnm-material-type"];
	}
	reset($schema_table);
	$v=current($schema_table);
	$schema_types = uncache_variable("../$schema_base_directory/$v->name"."_types.ser");
	$rsc_type = &$schema_types["rsc-type"];
	$rfcd_type = &$schema_types["rfcd-type"];
	$vrii_type = &$schema_types["vrii-type"];
	$rg_type = &$schema_types["research-group-type"];
	
	
///////////////////////////////////////////////////////////////
// top of the page"result-head"
///////////////////////////////////////////////////////////////
	standard_page_top("DEST Research Publications Database. School administration edit", "../style/default.css", "page-school", "../images/title/dest_rpc.gif", 700, 72, "DEST Research Publication Collection: School Admin View", "../common/necessary.js");
	br("all");
	if ($action_msg) {
		echo "<p><b>$action_msg</b></p>\n";
	}
?>
<p>Welcome to the school administrator's page.</p>
<p>To make reccomendations regarding the publications on view, or to add a PWI number, fill in
the appropriate details in the fields below the respective submissions. When you are finished,
click the "update rpc database" button to submit these changes to the database. Authors will then be
able to see the PWI number, your comments, and the acceptance status of their submission.</p>
<?php
	echo "<form action=\"schoolview.php\" method=\"get\">\n";
	echo "<b>Check publications for year:</b>\n";
	text_input("edit_pubs_for_year", $edit_pubs_for_year?$edit_pubs_for_year:"2005", 5, 5, "form.submit()", "");
	echo "<br>\n";
	echo "<b>Show publications:</b>\n";
	select_array("show_pubs_select",
					array(
						"unchecked_only",
						"checked_only",
						"all_pubs",
						"all_pubs_regardless",
						"rmit_authors"),
					"",
					array(
						"Unchecked by the school",
						"Checked by the school",
						"All publications with primary checks",
						"All publications",
						"RMIT Author Details Only"
						),
					"form.submit()", $show_pubs_select?$show_pubs_select:NULL, false);
	echo "</form>\n";
	if (!isset($edit_pubs_for_year)) {
		$edit_pubs_for_year = '2005';
	}
	
	$where = "";
	$au_where = "";
	if ($show_pubs_select == "rmit_authors") {
		$search_status = "primary_checked and (publication_year='$edit_pubs_for_year')";
		$search_rmit_author = "";
		$search_title = "";
		$search_author = "";
		$search_stnumber = "";
		$search_keywords = "";
	} else {
		$search_rmit_author = "%";
		$show_authors = true;
		$show_subjects = "rfc";
		$show_supporting = true;
		$show_timestamp = false;
		$show_affiliations = true;
		if (!isset($show_pubs_select)) {
			$show_pubs_select = "unchecked_only";
		}
		if ($show_pubs_select == "unchecked_only") {
			$search_status = "primary_checked and (not school_checked) and (publication_year='$edit_pubs_for_year')";
		} elseif ($show_pubs_select == "checked_only") {
			$search_status = "primary_checked and (school_checked) and (publication_year='$edit_pubs_for_year')";
		} elseif ($show_pubs_select == "all_pubs") {
			$search_status = "primary_checked and (publication_year='$edit_pubs_for_year')";
		} elseif ($show_pubs_select == "all_pubs_regardless") {
			$search_status = "(publication_year='$edit_pubs_for_year')";
		}
	}
//	$search_status = "primary_checked and (not school_checked)";
	
	if ($show_pubs_select == "rmit_authors") {
		div("result-head", "Authors found");
		$full_au_stnumb = array();
		$full_au_surnm = array();
		$full_au_firstnm = array();
		$full_au_authtitle = array();
		$full_au_gender = array();
		$full_au_type = array();
		$full_au_skoolkode = array();
		
		while (list($k,$v)=each($schema_table)) {
			$upbase = upload_base($k);
			$qwh = $au_where;
			if ($search_status && $search_status != 'any') {
				$qwh = "($qwh) and $search_status";
			}
			$query = "select stnumber,surname,firstname,author_title,gender,type,school_code from $v->name";
//				echo $query."<br>";

			$result = mysql_query($query);
			if ($result > 0) {
				$nitems = mysql_num_rows($result);
				if ($nitems > 0) {
					for($i=0; $i < $nitems; $i++) {
						$row = mysql_fetch_object($result);
						
						$au_stnumb = sqlschema_list_to_array($row->stnumber);
						$au_surnm = sqlschema_list_to_array($row->surname);
						$au_firstnm = sqlschema_list_to_array($row->firstname);
						$au_authtitle = sqlschema_list_to_array($row->author_title);
						$au_gender = sqlschema_list_to_array($row->gender);
						$au_type = sqlschema_list_to_array($row->type);
						$au_skoolkode = sqlschema_list_to_array($row->school_code);
						
						while (list($auk, $auv) = each($au_skoolkode)) {
							if ($auv != '-1') { // it is a valid rmit school code
								if (($au_stnumb[$auk] == "" || k_index_of(stripspace($au_stnumb[$auk]), $full_au_stnumb) < 0) &&
										 $au_surnm[$auk] && $au_firstnm[$auk] && $au_authtitle[$auk]){ // not yet in list
									$full_au_stnumb[] = stripspace($au_stnumb[$auk]);
									$full_au_surnm[] = $au_surnm[$auk];
									$full_au_firstnm[] = $au_firstnm[$auk];
									$full_au_authtitle[] = $au_authtitle[$auk];
									$full_au_gender[] = $au_gender[$auk];
									$full_au_type[] = $au_type[$auk];
									$full_au_skoolkode[] = $au_skoolkode[$auk];
								}
							}
						}
					}
				} else {
					// no authors in this category
				}
			} else {
				// mysql error on this query
			}
		}
		if (count($full_au_stnumb) > 0) {
			asort($full_au_surnm);
			reset($full_au_surnm);
			table_header(2,2,"", "","","90%");
			while (list($auk, $auv) = each($full_au_surnm)) {
				table_row();
				table_data_string($full_au_stnumb[$auk]);
				table_data_string($full_au_authtitle[$auk]);
				table_data_string($full_au_firstnm[$auk]);
				table_data_string($full_au_surnm[$auk]);
				table_data_string($full_au_gender[$auk]);
				table_data_string($full_au_type[$auk]);
				table_data_string($full_au_skoolkode[$auk]);
				table_data_string($rsc_type->Label($full_au_skoolkode[$auk]));
			}
			table_tail();
		} else {
			echo "No authors....<br>\n";
		}
	} elseif ($search_rmit_author) {
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
		//echo $au_query;
		$au_result = mysql_query($au_query);
		if (!$au_result) {
			echo "Database error: ", mysql_error();
		} else {

			$n_au_found = mysql_num_rows($au_result);
			if ($n_au_found >0) {
				form_header("schoolview.php", "schoolInputForm", "post");
				div("edit-controls");
				submit_input("schoolAction", "update rpc database");
				div();
				for($ai=0; $ai < $n_au_found; $ai++) {
					$au_row = mysql_fetch_object($au_result);
					$auth_head = "Publications for $au_row->stnumber, $au_row->title $au_row->firstname $au_row->surname";
					$heded = false;
					reset($schema_table);
					
					while (list($k,$v)=each($schema_table)) {
						$upbase = upload_base($k);
						$qwh = "first_author_stnumber='$au_row->stnumber'";
						if ($search_status && $search_status != 'any') {
							$qwh = "($qwh) and $search_status";
						}
						$query = "select * from $v->name where $qwh";
						//echo $query;
						$result = mysql_query($query);
						if ($result > 0) {
							$nitems = mysql_num_rows($result);
							if ($nitems > 0) {
								if (!$heded) {
									div("result-head", $auth_head);
									$heded = true;
								}
								div("result-title","<b><br>$nitems $v->label", ($nitems>1?"s ":" "), "for $au_row->stnumber, $au_row->title $au_row->firstname $au_row->surname</b>\n"); 
								for($i=0; $i < $nitems; $i++) {
									table_row();
									table_data_string("<font size=+1><b>$v->label ".($i+1)."</b></font><br>");
									table_data_string("&nbsp;");
									table_rend();
									
									table_header(0,0,"", "","","90%");
									$row = mysql_fetch_object($result);
									table_row();
									table_data_string("<b>Title</b>");
									table_data_string("&nbsp;");
									table_data_string($row->title);
									table_rend();

									table_row();
									table_data_string("<b>Keywords</b>");
									table_data_string("&nbsp;");
									table_data_string($row->keywords);
									table_rend();
		
									publication_details($row, $k);
									if ($show_authors) {
										author_list($row);
									}
									if ($show_subjects) {
										subject_breakdown($row, $show_subjects);
									}
									if ($show_affiliations) {
										affiliation_breakdown($row);
									}
									if ($show_timestamp) {
										timestamp_display($row);
									}
									if ($show_supporting) {
										supporting_materials($row, "../$upbase$row->code/", $k);
									}
									
									br();
									br();
									
									echo "<tr><td>&nbsp;</td></tr>\n";								
									echo "<tr><td>&nbsp;</td></tr>\n";								
									school_inputs($row, $k);
									
									table_tail();
									br();
									br();
									br();
								}
							}
						} else {
							echo "Mysql error ".mysql_error();
						}
					}
				}
				div("edit-controls");
				submit_input("schoolAction", $submit_changes_label);
				div();
				form_tail();
				br();br();
			} else {
				div("result-head", "There are no RMIT principal authors in this collection matching your criteria.");
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
			div("result-head", "Publications found");
			reset($schema_table);
			while (list($k,$v)=each($schema_table)) {
				$upbase = upload_base($k);
				$qwh = $au_where;
				if ($search_status && $search_status != 'any') {
					$qwh = "($qwh) and $search_status";
				}
				$query = "select * from $v->name where $qwh";
//				echo $query."<br>";
				$result = mysql_query($query);
				if ($result > 0) {
					$nitems = mysql_num_rows($result);
					if ($nitems > 0) {
						for($i=0; $i < $nitems; $i++) {
							table_row();
							echo "<td align=\"left\">";
							$row = mysql_fetch_object($result);
							table_header(0,0,"", "","","90%");
							$au_result = mysql_query("select * from people where stnumber='$row->first_author_stnumber'");
							if ($au_result && ($au_row = mysql_fetch_object($au_result))) {
								div("result-title","<b><br>$v->label publication for $row->first_author_stnumber, $au_row->title $au_row->firstname $au_row->surname</b>\n"); 
							} else {
								div("result-title","<b><br>$v->label publication for $row->first_author_stnumber (No other author information available</b>\n"); 
							}
							table_row();
							table_data_string("<font size=+1><b>$v->label ".($i+1)."</b></font><br>");
							table_data_string("&nbsp;");
							table_rend();
							
							table_header(0,0,"", "","","90%");
							table_row();
							table_data_string("<b>Title</b>");
							table_data_string("&nbsp;");
							table_data_string($row->title);
							table_rend();

							table_row();
							table_data_string("<b>Keywords</b>");
							table_data_string("&nbsp;");
							table_data_string($row->keywords);
							table_rend();
									
							publication_details($row);
							if ($show_authors) {
								author_list($row);
							}
							if ($show_subjects) {
								subject_breakdown($row, $show_subjects);
							}
							if ($show_affiliations) {
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
									
							echo "<tr><td>&nbsp;</td></tr>\n";								
							echo "<tr><td>&nbsp;</td></tr>\n";								
							school_inputs($row, $k);
							table_tail();
									
							br();
							br();
							br();
							
						}
					}
				} else {
					echo "Mysql error ".mysql_error();
				}
			}
			table_tail();
			br();br();
		}
	}
?>
<CENTER>
<TABLE CLASS="nav-grid" BORDER=0 CELLSPACING=0 CELLPADDING=0>
<TR>
<TD WIDTH=45 VALIGN=top ALIGN=center>
<A HREF="#top" CLASS="img-button" onClick="history.back(); return false;"><B>Back</B></A>
<TD WIDTH=45 VALIGN=top ALIGN=center>
<A HREF="#top" CLASS="img-button" onClick="history.forward(); return false;"><B>Forward</B></A>
<TD WIDTH=45 VALIGN=top ALIGN=center>
<A HREF="../index.php" CLASS="img-button"><B>DEST RPC Home</B></A>
<?php if ($sch_u && ($sch_p || $sch_p_e )): ?>
<TD WIDTH=45 VALIGN=top ALIGN=center>
<A HREF="../school/index.php?logout=user" CLASS="img-button"><B>Logout</B></A>
<?php endif; ?>
<TD WIDTH=45 VALIGN=top ALIGN=center>
<A HREF="#top" CLASS="img-button" onMouseUp="window.print(); return false;"><B>Print</B></A>
</CENTER>
<?php
	standard_page_bottom();
?>
