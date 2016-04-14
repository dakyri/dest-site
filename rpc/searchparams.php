<?php
	function search_param_url($for_stnumber=NULL, $for_author=NULL, $for_mode=NULL)
	{
		global	$show_authors;
		global	$show_detailed_listing;
		global	$show_citations;
		global	$show_timestamp;
		global	$show_arc;
		global	$search_mode;
		global	$search_status;
		global	$search_title;
		global	$search_rmit_author;
		global	$search_stnumber;
		global	$search_author;
		global	$search_year;
		global	$search_keywords;
		global	$show_subjects;
		
		$srchstr = "";
		
		if (isset($show_authors)) {
			if ($srchstr) $srchstr .= "&";
			$srchstr .= "show_authors=";
			$srchstr .= urlencode($show_authors);
		}
		if (isset($show_detailed_listing)) {
			if ($srchstr) $srchstr .= "&";
			$srchstr .= "show_detailed_listing=";
			$srchstr .= urlencode($show_detailed_listing);
		}
		if (isset($show_subjects)) {
			if ($srchstr) $srchstr .= "&";
			$srchstr .= "show_subjects=";
			$srchstr .= urlencode($show_subjects);
		}
		if (isset($show_citations)) {
			if ($srchstr) $srchstr .= "&";
			$srchstr .= "show_citations=";
			$srchstr .= urlencode($show_citations);
		}
		if (isset($show_timestamp)) {
			if ($srchstr) $srchstr .= "&";
			$srchstr .= "show_timestamp=";
			$srchstr .= urlencode($show_timestamp);
		}
		if (isset($show_arc)) {
			if ($srchstr) $srchstr .= "&";
			$srchstr .= "show_arc=";
			$srchstr .= urlencode($show_arc);
		}
		if ($for_mode) {
			if ($srchstr) $srchstr .= "&";
			$srchstr .= "search_mode=";
			$srchstr .= urlencode($for_mode);
		} elseif (isset($search_mode)) {
			if ($srchstr) $srchstr .= "&";
			$srchstr .= "search_mode=";
			$srchstr .= urlencode($search_mode);
		}
		if (isset($search_status)) {
			if ($srchstr) $srchstr .= "&";
			$srchstr .= "search_status=";
			$srchstr .= urlencode($search_status);
		}
		if (isset($search_title)) {
			if ($srchstr) $srchstr .= "&";
			$srchstr .= "search_title=";
			$srchstr .= urlencode($search_title);
		}
		if (isset($search_rmit_author)) {
			if ($srchstr) $srchstr .= "&";
			$srchstr .= "search_rmit_author=";
			$srchstr .= urlencode($search_rmit_author);
		}
		if (isset($search_keywords)) {
			if ($srchstr) $srchstr .= "&";
			$srchstr .= "search_keywords=";
			$srchstr .= urlencode($search_keywords);
		}
		if ($for_stnumber) {
			if ($srchstr) $srchstr .= "&";
			$srchstr .= "search_stnumber=";
			$srchstr .= urlencode($for_stnumber);
		} elseif (isset($search_stnumber)) {
			if ($srchstr) $srchstr .= "&";
			$srchstr .= "search_stnumber=";
			$srchstr .= urlencode($search_stnumber);
		}
		if ($for_author) {
			if ($srchstr) $srchstr .= "&";
			$srchstr .= "search_author=";
			$srchstr .= urlencode($for_author);
		} elseif (isset($search_author)) {
			if ($srchstr) $srchstr .= "&";
			$srchstr .= "search_author=";
			$srchstr .= urlencode($search_author);
		}
		if (isset($search_year)) {
			if ($srchstr) $srchstr .= "&";
			$srchstr .= "search_year=";
			$srchstr .= urlencode($search_year);
		}
		return $srchstr;
	}
	
	function search_param_reset()
	{
		global	$show_authors;
		global	$show_detailed_listing;
		global	$show_supporting;
		global	$show_citations;
		global	$show_timestamp;
		global	$show_arc;
		global	$show_subjects;
		global	$search_mode;
		global	$search_status;
		global	$search_title;
		global	$search_rmit_author;
		global	$search_stnumber;
		global	$search_author;
		global	$search_year;
		global	$search_keywords;

		$show_authors = "true";
		$show_detailed_listing = "false";
		$show_supporting = "true";
		$show_citations = "false";
		$show_timestamp = "false";
		$show_arc = "true";
		$show_subjects = "name";
		$search_mode = "publications";
		$search_status = "any";
		$search_rmit_author = "";
		$search_stnumber = "";
		$search_author = "";
		$search_year = "";
		$search_keywords = "";
	}
?>