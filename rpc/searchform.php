<?php
	if ($login_type > LOGIN_NONE &&
			file_exists("$schema_base_directory/$pub_schema_idx_name")) {
		$pubschema = uncache_variable("$schema_base_directory/$pub_schema_idx_name");
	}

	function main_index_controls()
	{
		global	$login_type;
		global	$pubschema;
		echo "<center>";
		div("edit-controls");
		echo "<table CELLPADDING='2' CELLSPACING='0' border='0' align='center'>\n";
		if ($login_type == LOGIN_USER) {
			echo "<tr>";
			while (list($k,$v) = each($pubschema)) {
				echo "<td>";
				button_input(
						"userAdd{$k}but",
						"Add ".ucfirst($k)." Entry",
						"location='edit/user_ed_schema_db.php?insert_single=true&sqlschema=$k'");
				echo "</td>";
			}
			echo "<td>";
			button_input(
					"userAddProjectBut",
					"Add Research Project",
					"location='edit/user_ed_schema_db.php?insert_single=true&sqlschema=research_project'");
			echo "</td>";
			echo "</tr>";
			echo "<tr>\n";
			echo "<td>";
			button_input(
					"userAddStudentBut",
					"Add Research Student",
					"location='edit/user_ed_schema_db.php?insert_single=true&sqlschema=students'");
			echo "</td>";
		} else {
			echo "<tr>\n";
		}
		echo "<td>";submit_input("rpcMode", "Search");	echo "</td>";
		echo "<td>";button_input("rpcMode", "Research Tracking", "location='quantaview.php'");	echo "</td>";
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
////////////////////////////////////////////////////////
// search form for rpc datbase
////////////////////////////////////////////////////////
?>
<script type='text/javascript'>
function setSearchModeControls(val, ss_val)
{
	var	displayTableRow ='table-row';
	var	selectAddStart = null;
	if(navigator.appName.indexOf("Microsoft") > -1){
		displayTableRow = 'block';
		selectAddStart = 0;
	}
	keywordrow = document.getElementById('srch_keyword_row');
	yearrow = document.getElementById('srch_year_row');
	citerow = document.getElementById('srch_cite_row');
	subjrow = document.getElementById('srch_subj_row');
	statrow = document.getElementById('srch_status_row');
	titlrow = document.getElementById('srch_title_row');
	anyprow = document.getElementById('srch_anyperson_row');
	rprinprow = document.getElementById('srch_rmitprincipal_row');
	rauthrow = document.getElementById('srch_rmitauthor_row');
	anyp_lbl = document.getElementById('srch_anyperson_lbl');
	rprinp_lbl = document.getElementById('srch_rmitprincipal_lbl');
	rauth_lbl = document.getElementById('srch_rmitauthor_lbl');
	titl_lbl = document.getElementById('srch_title_lbl');
	statL = document.searchForm.search_status;
	switch (val) {
		case "publications": {
			if (keywordrow != undefined) {
				keywordrow.style.display = displayTableRow;
			}
			if (yearrow != undefined) {
				yearrow.style.display = displayTableRow;
			}
			if (citerow != undefined) {
				citerow.style.display = displayTableRow;
			}
			if (subjrow != undefined) {
				subjrow.style.display = displayTableRow;
			}
			if (statrow != undefined) {
				statrow.style.display = displayTableRow;
			}
			if (titlrow != undefined) {
				titlrow.style.display = displayTableRow;
			}
			if (anyprow != undefined) {
				anyprow.style.display = displayTableRow;
			}
			if (rprinprow != undefined) {
				rprinprow.style.display = displayTableRow;
			}
			if (rauthrow != undefined) {
				rauthrow.style.display = displayTableRow;
			}
			if (rprinp_lbl != undefined) {
				rprinp_lbl.innerHTML = "<b>Principal RMIT Author</b><br>(Name or Staff/Student Number, may be a list)";
			}
			if (rauth_lbl != undefined) {
				rauth_lbl.innerHTML = "<b>RMIT Author</b><br>(Staff/Student Number: may be a list)";
			}
			if (anyp_lbl != undefined) {
				anyp_lbl.innerHTML = "<b>Author from any institution</b><br>(Name(s): may be a list)";
			}
			if (titl_lbl != undefined) {
				titl_lbl.innerHTML = "<b>Title</b>";
			}
			if (statL != undefined) {
				while (statL.length > 0) {
					statL.remove(0);
				}
				statL.add(new Option('Any', 'any'),selectAddStart);
				statL.add(new Option('Passed Primary Checks', 'primary'),selectAddStart);
				statL.add(new Option('Not Passed Primary Checks', 'nprimary'),selectAddStart);
				statL.add(new Option('Accepted by School', 'school'),selectAddStart);
				statL.add(new Option('Accepted by Portfolio', 'portfolio'),selectAddStart);
				statL.add(new Option('Pending acceptance by School', 'nschool'),selectAddStart);
				statL.add(new Option('Pending acceptance by Portfolio', 'nportfolio'),selectAddStart);
				statL.value = (ss_val!=null)?ss_val:'any';
			}
			break;
		}
		case "projects": {
			if (keywordrow != undefined) {
				keywordrow.style.display = displayTableRow;
			}
			if (yearrow != undefined) {
				yearrow.style.display = displayTableRow;
			}
			if (citerow != undefined) {
				citerow.style.display = 'none';
			}
			if (subjrow != undefined) {
				subjrow.style.display = 'none';
			}
			if (statrow != undefined) {
				statrow.style.display = 'none';
			}
			if (titlrow != undefined) {
				titlrow.style.display = displayTableRow;
			}
			if (anyprow != undefined) {
				anyprow.style.display = 'none';
			}
			if (rprinprow != undefined) {
				rprinprow.style.display = displayTableRow;
			}
			if (rauthrow != undefined) {
				rauthrow.style.display = displayTableRow;
			}
			if (rprinp_lbl != undefined) {
				rprinp_lbl.innerHTML = "<b>Principal RMIT Researcher</b><br>(Name or Staff number, may be a list)";
			}
			if (rauth_lbl != undefined) {
				rauth_lbl.innerHTML = "<b>Researcher (CI)</b><br>(Name(s) or Staff/Student Number: may be a list)";
			}
			if (anyp_lbl != undefined) {
//				anyp_lbl.innerHTML = "<b>Author from any institution</b><br>(by name(s): may be a list)";
			}
			if (titl_lbl != undefined) {
				titl_lbl.innerHTML = "<b>Project Name</b>";
			}
			break;
		}
		case "authors": {
			if (keywordrow != undefined) {
				keywordrow.style.display = 'none';
			}
			if (yearrow != undefined) {
				yearrow.style.display = 'none';
			}
			if (citerow != undefined) {
				citerow.style.display = 'none';
			}
			if (subjrow != undefined) {
				subjrow.style.display = 'none';
			}
			if (statrow != undefined) {
				statrow.style.display = displayTableRow;
			}
			if (titlrow != undefined) {
				titlrow.style.display = 'none';
			}
			if (anyprow != undefined) {
				anyprow.style.display = displayTableRow;
			}
			if (rprinprow != undefined) {
				rprinprow.style.display = 'none';
			}
			if (rauthrow != undefined) {
				rauthrow.style.display = displayTableRow;
			}
//			if (rprinp_lbl != undefined) {
//				rprinp_lbl.innerHTML = "<b>Principal RMIT Researcher</b><br>(Name or Staff/Student number, may be a list)";
//			}
			if (rauth_lbl != undefined) {
				rauth_lbl.innerHTML = "<b>RMIT author</b><br>(Staff/Student Number: may be a list)";
			}
			if (anyp_lbl != undefined) {
				anyp_lbl.innerHTML = "<b>Author from any institution</b><br>(by Name(s): may be a list)";
			}
//			if (titl_lbl != undefined) {
//				titl_lbl.innerHTML = "<b>Project Name</b>";
//			}
			if (statL != undefined) {
				while (statL.length > 0) {
					statL.remove(0);
				}
				statL.add(new Option('Any', 'any'),selectAddStart);
				statL.add(new Option('Has RMIT author code', 'checked'),selectAddStart);
				statL.add(new Option('Doesn\'t have RMIT author code', 'unchecked'),selectAddStart);
				statL.value = (ss_val!=null)?ss_val:'any';
			}
			break;
		}
		case "students": {
			if (keywordrow != undefined) {
				keywordrow.style.display = 'none';
			}
			if (yearrow != undefined) {
				yearrow.style.display = displayTableRow;
			}
			if (citerow != undefined) {
				citerow.style.display = 'none';
			}
			if (subjrow != undefined) {
				subjrow.style.display = 'none';
			}
			if (statrow != undefined) {
				statrow.style.display = displayTableRow;
			}
			if (titlrow != undefined) {
				titlrow.style.display = 'none';
			}
			if (anyprow != undefined) {
				anyprow.style.display = displayTableRow;
			}
			if (rprinprow != undefined) {
				rprinprow.style.display = 'none';
			}
			if (rauthrow != undefined) {
				rauthrow.style.display = displayTableRow;
			}
//			if (rprinp_lbl != undefined) {
//				rprinp_lbl.innerHTML = "<b>Principal RMIT Researcher</b><br>(Name or Staff number, may be a list)";
//			}
			if (rauth_lbl != undefined) {
				rauth_lbl.innerHTML = "<b>Supervisor</b><br>(Name(s) or Staff Number, may be a list)";
			}
			if (anyp_lbl != undefined) {
				anyp_lbl.innerHTML = "<b>Student</b><br>(Name(s) or Student Number: may be a list)";
			}
//			if (titl_lbl != undefined) {
//				titl_lbl.innerHTML = "<b>Project Name</b>";
//			}
			if (statL != undefined) {
				while (statL.length > 0) {
					statL.remove(0);
				}
				statL.add(new Option('Any', 'any'),selectAddStart);
				statL.add(new Option('Completed', 'completed'),selectAddStart);
				statL.add(new Option('Not Completed', 'uncompleted'),selectAddStart);
				statL.value = (ss_val!=null)?ss_val:'any';
			}
			break;
		}
		default: {
			break;
		}
	}
}
</script>
<?php
	form_header("publications.php", "searchForm", "POST", "", "");
	table_header(2, 2, "", "", "", "", "left", "top");
	echo "<tr><td colspan='2'>";
	main_index_controls();
	echo "<P class=\"title_text\"><font SIZE=3>Search the DEST research publications collection ...</font></P>";
	echo "<tr><td><b>Search For</b></td><td>";
	select_array("search_mode",
			array("publications","projects", "authors", "students"), "",
			array("Publications", "Research Projects", "Authors", "Students"),
			"setSearchModeControls(value)",
			$search_mode);
	echo "</td></tr>";
	echo "</td></tr>";
	echo "<tr id='srch_title_row'><td>",
		"<span id='srch_title_lbl'><b>Title</b></span>",
		"</td><td>";
	text_input("search_title", $search_title, 60, 200);
	echo "</td></tr>";
	echo "<tr id='srch_rmitprincipal_row'><td>",
		"<span id='srch_rmitprincipal_lbl'><b>Principal RMIT Author</b><br>(name or staff/student number, may be a list)</span>",
		"</td><td>";
	text_input("search_rmit_author", $search_rmit_author, 50, 200);
	echo "</td></tr>";
	echo "<tr><td id='srch_rmitauthor_row'>",
		"<span id='srch_rmitauthor_lbl'><b>RMIT Author</b><br>(Staff/Student Number: may be a list)</span>",
		"</td><td>";
	text_input("search_stnumber", $search_stnumber, 50, 200);
	echo "</td></tr>";
	echo "<tr id='srch_anyperson_row'><td>",
		"<span id='srch_anyperson_lbl'><b>Author from any institution</b><br>(by name(s): may be a list)</span>",
		"</td><td>";
	text_input("search_author", $search_author, 50, 200);
	echo "</td></tr>";
	echo "<tr id='srch_year_row'><td><b>For years</b></td><td>";
	text_input("search_year", $search_year, 10, 10);
	echo "</td></tr>";
	echo "<tr id='srch_keyword_row'><td><b>Keywords</b></td><td>";
	text_input("search_keywords", $search_keywords, 80, 200);
	echo "</td></tr>";
	echo "<tr id='srch_status_row'><td><b>For status</b></td><td>";
	select_array("search_status",
			array("any","primary", "nprimary", "school","portfolio", "nschool", "nportfolio"), "",
			array("Any", "Passed Primary Checks", "Not Passed Primary Checks", "Accepted by School", "Accepted by Portfolio", "Pending Acceptance by School", "Pending Acceptance by Portfolio"), "",
			$search_status);
	echo "</td></tr>";
	echo "<tr><td>&nbsp;</td></tr>";
	echo "<tr><td>&nbsp;</td></tr>";
//	echo "<tr><td><b>Show Authors</b></td><td>";
//	checkbox_input("show_authors", "true", $show_authors=="true");
//	echo "</td></tr>";
	hidden_field("show_authors", "true");
	echo "<tr id='srch_subj_row'><td><b>Show Subjects</b></td><td>";
	select_array("show_subjects",	
			array("none","name","rfc","both"), "",
			array("None", "As name", "As RFC code", "As Name and RFC code"), "",
			$show_subjects);
	echo "</td></tr>";
	echo "<tr><td><b>Show Full Details</b></td><td>";
	checkbox_input("show_detailed_listing", "true", $show_detailed_listing=="true");
//	echo "<tr><td><b>Show Supporting Materials</b></td><td>";
//	checkbox_input("show_supporting", "true", $show_supporting=="true");
//	echo "</td></tr>";
	hidden_field("show_supporting", "true");
	echo "<tr id='srch_cite_row'><td><b>Show Citation Searches</b></td><td>";
	checkbox_input("show_citations", "true", $show_citations=="true");
	echo "</td></tr>";
	echo "<tr><td><b>Show Timestamp</b></td><td>";
	checkbox_input("show_timestamp", "true", $show_timestamp=="true");
	echo "</td></tr>";
//	echo "<tr><td><b>Show in ARC bibliographic format</b></td><td>";
//	checkbox_input("show_arc", "true", $show_arc=="true");
//	echo "</td></tr>";
	$show_arc = "true";
	table_tail();
	br("all");
	echo "<p><sup>*</sup> Wildcards may be used in text search fields above: '_' matches a single character, and '%' matches any number of characters.","<BR>",
			"Lists are separated by ',' or ' '. ","Years are either single 4 digit years, or a range separated by '-'.","</p>";
	form_tail();
	br("all");
?>
<script type='text/javascript'>
	setSearchModeControls(document.searchForm.search_mode.value, '<?php echo $search_status; ?>');
</script>
