<?php
	error_reporting(3);
	import_request_variables("gpc");
	include_once("common/necessary.php");
	include_once("../common/common_mysql.php");
	require_once("login_chk.php");
	
	if ($login_type < LOGIN_USER) {
		header("Location: logon.php?login_destination=booking.php&cancel_destination=index.php");
		exit;
	}
	
	$days = array("sun", "mon","tue","wed","thu","fri","sat");
	$months = array("NaM", "jan", "feb","mar","apr","may","jun","jul", "aug","sep","oct","nov","dec");

	unset($startdate);
	if (isset($startt_ymd)) {
		$dstr = explode('-',$startt_ymd);
		if (count($dstr)==3) {
			$startdate = array();
			$y = (int)($dstr[0]);
			if ($y < 1997) $y = 1997; else if ($y >= 10000) $y = 9999;
			$m = (int)($dstr[1]);
			if ($m <= 0) $m = 1; else if ($m > 12) $m = 12;
			$d = (int)($dstr[2]);
			if ($d <= 0) $d = 1; else if ($d > 31) $d = 31;
			while (!checkdate($m, $d, $y)) {
				if ($d <= 1) {
					break;
				}
				$d--;
			}
			
			$startdate['year'] = $y;
			$startdate['mon'] = $m;
			$startdate['mday'] = $d;
			$startdate['wday'] = weekday_for($startdate['year'], $startdate['mon'], $startdate['mday']);
		}
	}
	if (!isset($startdate)) {
		$startdate = getdate();
	}
	$default_slot_size = "01:00:00"; // 1 hr as a mysql time 
	$inset_img_height = 120;
	$slot_dates = array();
	for ($i=0; $i<7; $i++) {
		$slot_dates[$i] = mysql_date_plus_days($i, $startdate);
	}
	
	if ($bookMode == "Lock In") {	// make changes in db
		if ($login_type == LOGIN_NONE) {
			header("Location: booking.php");
			exit;
		}
		if ($login_type < LOGIN_DBADMIN) {
			require_once("private/local.php");
			if ($mysql > 0) {
				@mysql_close($mysql);
			}
			$mysql = get_database(
				$database_name,
				$database_host,
				$database_mod_user,
				$database_mod_passwd);
		}
		if ($mysql <= 0) {
			lockin_redirect("MYSQL error while making booking: ".mysql_error());
		}
		
		if ($login_type >= LOGIN_ADMIN) {
			unset($user_code);
			unset($user_id);
			if ($book_for_user) {
				$uquery = "select * from people where stnumber='$book_for_user' and find_in_set('mmtc', properties)";
				
				$uresult = mysql_query($uquery);
				if ($uresult > 0) {
					$nuitems = mysql_num_rows($uresult);
					if ($nuitems > 0) {
						$u_row = mysql_fetch_object($uresult);
						$user_code = $u_row->code;
						$user_id = $u_row->stnumber;
					} else {
						lockin_redirect("User '$book_for_user' not in database");
					}
				} else {
					lockin_redirect("Mysql error getting user information: ".mysql_error());
				}
			}
			if ($login_type == LOGIN_ADMIN && !isset($user_code)) {
				$user_code = $login_user_row->code;
				$user_id = $login_user_row->stnumber;
			} else if (!isset($user_code)) {
				lockin_redirect("Database admin edit requires a user to book in for");
			}
		} else {
			$user_code = $login_user_row->code;
			$user_id = $login_user_row->stnumber;
		}
		
		if (is_array($bookings)) {
			$bg = array_keys($bookings);
		} else {
			$bg = array();
		}
		if (is_array($unbookings)) {
			$ug = array();
		} else {
			$ug = array();
		}
			
		$gear_obj[] = array();
		$booking_locknm = "lock_dest_booking";
		$booking_lock_timeout = 60;
		$ir_res = mysql_query("select get_lock('$booking_locknm', $booking_lock_timeout)");
		if (!$ir_res) {
			lockin_redirect("Can't get a lock for a booking. Mysql says: ".mysql_error());
		}
		if (is_array($bookings)) {
			reset($bookings);
			while (list($k,$v) = each($bookings)) {
				$gquery = "select * from equipment where code=$k && needs_booking";
				$gresult = mysql_query($gquery);
				$gear_row = NULL;
				if ($gresult > 0) {
					$ngitems = mysql_num_rows($gresult);
					if ($ngitems > 0) {
						$gear_row = mysql_fetch_object($gresult);
						list($earliest_time, $latest_time) = item_availability_bounds($gear_row);
						if (!isset($gear_row->booking_slot_size)) {
							$gear_row->booking_slot_size = $default_slot_size;
						}
						if ($gear_row->needs_training) {
							if ($login_type < LOGIN_ADMIN) {
								$trained = array_map("rawurldecode", explode("&", $gear_row->trained_stnumber));
								reset($trained);
								$booking_allowed = false;
								while (list($trk,$trv)=each($trained)) {
									if ($trv == $login_user_row->stnumber) {
										$booking_allowed = true;
									}					
								}
								if (!$booking_allowed) {
									lockin_redirect("User '$login_user_row->stnumber' not yet allowed to make bookings on {$gear_row->name}");
								}
							}
						}
						$gear_obj[$code] = $gear_row;
					}
				}
				if ($gear_row) {
					if ($login_type >= LOGIN_ADMIN) {
						if (!isset($booking_duration)) {
							$book_for = mysql_t2min($gear_row->booking_slot_size);
						} else {
							$book_for = $booking_duration;
						}
					} else {
						$book_for = mysql_t2min($gear_row->booking_slot_size);
					}
					if (is_array($v)) {
						reset($v);
						while (list($kk,$vv) = each($v)) {
							$slots = explode('-', $vv);
							$slot_start_m = slot_start_mins($gear_row, $slots[1]);
							$slot_start_h = (int)floor($slot_start_m / 60);
							$slot_start_mm = $slot_start_m - ($slot_start_h * 60);
							$slot_end_m = $slot_start_m + $book_for;
							$slot_end_h = (int)floor($slot_end_m / 60);
							$slot_end_mm = $slot_end_m - ($slot_end_h * 60);
							$sd = sprintf("%s %'02d:%'02d:00",
											$slot_dates[$slots[0]],
											$slot_start_h,
											$slot_start_mm);
							$ed = sprintf("%s %'02d:%'02d:00",
											$slot_dates[$slots[0]],
											$slot_end_h,
											$slot_end_mm);
							$bwhere = "(device_code=$k) && (date_add(book_at,interval book_for minute) > '$sd') and (book_at < '$ed')";
							$bquery = "select * from bookings where $bwhere";
//							echo $bquery, "<br>";
							$bresult = mysql_query($bquery, $mysql);
							if ($result < 0) {
								lockin_redirect("Mysql error while processing booking :".mysql_error());
							}
							$nbooking = mysql_num_rows($bresult);
							if ($nbooking > 0) {	// insert ok ... no clashes
								$clear_to_insert = false;
								for ($i=0; $i<$nbooking; $i++) {
									;
								}
							} else {
								$clear_to_insert = true;
							}
							if ($clear_to_insert) {
								$book_at = $sd;
								
								$set = "";
								$set = set_item($set, "user_code", $user_code);
								$set = set_item($set, "user_id", $user_id);
								$set = set_item($set, "device_code", $gear_row->code);
								$set = set_item($set, "device_name", $gear_row->name);
								$set = set_item($set, "book_at", $book_at);
								$set = set_item($set, "book_for", $book_for);
								$set = set_item($set, "purpose", $book_for_purpose);
								$set = set_item($set, "create_timestamp", date("Y-m-d H:i:s"));
								
								$bookquery = "insert into bookings set $set";
								$bresult = mysql_query($bookquery, $mysql);
								if ($bresult < 0) {
									lockin_redirect("Mysql error while processing booking :".mysql_error());
								}
							}
						}
					}
				}
			}
		} else {
//			echo "nothing";
		}
		if (is_array($unbookings)) {
			reset($unbookings);
			while (list($k,$v) = each($unbookings)) {
				if (isset($gear_obj[$k])) {
					$gear_row = $gear_obj[$k];
				} else {
					$gquery = "select * from equipment where code=$k && needs_booking";
					$gresult = mysql_query($gquery);
					$gear_row = NULL;
					if ($gresult > 0) {
						$ngitems = mysql_num_rows($gresult);
						if ($ngitems > 0) {
							$gear_row = mysql_fetch_object($gresult);
						}
					}
				}
				if ($gear_row) {
					if ($gear_row->needs_training) {
						if ($login_type < LOGIN_ADMIN) {
							$trained = array_map("rawurldecode", explode("&", $gear_row->trained_stnumber));
							reset($trained);
							$booking_allowed = false;
							while (list($trk,$trv)=each($trained)) {
								if ($trv == $login_user_row->stnumber) {
									$booking_allowed = true;
								}					
							}
							if (!$booking_allowed) {
								lockin_redirect("User '$login_user_row->stnumber' not yet allowed to unmake bookings on {$gear_row->name}");
							}
						}
					}
					if ($login_type >= LOGIN_ADMIN) {
						if (!isset($booking_duration)) {
							$book_for = mysql_t2min($gear_row->booking_slot_size);
						} else {
							$book_for = $booking_duration;
						}
					} else {
						$book_for = $gear_row->booking_slot_size;
					}
					if (is_array($v)) {
						reset($v);
						while (list($kk,$vv) = each($v)) {
							$slots = explode('-', $vv);
							$slot_start_m = slot_start_mins($gear_row, $slots[1]);
							$slot_start_h = (int)floor($slot_start_m / 60);
							$slot_start_mm = $slot_start_m - ($slot_start_h * 60);
							$slot_end_m = $slot_start_m + $book_for;
							$slot_end_h = (int)floor($slot_end_m / 60);
							$slot_end_mm = $slot_end_m - ($slot_end_h * 60);
							$sd = sprintf("%s %'02d:%'02d:00",
											$slot_dates[$slots[0]],
											$slot_start_h,
											$slot_start_mm);
							$ed = sprintf("%s %'02d:%'02d:00",
											$slot_dates[$slots[0]],
											$slot_end_h,
											$slot_end_mm);
//							echo $sd, " ",$slot_start_m, " ", $ed, " ", $slot_end_m, "<br>";
							$bwhere = "(device_code=$k) && (date_add(book_at,interval book_for minute) > '$sd') and (book_at < '$ed')";
							$bquery = "select * from bookings where $bwhere";
//							echo $bquery;
							$bresult = mysql_query($bquery, $mysql);
							if ($result < 0) {
								lockin_redirect("Mysql error while processing booking :".mysql_error());
							}
							$nbooking = mysql_num_rows($bresult);
							if ($nbooking > 0) {	// insert ok ... no clashes
								$to_del = array();
								for ($i=0; $i<$nbooking; $i++) {
									$brow = mysql_fetch_object($bresult);
									$to_del[] = $brow->code;
								}
								$delquery = "delete from bookings where code in (".list_string($to_del).")";
								$delresult = mysql_query($delquery);
								if (!$delresult) {
									lockin_redirect("Mysql error while unbooking: ".mysql_error());
								}
							} else {
							}
						}
					}
				}
			}
		} else {
//			echo "nothing";
		}
		$ir_res = mysql_query("select release_lock('$booking_locknm')");
		
		lockin_redirect();
	}
	
function lockin_redirect($msg=NULL)
{
	global	$code;
	global	$startt_ymd;
	global	$book_for_user;
	global	$book_for_purpose;
	$redirect = "booking.php";
	$redirectp = "";
	if (isset($code)) {
		if ($redirectp) $redirectp .= "&";
		$redirectp .= "code=$code";
	}
	if (isset($startt_ymd)) {
		if ($redirectp) $redirectp .= "&";
		$redirectp .= "startt_ymd=$startt_ymd";
	}
	if (isset($book_for_user)) {
		if ($redirectp) $redirectp .= "&";
		$redirectp .= "book_for_user=$book_for_user";
	}
	if (isset($book_for_purpose)) {
		if ($redirectp) $redirectp .= "&";
		$redirectp .= "book_for_purpose=$book_for_purpose";
	}
	if ($msg) {
		if ($redirectp) $redirectp .= "&";
		$redirectp .= "action_msg=".urlencode($msg);
	}
	header("Location: $redirect?$redirectp");
	exit;
}

function mysql_t2min($mysqlt)
{
	$fr = explode(':',$mysqlt);
	return ($fr[0] * 60) + $fr[1];
}

function mysql_dur_t2pp($mysqlt)
{
	$fr = explode(':',$mysqlt);
	$pps = "";
	$fr[0] = (int)($fr[0]);
	if ($fr[0] != 0) {
		$pps .= "$fr[0] hr";
	}
	$fr[1] = (int)($fr[1]);
	if ($fr[1] != 0) {
		if ($pps) {
			$pps .= ", ";
		}
		$pps .= "$fr[1] min";
	}
	$fr[2] = (int)($fr[2]);
	if ($fr[2] != 0) {
		if ($pps) {
			$pps .= ", ";
		}
		$pps .= "$fr[2] sec";
	}
	if (!$pps) {
		$pps = "0 min";
	}
	return $pps;
}

function slot_start_mins($gear_row, $slot)
{
	if (!$gear_row->booking_slot_size || $gear_row->booking_slot_size=="00:00:00") {
		$slotmins = 60;
	} else {
		$slotmins = mysql_t2min($gear_row->booking_slot_size);
	}
	if (!$gear_row->booking_avail_from || $gear_row->booking_avail_from==$gear_row->booking_avail_to) {
		$bookstartmins = 9*60; // 9am
	} else {
		$bookstartmins = mysql_t2min($gear_row->booking_avail_from);
	}
	$startmins =  ($slotmins * $slot) + $bookstartmins;
	return $startmins;
}

function item_availability_bounds(&$itemrow)
{
	if (!$from_time) {
		$from_time = array();
		if ($itemrow->booking_avail_from && ($itemrow->booking_avail_from != $itemrow->booking_avail_to)) {
			$from_time[0] = mysql_t2min($itemrow->booking_avail_from);
		} else {
			$from_time[0] = 9*60;
		}
		$from_time[1] = $from_time[0];
		$from_time[2] = $from_time[0];
		$from_time[3] = $from_time[0];
		$from_time[4] = $from_time[0];
		$from_time[5] = $from_time[0];
		$from_time[6] = $from_time[0];
	}
	if (!$to_time) {
		$to_time = array();
		if ($itemrow->booking_avail_to && ($itemrow->booking_avail_from != $itemrow->booking_avail_to)) {
			$to_time[0] = mysql_t2min($itemrow->booking_avail_to);
		} else {
			$to_time[0] = 18*60;
		}
		$to_time[1] = $to_time[0];
		$to_time[2] = $to_time[0];
		$to_time[3] = $to_time[0];
		$to_time[4] = $to_time[0];
		$to_time[5] = $to_time[0];
		$to_time[6] = $to_time[0];
	}
	$earliest_time = min($from_time);
	$latest_time = max($to_time);
	
	return array($earliest_time, $latest_time);
}

function time_for_slot_mins($slot, $booking_slot_mins, $startt)
{
	$tmins = $startt + $slot*$booking_slot_mins;
	$hour = (int)($tmins/60);
	$mins = $tmins-$hour*60;
	$tstr = sprintf("%d:%'02d",$hour, $mins);
	return $tstr;
}

function	date_increment(&$year,&$month,&$day,&$daynm,$slot)
{
	while ($slot > 0) {
		$daynm = ($daynm + 1)%7;
		if (checkdate($month, $day+1, $year)) {
			$day++;
		} else {
			$day = 1;
			$month++;
			if ($month > 12) {
				$month = 1;
				$year++;
			}
		}
		$slot--;
	}
}

function weekday_for($year,$month,$day) 
{
	if (!checkdate($month,$day,$year)) {
		return 5; // if we've lost time, then it must be friday
	}
	$timestamp = mktime ( 1/*hr*/, 0/*mi*/, 0/*sc*/, $month , $day, $year, false/*dst*/);
	$sd = getdate($timestamp);
	return $sd['wday'];
}
	
function date_plus_days($inc, &$startdate)
{
	global	$days;
	global	$months;
	
	$year = $startdate["year"];
	$month = $startdate["mon"];
	$day = $startdate["mday"];
	$tday = $startdate["wday"];
	
	date_increment($year,$month,$day,$tday,$inc);
	$fdate = sprintf("%s %d %s", $days[$tday], $day,$months[$month]);
	return $fdate;
}
	
function mysql_date_plus_days($inc, &$startdate)
{
	global	$days;
	global	$months;
	$year = $startdate["year"];
	$month = $startdate["mon"];
	$day = $startdate["mday"];
	$tday = $startdate["wday"];
	
	date_increment($year,$month,$day,$tday,$inc);
	$fdate = sprintf("%d-%'02d-%'02d", $year,$month,$day);
	return $fdate;
}

function start_time_input(&$startdate)
{
	global	$login_type;
	global	$book_for_user;
	global	$book_for_purpose;
	
	table_header(0,0);
	table_row();
	table_data("left","top",NULL,NULL,"30%");
	echo "<b>For week starting</b>\n";
	table_dend();
	table_data();
	$startdate_val = sprintf("%d-%d-%d", $startdate["year"], $startdate["mon"],$startdate["mday"]);
	time_input("startt_ymd", $startdate_val, "date", NULL, NULL, "form.submit();");
	table_dend();
	table_rend();
	if ($login_type >= LOGIN_ADMIN) {
		table_row();
		table_data();
		echo "<b><font color='red'>*</font>For user (staff/student number)</b><br>\n";
		echo "A few characters typed will match first names and surnames, then bring up a dropdown option menu of st-numbers";
		table_dend();
		table_data();
		text_input("book_for_user", $book_for_user, 15,15,"form.submit();");
		echo "<script type='text/javascript'>";
		echo "completer = new jxComplete('",
				in_parent_path("completion.php"),
					"?table=people&fetch_field[]=title&fetch_field[]=firstname&fetch_field[]=surname&fetch_field[]=stnumber&where_extra=",urlencode("kind!='admin'"),
					"&label_expr=",urlencode("\$row->title.' '.\$row->firstname.' '.\$row->surname"),
					"&match_field[]=firstname&match_field[]=surname&match_field[]=stnumber',",
				"'match_text',",
				"document.bookingSheet.book_for_user,",
				"'stnumber',",
				"null,",
				"null",
			");";
		echo "</script>";
		table_dend();
		table_rend();
	}
	table_row();
	table_data();
	echo "<br><b>For purpose </b>\n";
	table_dend();
	table_data();
	text_area("book_for_purpose", $book_for_purpose, 40, 1, /*$oc=*/"", /*$class=*/"", /*$dis=*/false);
	table_dend();
	table_rend();
}

$user_details = array();

function booking_table($itemrow, $mysql, $show_detail, $startdate)
{
	global	$days;
	global	$book_for_user;
	global	$slot_dates;
	global	$login_type;
	global	$user_details;
	
	if ($itemrow->availability) {
	}
	if ($itemrow->booking_slot_size) {
		$booking_slot_mins = mysql_t2min($itemrow->booking_slot_size);
	}
	list($earliest_time, $latest_time) = item_availability_bounds($itemrow);
	if (!$booking_slot_mins) {
		$booking_slot_mins = 60;
	}
	$ntimeslot = ceil(($latest_time - $earliest_time)/$booking_slot_mins);

// collect current bookings
	$sd = $slot_dates[0]." 00:00:00";
	$ed = $slot_dates[6]." 23:59:59";
	$query = "select * from bookings where (device_code=$itemrow->code)";
	$query .= " and (book_at >= '$sd')";
	$query .= " and (book_at <= '$ed')";
	$query .= " order by book_at";
//	echo "timescale $sd <=> $ed<br>";
	$slotted = array();
	$slotted_title = array();
	$changable_slot = array();
	if ($mysql > 0) {
		$result = mysql_query($query,$mysql);
		if ($result > 0) {
			$nbook = mysql_num_rows($result);
			if ($nbook > 0) {
				for ($i=0; $i<$nbook; $i++) {
					$row = mysql_fetch_object($result);
					$dtstrs = explode(' ', $row->book_at);
					$time_mins = mysql_t2min($dtstrs[1]);
					$this_time_slot = ($time_mins-$earliest_time)/$booking_slot_mins;
					$this_day_slot = 0;
					for ($ti=0; $ti<7; $ti++) {
						if ($dtstrs[0] == $slot_dates[$ti]) {
							$this_day_slot = $ti;
							break;
						}
					}
					$slotxt = "$row->user_id";
					$slot_title_txt = "";
					if (!isset($user_details[$row->user_id])) {
						$uquery = "select * from people where stnumber='$row->user_id'";
						$user_details[$row->user_id] = "";
						$uresult = mysql_query($uquery);
						if ($uresult > 0) {
							$nuitems = mysql_num_rows($uresult);
							if ($nuitems > 0) {
								$u_row = mysql_fetch_object($uresult);
								$user_details[$row->user_id] =
										htmlentities($u_row->firstname, ENT_QUOTES).
										"&nbsp;".htmlentities($u_row->surname, ENT_QUOTES);
							}
						}
					}
					if ($show_detail) {
						$slotxt .= "<br>".$user_details[$row->user_id];
						if ($row->purpose) {
							$slotxt .= "<br>";
							$slotxt .= htmlentities($row->purpose, ENT_QUOTES);
						}
					} else {
						$slot_title_txt = $user_details[$row->user_id];
						if ($row->purpose) {
							if ($slot_title_txt) $slot_title_txt .= ", ";
							$slot_title_txt .= htmlentities($row->purpose, ENT_QUOTES);
						}
					}
					if ($slotted[$this_day_slot][$this_time_slot]) {
						echo "Booking clash, slot $this_day_slot,$this_time_slot";
					} else {
						$slotted[$this_day_slot][$this_time_slot] = $slotxt;
						$slotted_title[$this_day_slot][$this_time_slot] = $slot_title_txt;
						if (($login_ype >= LOGIN_ADMIN) ||
								($book_for_user == $row->user_id && $row->book_at >= date("Y-m-d H:i:s"))) {
							$changable_slot[$this_day_slot][$this_time_slot] = true;
						}
					}
//					echo "found $row->code at $row->book_at for $row->device_code ($row->device_name)<br>";
//					echo "slotted $this_time_slot $this_day_slot<br>";
				}
				$have_bookings = true;
			}
		}
	}
	if (!isset($have_bookings)) {
		$have_bookings = false;
	}
// spit out a table
	echo "<div align='left'>";
	table_header(0,0,"booking-sheet", null, 1);
	table_row();
	echo "<th>&nbsp;</th>";
	for ($j=0; $j<$ntimeslot; $j++) {
		echo "<th>";
		echo time_for_slot_mins($j, $booking_slot_mins, $earliest_time);
		echo "</th>";
	}
	table_rend();
	for ($i=0; $i<7; $i++) {
		table_row();
		echo "<th>",date_plus_days($i, $startdate),"</th>";
		for ($j=0; $j<$ntimeslot; $j++) {
			if ($slotted[$i][$j]) {
				$txt = $slotted[$i][$j];
				if ($changable_slot[$i][$j]) {
					$class = 'slot-changable';
				} else {
					$class = 'slot-taken';
				}
			} else {
				$txt = '';
				$class = 'slot-free';
			}
			echo "<td align='center'"," valign='middle'",
					" class='$class'",
					" name='booking-cell'",
					($slotted_title[$i][$j]?(" title='".$slotted_title[$i][$j]."'"):""),
					" id='cell-".$itemrow->code."-$i-$j'",
					" onclick='bookMouseClick(this);'",
//					" onmousemove='bookMouseOver(this);'",
					" onmouseover='bookMouseOver(this);'",
					" onmouseout='bookMouseOut(this);'",
					">\n";
			echo $txt;
			echo "</td>";
		}
		table_rend();
	}
	table_tail();
	echo "</div>";
	
}

function booking_form_header($action, $query)
{
	echo "<form action='$action' name='bookingSheet' method='POST' onSubmit='return bookTableSubmit();'>";
	hidden_field("baseAction", $action);
	hidden_field("baseQuery", $query);
}


function booking_controls()
{
	$current_t = getdate()
?>
<p>Make bookings by selecting a time slot in the table next to the item of choice.
Potential selections are shown in blue. To unmake a booking select bookings that you have previously made.
Potential deselections are shown in turquoise.
When you have made your choices, press "Lock In" to submit these choices to the booking database.
Bookings which you may unmake or edit are shown in green. All other bookings are shown in violet.
</p>
<?php
	global	$code;
	if (!$code) {
?>
<p>A larger scale booking sheet with more information can be found by clicking on the image of a
particular piece of gear.</p>
<?php
	}
	echo "<p>","Current system time: ",
			$current_t["hours"],":",
			$current_t["minutes"],", ",
			$current_t["month"]," ",
			$current_t["mday"],", ",
			$current_t["year"],
			".</p>";
	div('edit-controls');
	submit_input("bookMode", "Refresh");
	submit_input("bookMode", "Lock In");
	div();
}

	mmtc_page_top(
		"booking",
		"RMIT MMTC (Microelectronics and Materials Technology Centre)",
		"page-main");
	echo "<script type='text/javascript' src='",
		in_parent_path("common/necessary.js"),
		"'></script>";
	echo "<script type='text/javascript' src='",
		in_parent_path("common/jx_complete.js"),
		"'></script>";
	if (isset($action_msg)) {
		echo "<b>$action_msg</b><br>";
	}
?>
<script type="text/javascript">
	function bookMouseOver(obj)
	{
		obj.originalClass = obj.className;
		if (obj.className != 'slot-taken') {
			obj.className = 'slot-hover';
		}
	}
	
	function bookMouseOut(obj)
	{
		if (obj.originalClass != undefined) {
			obj.className = obj.originalClass;
		} else {
			obj.className = 'slot-free';
		}
	}
	
	function bookMouseClick(obj)
	{
		if (obj.originalClass == 'slot-free') {
			obj.className = 'slot-book';
			obj.originalClass = 'slot-book';
		} else if (obj.originalClass == 'slot-book') {
			obj.originalClass = 'slot-free'
			obj.className = 'slot-free'
		} else if (obj.originalClass == 'slot-unbook') {
			obj.originalClass = 'slot-changable'
			obj.className = 'slot-changable'
		} else if (obj.originalClass == 'slot-changable') {
			obj.className = 'slot-unbook';
			obj.originalClass = 'slot-unbook';
		}
	}
	
	function bookTableSubmit()
	{
		nodes = document.getElementsByName('booking-cell');
		extras = '';
		for (i in nodes) {
			if (nodes[i].id != undefined) {
				inds = nodes[i].id.split('-');// 1 should be id, 2 and 3 should be the i and j
				if (inds[0] == 'cell' && inds.length == 4) { // sanity check
					if (nodes[i].className == 'slot-book') {
//						alert('book dev '+inds[1]+' slot '+inds[2]+','+inds[3]);
						if (extras) extras += '&'; //'
						extras += 'bookings['+inds[1]+'][]='+inds[2]+'-'+inds[3];
					} else if (nodes[i].className == 'slot-unbook') {
//						alert('unbook dev '+inds[1]+' slot '+inds[2]+','+inds[3]);
						if (extras) extras += '&'; //'
						extras += 'unbookings['+inds[1]+'][]='+inds[2]+'-'+inds[3];
					}
				} else {
//					alert('failed sanity check');
					break;
				}
			}
		}
		act = document.bookingSheet.baseAction.value;
		act += '?';
		act += document.bookingSheet.baseQuery.value;
		act += '&';//'
		act += extras;
		document.bookingSheet.action = act;
		return true;
	}
</script>
<?php
	div('wide-margin');
	if (!isset($mysql) || $mysql < 0) {
		$mysql = get_database(
					$schema_database_name,
					$database_host,
					$database_pleb_user,
					$database_pleb_passwd);
	}
	if ($login_u) {
		if ($login_type < LOGIN_ADMIN) {
			if (!isset($book_for_user)) {
				$book_for_user = $login_u;
			}
			echo "<b>Logged in as $login_u, $login_user_row->firstname $login_user_row->surname</b><br>\n";
		} else if ($login_type == LOGIN_ADMIN) {
			echo "<b>Logged in as administrator '$login_u'</b><br>\n";
		} else if ($login_type == LOGIN_DBADMIN) {
			echo "<b>Logged in as database administrator</b><br>\n";
		}
	}
	if (isset($code)) {
		$query = "select * from equipment where code=$code && needs_booking";
		$result = mysql_query($query);
		if ($result > 0) {
			$nitems = mysql_num_rows($result);
			if ($nitems > 0) {
				booking_form_header("booking.php", "code=${code}");
				booking_controls();
				start_time_input($startdate);
				table_header(0,0);
				table_row();
				table_data();
				$row = mysql_fetch_object($result);
				if (!$row->booking_slot_size) {
					$row->booking_slot_size = $default_slot_size;
				}
				div('item-head', $row->name);
				div('item-subhead');
				echo "(";
				$com = false;
				if ($row->full_name) {
					echo "$row->full_name";
					$com = true;
				}
				if ($row->location) {
					if ($com) {
						echo ",&nbsp;&nbsp;";
						$com = true;
					}
					echo anchor_str($row->location, "facilities.php?code=$row->location_code");
				}
				echo ")";
				div();
				echo "<b>Timeslot size:</b> ",
						($row->booking_slot_size=="00:00:00"? "1hr":							
							mysql_dur_t2pp($row->booking_slot_size)), "<br>";
	 			$img = array_map("rawurldecode", explode("&", $row->image));
	 			$img_align = array_map("rawurldecode", explode("&", $row->image_align));
	 			// these are linked in a group ... the arrays will be numbered, unless something is
	 			// seriously gone titsup on the database
				$img_base = "{$upload_base}/equ{$row->code}/";
				// unindexed page just post first para and a single image
				$have_para = false;
				$have_img = false;
	 			while (list($k,$v) = each($img)) {
	 				if ($img[$k]) {
	 					$img_src = $img_base.$img[$k];
	 					if (file_exists($img_src)) {
 							$dims = getimagesize($img_src);
 							$h = ($dims[1] > $inset_img_height)? $inset_img_height: $dims[1];
							echo "<a class='item-title-action' href='",
								"equipment.php?code={$row->code}",
								"'>";
	 						image_tag($img_base.$img[$k], NULL, $h, $img_caption[$k], $img_caption[$k], NULL, NULL);
	 						echo "</a>\n";
	 						break;
						}
	 				}
	 			}
				div();
				table_dend();
				
				$needs_training_request = false;
				if ($row->needs_training) {
					if ($login_type < LOGIN_ADMIN) {
						$needs_training_request = true;
						$trained = array_map("rawurldecode", explode("&", $row->trained_stnumber));
						reset($trained);
						while (list($trk,$trv)=each($trained)) {
							if ($trv == $login_user_row->stnumber) {
								$needs_training_request = false;
								break;
							}					
						}
					}
				}
				if ($needs_training_request) {
					table_data("right");
					echo "<br>";
					div("item-subhead");
					echo "<br>Usage of the ",$row->name," requires official training and registration.<br>";
					if ($row->sup_email) {
						if ($row->sup_firstname && $row->sup_surname) {
							echo "Please contact the appropriate person, ",
								"$row->sup_firstname $row->sup_surname, ",
								anchor_str($row->sup_email, "mailto:$row->sup_email");
						} else {
							echo "Please contact the person responsible for training for this item, at ",
								anchor_str($row->sup_email, "mailto:$row->sup_email");
						}
					} else {
						echo "Please contact an administrator from the MMTC.";
					}
					div();
				} else {
					table_data("right");
					echo "<br>";
				}
				booking_table($row,$mysql,true,$startdate);
				table_dend();
				table_rend();
				table_tail();
				form_tail();
				div();
				mmtc_page_bottom();
				exit;
			}
		}
	}
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
	$query = "select * from equipment where needs_booking && is_active";
	$result = mysql_query($query);
	if ($result <= 0) {
?>
<p>MMTC <a href="equipment.php">technical resources</a> are typically available for online advance booking. Unfortunately the booking system is currently off-line.</p>
<?php
		div();
		mmtc_page_bottom();
		exit();
	}
	$nitems = mysql_num_rows($result);
	if ($nitems > 0) {
		if (!$have_header) {
			$have_header = true;
?>
<?php
			booking_form_header("booking.php", "");
			booking_controls();
			start_time_input($startdate);
			table_header(0,0,NULL,NULL,NULL,NULL);
			table_row();
		} else {
			table_row();
		}
	} else {
	}
	for($i=0; $i < $nitems; $i++) {
		$row = mysql_fetch_object($result);
		if (!$row->booking_slot_size) {
			$row->booking_slot_size = $default_slot_size;
		}
	
		table_data("left","top");
		echo anchor_str(
					$row->name,
					"booking.php?code={$row->code}",
					NULL, "item-head"
				);
		div('item-subhead');
		echo "(";
		$com = false;
		if ($row->full_name) {
			echo "$row->full_name";
			$com = true;
		}
		if ($row->location) {
			if ($com) {
				echo ",&nbsp;&nbsp;";
				$com = true;
			}
			echo anchor_str($row->location, "facilities.php?code=$row->location_code");
		}
		echo ")<br>";
		echo "<b>Timeslot size:</b> ",
					($row->booking_slot_size=="00:00:00"? "1hr":							
							mysql_dur_t2pp($row->booking_slot_size));
		div();
		div('item-body');
 		$img = array_map("rawurldecode", explode("&", $row->image));
 		$img_align = array_map("rawurldecode", explode("&", $row->image_align));
 		$img_caption = array_map("rawurldecode", explode("&", $row->image_caption));
 			// these are linked in a group ... the arrays will be numbered, unless something is
 			// seriously gone titsup on the database
		$img_base = "{$upload_base}/equ{$row->code}/";
		// unindexed page just post first para and a single image
		while (list($k,$v) = each($img)) {
 			if ($img[$k]) {
 				$img_src = $img_base.$img[$k];
 				if (file_exists($img_src)) {
 					$dims = getimagesize($img_src);
 					$h = ($dims[1] > $inset_img_height)? $inset_img_height: $dims[1];
					echo "<A href=\"booking.php?code={$row->code}&book_for_user=${book_for_user}&startt_ymd=${startt_ymd}&book_for_purpose=".
							urlencode($book_for_purpose)."\" class=\"item-head\">";
 					image_tag($img_base.$img[$k], NULL, $h, $img_caption[$k], $img_caption[$k], NULL, "left");
 					echo "</A>";
 					break;
				}
 			}
 		}
		div();
		table_dend();
		$needs_training_request = false;
		if ($row->needs_training) {
			if ($login_type < LOGIN_ADMIN) {
				$needs_training_request = true;
				$trained = array_map("rawurldecode", explode("&", $row->trained_stnumber));
				reset($trained);
				while (list($trk,$trv)=each($trained)) {
					if ($trv == $login_user_row->stnumber) {
						$needs_training_request = false;
						break;
					}					
				}
			}
		}
		if ($needs_training_request) {
			table_data("right","middle");
			echo "<br>";
			div("item-subhead");
			echo "<br>Usage of the ",$row->name," requires official training and registration.<br>";
			if ($row->sup_email) {
				if ($row->sup_firstname && $row->sup_surname) {
					echo "Please contact the appropriate person, ",
						"$row->sup_firstname $row->sup_surname, ",
						anchor_str($row->sup_email, "mailto:$row->sup_email");
				} else {
					echo "Please contact the person responsible for training for this item, at ",
						anchor_str($row->sup_email, "mailto:$row->sup_email");
				}
			} else {
				echo "Please contact an administrator from the MMTC.";
			}
			div();
		} else {
			table_data("right","middle");
			echo "<br>";
		}
		booking_table($row,$mysql,false,$startdate);
		table_dend();
		table_rend();
	}
	table_rend();
	if ($have_header) {
		table_rend();
		table_tail();
		form_tail();
	} else {
?>
<p>MMTC <a href="equipment.php">technical resources</a> are typically available for online advance booking.
At the moment, all of the centre's equipment is either off-line, or not referenced by the booking system.</p>
<?php
	}
	div();
	mmtc_page_bottom();
?>
