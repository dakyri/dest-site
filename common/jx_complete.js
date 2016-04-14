var currentCompleter = null;
var nextCompleter = null;
var xmlHttp = null;
var xmlHttpJx = null;
var hasXMLHttp = true;
var lastError = null;

if (!window['Node']) {
    window.Node = new Object();
    Node.ELEMENT_NODE = 1;
    Node.ATTRIBUTE_NODE = 2;
    Node.TEXT_NODE = 3;
    Node.CDATA_SECTION_NODE = 4;
    Node.ENTITY_REFERENCE_NODE = 5;
    Node.ENTITY_NODE = 6;
    Node.PROCESSING_INSTRUCTION_NODE = 7;
    Node.COMMENT_NODE = 8;
    Node.DOCUMENT_NODE = 9;
    Node.DOCUMENT_TYPE_NODE = 10;
    Node.DOCUMENT_FRAGMENT_NODE = 11;
    Node.NOTATION_NODE = 12;
}

///////////HANDY GLOBALS//////////////////////////
function escapeURI(str){
  if(encodeURIComponent) {
    return encodeURIComponent(str);
  }
  if(escape) {
    return escape(str)
  }
  return str;
}

function findPosX(obj)
{
	var curleft = 0;
	if(obj.offsetParent)
		while(1) {
			curleft += obj.offsetLeft;
			if(!obj.offsetParent)
				break;
			obj = obj.offsetParent;
		}
	else if(obj.x)
		curleft += obj.x;
	return curleft;
}

function findPosY(obj)
{
	var curtop = 0;
	if(obj.offsetParent)
		while(1) {
			curtop += obj.offsetTop;
			if(!obj.offsetParent)
				break;
			obj = obj.offsetParent;
		}
	else if(obj.y)
		curtop += obj.y;
	return curtop;
}

function getXMLHttpReq()
{
	var A=null;
	try{
		A=new ActiveXObject("Msxml2.XMLHTTP")
	} catch(e){
		try{
			A=new ActiveXObject("Microsoft.XMLHTTP")
		} catch(oc){
      	A=null
		}
	}
	if(!A && typeof XMLHttpRequest != "undefined") {
		A=new XMLHttpRequest()
	}
	return A;
}


function createDocElement(tagname, idname, classname, hide)
{
	el = document.createElement(tagname);
	el.id = idname;
	el.className = classname;
	if (hide != null) {
		if (hide) {
			el.style.visibility = 'hidden';
		} else {
			el.style.visibility = 'visible';
		}
	}
	return el;
}


// 'class' "jxComplete" for a particular autocomplete

//////////////////////////////////////////////////
function readyStateChangeHandler()
{
	if(xmlHttp.readyState==4) {
		if (xmlHttp.status == 200 && xmlHttp.responseText) {
			data = null;
//			alert(xmlHttp.responseText);
			if(xmlHttp.responseXML != null && xmlHttpJx.processXMLResponse) {
				data = xmlHttpJx.processXMLResponse(xmlHttp.responseXML);
			} else if (xmlHttpJx.processTextResponse) {
				if (xmlHttp.responseText.charAt(0)=="<") {
//					alert('missed xml!');
				} else {
					data = xmlHttpJx.processTextResponse(xmlHttp.responseText);
				}
			}
//			alert('rsc');
			xmlHttpJx.responseData = data;
			if (xmlHttpJx.cache != null) {
				xmlHttpJx.resultCache[currentText] = data;
			}
			if (data == null) {
				xmlHttpJx.viewr.clear();
				xmlHttpJx.viewr.addRow('error', lastError,-1, false);
				xmlHttpJx.viewr.show();
			} else {
				if (xmlHttpJx.displayResponse) {
					xmlHttpJx.displayResponse(data);
				}
			}
		} else {
			switch (xmlHttp.status) {
				case 404:
					errorMsg = 'completer not found';
					break;
				default:
					errorMsg = 'unexpected http status '+xmlHttp.status;
					break;
			}
			xmlHttpJx.viewr.clear();
			xmlHttpJx.viewr.addRow('error', errorMsg,-1, false);
			xmlHttpJx.viewr.show();
		}
	} else {
	}
}

function phoneHome()
{
//	alert('phone home '+xmlHttp);
	var query_string = this.queryString();
	if (xmlHttp != null) {
		if (xmlHttp.readyState != 0 && xmlHttp.readyState != 4){
				// abort any current transfers .. maybe some cleanup of other field
			xmlHttp.abort()
		}
	}
	xmlHttp=getXMLHttpReq();//IE needs a new object for every request (FF is fine)
	if(xmlHttp != null) {
		xmlHttpJx = this;
		if (this.viewr) {
			this.viewr.clear();
		}
		xmlHttp.onreadystatechange=readyStateChangeHandler;
		xmlHttp.open("GET",query_string, true);
		xmlHttp.send(null)
	}
}

function processTextResponse(replytxt)
{
	data = null;
// might be easier but feels like bad karma ...
//	eval(replytxt);
	return data;
}

// data is returned as null, if there is no xml or an error,
// or as an object consisting of
//			label: label value returned by completer script
//			values: associative array of results
function processXMLResponse(xmldoc)
{
	if (xmldoc == null) {
		lastError = 'null xml reply';
		return null;
	}
	try {
		rootNode = xmldoc.documentElement;
		if (rootNode == null) {
			lastError = 'null root node xml reply';
			return null;
		}
		data = new Array();
//		alert('root'+rootNode.nodeName);
		for (p=rootNode.firstChild; p!=null; p=p.nextSibling) {
//			alert('child'+p.nodeName);
			if (p.nodeType==Node.ELEMENT_NODE) {
				if (p.nodeName == 'error') {
					q = p.childNodes.item(0);
					if (q != null && q.nodeType == Node.TEXT_NODE) {
						lastError = 'completer reports: '+q.nodeValue;
						return null;
					} else {
						lastError = 'unspecified error';
						return null;
					}
				} else if (p.nodeName == 'item') { // tag name is field
					obj = new Object();
					obj.values = new Array();
					data.push(obj);
					for (q=p.firstChild; q!=null; q=q.nextSibling) {
						r = q.firstChild;
						if (q.nodeType != undefined && q.nodeType==Node.ELEMENT_NODE) {
							if (q.nodeName == 'label') {
								if (r != null && r.nodeType == Node.TEXT_NODE) {
									obj.label = r.nodeValue;
								}
							} else if (q.nodeName == 'value') {
								nm = q.getAttribute('name');
								v = '';
								if (r != null && r.nodeType == Node.TEXT_NODE) {
									v = r.nodeValue;
								}
//								alert('item '+nm+'-'+v);
								if (nm != null) {
									obj.values[nm] = v;
								}
							}
						}
					}
				}
			}
		}
//		alert('got xml data '+data.length);
		return data;
	} catch (e) { // domexception
		lastError = 'Error parsing completion response: '+e.message;
		return null;
	}
}

// handlers. 'this' is reported as the control. has jx object added to it
// navigate the completion results with up/down arrow
function keyHandler(e)
{
	this.jx.currentText = this.value;
	ind = this.jx.viewr.selectedRow;
	if (e == undefined && window.event) {
		e = window.event;
	}
	switch(e.keyCode) {
		case 38:
			while (ind >= 0) {
				ind--;
				if (this.jx.viewr.select(ind)) {
					return false;
				}
			}
			break;
		case 40:
			while (ind < this.jx.viewr.div.childNodes.length) {
				ind++;
				if (this.jx.viewr.select(ind)) {
					return false;
				}
			}
			break;
		case 13:
		case 3:
			if (ind >= 0 && ind < this.jx.viewr.div.childNodes.length) {
				this.jx.setSelectedResult();
				this.blur();
			}
			return false;
			break;
	}
//		phoneHome();
	return true;
}

function blurHandler(event)
{
	this.hasFocus = false;
	if(!event && window.event) {
		event=window.event;
	}
	if(this.jx.viewr) {
		this.jx.setSelectedResult();
		this.jx.viewr.hide();
	}
}

function focusHandler(event)
{
	this.hasFocus = true;
	nextCompleter = this.jx;
	if(this.jx.viewr && this.jx.viewr.hasRows()) {
		this.jx.viewr.show();
	} else {
		;
	}
}


function queryString()
{
	with (this) {
		return query+'&'+txtUrlQueryParam+'='+escapeURI(currentText);
	}
}

function fieldChanged()
{
	with (this) {
		currentText = textInput.value;
		if (currentText == previousText) {
			return false;
		}
		previousText = currentText;
		if (currentText == '') {
			return false;
		}
		return true;
	}
}

function processFromCache()
{
	return false;
}

function displayResponse(data)
{
	var	i, j;
	this.viewr.clear();
	valfield = 'value';
	for (i=0; i<data.length; i++) {
		lbl = '';
		if (data[i].label) {
			lbl = data[i].label;
			valfield = data[i].values[this.textinField];
		} else {
			for (j in data[i].values) {
				if (j == this.textinField) {
					valfield = data[i].values[j];
				} else {
					if (lbl) lbl += ',';
					lbl+=data[i].values[j];
				}
			}
		}
		this.viewr.addRow(valfield, lbl, i, true);
	}
	if (this.textInput.hasFocus) {
		this.viewr.show();
	}
}

function setSelectedResult()
{
	if (this.responseData && this.viewr) {
		sel = this.viewr.selectedRow;
		if (sel >= 0 && sel < this.viewr.div.childNodes.length) {
			if (this.textinField && this.responseData[sel].values[this.textinField]) {
				this.textInput.value = this.responseData[sel].values[this.textinField];
			}
			if (this.fillField && this.fillInput) {
				for (j in this.fillField) {
					if (this.fillInput[j] == undefined) {
					} else if (this.responseData[sel].values[this.fillField[j]]) {
						this.fillInput[j].value = this.responseData[sel].values[this.fillField[j]];
					}
				}
			}
		}
	}
}


function jxComplete(query, txtUrlQueryParam, textInput, textinField, fillInput, fillField, viewerStyle, labelStyle, valueStyle)
{
	this.query = query;
	this.textInput = textInput;
	this.txtUrlQueryParam = txtUrlQueryParam;
	this.currentText = textInput.value;
	this.previousText = '';
	this.queryString = queryString;
	this.fieldChanged = fieldChanged;
	this.setSelectedResult = setSelectedResult;
	textInput.onkeydown = keyHandler;
	textInput.onfocus = focusHandler;
	textInput.onblur = blurHandler;
	textInput.jx = this;
	textInput.setAttribute('autocomplete','off');
	textInput.hasFocus = false;
	this.phoneHome = phoneHome;
	this.processXMLResponse = processXMLResponse;
	this.processTextResponse = processTextResponse;
	this.displayResponse = displayResponse;
	this.readyStateChangeHandler = readyStateChangeHandler;
	
	this.responseData = null;
	this.fillField = fillField;
	this.fillInput = fillInput;
	this.textinField = textinField;

	// create a viewer
	this.viewr = new OptionViewr(this, viewerStyle, labelStyle, valueStyle);
}

///// Response Option Viewer //////////////////////

function OptionViewr(jx, viewerStyle, labelStyle, valueStyle)
{
	function setWidth(w)
	{
		this.chkAddDiv();
		if (this.div) {
			this.div.style.width = w+'px';
		}
	}
	
	function moveTo(x,y)
	{
		this.chkAddDiv();
		if (this.div) {
//			this.div.style.offsetLeft = 100;
//			this.div.style.offsetTop = 100;
			this.div.style.left = x+'px';
			this.div.style.top = y+'px';
		}
	}

	
	function show()
	{
		this.chkAddDiv();
		if (this.div && this.div.style.visibility != 'visible') {
			w = 30;
			x = 30;
			y = 30;
			if (this.jx != null && this.jx.textInput != undefined) {
				if (this.jx.textInput.offsetWidth) {
					w = this.jx.textInput.offsetWidth;
				}
				if (this.jx.textInput.offsetWidth) {
					h = this.jx.textInput.offsetHeight;
				}
				if (!h) h = 22;
				x = findPosX(this.jx.textInput);
				y = findPosY(this.jx.textInput)+h;
			}
			this.moveTo(x,y);
			this.setWidth(w);
			this.div.style.visibility = 'visible';
		}
	}
	
	function hide()
	{
		if (this.div)
			this.div.style.visibility = 'hidden';
	}
	
	function viewrMouseDownHandler()
	{
		this.viewr.select(this.data_ind);
		if (this.jx) {
			this.jx.textInput.blur();
		}
	}
	
	function viewrMouseOverHandler()
	{
		this.className = 'jxc-viewer-hover';
	}
	
	function viewrMouseOutHandler()
	{
		if (this.viewr.selectedRow == this.data_ind) {
			this.className = 'jxc-viewer-select';
		} else {
			this.className = 'jxc-viewer-row';
		}
	}
	
	function addRow(val, lbl, ind, selectable)
	{
		rowd = createDocElement('DIV', 'jxcview', 'jxc-viewer-row', false);
		rowd.innerHTML = '<span class="jxc-viewer-value">'+val+'</span>'+
								'<span class="jxc-viewer-label">'+lbl+'</span>';
		rowd.style.visibility = 'inherit';
		if (selectable) {
			rowd.onmousedown=viewrMouseDownHandler;
			rowd.onmouseover=viewrMouseOverHandler;
			rowd.onmouseout=viewrMouseOutHandler;
			rowd.selectable=true;
		} else {
			rowd.selectable=false;
		}
		rowd.viewr = this;
		rowd.data_ind = ind;
		rowd.jx = this.jx;
		this.chkAddDiv();
		this.div.appendChild(rowd);
	}
			
	function clear()
	{
		this.chkAddDiv();
		this.selectedRow = -1;
	 	if (this.div) {
	 		while(this.div.childNodes.length>0) {
				this.div.removeChild(this.div.childNodes[0]);
			}
		}
	}
	
	function hasRows()
	{
		this.chkAddDiv();
		return this.div?this.div.childNodes.length>0:false;
	}
	
	function select(ind)
	{
		this.chkAddDiv();
		if (this.selectedRow >= 0 && this.selectedRow < this.div.childNodes.length) {
			this.div.childNodes.item(this.selectedRow).className = 'jxc-viewer-row';
		}
		if (ind >= this.div.childNodes.length) {
			ind = this.div.childNodes.length-1;
		}
		if (ind >= 0 && !this.div.childNodes.item(ind).selectable) {
			return false;
		}
		this.selectedRow = ind;
		if (ind >= 0) {
			this.div.childNodes.item(ind).className = 'jxc-viewer-select';
		}
		return true;
	}
	
	// in IE6, there are problems with creating and adding the div to do this on page create.
	// (fine in FF). problem apparently with document render timings in tables...
	function chkAddDiv()
	{
		if (this.div == null) {
			this.div = createDocElement('DIV', 'jxcview', 'jxc-viewer', true);
			document.body.appendChild(this.div);
		}
	}
	
	this.chkAddDiv = chkAddDiv;
	this.div = null;
	this.jx = jx;
	this.setWidth = setWidth;
	this.moveTo = moveTo;
	this.hide = hide;
	this.show = show;
	this.clear = clear;
	this.addRow = addRow;
	this.hasRows = hasRows;
	this.select = select;
	this.selectedRow = -1;
//	this.setWidth(30);
//	this.moveTo(30,30);
//	this.hide();
}

///////////MAIN LOOP//////////////////
function jxRequestLoop()
{
	if (nextCompleter != null) {
		currentCompleter = nextCompleter;
	}
	if(currentCompleter != null) {
//		timeStamp=(new Date()).getTime();
		if (currentCompleter.fieldChanged()) {
			if (currentCompleter.processFromCache == null || 
					!currentCompleter.processFromCache()) {
				if(hasXMLHttp){
					currentCompleter.phoneHome();
				} else {
				}
			}
		}
//		currentCompleter.inputField.focus()
	}
	if (requestLoopFired) {
		setTimeout("jxRequestLoop()",2000);
	}
	return true;
}

var	requestLoopFired=false;
// Call mainLoop() after 10 milliseconds...
function kickstartCompletions()
{
	if (!requestLoopFired) {
		requestLoopFired = true;
		setTimeout("jxRequestLoop()",10);
	}
}

function haltCompletions()
{
	requestLoopFired = false;
}

kickstartCompletions();
