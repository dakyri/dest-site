<?php
	error_reporting(3);
	import_request_variables("gp");
	
	if ($track_year) {
		$posted_year = $track_year;
		setcookie("track_year", $track_year);
	} else {
		$posted_year = false;
	}
	import_request_variables("c");
	if ($posted_year) {
		$track_year = $posted_year;
	}		

	require_once("common/necessary.php");
	require_once(in_parent_path("common/sqlschema_types.php"));
	require("searchparams.php");
	
	$login_destination = "quantaview.php";
	$cancel_destination = "quantaview.php";
	
	require("login_chk.php");
	
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
	
	if (!isset($action_msg)) {
		$action_msg = "";
	}
	$track_by = 'author';
	
	function quanta_index_controls()
	{
		global	$login_type;
		global	$return_rpc_url;
		
		echo "<center>";
		div("edit-controls");
		echo "<table CELLPADDING='2' CELLSPACING='0' align='center'>\n";
		echo "<tr>\n";
		echo "<td>";submit_input("rpcMode", "Show research quanta");	echo "</td>";
		echo "<td>";
			echo "<input type=\"submit\"",
						" name=\"rpcMode\"",
						" title=\"Saved in a file suitable for import to Excel: tab separated columns and newline separated rows\"",
						" value=\"Save to text file\"",
						">";
			echo "</td>\n";
		echo "<td>";button_input("rpcMode",
				"Search Publications",
				"location='".($return_rpc_url?$return_rpc_url:"index.php")."'");	echo "</td>";
		if ($login_type > LOGIN_NONE) {
			if ($login_type == LOGIN_USER) {
			} else {
				if ($login_type == LOGIN_DBADMIN) {
					echo "<td>";button_input("adminZoneBut", "DB Admin Menu", "location='admin/'");echo "</td>";
				} else {
					echo "<td>";button_input("adminZoneBut", "Admin Menu", "location='admin/'");echo "</td>";
				}
			}
			echo "<td>";submit_input("rpcMode", "Logout");echo "</td>";
		} else {
			echo "<td>";submit_input("rpcMode", "Login");echo "</td>";
		}
		echo "</table>\n";
		div();
		echo "</center>";
	}

	function count_authors($row)
	{
		global	$track_rmit_quant1;
		
		$aunames = sqlschema_list_to_array($row->surname);
		$auschool = sqlschema_list_to_array($row->school_code);
		$cnt = 0;
		while (list($k,$v) = each($aunames)) {
			$v = str_replace(" ", "", $v);
			if ($v) {
				if ($track_rmit_quant1 == 'true') {
					if ($auschool[$k] && $auschool[$k] > 0) {
						$cnt++;
					}
				} else {
					$cnt++;
				}
			}
		}

		return $cnt==0?1:$cnt;
	}
	
	function sum(&$a)
	{
		$sum = 0;
		reset($a);
		while (list($k, $v) = each($a)) {
			if (is_array($v)) {
				$sum += sum($v);
			} else {
				$sum += $v;
			}
		}
		return $sum;
	}

	function mean(&$a)
	{
		$sum = 0;
		$n = 0;
		reset($a);
		while (list($k, $v) = each($a)) {
			if (is_array($v)) {
				while (list($kk, $vv) = each($v)) {
					$sum += $vv;
					$n++;
				}
			} else {
				$sum += $v;
				$n++;
			}
		}
		if ($n == 0) {
			return 0;
		}
		return $sum/$n;
	}

	function deviation(&$a)
	{
		$sum = 0;
		$sum2 = 0;
		$n = 0;
		reset($a);
		while (list($k, $v) = each($a)) {
			if (is_array($v)) {
				while (list($kk, $vv) = each($v)) {
					$sum += $vv;
					$sum2 += $vv*$vv;
					$n++;
				}
			} else {
				$sum += $v;
				$sum2 += $v*$v;
				$n++;
			}
		}
		if ($n == 0) {
			return 0;
		}
		$m1 = $sum/$n;
		$m2 = $sum2/$n;
		return sqrt($m2-($m1*$m1));
	}
	
	function impact_factor($pub_typ, $row, $track_impact)
	{
		if (!$track_impact || $track_impact == 'none') {
			return 1;
		}
		if ($pub_typ == 'book') {
			return 1;
		}
		if ($pub_typ == 'chapter') {
			return 1;
		}
		if ($pub_typ == 'journal') {
			$rnm = $row->journal_name;
			if (!ereg('[0-9A-Za-z]+', $rnm, $rnmwords)) {
				return 1;
			}
			$mtch = "%";
			while (list($k,$v)=each($rnmwords)) {
				$mtch .= "$v%";
			}
			$query = "select * from journal_info where journal_name like '$mtch'";
			$result = mysql_query($query);
			if ($result < 0) {
				echo "Mysql error looking for impact factor: ".mysql_error();
				return 1;
			}
			$nmatch = mysql_num_rows($result);
			if ($nmatch == 0) {
				return 1;
			}
			$jrow = mysql_fetch_object($result);
			$ift = sqlschema_list_to_array($jrow->impact_type);
			$iff = sqlschema_list_to_array($jrow->impact_factor);
			reset($iff);
			while (list($k,$v) = each($ift)) {
				if ($v == $track_impact) {
					return $iff[$k];
				}
			}
			return 1;
		}
		if ($pub_typ == 'conference') {
			$rnm = $row->conference_name;
			if (!ereg('[0-9A-Za-z]+', $rnm, $rnmwords)) {
				return 1;
			}
			$mtch = "%";
			while (list($k,$v)=each($rnmwords)) {
				$mtch .= "$v%";
			}
			$query = "select * from conference_info where conference_name like '$mtch'";
			$result = mysql_query($query);
			if ($result <= 0) {
				echo "Mysql error looking for impact factor: ".mysql_error();
				return 1;
			}
			$nmatch = mysql_num_rows($result);
			if ($nmatch == 0) {
				return 1;
			}
			$jrow = mysql_fetch_object($result);
			$ift = sqlschema_list_to_array($jrow->impact_type);
			$iff = sqlschema_list_to_array($jrow->impact_factor);
			reset($iff);
			while (list($k,$v) = each($ift)) {
				if ($v == $track_impact) {
					return $iff[$k];
				}
			}
			return 1;
		}
		return 1;
	}

	function accumulate_pubs($qwh, &$count, &$quant, &$quimp, $track_impact)
	{
		global	$schema_table;
		
		reset($schema_table);
		while (list($k,$v)=each($schema_table)) {
			$query = "select * from $v->name where $qwh";
			$result = mysql_query($query);
			if ($result > 0) {
				$nitems = mysql_num_rows($result);
				if ($nitems > 0) {
					for($i=0; $i < $nitems; $i++) {
						$row = mysql_fetch_object($result);
						$n_row_authors = count_authors($row);
						$quant[$v->name] += (1/$n_row_authors);
						$impfact = impact_factor($v->name, $row, $track_impact);
						$quimp[$v->name] += ($impfact/$n_row_authors);
						$count[$v->name]++;
					}
				}
			} else {
				echo "Mysql error ".mysql_error();
			}
		}
	}
///////////////////////////////////////////
// get database and table formats
///////////////////////////////////////////
	$schema_idx_path = in_parent_path("$schema_base_directory/$pub_schema_idx_name");
	if (file_exists($schema_idx_path)) {
		$uploaded_schema = uncache_variable($schema_idx_path);
	} else {
		errorpage("publication format index '$schema_idx_path' does not exist");
	}
	if (!$uploaded_schema) {
		errorpage("publication format index '$schema_idx_path' is empty");
	}
	while (list($k,$v)=each($uploaded_schema)) {
		$scv = uncache_variable(in_parent_path("$schema_base_directory/$k"."_tables.ser"));
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
	
	if (!$track_by) {
		$track_by = "author";
	}
	if (!isset($track_year)) {
		$track_year = '2005';
	}
	if (!isset($track_score_book)) {
		$track_score_book = 4;
	}
	if (!isset($track_score_chapter)) {
		$track_score_chapter = 3;
	}
	if (!isset($track_score_journal)) {
		$track_score_journal = 2;
	}
	if (!isset($track_score_conference)) {
		$track_score_conference = 1;
	}
	if (!isset($track_score_project_earning)) {
		$track_score_project_earning = .001;
	}
	if (!isset($track_score_completion)) {
		$track_score_completion = 1;
	}
	$track_by = "author";
	$where = "";
	$au_where = "";
	if ($track_checked_pubs == 'true') {
		$search_status = "school_checked";
	} else {
		$search_status = "";
	}
	$n_au_found = 0;
	if ($track_by == "author") {
		$au_where = "kind = 'staff'";
		$au_query = "select * from people";
		if ($au_where) {
			$au_query .= " where ($au_where) and (kind != 'admin')";
		}
		if ($au_order) {
			$au_query .= " order $au_order";
		}
		$au_result = mysql_query($au_query, $mysql);
		if (!$au_result) {
			errorpage("Database error: ". mysql_error());
		} else {
			$n_au_found = mysql_num_rows($au_result);
			if ($n_au_found > 0) {
				$au_list_title = array();
				$au_pub_summary = array();
				$au_item_count = array();
				$au_quant_total = array();
				$au_score_total = array();
				$au_impscore_total = array();
				$au_completion_total = array();
				$au_completion_score = array();
				$au_project_total = array();
				$au_project_quant = array();
				$au_project_score = array();
				$au_name = array();
				
				for($ai=0; $ai < $n_au_found; $ai++) {
					$au_row = mysql_fetch_object($au_result);
					$auth_head = "$au_row->title $au_row->firstname $au_row->surname, $au_row->stnumber";
					$au_name[$au_row->stnumber] = "$au_row->title $au_row->firstname $au_row->surname";
					$au_list_title[$au_row->stnumber] =
						"<font size=-1>\n".
						anchor_str($auth_head, "publications.php?search_stnumber=$au_row->stnumber&show_arc=true").
						"</font>";
						
					$qwh = "(stnumber like '%$au_row->stnumber%' || first_author_stnumber='$au_row->stnumber')&& publication_year='$track_year'";
					if ($search_status && $search_status != 'any') {
						$qwh = "($qwh) and $search_status";
					}
					$pt_item_quant = array();
					$pt_item_quimp = array();
					$pt_item_count = array();
					
					reset($schema_table);
					while (list($k,$v)=each($schema_table)) {
						$pt_item_count[$v->name] = 0;
						$pt_item_quant[$v->name] = 0;
						$pt_item_quimp[$v->name] = 0;
					}
					$pt_item_count["completions"] = 0;
					$pt_item_quant["completions"] = 0;
					$pt_item_quimp["completions"] = 0;
					
					$pt_item_count["projects"] = 0;
					$pt_item_quant["projects"] = 0;
					$pt_item_quimp["projects"] = 0;
					
					accumulate_pubs($qwh, $pt_item_count, $pt_item_quant, $pt_item_quimp, $track_impact);

					$au_pub_summary[$au_row->stnumber] = array();
					reset($schema_table);
					while (list($k,$v)=each($schema_table)) {
						$au_pub_summary[$au_row->stnumber][$v->name] = $pt_item_count[$v->name];
					}
					if ($track_projects_of) {
						$prj_query = "select * from research_project";
						$qwh = "(stnumber like '%$au_row->stnumber%' || owning_researcher_stnumber='$au_row->stnumber')&& (start_year<='$track_year' || start_year='none') && (end_year>='$track_year' || end_year='none')";
						if ($qwh) {
							$prj_query .= " where $qwh";
						}
						$prj_result = mysql_query($prj_query, $mysql);
						if (!$prj_result) {
							errorpage("Database error searching for research projects: ".mysql_error()."<br>");
						} else {
							$n_prj_items = mysql_num_rows($prj_result);
							if ($n_prj_items > 0) {
								for($i=0; $i < $n_prj_items; $i++) {
									$row = mysql_fetch_object($prj_result);
									$n_row_authors = count_authors($row);
									$pt_item_quant["projects"] += (1/$n_row_authors);
									$earn_yrs = sqlschema_list_to_array($row->income_year);
									$earn_amt = sqlschema_list_to_array($row->income_amount);
									$nyrs = count($earn_yrs);
									$impfact = 0;
									for ($yi=0; $yi<$nyrs; $yi++) {
										if ($earn_yrs[$yi] == $track_year) {
											$impfact += ($earn_amt[$yi] * $track_score_project_earning);
										}
									}
									$pt_item_quimp["projects"] += ($impfact/$n_row_authors);
									$pt_item_count["projects"]++;
								}
							}
						}
					}
					if ($track_completions_of) {
						$st_query = "select * from students";
						$st_query .= " where (supervisor='$au_row->stnumber') and (completion_year = '$track_year')";
						$st_result = mysql_query($st_query, $mysql);
						if (!$st_result) {
							errorpage("Database error searching for completing students: ".mysql_error()."<br>");
						} else {
							$n_st_items = mysql_num_rows($st_result);
							$pt_item_count["completions"]+=$n_st_items;
							$pt_item_quant["completions"]+=$n_st_items; // what if multiple supervisors????
							$pt_item_quimp["completions"]+=$n_st_items*$track_score_completion;
						}
					}
					if ($track_student) {
						$st_query = "select stnumber from students";
						if ($st_where) {
							$st_query .= " where (supervisor='$au_row->stnumber') and (commence_year <= '$track_year') and (completion_year = '' or completion_year = 'none' or completion_year >= '$track_year')";
						}
						$st_result = mysql_query($st_query, $mysql);
						if (!$st_result) {
							errorpage("Database error searching for supervised students: ".mysql_error()."<br>");
						} else {
							$nstudes = mysql_num_rows($st_result);
							if ($nstudes > 0) {
								$st_item_quant = array();
								$st_item_quimp = array();
								$st_item_count = array();
								
								reset($schema_table);
								while (list($k,$v)=each($schema_table)) {
									$st_item_count[$v->name] = 0;
									$st_item_quant[$v->name] = 0;
									$st_item_quimp[$v->name] = 0;
								}
					
								for ($si=0; $si<$nstudes; $si++) {
									$st_row = mysql_fetch_object($st_result);
									$qwh = "(publication_year='$track_year')";
									$qwh .= "&&(stnumber like '%$st_row->stnumber%' || first_author_stnumber='$st_row->stnumber')";

									accumulate_pubs($qwh, $st_item_count, $st_item_quant, $st_item_quimp, $track_impact);
								}
								
								while (list($k,$v)=each($schema_table)) {
									$au_pub_summary[$au_row->stnumber][$v->name] += $st_item_count[$v->name];
									
									$pt_item_count[$v->name] += $st_item_count[$v->name];
									$pt_item_quant[$v->name] += $st_item_quant[$v->name];
									$pt_item_quimp[$v->name] += $st_item_quimp[$v->name];
								}
								if ($track_projects_of) {
									for ($si=0; $si<$nstudes; $si++) {
										$st_row = mysql_fetch_object($st_result);
										$qwh = "(stnumber like '%$st_row->stnumber%' || owning_researcher_stnumber='$st_row->stnumber')&& (start_year<='$track_year' || start_year='none') && (end_year>='$track_year' || end_year='none')";
										$prj_query = "select * from research_project";
										if ($qwh) {
											$prj_query .= " where $qwh";
										}
										$prj_result = mysql_query($prj_query, $mysql);
										if (!$prj_result) {
											errorpage("Database error searching for research projects: ".mysql_error()."<br>");
										} else {
											$n_prj_items = mysql_num_rows($prj_result);
											if ($n_prj_items > 0) {
												for($i=0; $i < $n_prj_items; $i++) {
													$row = mysql_fetch_object($prj_result);
													$n_row_authors = count_authors($row);
													$pt_item_quant["projects"] += (1/$n_row_authors);
													$earn_yrs = sqlschema_list_to_array($row->income_year);
													$earn_amt = sqlschema_list_to_array($row->income_amount);
													$nyrs = count($earn_yrs);
													$impfact = 0;
													for ($yi=0; $yi<$nyrs; $yi++) {
														if ($earn_yrs[$yi] == $track_year) {
															$impfact += $earn_amt[$yi] * $track_score_project_earning;
														}
													}
													$pt_item_quimp["projects"] += ($impfact/$n_row_authors);
													$pt_item_count["projects"]++;
												}
											}
										}
									}
								}
							}
						}
					}
					

					$au_item_count[$au_row->stnumber] = 0;
					$au_quant_total[$au_row->stnumber] = array();
					$au_score_total[$au_row->stnumber] = 0;
					$au_impscore_total[$au_row->stnumber] = 0;
					
					reset($schema_table);
					while (list($k,$v)=each($schema_table)) {
						$au_quant_total[$au_row->stnumber][$v->name] = 0;
					}
					
					$au_completion_total[$au_row->stnumber] = $pt_item_count["completions"];
					$au_completion_score[$au_row->stnumber] = $pt_item_quimp["completions"];
					$au_project_total[$au_row->stnumber] = $pt_item_count["projects"];
					$au_project_quant[$au_row->stnumber] = $pt_item_quant["projects"];
					$au_project_score[$au_row->stnumber] = $pt_item_quimp["projects"];

					reset($schema_table);
					while (list($k,$v)=each($schema_table)) {
						$score_nm = "track_score_$v->name";
						$au_item_count[$au_row->stnumber]+= $pt_item_count[$v->name];
						$au_quant_total[$au_row->stnumber][$v->name] += $pt_item_quant[$v->name];
						$au_score_total[$au_row->stnumber] += $pt_item_quant[$v->name]*$$score_nm;
						$au_impscore_total[$au_row->stnumber] += $pt_item_quimp[$v->name]*$$score_nm;
					}
				}
			}
		}
	}
	if ($rpcMode == "Save to text file") {
		header("Content-Type: text/plain");
		header("Content-Disposition: attachment; filename=\""."quanta_track-".date("d-m-y")."\"");
		if ($n_au_found >0) {
// now spit out the table				
			echo "Staff Number","\t";
			echo "Name","\t";
			reset($schema_table);
			while (list($k,$v)=each($schema_table)) {
				echo ucfirst($v->name), "s","\t";
			}
			reset($schema_table);
			while (list($k,$v)=each($schema_table)) {
				echo ucfirst($v->name), " Quanta","\t";
			}
			echo "Publication Score", "\t";
			if ($track_impact != 'none') {
				echo "Impact adjusted Score\t";
			}
			if ($track_completions_of || $track_projects_of) {
				if ($track_completions_of) {
					echo "Student Completions","\t";
					echo "Completions Score","\t";
				}
				if ($track_projects_of) {
					echo "Research Projects","\t";
					echo "Project Quanta \t";
					echo "Project Score \t";
				}
				echo "Total Score","\t";
			}
			echo "\n";
			reset($au_list_title);
			while (list($auk,$auv) = each($au_list_title)) {
				echo $auk, "\t";
				echo $au_name[$auk], "\t";
				reset($au_pub_summary[$auk]);
				reset($schema_table);
				while (list($k,$v)=each($schema_table)) {
					echo $au_pub_summary[$auk][$v->name], "\t";
				}
				reset($schema_table);
				while (list($k,$v)=each($schema_table)) {
					printf("%.3f", $au_quant_total[$auk][$v->name]); echo "\t";
				}
				printf("%.3f", $au_score_total[$auk]); echo "\t";
				if ($track_impact != 'none') {
					printf("%.3f", $au_impscore_total[$auk]); echo "\t";
				}
				if ($track_completions_of || $track_projects_of) {
					if ($track_completions_of) {
						printf("%d", $au_completion_total[$auk]);
						echo "\t";
						printf("%.3f", $au_completion_score[$auk]);
						echo "\t";
					}
					if ($track_projects_of) {
						printf("%d", $au_project_total[$auk]);
						echo "\t";
						printf("%.3f", $au_project_quant[$auk]);
						echo "\t";
						printf("%.3f", $au_project_score[$auk]);
						echo "\t";
					}
					printf("%.3f", $au_completion_score[$auk]+$au_impscore_total[$auk]+$au_project_score[$auk]);
					echo "\t";
				}
				echo "\n";
			}
		}
		exit;
	}
///////////////////////////////////////////////////////////////
// top of the page"result-head"
///////////////////////////////////////////////////////////////
	standard_page_top("DEST Research Publications Database", "style/default.css", "page-noframe",
			"images/title/research_tracking.gif", 
			561, 72, "DEST RPC: Research Quantum Tracking", "../common/necessary.js");
	br("all");
	if ($action_msg) {
		echo "<p><b>$action_msg</b></p>\n";
	}
	echo "<p>Welcome to the research quanta tracking page.</p>";

	echo "<form name=\"quantaForm\" action=\"quantaview.php\" method=\"get\">\n";
	quanta_index_controls();
	
	table_header(2, 2);
	
	table_row();
	table_data();
	echo "<b>Show research quanta for year</b>\n";
	table_dend();
	table_data();
	select_array("track_year",
			array("2005","2006", "2007", "2008", "2009", "2010", "2011", "2012", "2013", "2014", "2015"), "",
			array("2005", "2006", "2007", "2008", "2009", "2010", "2011", "2012", "2013", "2014", "2015"),
			"form.submit()",
			$track_year?$track_year:"2005");
	table_dend();
	table_rend();
	
//	table_row();
//	table_data();
//	echo "<b>Show research quanta by</b>\n";
//	table_dend();
//	table_data();
//	select_array("track_by",
//			array("author", "group"), "",
//			array("Author", "Research Group"), "",
//			$track_by);
//	table_dend();
//	table_rend();
	
	table_row();
	table_data();
	echo "<b>Include impact factors</b>\n";
	table_dend();
	table_data();
	select_array("track_impact",
			array("none", "isi"), "",
			array("Not", "ISI Impact factor"),
			"if (value == 'none') show_spans('ifup',false); else show_spans('ifup',true); ",
			$track_impact);
	table_dend();
	table_rend();
	
	table_row();
	table_data();
	echo "<b>Include research by supervised students</b>\n";
	table_dend();
	table_data();
	checkbox_input("track_student", "true", $track_student=="true");
	table_dend();
	table_rend();
	
	table_row();
	table_data();
	echo "<b>Track only checked publications</b>\n";
	table_dend();
	table_data();
	checkbox_input("track_checked_pubs", "true", $track_checked_pubs=="true");
	table_dend();
	table_rend();
	
	table_row();
	table_data();
	echo "<b>Divide quanta only between RMIT authors</b>\n";
	table_dend();
	table_data();
	checkbox_input("track_rmit_quant1", "true", $track_rmit_quant1=="true");
	table_dend();
	table_rend();
	
	table_row();
	table_data();
	echo "<b>Include research projects</b>\n";
	table_dend();
	table_data();
	checkbox_input("track_projects_of", "true", $track_projects_of=="true", NULL, NULL, "if (checked) {show_spans('rfup',true);track_score_project_earning.style.visibility='visible';} else { show_spans('rfup',false);track_score_project_earning.style.visibility='hidden';} ");
	table_dend();
	table_rend();
	
	table_row();
	table_data();
	echo "<b>Include completions of supervised students</b>\n";
	table_dend();
	table_data();
	checkbox_input("track_completions_of", "true", $track_completions_of=="true", NULL, NULL, "if (checked) {show_spans('scup',true);track_score_completion.style.visibility='visible';} else { show_spans('scup',false);track_score_completion.style.visibility='hidden';} ");
	table_dend();
	table_rend();
	
	table_row();
	table_data('left','top');
	echo "<b>Scoring function</b>\n";
	table_dend();
	table_data();
	table_header(1,1);
	table_row();
	table_data(); echo "&nbsp;&#931;&nbsp;(book publications&nbsp;/&nbsp;no. of authors)&nbsp;&#215;";
		table_data(); text_input("track_score_book", $track_score_book?$track_score_book:"4", 5, 5,
			 "x=parseFloat(value);value = isNaN(x)?0:x;", "");	echo "<br>\n";
	table_rend();
	table_row();
	table_data(); echo "+&nbsp;&#931;&nbsp;(chapte&nbsp;publications&nbsp;/&nbsp;no.&nbsp;of&nbsp;authors)&nbsp;&#215;";  table_data(); text_input("track_score_chapter", $track_score_chapter?$track_score_chapter:"3", 5, 5,
			 "x=parseFloat(value);value = isNaN(x)?0:x;", "");	echo "<br>\n";
	table_rend();
	table_row();
	table_data(); echo "+&nbsp;&#931;&nbsp;(journal&nbsp;publications&nbsp;/&nbsp;no.&nbsp;of&nbsp;authors)&nbsp;&#215;";
		table_data(); text_input("track_score_journal", $track_score_journal?$track_score_journal:"2", 5, 5,
			 "x=parseFloat(value);value = isNaN(x)?0:x;", "");
			 echo "<span name='ifup' style='visibility:hidden'>&#215; journal impact factor</span>";
			 echo "<br>\n";
	table_rend();
	table_row();
	table_data(); echo "+&nbsp;&#931;&nbsp;(conference&nbsp;publications&nbsp;/&nbsp;no.&nbsp;of&nbsp;authors)&nbsp;&#215;";
		table_data(); text_input("track_score_conference", $track_score_conference?$track_score_conference:"1", 5, 5,
			 "x=parseFloat(value);value = isNaN(x)?0:x;", "");
			 echo "<span name='ifup' style='visibility:hidden'>&#215; conference impact factor</span>";
			 echo "<br>\n";
	table_rend();
	table_row();
	table_data();
			echo "<span name='rfup' style='visibility:hidden'>",
			"+&nbsp;&#931;&nbsp;(research&nbsp;project&nbsp;income&nbsp;/&nbsp;no.&nbsp;of&nbsp;investigators)&nbsp;&#215;";
		table_data(); text_input("track_score_project_earning", $track_score_project_earning?$track_score_project_earning:".001", 5, 5,
			 "x=parseFloat(value);value = isNaN(x)?0:x;", "");
			 echo "</span>";
			 echo "<br>\n";
	table_rend();
	table_row();
	table_data();
			echo "<span name='scup' style='visibility:hidden'>",
			"+&nbsp;&#931;&nbsp;(student&nbsp;completions)&nbsp;&#215;";
		table_data(); text_input("track_score_completion", $track_score_completion?$track_score_completion:"1", 5, 5,
			 "x=parseFloat(value);value = isNaN(x)?0:x;", "");
			 echo "</span>";
			 echo "<br>\n";
	table_rend();
	table_tail();
	
	table_dend();
	table_rend();
	
	
	table_tail();
	echo "</form>\n";
?>
<script type="text/javascript" LANGUAGE="javascript">
	if (quantaForm.track_completions_of.checked) {
		show_spans('scup',true);
		quantaForm.track_score_completion.style.visibility='visible';
	} else {
		show_spans('scup',false);
		quantaForm.track_score_completion.style.visibility='hidden';
	}
	if (quantaForm.track_projects_of.checked) {
		show_spans('rfup',true);
		quantaForm.track_score_project_earning.style.visibility='visible';
	} else {
		show_spans('rfup',false);
		quantaForm.track_score_project_earning.style.visibility='hidden';
	}
	if (quantaForm.track_impact.value == 'none') {
		show_spans('ifup',false);
	} else {
		show_spans('ifup',true); 
	}
	if (kd_ret_nosubmit != undefined) {
		quantaForm.track_year.onkeydown = kd_ret_nosubmit;
		quantaForm.track_score_book.onkeydown = kd_ret_nosubmit;
		quantaForm.track_score_chapter.onkeydown = kd_ret_nosubmit;
		quantaForm.track_score_journal.onkeydown = kd_ret_nosubmit;
		quantaForm.track_score_conference.onkeydown = kd_ret_nosubmit;
	}
</script>
<?php
	
//	reset($uploade	$track_by = "author";d_schema);
//	while (list($k,$v) = each($uploaded_schema)) {
//		$score_var = "track_score_$k";
//		echo "$k -> ".$$score_var." point<br>";
//	}

	if ($track_by == "author") {
		if ($n_au_found >0) {
// now spit out the table				
			table_header(1,0,NULL,NULL,1);
			table_row();
			echo "<td>","<b>Author</b>","</td>";
			reset($schema_table);
			while (list($k,$v)=each($schema_table)) {
				echo "<td>","<b>",ucfirst($v->name), "s","</b>","</td>","\t";
			}
			reset($schema_table);
			while (list($k,$v)=each($schema_table)) {
				echo "<td>","<b>",ucfirst($v->name), "<br>Quanta","</b>","</td>","\t";
			}
			echo "<td>","<b>","Publications Score","</b>","</td>";
			if ($track_impact != 'none') {
				echo "<td>","<b>","Impact adjusted Score","</b>","</td>";
			}
			if ($track_completions_of || $track_projects_of) {
				if ($track_completions_of) {
					echo "<td>","<b>","Student Completions","</b>","</td>";
					echo "<td>","<b>","Completions Score","</b>","</td>";
				}
				if ($track_projects_of) {
					echo "<td>","<b>","Research Projects","</b>","</td>";
					echo "<td>","<b>","Project Quanta","</b>","</td>";
					echo "<td>","<b>","Project Score","</b>","</td>";
				}
				echo "<td>","<b>","Total Score","</b>","</td>";
			}
			table_rend();
			reset($au_list_title);
			while (list($auk,$auv) = each($au_list_title)) {
				table_row();
				table_data('left','top'); echo $auv; table_dend();
				reset($au_pub_summary[$auk]);
				while (list($k,$v)=each($au_pub_summary[$auk])) {
					table_data('left','top'); echo $v; table_dend();
				}
//					table_data('center','top'); echo $au_item_count[$auk]; table_dend();
//					table_data('center','top'); printf("%.3f", $au_quant_total[$auk]); table_dend();
				while (list($k,$v)=each($au_quant_total[$auk])) {
					table_data('left','top'); printf("%.3f", $v); table_dend();
				}
				table_data('center','top'); printf("%.3f", $au_score_total[$auk]); table_dend();
				if ($track_impact != 'none') {
					table_data('center','top'); printf("%.3f", $au_impscore_total[$auk]); table_dend();
				}
				if ($track_completions_of || $track_projects_of) {
					if ($track_completions_of) {
						table_data('center','top');
							printf("%d", $au_completion_total[$auk]);
						table_dend();
						table_data('center','top');
							printf("%.3f", $au_completion_score[$auk]);
						table_dend();
					}
					if ($track_projects_of) {
						table_data('center','top');
							printf("%d", $au_project_total[$auk]);
						table_dend();
						table_data('center','top');
							printf("%.3f", $au_project_quant[$auk]);
						table_dend();
						table_data('center','top');
							printf("%.3f", $au_project_score[$auk]);
						table_dend();
					}
					table_data('center','top');
						printf("%.3f", $au_completion_score[$auk]+$au_impscore_total[$auk]+$au_project_score[$auk]);
					table_dend();
				}
				table_rend();
			}
			table_tail();
			br();br();
		} else {
			div("result-head", "There are no RMIT principal authors in this collection matching your criteria.");
		}
		echo "Totals<br>"; 
		printf("Quanta %.3f (mean %.3f, &#963; %.3f)<br>\n",
					sum($au_quant_total),
					mean($au_quant_total),
					deviation($au_quant_total));
		printf("Score %.3f (mean %.3f, &#963; %.3f)<br>\n",
					sum($au_score_total),
					mean($au_score_total),
					deviation($au_score_total));
	}
	standard_page_bottom();
?>
