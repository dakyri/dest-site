<?php
	error_reporting(3);
	require_once(in_parent_path('common/pdfwriter.php'));
	require_once(in_parent_path('common/common_mysql.php'));
// using fpdf, cheap, not especially nasty, and thoroughly open source
/////////////////////////////////////////////////////////
// XML parser bits
/////////////////////////////////////////////////////////
//	var	$xml_pdfschema_element_stack;
//	var	$xml_pdfschema_current_table;
//	var	$xml_pdfschema_current_cdata;
//	var	$xml_pdfschema_current_documentation;
//	var	$xml_pdfschema_current_validation;
//	var	$xml_pdfschema_current_field;
//	var	$schema_tables;
//	var	$xml_pdfschema_current_utype;
//	var	$xml_pdfschema_utype_stack;
//	var	$schema_types;
//	var	$xml_pdfschema_parser;
	
	$pdfschema_element_stack = array();
	$pdfschema_style = array();
	$pdfschema_style_stack = array();
	$pdfschema_list_stack = array();
	$pdfschema_matrix_stack = array();
	$pdfschema_lists = NULL;
	$pdfschema_writer = NULL;
	$pdfschema_current_cell = NULL;
	$pdfschema_current_matrix = NULL;
	$pdfschema_current_cell_list = NULL;
	$pdfschema_error_msg = "Indeterminate error";
	
	class Style
	{
		var		$name;
		var		$font;
		var		$style;
		var		$size;
		
		function Style($n, $f, $style, $size)
		{
			$this->name = $n;
			$this->font = $f;
			$this->style = $style;
			$this->size = $size;
		}
	}
	
	// main holder for images
	class Img
	{
		var		$x;
		var		$y;
		var		$height;
		var		$width;
		var		$src;
		
		function Img($src, $x,$y, $width, $height)
		{
			$this->src = $src;
			$this->x = $x;
			$this->y = $y;
			$this->width = $width;
			$this->height = $height;
		}
	}
		
	// main holder for text
	//   values according to the extended FPDF "Cell"/"VCell" call
	class Cell
	{
		var		$text;	// an array of text fragment or field elements (whinch interpolate from db)
		var		$style;	// the name of a Style structure defined by "define-style"
		var		$linebreak;	//
		var		$align;	//
		var		$border;
		var		$direction;	// "H", or default for horizontal, "V" for vertical
		var		$fill_color;	// background color in #RRGGBB format
		var		$text_color;	// text color in #RRGGBB format
		var		$x;
		var		$y;
		var		$height;
		var		$width;
			
		function Cell($s, $x=NULL, $y=NULL, $width=0, $height=0, $lb="", $a=NULL, $brdr=NULL, $direction=NULL, $fillcol=NULL, $textcol=NULL)
		{
			$this->text = array();
			$this->style = $s;
			$this->border = $brdr;
			$this->linebreak = $lb;
			$this->align = $align;
			$this->direction = $direction;
			$this->x = $x;
			$this->y = $y;
			$this->width = $width;
			$this->height = $height;
			$this->fill_color = $fillcol;
			$this->text_color = $textcol;
		}
	}
	
	class Field
	{
		var		$object;
		var		$field;
		var		$index;
		var		$expr;
		var		$cond;
		
		function Field($o, $f, $i, $ex, $ic)
		{
			$this->object = $o;
			$this->field = $f;
			$this->index = $i;
			$this->expr = $ex;
			$this->cond = $ic;
		}
	}
	
	class Checkbox
	{
		var		$x;
		var		$y;
		var		$width;
		var		$object;
		var		$field;
		var		$index;
		var		$expr;
		
		function Checkbox($width, $o, $f, $i, $ex)
		{
			$this->x = 0;	// set according to string position
			$this->y = 0;
			$this->width = $width;
			$this->object = $o;
			$this->field = $f;
			$this->index = $i;
			$this->expr = $ex;
		}
	}
		
	class Expression
	{
		var		$expr;
		
		function Expression($ex)
		{
			$this->expr = $ex;
		}
	}
	
	// CellRow holds an array of textcells, and handles their horizontal alignment
	class CellRow
	{
		var		$textcells;
		
		function CellRow()
		{
			$this->textcells = array();
		}
		
		function Reset()
		{
			$this->textcells = array();
		}
	}
	
	class CellList
	{
		var		$textcells;
		var		$nitem;
		var		$iterator;
		
		function CellList($i, $n)
		{
			$this->textcells = array();
			$this->nitem = $n;
			$this->iterator = $i;
		}
		function Reset()
		{
			$this->textcells = array();
		}
	}
	
	class CellMatrix
	{
		var	$x;
		var	$y;
		var	$width;
		var	$height;
		var	$border;
		var	$column;
		var	$title;
		
		function CellMatrix($x,$y,$width,$height,$border)
		{
			$this->x = $x;
			$this->y = $y;
			$this->width = $width;
			$this->height = $height;
			$this->border = $border;
			$this->column = array();
			$this->title = NULL;
		}
		
		function AddColumn($nm,$x,$w)
		{
			$this->column[$nm] = $x;
		}
		function ColumnWidth($nm)
		{
			if (isset($this->column[$nm])) {
				$nmx = $this->column[$nm];
			} else {
				return 0;
			}
			reset($this->column);
			$ncx = 0;
			while (list($k,$v)=each($this->column)) {
				if ($v > $nmx && ($v < $ncx || $ncx == 0)) {
					$ncx = $v;
				}
			}
			if ($ncx == 0) {
				return 0; // width of page
			}
			return $ncx-$nmx;
		}
	}
	function pdf_debug($s)
	{
	}
	
// Element events are issued whenever the XML parser encounters start or end tags. There are separate handlers for start tags and end tags. 
	function pdfschema_start_element_handler( $parser, $tag_name, $attribs)
	{
		pdf_debug("start $tag_name");
		global 	$pdfschema_element_stack;
		global	$pdfschema_current_cell;
		global	$pdfschema_style;
		global	$pdfschema_style_stack;
		global	$pdfschema_list_stack;
		global	$pdfschema_current_list;
		global	$pdfschema_current_matrix;
		global	$pdfschema_matrix_stack;
		global	$pdfschema_params;
		global	$pdfschema_writer;
		
		global	$mysql;
		
		$tos = end($pdfschema_element_stack);
		$tos_2 = prev($pdfschema_element_stack);
				
//		if ($tos == "documentation") {
//			echo "->documentation<br>";
//			$attrib_string = attrib_string($attribs);
//			if ($attrib_string) {
//				$xml_pdfschema_current_documentation .= "<$tag_name $attrib_string>";
//			} else {
//				$xml_pdfschema_current_documentation .= "<$tag_name>";
//			}
//			return;
//		}
		reset($pdfschema_params);
		while (list($k,$v)=each($pdfschema_params)) {
			$$k = $v;
		}

		$name = NULL;
		$font = NULL;
		$style = NULL;
		$size = NULL;
		
		$src = NULL;
		$x = NULL;
		$y = NULL;
		$link = NULL;
		
		$height = NULL;
		$width = NULL;
		
		$database = NULL;
		$table = NULL;
		$where = NULL;
		
		$object = NULL;
		$field = NULL;
		$index = NULL;
		
		$expr = NULL;
		$cond = NULL;
		
		$nelems = NULL;
		$iterator = NULL;
		
		$style = NULL;
		$linebreak = NULL;
		$border = NULL;
		$align = NULL;
		$direction = NULL;
		$fill_color = NULL;
		$text_color = NULL;
		
		reset($attribs);
		switch ($tag_name) {
			case "pdfschema":
				while (list($key, $value)=each($attribs)) {
					switch($key) {
						case "name":
							$name = $value;
							break;
						default:
							break;
					}
				}
				break;
				
			case "database-object":
				while (list($key, $value)=each($attribs)) {
					switch($key) {
						case "name":
							$name = $value;
							break;
						case "database":
							$database = $value;
							break;
						case "table":
							$table = $value;
							break;
						case "where":
							$where = $value;
							break;
						default:
							break;
					}
				}
				if ($name && $database && $table && $where) {
//					echo "selecting", $where, " $code<br>";
					$where = eval("return \"$where\";");
					$query = "select * from $database.$table where $where";
//					echo $query, "<br>";
					$result = mysql_query($query);
					if (!$result) {
						errorpage("Didn't find '$where' in $database.$table");
					} else {
						$nitems = mysql_num_rows($result);
//						echo " nitems", $nitems, "<br>";
						if ($nitems == 0) {
							$GLOBALS[$name] = "";
						} else {
							$val = mysql_fetch_object($result);
							$GLOBALS[$name] = $val;
						}
					}
				}
				break;
				
			case "define-style":
				while (list($key, $value)=each($attribs)) {
					switch($key) {
						case "name":
							$name = $value;
							break;
						case "font":
							$font = $value;
							break;
						case "style":
							$style = $value;
							break;
						case "size":
							$size = $value;
							break;
						default:
							break;
					}
				}
				if ($name) {
					$pdfschema_style[$name] = new Style($name, $font, $style, $size);
				}
				break;
				
			case "img":
				while (list($key, $value)=each($attribs)) {
					switch($key) {
						case "src":
							$src = $value;
							break;
						case "x":
							$x = $value;
							break;
						case "y":
							$y = $value;
							break;
						case "width":
							$width = $value;
							break;
						case "height":
							$height = $value;
							break;
						case "link":
							$link = $value;
							break;
						default:
							break;
					}
				}
				if ($pdfschema_current_list) {
					$pdfschema_current_list->textcells[] = new Img($src, $x, $y, $width, $height);
					$pdfschema_current_cell = NULL;
				}
				break;
				
			case "title":
				while (list($key, $value)=each($attribs)) {
					switch($key) {
						default:
							break;	
					}
				}
				// iterate several times through things in between <list>...</list>
				
				array_push($pdfschema_list_stack, $pdfschema_current_list);
				$pdfschema_current_list = new CellList(NULL, NULL);
				break;

			case "list":
				while (list($key, $value)=each($attribs)) {
					switch($key) {
						case "nitem":
							$nelems = $value;
							break;
						case "iterator":
							$iterator = $value;
							break;
						case "name":
							$name = $value;
							break;
						default:
							break;	
					}
				}
				// iterate several times through things in between <list>...</list>
				
				array_push($pdfschema_list_stack, $pdfschema_current_list);
				$pdfschema_current_list = new CellList($iterator, $nelems);
				break;

			case "col":
				while (list($key, $value)=each($attribs)) {
					switch($key) {
						case "name":
							$name = $value;
							break;
						case "x":
							$x = $value;
							break;
						case "width":
							$width = $value;
							break;
						default:
							break;	
					}
				}
				if ($pdfschema_current_matrix) {
					$pdfschema_current_matrix->AddColumn($name, $x, $width);
				}
				break;

			case "row":
				while (list($key, $value)=each($attribs)) {
					switch($key) {
						case "name":
							$name = $value;
							break;
						case "y":
							$y = $value;
							break;
						case "height":
							$height = $value;
							break;
						default:
							break;	
					}
				}
				array_push($pdfschema_list_stack, $pdfschema_current_list);
				$pdfschema_current_list = new CellRow($name, $y,$height);
				break;

			case "cell":
				while (list($key, $value)=each($attribs)) {
					switch($key) {
						case "style":
							$style = $value;
							break;
						case "linebreak":
							$linebreak = $value;
							break;	
						case "border":
							$border = $value;
							break;	
						case "align":
							$align = $value;
							break;	
						case "direction":
							$direction = $value;
							break;	
						case "fillcolor":
							$fill_color = $value;
							break;	
						case "textcolor":
							$text_color = $value;
							break;
						case "x":
							$x = $value;
							break;
						case "y":
							$y = $value;
							break;
						case "width":
							$width = $value;
							break;
						case "height":
							$height = $value;
							break;
							break;
						default:
							break;	
					}
				}
				if ($pdfschema_current_cell == NULL) {
					if ($pdfschema_current_matrix != NULL) {
						if (isset($pdfschema_current_matrix->column[$x])) {
							$nm = $x;
							$x = $pdfschema_current_matrix->column[$nm]+$pdfschema_current_matrix->x;
							if ($width == NULL) {
								$width = $pdfschema_current_matrix->ColumnWidth($nm);
							}
//							echo "x $x wid $width<br>";
						}
					}
					$pdfschema_current_cell = new Cell(
												$style,
												$x,
												$y,
												$width,
												$height,
												$linebreak,
												$align,
												$border,
												$direction,
												$fill_color,
												$text_color);
				} else {
//					echo "not adding a cell<br>";
					// this is a bit of a parse error
				}
				break;

			case "expression":
				while (list($key, $value)=each($attribs)) {
					switch($key) {
						case "expr":
							$expr = $value;
							break;
						default:
							break;	
					}
				}
//				echo "field $object $field<br>current cell is<br>";
//				var_dump($pdfschema_current_cell);
//				echo "<br>";
				if ($pdfschema_current_cell && $expr) {
					$pdfschema_current_cell->text[] = new Expression($expr);
				} else {
//					echo "not adding an expression<br>";
				}
				break;
				
			case "checkbox":
				while (list($key, $value)=each($attribs)) {
					switch($key) {
						case "x":
							$x = $value;
							break;
						case "y":
							$y = $value;
							break;
						case "width":
							$width = $value;
							break;
						case "height":
							$height = $value;
							break;
						case "object":
							$object = $value;
							break;
						case "field":
							$field = $value;
							break;
						case "index":
							$index = $value;
							break;
						case "link":
							$link = $value;
							break;
						case "expr":
							$expr = $value;
							break;
						default:
							break;
					}
				}
				if ($pdfschema_current_cell) {
					$pdfschema_current_cell->text[] = new Checkbox($width, $object, $field, $index, $expr);
				} else {
//					echo "not addding checkbox<br>";
				}
				break;

			case "field":
				while (list($key, $value)=each($attribs)) {
					switch($key) {
						case "object":
							$object = $value;
							break;
						case "field":
							$field = $value;
							break;
						case "index":
							$index = $value;
							break;
						case "expr":
							$expr = $value;
							break;
						case "condition":
							$cond = $value;
							break;
						default:
							break;	
					}
				}
//				echo "field o $object f $field<br>current cell is<br>";
//				var_dump($pdfschema_current_cell);
//				echo "<br>";
				if ($pdfschema_current_cell && $object && $field) {
					$pdfschema_current_cell->text[] = new Field($object, $field, $index, $expr, $cond);
				} else {
//					echo "not adding a field<br>";
				}
				
				
				break;

			case "matrix":
				while (list($key, $value)=each($attribs)) {
					switch($key) {
						case "border":
							$border = $value;
							break;	
						case "x":
							$x = $value;
							break;
						case "y":
							$y = $value;
							break;
						case "width":
							$width = $value;
							break;
						case "height":
							$height = $value;
							break;
						default:
							break;	
					}
				}
				if ($x == NULL) {
					$x = 0;
				}
				if ($y == NULL) {
					$y = $pdfschema_writer->y;
				}
				if ($y < $pdfschema_writer->tMargin) {
					$y = $pdfschema_writer->tMargin;
				}

				if ($pdfschema_current_matrix) {
					$x += $pdfschema_current_matrix->x;
					$y += $pdfschema_current_matrix->y;
					array_push($pdfschema_list_stack, $pdfschema_current_matrix);
				} else {
					$x += $pdfschema_writer->lMargin;
				}
//				echo "new mat @$width@$height@ x $x y $y<br>";
				$pdfschema_current_matrix = new CellMatrix($x,$y,$width,$height,$border);
				break;
				
			default:
				break;
		}
		
		array_push($pdfschema_element_stack, $tag_name);
	}

	function pdfschema_end_element_handler( $parser, $tag_name)
	{
		global 	$pdfschema_element_stack;
		global	$pdfschema_current_cell;
		global	$pdfschema_current_list;
		global	$pdfschema_current_matrix;
		global	$pdfschema_style;
		global	$pdfschema_style_stack;
		global	$pdfschema_list_stack;
		global	$pdfschema_writer;
		global	$mysql;
		
		$tos = end($pdfschema_element_stack);
		
		pdf_debug("end $tag_name tos=$tos");		
		$expected_name = array_pop($pdfschema_element_stack);	// expect "$expected_name == $tag_name" but not worth forcing the issue
		$tos = end($pdfschema_element_stack);
		
//		echo "'$tag_name' tag name, tos now $tos<br>";
		switch ($tag_name) {
			case "pdfschema":
				break;
				
			case "matrix":
				$cur_y = $pdfschema_writer->y;
				$pdfschema_writer->Ln();
				if ($pdfschema_current_matrix->border == "frame") {
					if ($pdfschema_current_matrix->height == NULL) {
						$h = $cur_y - $pdfschema_current_matrix->y;
					}
					if ($pdfschema_current_matrix->width == NULL) {
						$w = $pdfschema_writer->fw-$pdfschema_writer->lMargin-$pdfschema_writer->rMargin;
					} else {
						$w = $pdfschema_current_matrix->width;
					}
					$pdfschema_writer->Rect(
							$pdfschema_current_matrix->x,
							$pdfschema_current_matrix->y,
							$w,$h);
				}
				$pdfschema_current_matrix = NULL;
				break;

			case "title":
				$lind = count($pdfschema_list_stack);
				if ($lind == 0) {
				} else {
					$border = false;
					if ($pdfschema_current_matrix) {
//						$pdfschema_current_matrix->title = $pdfschema_current_list->textcells;
						if ($pdfschema_current_matrix->border == "all") {
							$border = true;
						} else {
							$border = false;
						}
					}
					pdf_lister($pdfschema_current_list, NULL, $border, true);
					$pdfschema_current_list = array_pop($pdfschema_list_stack);
				}
				break;

			case "list":
				$lind = count($pdfschema_list_stack);
				if ($lind == 0) {
				} else {
					$pdfschema_list_stack[$lind-1]->textcells[] = $pdfschema_current_list;
					$pdfschema_current_list = array_pop($pdfschema_list_stack);
				}
				break;

			case "row":
				$lind = count($pdfschema_list_stack);
				if ($lind == 0) {
					
				} else {
					$pdfschema_list_stack[$lind-1]->textcells[] = $pdfschema_current_list;
					$pdfschema_current_list = array_pop($pdfschema_list_stack);
				}
				break;
				
			case "col":
				break;

			case "img":
				break;

			case "checkbox":
				break;

			case "cell":
				$pdfschema_current_list->textcells[] = $pdfschema_current_cell;
				$pdfschema_current_cell = NULL;
				break;

			case "expression":
				break;

			case "field":
				break;

			default:
				break;
		}
		if (count($pdfschema_list_stack) == 0) {	// we're at the top
			$border = NULL;
			if ($pdfschema_current_matrix) {
				if ($pdfschema_current_matrix->border == "all" || $pdfschema_current_matrix->border == "children") {
					$border = true;
				}
			}
			pdf_lister($pdfschema_current_list, NULL, $border, true);
		} else {
//			echo "lists on stack = ", count($pdfschema_list_stack);
//			var_dump($pdfschema_list_stack);
//			echo "<br>";
		}
	}

	function pdfschema_character_data_handler($parser, $data) 
//Character data is roughly all the non-markup contents of XML documents, including whitespace between tags. Note that the XML parser does not add or remove any whitespace, it is up to the application (you) to decide whether whitespace is significant. 
	{
//		echo "cdata [$data]<br>";
		global 	$pdfschema_element_stack;
		global 	$pdfschema_current_cell;
		global 	$pdfschema_list_stack;
		
		$tos = end($pdfschema_element_stack);
		if ($pdfschema_current_cell) {
			$data = str_replace('\n', '', $data);
			if (strlen($data) > 0) {
				$pdfschema_current_cell->text[] = $data;
			}
//			echo "current cell is<br>";
//			var_dump($pdfschema_current_cell);
//			echo "<br>";
		}
	}

	function pdfschema_processing_instruction_handler($parser, $target, $data) 
// PHP programmers should be familiar with processing instructions (PIs) already.  is a processing instruction, where php is called the "PI target". The handling of these are application-specific, except that all PI targets starting with "XML" are reserved. 
	{
//		echo "pi $target $data<br>";
		global 	$pdfschema_element_stack;
		global 	$pdfschema_current_cell;
		
		$tos = end($xml_pdfschema_element_stack);
		
		if ($pdfschema_current_cell) {
			$pdfschema_current_cell->text[] = "\<\?$target $data\?\>";
		}
	}


	function pdfschema_default_handler($parser, $data)
// What goes not to another handler goes to the default handler. You will get things like the XML and document type declarations in the default handler. 
	{
//		echo "default: |$data|<br>";
		global 	$pdfschema_element_stack;
		global 	$pdfschema_current_cell;
		
		$tos = end($pdfschema_element_stack);
		
		if ($pdfschema_current_cell) {
			$pdfschema_current_cell->text[] = $data;
		}
	}


	function pdfschema_unparsed_entity_decl_handler($parser, $entity_name, $base, $system_id, $public_id, $notation_name) 
// This handler will be called for declaration of an unparsed (NDATA) entity. 
// entity_name: The name of the entity that is about to be defined. 
// base: This is the base for resolving the system identifier (systemId) of the external entity. Currently this parameter will always be set to an empty string. 
// system_id: System identifier for the external entity. 
// public_id: Public identifier for the external entity. 
// notation_name: Name of the notation of this entity (see xml_set_notation_decl_handler()). 
	{
//		echo "unparsed entity $notation_name: $base, $system_id, $public_id<br>";
		global 	$pdfschema_element_stack;
		global 	$pdfschema_current_cell;
		
		$tos = end($pdfschema_element_stack);
		
					$nt = count($cell->text);
		if ($pdfschema_current_cell) {
			$str = "<!ENTITY $entity_name";
			$str .= ($public_id?" PUBLIC $public_id": " $system_id");
			$str .= " NDATA $notation_name>";
			$pdfschema_current_cell->text[] = $str;
			return;
		}
 	}

	function pdfschema_notation_decl_handler($parser, $notation_name, $base, $system_id, $public_id) 
// This handler is called for declaration of a notation. 
// notation_name: This is the notation's name, as per the notation format described above. 
// base: This is the base for resolving the system identifier (system_id) of the notation declaration. Currently this parameter will always be set to an empty string. 
// system_id: System identifier of the external notation declaration. 
// public_id: Public identifier of the external notation declaration. 
	{
//		echo "notation decl: $base, $system_id, $public_id<br>";
		
		global 	$pdfschema_element_stack;
		global 	$pdfschema_current_cell;
		
		$tos = end($pdfschema_element_stack);
		
		if ($pdfschema_current_cell) {
			$str = "<!NOTATION $notation_name";
			$str .= ($public_id?" PUBLIC $public_id": " $system_id");
			$str .= ">";
			$pdfschema_current_cell->text[] = $str;
			return;
		}

	}

	function pdfschema_external_entity_ref_handler($parser, $open_entity_names, $base, $system_id, $public_id) 
// open_entity_names: The second parameter, open_entity_names, is a space-separated list of the names of the entities that are open for the parse of this entity (including the name of the referenced entity). 
// base: This is the base for resolving the system identifier (system_id) of the external entity. Currently this parameter will always be set to an empty string. 
// system_id: The fourth parameter, system_id, is the system identifier as specified in the entity declaration. 
// public_id: The fifth parameter, public_id, is the public identifier as specified in the entity declaration, or an empty string if none was specified; the whitespace in the public identifier will have been normalized as required by the XML spec. 
	{
		global	$pdfschema_external_base;
		
		global 	$pdfschema_element_stack;
		global 	$pdfschema_current_cell;
		
		$tos = end($pdfschema_element_stack);
		
		if ($pdfschema_current_cell) {
			$str = "<!ENTITY $entity_name";
			$str .= ($public_id?" PUBLIC $public_id": " SYSTEM $system_id");
			$str .= " NDATA $notation_name>";
			$pdfschema_current_cell->text[] = $str;
			return;
		}

		if ($system_id) {
			if (!($fp = fopen("$xml_pdfschema_external_base/$system_id", "r"))) {
//				echo "failed to open $xml_pdfschema_external_base/$system_id";
				return false;
			}
			if (!($xparser = new_sqlschema_parser($pdfschema_external_base))) {
	            printf("Could not open entity %s at %s\n", $open_entity_names,
	                   $system_id);
	            return false;
	      }
	      while ($data = fread($fp, 4096)) {
	         if (!xml_parse($xparser, $data, feof($fp))) {
	                printf("XML error: %s at line %d while parsing entity %s\n",
	                       xml_error_string(xml_get_error_code($xparser)),
	                       xml_get_current_line_number($xparser), $open_entity_names);
	                xml_parser_free($xparser);
	                return false;
				}
	      }
	      xml_parser_free($xparser);
	      return true;
	   }
	   return false;
	}
	
	function new_pdfschema_parser($base)
	{
		global	$pdfschema_external_base;
		
		$pdfschema_parser = xml_parser_create();
		$pdfschema_external_base = $base;
		if (!xml_set_element_handler($pdfschema_parser, "pdfschema_start_element_handler", "pdfschema_end_element_handler")) return false;
		if (!xml_set_character_data_handler($pdfschema_parser, "pdfschema_character_data_handler")) return false;
		if (!xml_set_processing_instruction_handler($pdfschema_parser, "pdfschema_processing_instruction_handler")) return false ;
		if (!xml_set_default_handler($pdfschema_parser, "pdfschema_default_handler")) return false;
		if (!xml_set_unparsed_entity_decl_handler($pdfschema_parser, "pdfschema_unparsed_entity_decl__handler")) return false;
		if (!xml_set_notation_decl_handler($pdfschema_parser, "pdfschema_notation_decl_handler")) return false;
		if (!xml_set_external_entity_ref_handler($pdfschema_parser, "pdfschema_external_entity_ref_handler")) return false;
		if (!xml_parser_set_option($pdfschema_parser, XML_OPTION_CASE_FOLDING, false)) return false;
		return $pdfschema_parser;
	}
	
	function parse_pdfschema($parser, $fp, $filename)
	{
		global	$pdfschema_error_msg;
		while ($data = fread($fp, 4096)) {
			if (!xml_parse($parser, $data)) {
				$pdfschema_error_msg = (sprintf("XML error: %s at line %d in %s",
   	                 xml_error_string(xml_get_error_code($parser)),
   	                 xml_get_current_line_number($parser), $filename));
				return false;
			}
		}
		return true;
	}
	
	
	function	generate_schema_pdf(
		$pdf_schema, $file)
	{
		global	$pdfschema_writer;
		global	$pdfschema_list_stack;
		global	$pdfschema_current_list;
		global	$pdfschema_current_matrix;
		global	$pdfschema_element_stack;
		global	$pdfschema_current_cell;
		global	$pdfschema_error_msg;
		global	$pdfschema_iterators;
		
		$pdfschema_parser = NULL;
		
		$pdfschema_current_list= new CellList(NULL, 1);
		$pdfschema_list_stack = array();
		$pdfschema_element_stack = array();
		$pdfschema_current_cell = NULL;
		$pdfschema_current_matrix = NULL;
		$pdfschema_iterators = array();

// find and open the template
		$template_fp = NULL;
		$template_fp = @fopen($pdf_schema, "r");
		if (!$template_fp) {
			$pdfschema_error_msg = "Failed to open pdf xml schema '$pdf_schema'";
			return false;
		}
		
// initialise our pdf writer		
		$pdfschema_writer = new PDFWriter('P','mm','A4');	
		$pdfschema_writer->AddPage();
		$pdfschema_writer->SetFont('Arial','B',16);

// initialise and run the parser
		$pdfschema_parser = new_pdfschema_parser("base");
		if (!parse_pdfschema($pdfschema_parser, $template_fp, $pdf_schema)) {
			return false;
		}
		
// spit out our stuff
		$pdfschema_writer->Output($file, "I");
		return true;
	}
	
	// spit out a list of textcells
	// could use 'each' but these may be big objects. each does a value assign
	function pdf_lister(&$cellistr, $row_y,$border,$clear_list)
	{
		global	$pdfschema_writer;
		global	$pdfschema_style;
		global	$pdfschema_current_matrix;
		global	$pdfschema_iterators;
		
		reset($pdfschema_iterators);
		while (list($k,$v) = each($pdfschema_iterators)) {
			$$k = $v;
		}
		$i = 0;
		$j = 0;
		$cellistrclass = get_class($cellistr);
		$ncells = count($cellistr->textcells);
		$isarow = false;
		if ($cellistrclass == "CellList" || $cellistrclass == "celllist") {
			if ($cellistr->nitem != NULL) {
				$nlistitems = eval("return $cellistr->nitem;");
			} else {
				$nlistitems = 1;
			}
			$iterator = $cellistr->iterator;
		} elseif ($cellistrclass == "CellRow" || $cellistrclass == "cellrow") {
			$isarow = true;
			if ($cellistr->y != NULL) {
				$row_y = $cellistr->y;
			}
			$nlistitems = 1;
			$iterator = NULL;
		} else {
			$nlistitems = 1;
			$iterator = NULL;
		}
		$listitem = 0;
		while ($listitem < $nlistitems) {
			if ($iterator) {
				$$iterator = $listitem;
				$pdfschema_iterators[$iterator] = $listitem;
			}
			pdf_debug("pdf lister iter $listitem, $ncells cells");
			
			$col_x = $pdfschema_writer->GetX();
			$max_height = 0;
			for ($i=0; $i<$ncells; $i++) {
				$cell = &$cellistr->textcells[$i];
				$cellclass = get_class($cell);
				pdf_debug("pdf lister cell $i $cellclass");
				if ($cellclass == "Cell" || $cellclass == "cell") {
					$cell->current_text = get_cell_text($cell);
					pdf_debug("cell text $cell->current_text");
					if ($cell->style) {
						$style = &$pdfschema_style[$cell->style];
						set_style($pdfschema_writer, $style);
					}
					if ($cell->height) {
						$height = $cell->height;
					} else {
						$height = $pdfschema_writer->FontSize;
//						echo "font height ", $height, "<br>";
					}
					if ($cell->width && $cell->width > 0) {
						$width = $cell->width;
					} elseif ($cell->current_text) {
						$width = $pdfschema_writer->GetStringWidth($cell->current_text);
					}
					$pg_rem_width = $pdfschema_writer->fw - 
									$pdfschema_writer->rMargin -
									$col_x;
					if ($width == NULL || $width == 0 || ($isarow && $i==$ncells-1) || $width > $pg_rem_width) {
						$width = $pg_rem_width;
					}
					$col_x += $width;
					if ($pdfschema_writer->GetStringWidth($cell->current_text) > $width ||
							strstr($cell->current_text, "\n") || strstr($cell->current_text, "\\n")) {
						pdf_debug("trying layout $cell->current_text .. ");
						$cell->current_text = layout_text_to_width($cell->current_text, $width, $height);
//						echo "height is now $height<br>";
					}
					$cell->current_width = $width;
					$cell->current_height = $height;
					if ($height > $max_height) {
						$max_height = $height;
					}
				}
			}
			
			if ($isarow) {
				for ($i=0; $i<$ncells; $i++) {
					$cell = &$cellistr->textcells[$i];
					$cellclass = get_class($cell);
					if ($cellclass == "Cell" || $cellclass == "cell") {
						$cell->current_height = $max_height;
					}
				}
			}
			
			for ($i=0; $i<$ncells; $i++) {
				pdf_debug("pdf lister cell $i");
				$cell = &$cellistr->textcells[$i];
				$cellclass = get_class($cell);
//				echo "cell class $cellclass<br>";
				if ($cellclass == "CellList" || $cellclass == "celllist") {
					pdf_lister($cell, $row_y,$border, false);
				} elseif ($cellclass == "CellRow" || $cellclass == "cellrow") {
					pdf_lister($cell, $pdfschema_writer->GetY(),$border, $clear_list);
					$pdfschema_writer->Ln();
				} elseif ($cellclass == "Img" || $cellclass == "img") {
					if ($cell->x) {
						$x = $cell->x;
					} else {
						$x = $pdfschema_writer->GetX();
					}
					if ($cell->y) {
						$y = $cell->y;
					} else {
						$y = $pdfschema_writer->GetY();
					}
					if ($pdfschema_current_matrix) {
						$x += $pdfschema_current_matrix->x;
						$y += $pdfschema_current_matrix->y;
					}
					if ($cell->width) {
						$width = $cell->width;
					} else {
						$width = 0;
					}
					if ($cell->height) {
						$height = $cell->height;
					} else {
						$height = 0;
					}
					$ifnm = in_parent_path($cell->src);
					if (file_exists($ifnm)) {
						$pdfschema_writer->Image($ifnm, $x, $y, $width, $height);
					}
				} elseif ($cellclass == "Cell" || $cellclass == "cell") {
		// a regular cell. collate the bits and spit em out
					$cell->current_text;
					
					$cell_fill = 0;
					if ($cell->style) {
						$style = &$pdfschema_style[$cell->style];
						set_style($pdfschema_writer, $style);
					}
					$direction = $cell->direction;
					$align = $cell->align;
					if ($row_y) {
						pdf_debug("0 linebreak");
						$linebreak = 0;
					} elseif ($cell->linebreak) {
						$linebreak = $cell->linebreak;
					} else {
						pdf_debug("1 linebreak");
						$linebreak = 1;
					}
					if (!$direction || !($direction == "H" || $direction == "V")) {
						$direction = "H";
					}
					if ($cell->fill_color) {
					}
					if ($cell->text_color) {
					}
					if ($cell->column) {
						pdf_debug("col  $cell->column");
					}
					if ($cell->border) {
						$border = $cell->border;
					}
					if ($border==NULL && $pdfschema_current_matrix && $pdfschema_current_matrix->border == "all") {
						$border = 1;
					}
					if ($direction == "V") {
						if (!$align ||
								!($align == "U" || $align == "D" || $align == "C")) {
							$align = "D";
						}
						$pdfschema_writer->VCell(
								$cell->current_width,
								$cell->current_height,
								$cell->current_text,
								$border,
								$linebreak,
								$align);
					} else {
						if (!$align ||
								!($align == "L" || $align == "R" || $align == "C")) {
							$align = "L";
						}
						if ($cell->x) {
							$pdfschema_writer->SetX($cell->x);
							pdf_debug("set X $cell->x");
						}
						if ($cell->y) {
							$pdfschema_writer->SetY($cell->y);
							pdf_debug("set Y $cell->y");
						}
						$at_y = $pdfschema_writer->y;
						$at_x = $pdfschema_writer->x;
						$pdfschema_writer->Cell(
								$cell->current_width,
								$cell->current_height,
								$cell->current_text,
								$border,
								$linebreak,
								$align);
						$nt = count($cell->text);
						$chk_width = 1.5;
						for ($j=0; $j<$nt; $j++) {
							$tex = &$cell->text[$j];
							if (is_a($tex, "Checkbox")) {
								pdf_debug("draw a rect $at_y, $tex->x");
								$pdfschema_writer->Rect($at_x+$tex->x, $at_y+($cell->current_height-$chk_width)/2, $chk_width, $chk_width);
							}
						}
					}
				}
			}
			$listitem++;
		}
		if ($clear_list) {
			$cellistr->Reset();
		}
	}
	
	function get_cell_text(&$cell)
	{
		global	$pdfschema_iterators;
		global	$pdfschema_writer;
		
		reset($pdfschema_iterators);
		while (list($k,$v) = each($pdfschema_iterators)) {
			$$k = $v;
		}
		
		$celltext = "";
		$nt = count($cell->text);
		for ($j=0; $j<$nt; $j++) {
			$tex = &$cell->text[$j];
			if (is_string($tex)) {
				$celltext .= $tex;
			} elseif (is_a($tex, "Checkbox")) {
				$tex->x = $pdfschema_writer->GetStringWidth($celltext)+1;
				$object = $tex->object;
				$field = $tex->field;
				$index = $tex->index;
				$checked = false;
//				echo "field value $object $field $index<br>";
				if ($object && $field) {
					$fval = $GLOBALS[$object]->$field;
//					echo "fval $fval<br>";
					if ($fval) {
						if ($index) {
//							echo "index $index $auth_row<br>";
							$aval = array_map("rawurldecode", explode("&", $fval));
							if (count($aval) > 1) {
								$whex = eval("return $index;");
//								echo $whex,"!";
								$fval = $aval[$whex];
//								echo "fval now $fval<br>";
							}
						}
					}
				}
				if ($tex->expr) {
					$exp = str_replace("@", "'$fval'", $tex->expr);
					$whex = eval("return $exp;");
					if ($whex) {
						$checked = true;
					}
				}
				if ($checked) {
					$celltext .= "x";
				} else {
					$celltext .= " ";
				}
				$celltext .= "  ";
			} elseif (is_a($tex, "Expression")) {
				pdf_debug("expression $tex->expr");
				if ($tex->expr) {
					$fval = eval("return $tex->expr;");
					$celltext .= $fval;
				}
			} elseif (is_a($tex, "Field")) {
				if ($tex->cond) {
					$do_field = false;
					pdf_debug("conditional field $tex->cond, |$celltext|<br>");
					switch($tex->cond) {
						case "empty-cell":
							if ($celltext == "") {
								$do_field = true;
							}
							break;
					}
				} else {
					$do_field = true;
				}
				if ($do_field) {
					$object = $tex->object;
					$field = $tex->field;
					$index = $tex->index;
					pdf_debug("field value $object $field $index");
					if ($object && $field) {
						$fval = $GLOBALS[$object]->$field;
						pdf_debug("fval $fval");
						if ($fval) {
							if ($index) {
								pdf_debug("index $index $auth_row");
								$aval = array_map("rawurldecode", explode("&", $fval));
								if (count($aval) > 1) {
									$whex = eval("return $index;");
									$fval = $aval[$whex];
									pdf_debug("fval now $whex $fval");
								}
							}
						}
						if ($tex->expr) {
							$exp = str_replace("@", "'$fval'", $tex->expr);
							$whex = eval("return $exp;");
							if ($whex) {
								$celltext .= $whex;
							}
						} else {
							$celltext .= $fval;
						}
					}
				}
			}
		}
		return $celltext;
	}

	function layout_text_to_width($text, $width, &$height)
	{
		global	$pdfschema_writer;
		
//		$lntext = explode('\n', $text);
		$lntext = preg_split("/\n|\\n/", $text);
		$fit_lntext = array();
		while (list($k,$v) = each($lntext)) {
			$tw = $pdfschema_writer->GetStringWidth($v);
//			echo "v = $v<br>";
			if ($tw > $width) {
//				echo "needs a massage<br>";
				$nc = strlen($v);
				$cb = 0;
				$ssi = 0;
				$last_cb = -1;
				while ($cb < $nc) {
					if ($v{$cb} == ' ' || $v{$cb} == '.' ||
							$v{$cb} == '/' || $v{$cb} == ',' || $v{$cb} == '-') {
						$trystr = substr($v,$ssi,$cb-$ssi+1);
//						echo $trystr, "<br>";
						if ($pdfschema_writer->GetStringWidth($trystr) <= $width) {
//							echo "is a possible<br>";
							$last_cb = $cb;
							$cb++;
						} else {
							if ($last_cb < 0) {
								$last_cb = $cb;
							}
							$fit_lntext[] = substr($v,$ssi, $last_cb-$ssi+1);
							$ssi=$last_cb+1;
							$trystr = substr($v,$ssi,$cb-$ssi+1);
//							echo "going on ...", $trystr, "<br>";
							if ($pdfschema_writer->GetStringWidth($trystr) <= $width) {
//								echo " is a possible<br>";
								$last_cb = $cb;
								$cb++;
							} else {
//								echo "whack in a bad fit anyway<br>";
								$fit_lntext[] = substr($v, $ssi, $cb-$ssi+1);
								$last_cb = -1;
								$ssi = $cb+1;
								$cb++;
							}
						}
					} else {
						$cb++;
					}
				}
				if ($cb >= $ssi) {
					if ($last_cb < 0) {
//						echo "add remainder<br>";
						$fit_lntext[] = substr($v, $ssi);
					} else {
//						echo "add last poss ",substr($v, $ssi, $last_cb-$ssi+1)," plus remainder ",substr($v, $last_cb+1),"<br>";
						$fit_lntext[] = substr($v, $ssi, $last_cb-$ssi+1);
						if (strlen(substr($v, $last_cb+1)) > 0) {
							$fit_lntext[] = substr($v, $last_cb+1);
						}
					}
				}
			} else {
				$fit_lntext[] = $v;
			}
		}
		$text = "";
		$height = $pdfschema_writer->FontSize;
		while (list($k,$v) = each($fit_lntext)) {
			if ($text) {
				$height += 1.4*$pdfschema_writer->FontSize;
				$text .= "\n";
			}
			$text .= $v;
		}
		return $text;
	}
	
	function set_style(&$pdfschema_writer, &$style)
	{
		if ($style->font && $style->style && $style->size) {
			$pdfschema_writer->SetFont($style->font, $style->style, $style->size);
		} elseif ($style->font && $style->style) {
			$pdfschema_writer->SetFont($style->font, $style->style);
		} elseif ($style->font && $style->size) {
			$pdfschema_writer->SetFont($style->font, "", $style->size);
		} elseif ($style->font) {
			$pdfschema_writer->SetFont($style->font);
		} elseif ($style->size) {
			$pdfschema_writer->SetFontSize();
		}
	}
?>