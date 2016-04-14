// Sets cookie values. Expiration date is optional
// Notice the use of escape to encode special characters (semicolons, commas,
// spaces) in the value string. This function assumes that cookie names do not
// have any special characters.

function set_cookie(name, value, expire) 
{
	document.cookie = name + "=" + escape(value)
	    + ((expire == null) ? "" : ("; expire=" + expire.toUTCString()));
}


// The following function returns a cookie value, given the name of the cookie:

function get_cookie(name)
{
	var search = name + "="
	if (document.cookie.length > 0) { // if there are any cookies
		offset = document.cookie.indexOf(search);
		if (offset != -1) { // if cookie exists 
			offset += search.length 
// set index of beginning of value
			end = document.cookie.indexOf(";", offset) 
// set index of end of cookie value
			if (end == -1) 
				end = document.cookie.length
			return unescape(document.cookie.substring(offset, end))
		}
	}
	return "";
}

function kd_ret_nosubmit(e)
{
	switch(e.keyCode) {
		case 13:
		case 3:
			this.blur();
			return false;
	}
//		phoneHome();
	return true;
}

function show_spans(nm, vis)
{
	objs = document.getElementsByName(nm);
	if (objs) {
		for (i=0; i<objs.length; i++) {
			sp = objs.item(i);
			sp.style.visibility = (vis?'visible':'hidden');
		}
	}
}

function _timeaggregate(tt,y,mo,d,h,mi,s)
{
	ts = '';
	if (tt != 'time') {
		if (y.value < 10) {
			ts += '000'+y.value;
		} else if (y.value < 100) {
			ts += '00'+y.value;
		} else if (y.value < 1000) {
			ts += '0'+y.value;
		} else {
			ts += y.value;
		}
	}
	if (tt=='date' || tt=='datetime') {
		ts +='-';
		ts += mo.value;
		if (d==null) {
			ts += '-01';
		} else {
			ts += '-'+d.value;
		}
	}
	if (tt=='time' || tt=='datetime') {
		ts += ' '+h.value
		if (mi == null) {
			ts += ':00';
		} else {
			ts += ':'+mi.value;
		}
		if (s == null) {
			ts+= ':00';
		} else {
			ts += s.value;
		}
	}
	return ts;
}

function __check_year_range(val)
{
	ival = parseInt(val);

	if (isNaN(ival)) {
		return 1997;
	}
	if (ival < 1) {
		ival = 1;
	} else if (ival > 3000) {
		ival = 3000;
	}
	return ival;
}

function setmysqldatetime(str, yinp, moinp, dinp, hin, miinp, sinp)
{
	dtstrs = str.split(' ');
	if (dtstrs.length == 2) {
		setmysqldate(dtstrs[0], yinp, moinp, dinp);
		setmysqltime(dtstrs[1], hin, miinp, sinp);
	}
}

function setmysqldate(str, yinp, moinp, dinp)
{
	dstrs = str.split('-');
	if (dstrs.length == 3) {
		if (yinp != undefined && yinp.value != undefined) yinp.value = dstrs[0];
		if (moinp != undefined && moinp.value != undefined) moinp.value = dstrs[1];
		if (dinp != undefined && dinp.value != undefined) dinp.value = dstrs[2];
	}
}

function setmysqltime(str, hinp, miinp, sinp)
{
	tstrs = str.split(':');
	if (tstrs.length >= 2) {
		if (hinp != undefined && hinp.value != undefined) hinp.value = tstrs[0];
		if (miinp != undefined && miinp.value != undefined) miinp.value = tstrs[1];
		if (tstrs.length >= 3) {
			if (sinp != undefined && sinp.value != undefined) sinp.value = tstrs[2];
		}
	}
}
