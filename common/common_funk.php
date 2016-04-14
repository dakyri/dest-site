<?php

function zoterr($str)
{
//	errorpage( "<B>Unfortunately, $str</B><BR>");
//	exit;
}

function img_dim_attr($img_file)
{
	return "";
}

function file_copy($src, $dst)
{
	if (!($infp=@fopen($src, "r"))) {
		return false;
	}
	if (!($outfp=@fopen($dst, "w"))) {
		fclose($infp);
		return false;
	}
//	echo "$src $dst<BR>";
	while (strlen($str = fread($infp, 1024)) > 0) {
//		echo "@";
		fwrite($outfp, $str);
	}

	
	fclose($infp);
	fclose($outfp);
	return true;
}

function zotmsg($str)
{
//	echo "<B>".$str."</B>"."<BR>";
}

// for php 3.0
function index_of($item, $array)
{
	for ($i=0; $i<sizeof($array); $i++) {
		if ($array[$i] == $item) {
			return $i;
		}
	}
	return -1;
}

function k_index_of($item, &$array)
{
	reset($array);
	while (list($k,$v) = each($array)) {
		if ($v == $item) {
			return $k;
		}
	}
	return -1;
}



function get_directory($dir)
{
	if ($handle = opendir("$dir")) {
 
    /* This is the correct way to loop over the directory. */
    while (false !== ($file = readdir($handle))) {
    	if ($file[0] != ".") {
        $list[] = $file;
      }
    }
   }
	natcasesort($list);
//	$list = array_reverse($list);
	return $list;
}

function cache_variable($var, $filename)
{
		$ser_var_str = serialize($var);
		if (!($remotefile = @fopen($filename, "w"))) {
//			echo "cache variable open error";
			return false;
		}
		if (!@fwrite($remotefile, $ser_var_str, strlen($ser_var_str))) {
//			echo "cache variable write error";
			return false;
		}
		@fclose($remotefile);
		@chmod($filename, 0644);
		return true;
}


function uncache_variable($filename)
{
			
		if (!($remotefile = @fopen($filename, "r"))) {
			return false;
		}
		if (!($ser_var_str=@fread($remotefile, filesize($filename))) ) {
			return false;
		}
		@fclose($remotefile);
		
		return unserialize($ser_var_str);
}

function mkdir_hierarchy($path, $mode) // stub, not quite needed now: in case we want every dir in "path" made at some stage
{
	return mkdir($path, $mode);
}

function in_parent_path($filename, $n=3)
{
	$orig_name = $filename;
	do {
		if (file_exists($filename)) {
			return $filename;
		}
		$n--;
		$filename = "../$filename";
	} while ($n >= 0);
	return $orig_name;
}
?>
