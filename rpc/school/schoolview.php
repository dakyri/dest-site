<?php
	error_reporting(3);
	import_request_variables("gp");
	
	if ($edit_pubs_for_year) {
		$posted_year = $edit_pubs_for_year;
		setcookie("edit_pubs_for_year", $edit_pubs_for_year);
	} else {
		$posted_year = false;
	}
	import_request_variables("c");
	if ($posted_year) {
		$edit_pubs_for_year = $posted_year;
	}		
	
	require_once("../common/necessary.php");
	require_once(in_parent_path("/common/sqlschema_types.php"));
	require_once(in_parent_path("/common/adminlib.php"));
	require_once("schoolfunc.php");
	
	function school_page_controls()
	{
		global	$show_pubs_select;
		global	$edit_pubs_for_year;
		global	$return_rpc_url;
		if (!$show_pubs_select) {
			$show_pubs_select = "unchecked_only";
		}
		if (!$return_rpc_url) {
			$return_rpc_url = "..";
		}
		
		echo "<form action=\"schoolview.php\" method=\"get\">\n";
	
		echo "<center>";
		div("edit-controls");

		echo "<table CELLPADDING='2' CELLSPACING='0' align='center'>\n";
		echo "<tr><td>";
		switch ($show_pubs_select) {
			case "unchecked_only":
				echo "<b>","View/Approve currently unapproved publications", "</b><br>";
				break;
			case "checked_only":
				echo "<b>","View/Edit currently approved publications", "</b><br>";
				break;
			case "all_pubs":
				echo "<b>","View/Edit all approved and unapproved publications that have passed the primary check", "</b><br>";
				break;
			case "all_pubs_regardless":
				echo "<b>","View/Edit all approved and unapproved publications", "</b><br>";
				break;
			case "rmit_authors":
				echo "<b>","View/Edit RMIT author details", "</b><br>";
				break;
			default:
				// anyones guess really
		}
	
	//<li><a href="schoolview.php?show_pubs_select=unchecked_only">View/Approve currently unapproved publications</a></li>
	//<li><a href="schoolview.php?show_pubs_select=checked_only">View/Edit currently approved publications</a></li>
	//<li><a href="schoolview.php?show_pubs_select=all_pubs">View/Edit all approved and unapproved publications that have passed the primary check</a></li>
	//<li><a href="schoolview.php?show_pubs_select=all_pubs_regardless">View/Edit all approved and unapproved publications</a></li>
	//<li><a href="schoolview.php?show_pubs_select=rmit_authors">View/Edit RMIT author details</a></li>
		echo "</td></tr>";
		echo "<tr><td align='center'>";
		echo "<b>Check publications for year:</b>\n";
		select_array("edit_pubs_for_year",
				array("2005","2006", "2007", "2008", "2009", "2010", "2011", "2012", "2013", "2014", "2015"), "",
				array("2005", "2006", "2007", "2008", "2009", "2010", "2011", "2012", "2013", "2014", "2015"),
				"form.submit()",
				$edit_pubs_for_year?$edit_pubs_for_year:"2005");
		
		echo "</td></tr>";
		hidden_field("show_pubs_select", $show_pubs_select);
		
//		echo "<td>";submit_input("rpcMode", "Show research quanta");	echo "</td>";
//		echo "<td>";
//			echo "<input type=\"submit\"",
//						" name=\"rpcMode\"",
//						" title=\"Saved in a file suitable for import to Excel: tab separated columns and newline separated rows\"",
//						" value=\"Save to text file\"",
//						">";
//			echo "</td>\n";
		echo "<td align='center'>";
			echo "<table CELLPADDING='2' CELLSPACING='0' align='center'>\n";
			echo "<tr>";
			echo "<td>";button_input("rpcMode",
				"Return to RPC Main",
				"location='".($return_rpc_url?$return_rpc_url:"index.php")."'");	echo "</td>";
			echo "<td>";button_input("rpcMode",
				"Return to school Menu",
				"location='index.php'");	echo "</td>";
			echo "<td>";submit_input("schoolMode", "Logout");echo "</td>";
			echo "</tr></table>\n";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		div();
		echo "</center>";
		echo "</form>\n";
	}
	
/////////////////////////////////////////////////////////
// database access specific to needs of school admin
//   identical to main view script, except for format
////////////////////////////////////////////////////////
	if ($schoolMode=="Logout") {
		setcookie("sch_u", "");
		setcookie("sch_p", "");
		header("Location: index.php?logout=true");
		exit();
	} else if (isset($sch_p) && $sch_p != "") {
		$mysql = get_database($database_name, $database_host, $sch_u, $sch_p);
		if ($mysql <= 0) {
			setcookie("sch_u", "");
			setcookie("sch_p", "");
			header("Location: index.php?login_msg=".urlencode("Please enter a valid password"));
			exit();
		}
		setcookie("sch_u", $sch_u);
		setcookie("sch_p", $sch_p);
	} else {
		header("Location: index.php?login_msg=".urlencode("Please enter a valid password"));
		exit();
	}
	
	$do_edit = false;
	$edit_entry_label = "Edit this entry";
	$edit_submit_label = "Submit changes";
	$pdf_cover_label = "Print cover sheet";
	$other_action_labels = array(
			$pdf_cover_label
		);
	if ($schoolAction == $edit_entry_label) {
		$do_edit = true;
	} elseif ($schoolAction == $pdf_cover_label) {
		$pdfschema_params = array();
		$pdfschema_params["table"] = $edit_pub_table;
		$pdfschema_params["code"] = $edit_pub_code;
		$pdfschema_params["user"] = $edit_pub_stnumber;
		$GLOBALS["pdfschema_params"] = $pdfschema_params;
		
		switch($edit_pub_table) {
			case "book":
				$xml_template = in_parent_path("template_master/book_cover.xpdf");
				break;
			case "chapter":
				$xml_template = in_parent_path("template_master/chapter_cover.xpdf");
				break;
			case "journal":
				$xml_template = in_parent_path("template_master/journal_cover.xpdf");
				break;
			case "conference":
				$xml_template = in_parent_path("template_master/conference_cover.xpdf");
				break;
			default:
				$xml_template = in_parent_path("template_master/cover_sheet.xpdf");
				break;
		}
		$schema_types = uncache_variable("../$schema_base_directory/$edit_pub_table"."_types.ser");
		$vrii_type = &$schema_types["vrii-type"];
		$rsc_type = &$schema_types["rsc-type"];

		require_once(in_parent_path("common/pdfschema.php"));

		if (!generate_schema_pdf(
				$xml_template,
				"cover_{$edit_pub_stnumber}_{$edit_pub_table}_{$edit_pub_code}.pdf",
				$pdfschema_params)) {
			errorpage($pdfschema_error_msg);
		}
		exit;
	} elseif ($schoolAction == $edit_submit_label) { // change to a button, or auto form 
		$action_msg = "";
		if ($edit_pub_table && $edit_pub_code) {
			$set_str = "school_comment='$edit_school_comment'";
			if ($edit_pwi_code) {
				$set_str .= ", pwi_code='$edit_pwi_code'";
			}
			if ($edit_school_check) {
				$set_str .= ", school_checked=1";
			} else {
				$set_str .= ", school_checked=0";
			}
			$query = "update $edit_pub_table set $set_str where code='$edit_pub_code'";
			if (!mysql_query($query)) {
				$action_msg = "Database update fails, ".mysql_error()."<br>";
			}
			$red_string = "Location: schoolview.php?";
			if ($action_msg) {
				$red_string .= "action_msg=$action_msg";
			}
			if ($show_pubs_select) {
				$red_string .= "show_pubs_select=$show_pubs_select";
			}
			header($red_string);
		} else {
			$action_msg = "Update failed, incorrect settings from edit page.";
		}
	}
	
	if (!isset($action_msg)) {
		$action_msg = "";
	}

///////////////////////////////////////////
// get database and table formats
///////////////////////////////////////////
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
	standard_page_top("DEST Research Publications Database", "../style/default.css", "page-school", "../images/title/dest_rpc.gif", 700, 72, "DEST Research Publication Collection: School Admin View", "../common/necessary.js");
	br("all");
	echo $schoolAction,"<br>";
	if ($action_msg) {
		echo "<p><b>$action_msg</b></p>\n";
	}
	if ($do_edit):
?>
<p>Welcome to the school administrator's page.</p>
<p>To make reccomendations regarding the publications on view, or to add a PWI number,
click "Edit this entry", fill in
the appropriate details in the form, and click the "Submit changes" button to make these changes to the database. Authors will then be
able to see the PWI number, your comments, and the acceptance status of their submission.</p>
<?php
	else:
?>
<p>Welcome to the school administrator's page.</p>
<p>To make reccomendations regarding the publications on view, or to add a PWI number,
click "Edit this entry", fill in
the appropriate details in the form, and click the "Submit changes" button to make these changes to the database. Authors will then be
able to see the PWI number, your comments, and the acceptance status of their submission.</p>
<?php
	endif;
	
	school_page_controls();
	
	if (!isset($edit_pubs_for_year)) {
		$edit_pubs_for_year = '2005';
	}
	
	$where = "";
	$au_where = "";
	if ($do_edit && $edit_pub_table && $edit_pub_code && $edit_pub_stnumber) {
	} else {
		$do_edit = false;
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
	}
//	$search_status = "primary_checked and (not school_checked)";
	
	if ($do_edit) {
		$au_query = "select * from people";
		$au_query .= " where (stnumber='$edit_pub_stnumber') and (kind != 'admin')";
		//echo $au_query;
		$au_result = mysql_query($au_query);
		if (!$au_result) {
			echo "<br><br>Database error while looking for user $edit_pub_stnumber: ", mysql_error();
		} else {
			$au_row = mysql_fetch_object($au_result);
			$heded = false;
			reset($schema_table);
					
			$upbase = upload_base($edit_pub_table);
			$query = "select * from $edit_pub_table where code='$edit_pub_code'";
			//echo $query;
			$result = mysql_query($query);
			if ($result > 0) {
				$nitems = mysql_num_rows($result);
				if ($nitems == 1) {
					div("result-title", "<b>$edit_pub_table for $au_row->title $au_row->firstname $au_row->surname, $au_row->stnumber</b>\n");
					table_header(0,0,"", "","","100%");
							
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
					publication_details($row, $edit_pub_table);
					author_list($row);
					subject_breakdown($row, $show_subjects);
					affiliation_breakdown($row);
					supporting_materials($row, "../$upbase$row->code/", $edit_pub_table);
					timestamp_display($row);
									
					echo "<tr><td>&nbsp;</td></tr>\n";								
					echo "<tr><td>&nbsp;</td></tr>\n";								
					school_inputs(
						$row, $edit_pub_table, $edit_pub_table, $show_pubs_select,
						$do_edit, $au_row->stnumber,
						$edit_entry_label, $edit_submit_label, $other_action_labels);
									
					table_tail();
					br();
					br();
					br();
				} else {
					echo "Wrong number of matching publications. Please contact the site administrator";
				}
			}
		}
	} elseif ($show_pubs_select == "rmit_authors") {
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
				for($ai=0; $ai < $n_au_found; $ai++) {
					$au_row = mysql_fetch_object($au_result);
					$auth_head = "Publications for $au_row->title $au_row->firstname $au_row->surname, $au_row->stnumber";
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
								div("result-title","<b><br>$nitems $v->label", ($nitems>1?"s ":" ")); 
								for($i=0; $i < $nitems; $i++) {
									echo "<a name='$k$row->code'></a>\n";
									div("result-title", "<b>$v->label ".($i+1)." for $au_row->title $au_row->firstname $au_row->surname, $au_row->stnumber</b>\n");
									table_header(0,0,"", "","","100%");
									
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
									
									echo "<tr><td>&nbsp;</td></tr>\n";								
									echo "<tr><td>&nbsp;</td></tr>\n";								
									school_inputs(
											$row, $k, $v->name, $show_pubs_select,
											 $do_edit, $au_row->stnumber,
											$edit_entry_label, $edit_submit_label, $other_action_labels);
									
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
							school_inputs(
									$row, $k, $v->name, $show_pubs_select,
									$do_edit, $row->first_author_stnumber,
									$edit_entry_label, $edit_submit_label, $other_action_labels);
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
//<CENTER>
//<TABLE CLASS="nav-grid" BORDER=0 CELLSPACING=0 CELLPADDING=0>
//<TR>
//<TD WIDTH=45 VALIGN=top ALIGN=center>
//<A HREF="../index.php" CLASS="img-button"><B>DEST RPC Home</B></A>
//<TD WIDTH=45 VALIGN=top ALIGN=center>
//<A HREF="index.php" CLASS="img-button"><B>School Admin Menu</B></A>
//<TD WIDTH=45 VALIGN=top ALIGN=center>
//<A HREF="index.php?logout=user" CLASS="img-button"><B>Logout</B></A>
//</CENTER>
	school_page_controls();
	standard_page_bottom();
?>
	