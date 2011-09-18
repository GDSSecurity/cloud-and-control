#!/usr/bin/php
<?php

require('genjohnwu-tools.php');

$stamp=strftime("%b%d_%H%M",time());
$tmpdir="tmp_pseudocidal";

$shortopts = "b:c:p:f:a:";
$longopts = array(
		  "hashcat-hashes:",
		  "john-hashes:",
		  "wordlist-inc-filter:",
		  "wordlist-exc-filter:",
		  "resources:",
		  "hashcat:",
		  "cudahashcat+:",
		  "john:");

$args = getopt($shortopts, $longopts);
$acceptable_formats = getAcceptableFormats();

if( empty($args['hashcat-hashes']) || empty($args['john-hashes']) || empty($args['a']) 
	|| empty($args['c']) || empty($args['p']) || empty($args['b']) || empty($args['f'])
	|| empty($acceptable_formats[$args['f']]))
{
	echo "Usage: " . $argv[0] . " \n";
	echo "Required:\n";
	echo "\t -b base_name\n";
	echo "\t -c pot_file\n";
	echo "\t -p phase\n";
	echo "\t -f format\n";
	echo "\t -a app\n";

	echo "\t --hashcat-hashes=hashes_file - list of hashes for hashcat and cudahashcat+\n";
	echo "\t --john-hashes=hashes_file - list of hashes for john the ripper\n";

	echo "Optional:\n";
	echo "\t --resources=[\"intelligent\"/nH] - when 'intelligent', attempts to figure out the resource limit\n";
	echo "\t                                    otherwise MAXIMUM of n hours on a very fast machine.\n";
	echo "\t --wordlist-inc-filter=\"filter\" - any wordlist operations will only be done on lists matching this filter\n";
	echo "\t --wordlist-exc-filter=\"filter \" - any wordlist operations will only be done on lists not matching this filter\n";
	echo "\t --john=\"parameters\" - extra parameters passed to john\n";
	echo "\t --hashcat=\"parameters\" - extra parameters passed to hashcat\n";
	echo "\t --cudahashcat+=\"parameters\" - extra parameters passed to cudahashcat+\n";

	echo "Formats: (J => john-capable, H => hashcat-capable, C+ => cudahashcat+-capable)\n"; $i=0;
	foreach($acceptable_formats as $k => $v)
	{
		$extras = "(";
		if(!empty($v['john-format']) && !empty($v['john-fpoppercrypt'])) $extras .= "J/";
		if(!empty($v['hashcat-format']) && !empty($v['hashcat-fpoppercrypt'])) $extras .= "H/";
		if(!empty($v['cudahashcat+-format'])) $extras .= "C+/";
		$extras = substr($extras, 0, -1);
		if(!empty($extras)) $extras .= ")";

		print "\t" . str_pad($k . " " .$extras, 12);
		if(++$i % 5 == 0)
		print "\n";
	}
	print "\n";
	exit(1);
}

#Arguments ==================================
$name  = $args['b'];
$passwordfile_hashcat = $args['hashcat-hashes'];
$passwordfile_john = $args['john-hashes'];
$johnpotfile = $args['c'];
$phase = $args['p'];
$hashformat = $acceptable_formats[$args['f']];
$app = $args['a']; getAppType();

$wordlist_inc_filter = !empty($args['wordlist-inc-filter']) ? array($args['wordlist-inc-filter']) : array();
$wordlist_exc_filter = !empty($args['wordlist-exc-filter']) ? array($args['wordlist-exc-filter']) : array();
$resource_allocation = !empty($args['resources']) ? $args['resources'] : "intelligent";
$user_john_options = !empty($args['john']) ? $args['john'] : "";
$user_hashcat_options = !empty($args['hashcat']) ? $args['hashcat'] : "";
$user_cudahashcat_plus_options = !empty($args['cudahashcat+']) ? $args['cudahashcat+'] : "";

if($passwordfile_john == "/dev/null")
	$passwordfile_john = "";
if($passwordfile_hashcat == "/dev/null")
	$passwordfile_hashcat = "";

if(!file_exists($johnpotfile))
	die("Could not locate $johnpotfile\n");
if(!empty($passwordfile_hashcat) && !file_exists($passwordfile_hashcat))
	die("Could not locate $passwordfile_hashcat\n");
if(!file_exists($passwordfile_john))
	die("Could not locate $passwordfile_john\n");

if($resource_allocation != "intelligent" && !ereg("[0-9]+H", $resource_allocation))
	die("--resources was not set to an acceptable value.\n");

echo "generating work for $name...\n";

@unlink("createWorkScript");
$f=fopen("createWorkScript","wb");

$jobNum = 1;

//fpops limit for a job before it's split
define("C1XLARGE_FPOPS", 2150000000);

$MAX_FPOPS_PER_JOB = array();
$MAX_FPOPS_PER_JOB['cudahashcat+'] = -1;
$MAX_FPOPS_PER_JOB['cudahashcat+-7zwordlist'] = -1;
$MAX_FPOPS_PER_JOB['john'] = C1XLARGE_FPOPS * 3600;
$MAX_FPOPS_PER_JOB['hashcat'] = 8 * C1XLARGE_FPOPS * 3600;
$MAX_FPOPS_PER_JOB['hashcat-7zwordlist'] = -1;
function getWLRuleMultiplicationFactor($wordlistFlags)
{
	global $app;
	$multiplicationFactor = 1;

	if($app == "john" && JOHN_WL_FORUPPERCASE & $wordlistFlags)  
		$multiplicationFactor += 50;
	if($app == "john" && JOHN_WL_FORLOWERCASE  & $wordlistFlags)
		$multiplicationFactor += 57;
	if(getAppSubtype() == "hashcat" && HASHCAT_WL_238K & $wordlistFlags)
		$multiplicationFactor += 23800;
	if(getAppSubType() == "cudahashcat+" && CUDAHASHCAT_WL_238K & $wordlistFlags)
		$multiplicationFactor += 23800;

	return $multiplicationFactor;
}
$linesInFilecache = array();
function linesInFile($file)
{
	if(isset($linesInFilecache[$file])) return $linesInFilecache[$file];
	if(!file_exists($file)) die("Attempted to get lines in non-existant file: $file\n");
	$lines = exec("cat $file | wc -l");
	$linesInFilecache[$file] = $lines;
	return $lines;
}
$saltCache = array();
function getSaltCount()
{
	global $hashformat, $passwordfile_john, $saltCache;

	if(!empty($saltCache[$passwordfile_john]))
		return $saltCache[$passwordfile_john];

	exec("rm -f /home/tom/amber/build/john-jumbo12-clean/john.pot /home/tom/amber/build/john-jumbo12-clean/john.log");
	exec("/home/tom/amber/build/john-jumbo12-clean/john --format=" . $hashformat['john-format'] . " --wordlist=/dev/null " . $passwordfile_john . " 2>/dev/null",
		$output, $ret);
	if($ret != 0)
		die("john died when trying to get salt values\n");
  
	$passwords = $salts = 0;
	foreach($output as $line)
	{
		list($garbage2, $passwords, $salts) = sscanf($line, "Loaded%[^0-9]%i password hashes with %[^ ] different salts");

		if(!empty($passwords) && !empty($salts))
		{
			if($salts == "no") $salts = 1;
			$saltCache[$passwordfile_john] = array($passwords, $salts);
			return $saltCache[$passwordfile_john];
		}
	}
	die("Never found salt count\n");
}
function estimateBruteForceTimeInHours($params)
{
	global $app, $MAX_FPOPS_PER_JOB;
	$fpops = 0;
	$i = $params['max-length'];
	while($i > $params['min-length'])
	{
		$fpops += getFactorOfApp() * pow(strlen($params['charset']), $i);
		$i--;
	}

	if($app == "hashcat")
		return $fpops / C1XLARGE_FPOPS / 3600 / 8;//Assume 8 cores
	else
		return $fpops / C1XLARGE_FPOPS / 3600;
}
function getFactorOfApp()
{
	global $app, $hashformat, $passwordfile_john;


	if(!empty($hashformat[$app . '-perhashmultiplier']) || !empty($hashformat[$app . '-persaltmultiplier']))
	{
		list($passwords, $salts) = getSaltCount();
		if(!empty($hashformat[$app . '-perhashmultiplier']))
			$hashesFactor = $passwords;
		else 
			$hashesFactor = $salts;
	}
	else
		$hashesFactor = 1;

	if(empty($hashformat[$app . '-fpoppercrypt']) && getAppSubType() != "cudahashcat+")
		die("Tried computing resource estimates for the hashformat in $app, but format not supported.\n");

	$ret = $hashesFactor * $hashformat[$app . '-fpoppercrypt'];
	return $ret;
}
function wordlistShouldBeSplit($file, $params)
{
	global $app, $MAX_FPOPS_PER_JOB;

	if($app == "cudahashcat+")
		return false;
	//Test file ending:
	if(substr($file, strlen($file) - strlen("7z")) == "7z")
		return false;
	if(substr($file, strlen($file) - strlen("xz")) == "xz")
		return false;
	if($MAX_FPOPS_PER_JOB[$app] < 0)
		return false;

	$partitionsize = $MAX_FPOPS_PER_JOB[$app];
	$fpopsPerLine = getFactorOfApp() * getWLRuleMultiplicationFactor($params['wordlist-flags']);
	$fpopsinfile = linesInFile($file) * $fpopsPerLine;

	if($fpopsinfile >= $partitionsize)
		return true;

	return false;
}
function splitWordlist($file, $filename, $func, $params)
{
	global $tmpdir, $stamp, $f, $app, $MAX_FPOPS_PER_JOB, $hashformat;

	$fpopsPerLine = getFactorOfApp() * getWLRuleMultiplicationFactor($params['wordlist-flags']);
	$linesPerPartition = ceil($MAX_FPOPS_PER_JOB[$app] / $fpopsPerLine);
	$lines = linesInFile($file);

	$random = rand();//Needed so we can have two english_x_y_0 files
	$i=0;
	$throwaway = array();
	if($lines / $linesPerPartition > 100)
		echo "\nSplitting $file into " . ceil($lines / $linesPerPartition) . " parts...\n";
	for($start=0; $start < $lines; $start += $linesPerPartition)
	{
		$ret = -1;
		$segmentname = sprintf("%s_%d_%d_%d", $filename, $random, (($start + $linesPerPartition > $lines) ? ($lines - $start) : $linesPerPartition), $i);
		exec("tail -n +$start $file 2>/dev/null | head -n $linesPerPartition > {$tmpdir}/${segmentname}", $throwaway, $ret);
		if($ret != 0) {
			echo "ERROR: tail | head concatting failed with $ret\n";
			exit;
		}
	  fwrite($f, "# tail -n+$start $file | head -n $linesPerPartition > {$tmpdir}/${segmentname}\n");
	  $func("{$tmpdir}/${segmentname}", $params);
	  fwrite($f, "rm {$tmpdir}/${segmentname}\n");
	  $i++;
	}
}
function allowWordlistForPhase($wordlist, $phase)
{
	if($phase <= 2) return true;
  
	$phase3 = array('nsfw_ascii_art.txt');
	$phase4 = array_merge($phase3, array());

	if($phase == 3)
		foreach($phase3 as $v)
			if(stristr($wordlist, $match) !== false)
				return false;

	if($phase == 4)
		foreach($phase4 as $v)
			if(stristr($wordlist, $match) !== false)
				return false;

  return true;
}

define('JOHN_HEADER', 1);
define('JOHN_WORDLISTRULES', 2);
define('JOHN_SINGLERULES', 4);
define('JOHN_INCREMENTAL_ALL_3', 8);
define('JOHN_INCREMENTAL_ALL_4', 1024);
define('JOHN_INCREMENTAL_ALL_5', 2048);
define('JOHN_INCREMENTAL_ALL_6', 4096);
define('JOHN_INCREMENTAL_ALL_7', 8192);
define('JOHN_INCREMENTAL_DIGITS_6', 16);
define('JOHN_WL_DEFAULT', 32);
define('JOHN_WL_FORUPPERCASE', 64);
define('JOHN_WL_FORLOWERCASE', 128);
define('JOHN_S_DEFAULT', 256);
define('HASHCAT_WL_238K', 512);
define('CUDAHASHCAT_WL_238K', 1024);
function makeConfigFile($flags)
{
	global $tmpdir, $name, $jobNum, $stamp;

	$configfile_stamped = sprintf("john-conf_%s_%d_%s", $name, $jobNum, $stamp);
	$cfpath = $tmpdir . "/" . $configfile_stamped;
  
	if(JOHN_HEADER & $flags)
		file_put_contents($cfpath, file_get_contents("templates/john.conf.header"), FILE_APPEND);
	if(JOHN_INCREMENTAL_ALL_3 & $flags)
		file_put_contents($cfpath, file_get_contents("templates/john.conf.incremental.all3"), FILE_APPEND);
	if(JOHN_INCREMENTAL_ALL_4 & $flags)
		file_put_contents($cfpath, file_get_contents("templates/john.conf.incremental.all4"), FILE_APPEND);
	if(JOHN_INCREMENTAL_ALL_5 & $flags)
		file_put_contents($cfpath, file_get_contents("templates/john.conf.incremental.all5"), FILE_APPEND);
	if(JOHN_INCREMENTAL_ALL_6 & $flags)
		file_put_contents($cfpath, file_get_contents("templates/john.conf.incremental.all6"), FILE_APPEND);
	if(JOHN_INCREMENTAL_ALL_7 & $flags)
		file_put_contents($cfpath, file_get_contents("templates/john.conf.incremental.all7"), FILE_APPEND);
	if(JOHN_INCREMENTAL_DIGITS_6 & $flags)
		file_put_contents($cfpath, file_get_contents("templates/john.conf.incremental.digits6"), FILE_APPEND);

	//Wordlist Headers
	if(JOHN_WORDLISTRULES & $flags)
		file_put_contents($cfpath, "[List.Rules:Wordlist]\n", FILE_APPEND);
	else if(JOHN_SINGLERULES & $flags)
		file_put_contents($cfpath, "[List.Rules:Single]\n", FILE_APPEND);
  
	//Wordlist Rules
	if(JOHN_WL_DEFAULT & $flags)
		file_put_contents($cfpath, file_get_contents("templates/john.conf.rules.wordlistdefault"), FILE_APPEND);
	if(JOHN_WL_FORUPPERCASE & $flags)
		file_put_contents($cfpath, file_get_contents("templates/john.conf.rules.wordlistupper"), FILE_APPEND);
	if(JOHN_WL_FORLOWERCASE & $flags)
		file_put_contents($cfpath, file_get_contents("templates/john.conf.rules.wordlistlower"), FILE_APPEND);
	else if(JOHN_S_DEFAULT & $flags)
		file_put_contents($cfpath, file_get_contents("templates/john.conf.rules.singledefault"), FILE_APPEND);
	
	movefile($configfile_stamped);
	return $configfile_stamped;
}
function makeRulesFile($flags)
{
	global $tmpdir, $name, $jobNum, $stamp, $app;

	$rulesfile_stamped = sprintf("wordlist-rules_%s_%d_%s", $name, $jobNum, $stamp);
	$rlpath = $tmpdir . "/" . $rulesfile_stamped;

	if(HASHCAT_WL_238K & $flags || CUDAHASHCAT_WL_238K & $flags)
		file_put_contents($rlpath, file_get_contents("templates/hashcat.rules.d3ad0ne_23.8K.rule"), FILE_APPEND);

	movefile($rulesfile_stamped);
	return $rulesfile_stamped;
}
function copyfile($file, $middle)
{
	global $name, $stamp, $tmpdir, $f;
	$file_stamped = sprintf("%s_%s_%s_%s", $name, basename($file), $middle, $stamp);

	fwrite($f, "cp $file $tmpdir/$file_stamped \n");
	movefile($file_stamped);
	return $file_stamped;
}
function movefile($file)
{
	global $stamp, $tmpdir, $f;
	fwrite($f, "mv $tmpdir/$file `bin/dir_hier_path $file`\n");
	fwrite($f, "chmod 644 `bin/dir_hier_path $file`\n");
}
function subtractAndCopyPotfileForHashcat($passwordfile, $johnpotfile)
{
	global $name, $stamp, $tmpdir, $app, $f, $passwordfile_hashcat;
	$passwordfile_stamped = sprintf("%s_%s_%s_%s_%s", $name, basename($passwordfile), "hashcat", "hashfile", $stamp);

	if(empty($passwordfile_hashcat)) return "";

	fwrite($f, "cut -f 1 --delim=: $johnpotfile | sed 's/^\\$\([A-Z]\+\)\\$//' | sort | uniq > $tmpdir/tempcutfile \n");
	fwrite($f, "sort $passwordfile | uniq > $tmpdir/tempsorted \n");
	fwrite($f, "comm -23 $tmpdir/tempsorted $tmpdir/tempcutfile > $tmpdir/$passwordfile_stamped \n");
	movefile($passwordfile_stamped);
	fwrite($f, "rm $tmpdir/tempsorted $tmpdir/tempcutfile \n");
	return $passwordfile_stamped;
}
function writeJobHeader()
{
	global $passwordfile_john, $passwordfile_hashcat, $johnpotfile, $passwordfile_john_stamped, $passwordfile_hashcat_stamped, $johnpotfile_stamped, $f;
  
	$passwordfile_john_stamped = copyfile($passwordfile_john, "john_hashfile");
	$passwordfile_hashcat_stamped = subtractAndCopyPotfileForHashcat($passwordfile_hashcat, $johnpotfile);
	$johnpotfile_stamped = copyfile($johnpotfile, "potfile");

	fwrite($f, "#########\n\n");
}
define(ONESECOND_ON_A_FAST_MACHINE, 642666666666);
function createParameters($app, $params, &$fpops)
{
	global $rulesfile_stamped, $configfile_stamped, $wordlist_stamped, $user_john_options, $user_hashcat_options, $user_cudahashcat_plus_options, $wordlistFlags, $resource_allocation, $hashformat;
	$d = "";

	$fpops = getFactorOfApp();

	if($app == "john")
	{
		$configFlags = JOHN_HEADER;
		$d.= " --format=" . $hashformat[getAppSubType() . '-format'];
		$d.= " " . $user_john_options . " ";
		if(isset($params['wordlist']))
		{
			$d.= " --wordlist=<<WORDLIST>> ";
			if(isset($params['rules']))
			{
				$d.= " --rules ";
				$configFlags |= JOHN_WORDLISTRULES;
				$configFlags |= $params['wordlist-flags'];
				$fpops *= getWLRuleMultiplicationFactor($params['wordlist-flags']);
			}
		$fpops *= linesInFile($params['wordlist']);
		}
		else if(isset($params['bruteforce']))
		{
			$fpops = 0;
			$i = $params['max-length'];
			while($i > $params['min-length'])
			{
				$fpops += getFactorOfApp() * pow(strlen($params['charset']), $i);
				$i--;
			}

			if(stristr($params['charset'], "a") === false) 
			{
				$d.= " --incremental=digits ";
				$configFlags |= JOHN_INCREMENTAL_DIGITS_6;
			}
			else
			{
				$d.= " --incremental=all ";
				switch($params['max-length'])
				{
					case 4:
						$configFlags |= JOHN_INCREMENTAL_ALL_4;
						break;
					case 5:
						$configFlags |= JOHN_INCREMENTAL_ALL_5;
						break;
					case 6:
						$configFlags |= JOHN_INCREMENTAL_ALL_6;
						break;
					case 7:
						$configFlags |= JOHN_INCREMENTAL_ALL_7;
						break;
					default:
						$configFlags |= JOHN_INCREMENTAL_ALL_3;
						break;
				}
			}
		}
		$configfile_stamped = makeConfigFile($configFlags);
	}
	else if(getAppType() == "hashcat")
	{
		if(getAppSubType() == "hashcat")
		{
			$d.= " -m " . $hashformat[getAppSubType() . '-format'];
			$d.= " " . $user_hashcat_options . " ";
		}
		else if(getAppSubType() == "cudahashcat+")
		{
			$d.= " -m " . $hashformat[getAppSubType() . '-format'];
			$d.= " " . $user_cudahashcat_plus_options . " ";
		}
		else
			die("unrecognized app\n");

		if($params['bruteforce'] && $app != "hashcat")
		{
			echo "ERROR: Tried to use cudahashcat+ with brute-force, application doesn't support that\n";
			exit;
		}
		else if($params['bruteforce'])
		{
			$d.= " -a 3 ";
			if(isset($params['charset']))
				$d.= " --bf-cs-buf=\"".$params['charset']."\" ";
			if(isset($params['min-length']))
				$d.= " --bf-pw-min=".$params['min-length']." ";
			if(isset($params['max-length']))
				$d.= " --bf-pw-max=".$params['max-length']." ";

			$fpops = 0;
			$i = $params['max-length'];
			while($i > $params['min-length'])
			{
				$fpops += getFactorOfApp() * pow(strlen($params['charset']), $i);
				$i--;	
			}
		}
		$d.= " passwordlist "; //Matches the template file
		if(!$params['bruteforce'] && $params['wordlist'])
		{
			$d.= " wordlist ";
			$fpops *= linesInFile($params['wordlist']);

			if(isset($params['rules']))
			{
				$d.= " --rules-file=wordlist-rules";
				$fpops *= getWLRuleMultiplicationFactor($params['wordlist-flags']);
				$rulesfile_stamped = makeRulesFile($params['wordlist-flags']);
			}
		}
	}
	else
		die("WTF?");
	
	if($resource_allocation != "intelligent")
	{
		$hours = str_replace("H", "", $resource_allocation);
		$tmpfpops = $hours * 60 * 60 * ONESECOND_ON_A_FAST_MACHINE / 10;
		$fpops = max($fpops, $tmpfpops);
	}
	else if(getAppSubType() == "cudahashcat+")
		die("Tried to use intelligent resource estimate on cudahashcat+ - not really possible.\n");

	return $d;
}
function writeJob($params)
{
	global $f, $app, $name, $tmpdir, $jobNum, $stamp, $cw, $wuname, $MAX_FPOPS_PER_JOB; 
	global $johnpotfile_stamped, $passwordfile_john_stamped, $passwordfile_hashcat_stamped, $configfile_stamped, $rulesfile_stamped; 
	global $wordlist_stamped;
	$wuname = sprintf("%s_%s_%d_%s", $name, $app, $jobNum, $stamp);

	// Parameters ==================================================
	$fpops = 0;
	$createworkparams = "";
	$app_params = createParameters($app, $params, $fpops);
	$createworkparams = "\t --command_line \"". str_replace('"', '\"', $app_params)  ."\" \\\n";
	$fpops_bound = $fpops * 10;
	if(getAppSubType() == "cudahashcat+")
		$fpops_bound += 15 * ONESECOND_ON_A_FAST_MACHINE; //Enough time to start up in 15 seconds on a superfast machine
	else
		$fpops_bound += 3 * ONESECOND_ON_A_FAST_MACHINE; //3 Seconds Start up time


	// Script ======================================================
	$cw="";

	$cw.="bin/create_work \\\n";
	$cw.="\t --appname $app \\\n";
	$cw.="\t --wu_name $wuname \\\n";
	if($params['rules'] && $params['wordlist'] && getAppType() == "hashcat")
		$cw.="\t --wu_template templates/{$app}_wu_wordlist_rules.xml \\\n";
	else if($params['wordlist'])
		$cw.="\t --wu_template templates/{$app}_wu_wordlist.xml \\\n";
	else
		$cw.="\t --wu_template templates/{$app}_wu_no-wordlist.xml \\\n";
	$cw.="\t --result_template templates/{$app}_result.xml \\\n";
	$cw.="\t --rsc_fpops_est " . ( $fpops ) . " \\\n";
	$cw.="\t --rsc_fpops_bound " . ( $fpops_bound ) . " \\\n";
	$cw.="\t --rsc_memory_bound 131072000 \\\n";//125MB
	$cw.=$createworkparams;
  
	if($app == "john")
		$cw.="\t $passwordfile_john_stamped \\\n";
	else if(getAppType() == "hashcat")
		$cw.="\t $passwordfile_hashcat_stamped \\\n";
	else die("WTF?");

	if($app == "john")
		$cw.="\t $configfile_stamped \\\n";

	if($params['wordlist'])
		$cw.="\t $wordlist_stamped \\\n";

	if($app == "john")
		$cw.="\t $johnpotfile_stamped \n";
	else if($params['rules'] && $params['wordlist'] && getAppType() == "hashcat")
		$cw.="\t $rulesfile_stamped \n";
	else if(getAppType() == "hashcat")
		$cw.="\t \n";
	else die("WTF?");

	if($MAX_FPOPS_PER_JOB[$app] > 0)
	{
		$maybetime = $fpops / $MAX_FPOPS_PER_JOB[$app];
		$cw.="# Estimate: $maybetime hours on a c1.xlarge\n";
	}

	fwrite($f,$cw);
	$jobNum++;
	echo ".";
	if($jobNum % 10 == 0)
		echo $jobNum . "\n";
}

writeJobHeader();
echo "Working on Phase $phase\n";
switch ($phase)
{
	case -1:
		$func = create_function('$filename, $params','echo $filename . "\n";');
		$params = array();

		echo "Lowercase Wordlists:\n";
		forEachLowercaseWordlist($func, $params);

		echo "\n\nUppercase Wordlists:\n";
		forEachUppercaseWordlist($func, $params);

		//echo "\n\nMarkov lvl123\n";
		//forEachMarkov($func, "lvl123", $params);

		break;
	case -2:
		$params = array();
		$params["rules"] = 1;
		$params["wordlist-flags"] = JOHN_WL_FORLOWERCASE | CUDAHASHCAT_WL_238K;
	
		$func = create_function('$filename, $params','
			global $wordlist_stamped, $app;
			$wordlist_stamped = copyfile($filename, "wl");
			$params["wordlist"] = $filename;
			writeJob($params);
			');
		forEachLowercaseWordlist($func, $params, array("english"));

		break;
	case -3:
		$params = array();
		$func = create_function('$filename, $params','
			global $wordlist_stamped, $app;
			$wordlist_stamped = copyfile($filename, "wl");
			$params["wordlist"] = $filename;
			writeJob($params);
			');
		forEachSubFile($func, $params, "build/wordlists/categorized", array("english"));

		break;
	case -4:
		// Incremental-3-All
		$params = array();
		$params['bruteforce'] = 1;
		
		$params['charset'] = "abcdefghizjlmnopqrstuvwxyzABCDEFGHIJLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-=_+,./<>?;\':\\\\\"[]\{}|\`~";
		//$params['charset'] = "abcdefghizjlmnopqrstuvwxyzABCDEFGHIJLMNOPQRSTUVWXYZ0123456789";
		
		$params['min-length'] = 1;
		$params['max-length'] = 3;
		while(estimateBruteForceTimeInHours($params) < 1)
			$params['max-length']++;
		$params['max-length']--;
		
		writeJob($params);
	
		break;
	case -5:
		$params = array();
		$func = create_function('$filename, $params','
			global $wordlist_stamped, $app;
			$wordlist_stamped = copyfile($filename, "wl");
			$params["wordlist"] = $filename;
			writeJob($params);
			');
		forEachFile($func, $params, "build/markovlists/lvl185-compressed/", array("english"));

		break;
	case -10:
		$params = array();
		$func = create_function('$filename, $params','
		global $wordlist_stamped, $app;
		$wordlist_stamped = copyfile($filename, "wl");
		$params["wordlist"] = $filename;
		writeJob($params);
		');
		forEachSubFile($func, $params, "build/wordlists/categorized", array("special"));

		$params = array();
		$params['bruteforce'] = 1;
		$params['charset'] = "abcdefghizjlmnopqrstuvwxyz0123456789";
		$params['min-length'] = 1;
		$params['max-length'] = 3;
		writeJob($params);
		
		break;

	//-----------------------------------------------------------------
	case 2:
		echo "Run Phases 2.1 (wordlists) and 2.2 (incremental)\n";
		break;
	case 2.1:
		$params = array();
		$func = create_function('$filename, $params','
			global $wordlist_stamped, $app;
			$wordlist_stamped = copyfile($filename, "wl");
			$params["wordlist"] = $filename;
			writeJob($params);
			');
		forEachWordlist($func, $params, $wordlist_inc_filter, $wordlist_exc_filter);
		break;
	case 2.2:
		// Incremental-3-All
		$params = array();
		$params['bruteforce'] = 1;
		$params['charset'] = "abcdefghizjlmnopqrstuvwxyzABCDEFGHIJLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-=_+,./<>?;':\\\\\"[]\{}|`~";
		
		$params['min-length'] = 1;
		$params['max-length'] = 3;
		while(estimateBruteForceTimeInHours($params) < 1)
			$params['max-length']++;
		$params['max-length']--;
		
		writeJob($params);
	
		if($params['max-length'] < 6)
		{
			// Incremental-6-Digits
			$params = array();
			$params['bruteforce'] = 1;
			$params['charset'] = "0123456789";
			$params['min-length'] = 1;
			$params['max-length'] = 6;
			writeJob($params);
		}

		break;

	case 3:
		echo "Run phases 3.1 (lowercase-rules), 3.2 (uppercase-rules), and 3.3 (markov-123)\n";
		break;
	case 3.1:
		$params = array();
		$params["rules"] = 1;
		$params["wordlist-flags"] = JOHN_WL_FORLOWERCASE | HASHCAT_WL_238K | CUDAHASHCAT_WL_238K;
		$func = create_function('$filename, $params','
			global $wordlist_stamped, $app;
			if(!allowWordlistForPhase($filename, 3)) return;
			$wordlist_stamped = copyfile($filename, "wl");
			$params["wordlist"] = $filename;
			writeJob($params);
			');
		forEachLowercaseWordlist($func, $params, $wordlist_inc_filter, $wordlist_exc_filter);
		break;
	case 3.2:
		$params = array();
		$params["rules"] = 1;
		$params["wordlist-flags"] = JOHN_WL_FORUPPERCASE | HASHCAT_WL_238K | CUDAHASHCAT_WL_238K;
		$func = create_function('$filename, $params','
			global $wordlist_stamped, $app;
			if(!allowWordlistForPhase($filename, 3)) return;
			$wordlist_stamped = copyfile($filename, "wl");
			$params["wordlist"] = $filename;
			writeJob($params);
			');
		forEachUppercaseWordlist($func, $params, $wordlist_inc_filter, $wordlist_exc_filter);
		break;
	case 3.3:
		$params = array();
		$func = create_function('$filename, $params','
			global $wordlist_stamped, $app;
			$wordlist_stamped = copyfile($filename, "wl");
			$params["wordlist"] = $filename;
			writeJob($params);
			');
		forEachMarkov($func, $params, "lvl123", $wordlist_inc_filter, $wordlist_exc_filter);

		break;

	case 4:
		echo "Run phases 4.1 (markov-123-rules), 4.2 (markov-185), 4.3 (rockyou), 4.4 (nsfw-ascii), 4.5 (allcategorizedwordlists)\n";
		break;
	case 4.1:
		$params = array();
		$params["rules"] = 1;
		$params["wordlist-flags"] = JOHN_WL_FORLOWERCASE | HASHCAT_WL_238K | CUDAHASHCAT_WL_238K;
		$func = create_function('$filename, $params','
			global $wordlist_stamped, $app;
			$wordlist_stamped = copyfile($filename, "wl");
			$params["wordlist"] = $filename;
			writeJob($params);
			');
		forEachFile($func, $params, "build/markovlists/lvl123/", $wordlist_inc_filter, $wordlist_exc_filter);
		break;
	case 4.2:
		if(!getAppZip())
			die("lvl185 must be used with a 7z app\n");

		$params = array();
		$func = create_function('$filename, $params','
			global $wordlist_stamped, $app;
			$wordlist_stamped = copyfile($filename, "wl");
			$params["wordlist"] = $filename;
			writeJob($params);
			');
		forEachFile($func, $params, "build/markovlists/lvl185-compressed/", $wordlist_inc_filter, $wordlist_exc_filter);
		break;
	case 4.3:
		$params = array();
		$params['wordlist'] = 'build/wordlists/extralarge/rockyou-uniqed.txt';
		if(getAppZip()) $params['wordlist'] .= ".xz";
		$func = create_function('$filename, $params','
			global $wordlist_stamped, $app;
			$wordlist_stamped = copyfile($filename, "wl");
			$params["wordlist"] = $filename;
			writeJob($params);
			');

		if(wordlistShouldBeSplit($params['wordlist'], $params))
			splitWordlist($params['wordlist'], basename($params['wordlist']), $func, $params);
		else
			$func($params['wordlist'], $params);

		break;
	case 4.4:
		$params = array();
		$params['wordlist'] = 'build/wordlists/extralarge/nsfw_ascii_art.txt';
		if(getAppZip()) $params['wordlist'] .= ".xz";
		$func = create_function('$filename, $params','
			global $wordlist_stamped, $app;
			$wordlist_stamped = copyfile($filename, "wl");
			$params["wordlist"] = $filename;
			writeJob($params);
			');

		if(wordlistShouldBeSplit($params['wordlist'], $params))
			splitWordlist($params['wordlist'], basename($params['wordlist']), $func, $params);
		else
			$func($params['wordlist'], $params);

		break;
	case 4.5:
		$params = array();
		$params['wordlist'] = 'build/wordlists/extralarge/allcategorizedwordlists.txt';
		if(getAppZip()) $params['wordlist'] .= ".xz";
		$func = create_function('$filename, $params','
			global $wordlist_stamped, $app;
			$wordlist_stamped = copyfile($filename, "wl");
			$params["wordlist"] = $filename;
			writeJob($params);
			');

		if(wordlistShouldBeSplit($params['wordlist'], $params))
			splitWordlist($params['wordlist'], basename($params['wordlist']), $func, $params);
		else
			$func($params['wordlist'], $params);

		break;
	case 5:
		echo "Run phases 5.1 (markov-185-rules), 5.2 (rockyou-rules), 5.3 (allcategorizedwordlists-rules), 5.4 (nsfw-ascii-rules)\n";
		break;
	case 5.1:
		if($app != "cudahashcat+-7zwordlist")
			die("You really only want to run this phase with cudahashcat+-7zwordlist.\n");

		$params = array();
		$params["rules"] = 1;
		$params["wordlist-flags"] = CUDAHASHCAT_WL_238K;
		$func = create_function('$filename, $params','
			global $wordlist_stamped, $app;
			$wordlist_stamped = copyfile($filename, "wl");
			$params["wordlist"] = $filename;
			writeJob($params);
			');
		forEachFile($func, $params, "build/markovlists/lvl185-compressed/", $wordlist_inc_filter, $wordlist_exc_filter);
		break;
	case 5.2:
		$params = array();
		$params["rules"] = 1;
		$params['wordlist'] = 'build/wordlists/extralarge/rockyou-uniqed.txt';
		if(getAppZip()) $params['wordlist'] .= ".xz";
		$params["wordlist-flags"] = JOHN_WL_FORLOWERCASE | HASHCAT_WL_238K | CUDAHASHCAT_WL_238K;
		$func = create_function('$filename, $params','
			global $wordlist_stamped, $app;
			$wordlist_stamped = copyfile($filename, "wl");
			$params["wordlist"] = $filename;
			writeJob($params);
			');

		if(wordlistShouldBeSplit($params['wordlist'], $params))
			splitWordlist($params['wordlist'], basename($params['wordlist']), $func, $params);
		else
			$func($params['wordlist'], $params);
		break;
	case 5.3:
		$params = array();
		$params["rules"] = 1;
		$params['wordlist'] = 'build/wordlists/extralarge/allcategorizedwordlists.txt';
		if(getAppZip()) $params['wordlist'] .= ".xz";
		$params["wordlist-flags"] = JOHN_WL_FORLOWERCASE | HASHCAT_WL_238K | CUDAHASHCAT_WL_238K;
		$func = create_function('$filename, $params','
			global $wordlist_stamped, $app;
			$wordlist_stamped = copyfile($filename, "wl");
			$params["wordlist"] = $filename;
			writeJob($params);
			');

		if(wordlistShouldBeSplit($params['wordlist'], $params))
			splitWordlist($params['wordlist'], basename($params['wordlist']), $func, $params);
		else
			$func($params['wordlist'], $params);
		break;
	case 5.4:
		$params = array();
		$params["rules"] = 1;
		$params['wordlist'] = 'build/wordlists/extralarge/nsfw_ascii_art.txt';
		if(getAppZip()) $params['wordlist'] .= ".xz";
		$params["wordlist-flags"] = JOHN_WL_FORLOWERCASE | HASHCAT_WL_238K | CUDAHASHCAT_WL_238K;
		$func = create_function('$filename, $params','
			global $wordlist_stamped, $app;
			$wordlist_stamped = copyfile($filename, "wl");
			$params["wordlist"] = $filename;
			writeJob($params);
			');

		if(wordlistShouldBeSplit($params['wordlist'], $params))
			splitWordlist($params['wordlist'], basename($params['wordlist']), $func, $params);
		else
			$func($params['wordlist'], $params);
		break;
	default:
		echo "This phase is not implemented.\n";
		break;
  }

fwrite($f,"rm createWorkScript\n");
//fwrite($f,"chown -R apache download/\n");
fclose($f);

echo "\ndone, created ".($jobNum - 1)." jobs\n";

?>
