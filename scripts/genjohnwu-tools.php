<?php

function anyMatch($file, $arr)
{
	foreach($arr as $filter)
		if(false !== stristr($file, $filter))
			return true;
 
	return false;
}
function matchAll($file, $arr)
{
	foreach($arr as $filter)
		if(false === stristr($file, $filter))
			return false;
	return true;
}

function forEachFile($func, $params, $root, $incfilter = array(), $excfilter = array("ZZZZ"))
{
	$dh = opendir($root);
	while(false !== ($filename = readdir($dh)))
		if(!is_dir($root . "/" . $filename))
		{
			if(anyMatch($filename, $excfilter))
				continue;
			if(!matchAll($filename, $incfilter))
				continue;

			if(wordlistShouldBeSplit($root . "/" . $filename, $params))
				splitWordlist($root . "/" . $filename, $filename, $func, $params);
			else
				$func($root . "/" . $filename, $params);
		}
}
function forEachSubFile($func, $params, $root, $incfilter = array(), $excfilter = array("ZZZZ"))
{
	$dh = opendir($root);
	while(false !== ($dirname = readdir($dh)))
		if(is_dir($root . "/" . $dirname) && !in_array($dirname, array(".", "..", ".svn")))
		{
			$dirh = opendir($root . "/" . $dirname);
			while(false !== ($filename = readdir($dirh)))	 
			{
				$thisfile = $root . "/" . $dirname . "/" . $filename;

				if(is_dir($thisfile))
					continue;

				if(anyMatch($thisfile, $excfilter))
					continue;
				if(!matchAll($thisfile, $incfilter))
					continue;

				if(wordlistShouldBeSplit($thisfile, $params))
					splitWordlist($thisfile, $filename, $func, $params);
				else
					$func($thisfile, $params);
			}
		}
}
function forEachWordlist($func, $params, $incfilter = array(), $excfilter = array("ZZZZ"))
{ 
	forEachSubFile($func, $params, "build/wordlists/categorized", $incfilter, $excfilter); 
}
function forEachLowercaseWordlist($func, $params, $incfilter = array(), $excfilter = array("ZZZZ"))
{ 
	forEachSubFile($func, $params, "build/wordlists/categorized", $incfilter, array_merge($excfilter, array("uppercase"))); 
}
function forEachUppercaseWordlist($func, $params, $incfilter = array(), $excfilter = array("ZZZZ"))
{ 
	forEachSubFile($func, $params, "build/wordlists/categorized", array_merge($incfilter, array("uppercase")), $excfilter); 
}
function forEachMarkov($func, $params, $lvl, $incfilter = array(), $excfilter = array("ZZZZ"))
{ 
	forEachFile($func, $params, "build/markovlists/" . $lvl, $incfilter, $excfilter); 
}

function getAppType()
{
  global $app;
  if($app == "john")
	return $app;
  if($app == "hashcat" ||
	 $app == "hashcat-7zwordlist" ||
	 $app == "cudahashcat+" ||
	 $app == "cudahashcat+-7zwordlist")
	return "hashcat";
  die("Unknown app given: $app\n");
}
function getAppSubType()
{
  global $app;
  if($app == "john")
	return $app;
  if($app == "hashcat" ||
	 $app == "hashcat-7zwordlist")
	return "hashcat";
  if($app == "cudahashcat+" ||
	 $app == "cudahashcat+-7zwordlist")
	return "cudahashcat+";
  die("Unknown app given: $app\n");
}
function getAppZip()
{
  if($app == "hashcat-7zwordlist" ||
	 $app == "cudahashcat+-7zwordlist")
	return true;
  return false;
}
		  

function getAcceptableFormats()
{
  $acceptable_formats = array(
				"afs" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "afs", 

							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,

							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"bf" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "bf", 
					  
							"john-fpoppercrypt" => 5155000, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => true,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"bfegg" =>
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "bfegg", 
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"bsdi" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "bsdi", 
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"des" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "des",
 
							"john-fpoppercrypt" => 850, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,

							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => true,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"dominosec" =>
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "dominosec", 
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"epi" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "epi", 
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,

							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"hdaa" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "hdaa", 
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"hmac-md5" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "hmac-md5", 
					  
							"john-fpoppercrypt" => 0,
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"ipb2" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "ipb2", 
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"krb4" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "krb4", 
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"krb5" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "krb5", 
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"lm" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "lm", 
					  
							"john-fpoppercrypt" => 614, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"lotus5" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "lotus5", 
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"md4-gen" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "md4-gen", 
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"md5" => 
					array(	"hashcat-format" => "500",
							"cudahashcat+-format" => "500", 
							"john-format" => "md5", 
					  
							"john-fpoppercrypt" => 236000, 
							"hashcat-fpoppercrypt" => 261440,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => true,
							"hashcat-persaltmultiplier" => true,
							"cudahashcat+-persaltmultiplier" => false),
				"md5a" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "md5a", 
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"md5ns" =>
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "md5ns", 
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"mscash" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "mscash", 

							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"mscash2" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "mscash2", 
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"mschapv2" =>
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "mschapv2", 
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"mssql" =>
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "mssql", 
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"mssql05" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "mssql05", 
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"mysql" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "mysql", 
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"mysql-fast" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "mysql-fast", 

					  "john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"mysql-sha1" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "mysql-sha1", 
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"nethalflm" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "nethalflm", 
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"netlm" =>
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "netlm", 
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"netlmv2" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "netlmv2", 
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"netntlm" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "netntlm", 
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"netntlmv2" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "netntlmv2", 
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"nsldap" => 
					array(	"hashcat-format" => "600",
							"cudahashcat+-format" => "", 
							"john-format" => "nsldap", 

					  "john-fpoppercrypt" => 768, 
							"hashcat-fpoppercrypt" => 614,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"nt" => 
					array(	"hashcat-format" => "1000",
							"cudahashcat+-format" => "1000", 
							"john-format" => "nt", 
					  
					  "john-fpoppercrypt" => 1074, 
							"hashcat-fpoppercrypt" => 750,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"openssha" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "openssha", 
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"oracle" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "oracle", 
					  
							"john-fpoppercrypt" => 4145, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"oracle11" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "oracle11", 
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"phpass-md5" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "phpass-md5", 
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"phps" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "phps", 
					  
					  "john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"pix-md5" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "pix-md5", 
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"po" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "po", 
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"raw-md4" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "raw-md4", 
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"raw-md5" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "raw-md5", 
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"raw-sha1" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "raw-sha1",
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"sapb" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "sapb", 
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"sapg" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "sapg", 
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"sha1-gen" => 
					array(	"hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "sha1-gen", 
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				"ssha" => 
					array(	"hashcat-format" => "700",
							"cudahashcat+-format" => "", 
							"john-format" => "ssha", 
					  
							"john-fpoppercrypt" => 990, 
							"hashcat-fpoppercrypt" => 343,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => true,
							"hashcat-persaltmultiplier" => true,
							"cudahashcat+-persaltmultiplier" => false),
				"xsha" => 
					array("hashcat-format" => "",
							"cudahashcat+-format" => "", 
							"john-format" => "xsha", 
					  
							"john-fpoppercrypt" => 0, 
							"hashcat-fpoppercrypt" => 0,
							"cudahashcat+-fpoppercrypt" => 0,
					  
							"john-perhashmultiplier" => false,
							"hashcat-perhashmultiplier" => false,
							"cudahashcat+-perhashmultiplier" => false,

							"john-persaltmultiplier" => false,
							"hashcat-persaltmultiplier" => false,
							"cudahashcat+-persaltmultiplier" => false),
				);

  return $acceptable_formats;
}