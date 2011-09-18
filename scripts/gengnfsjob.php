#!/usr/bin/php
<?php

function hasa5($l) { return stripos(strtolower(trim($l)), 'a5') === 0; }
function parseFile($filename)
{
	$contents = explode("\n", file_get_contents($filename));
	$polyfile['n'] = valueofline('n', $contents);
	$polyfile['digits'] = strlen($polyfile['n']);
	$polyfile['digs'] = strlen($polyfile['n']) / .72;
	$polyfile['skew'] = valueofline('skew', $contents);
	if(count(array_filter($contents, "hasa5")) > 0)
		$polyfile['c5'] = valueofline('a5', $contents);
	$polyfile['c4'] = valueofline('a4', $contents);
	$polyfile['c3'] = valueofline('a3', $contents);
	$polyfile['c2'] = valueofline('a2', $contents);
	$polyfile['c1'] = valueofline('a1', $contents);
	$polyfile['c0'] = valueofline('a0', $contents);
	$polyfile['y1'] = valueofline('r1', $contents);
	$polyfile['y0'] = valueofline('r0', $contents);

	echo "Read a " . $polyfile['digits'] . " digit number from " . $filename . "\n";

	return $polyfile;
}

function getChosenParameters($polyfile)
{
	global $parameters;	
	global $indexes;

	for($i = 0; $i < count($parameters); $i++)
	{
		if(abs($parameters[$i][$indexes['digits']] - $polyfile['digits']) <
		   abs($parameters[$i-1][$indexes['digits']] - $polyfile['digits']))
		$chosen = $parameters[$i];
	}

	if($polyfile['digs'] >= 160)
	{
	  $chosen[$indexes['rlim']] = $chosen[$indexes['alim']] = 
		floor(0.07 * pow(10,($polyfile['digs'] / 60.0)) + 0.5) * 100000;
	  $chosen[$indexes['lpbr']] = $chosen[$indexes['lpba']] = 
		floor(21 + $polyfile['digs'] / 25.0);
	  $chosen[$indexes['mfbr']] = $chosen[$indexes['mfba']] = 
		2 * $chosen[$indexes['lpbr']] - ($polyfile['digs'] < 190 ? 1 : 0);
	  $chosen[$indexes['rlambda']] = $chosen[$indexes['alambda']] = 
		$polyfile['digs'] < 200 ? 2.5 : 2.6;
	}

	echo "Using the default parameters for a number with " . $chosen[$indexes['digits']] . " digits.\n";

	return $chosen;
}

function populateObject(&$number, $chosen)
{
	global $indexes;

	$number['rlim'] = $chosen[$indexes['rlim']];
	$number['alim'] = $chosen[$indexes['alim']];
	$number['lpbr'] = $chosen[$indexes['lpbr']];
	$number['lpba'] = $chosen[$indexes['lpba']];
	$number['mfbr'] = $chosen[$indexes['mfbr']];
	$number['mfba'] = $chosen[$indexes['mfba']];
	$number['rlambda'] = $chosen[$indexes['rlambda']];
	$number['alambda'] = $chosen[$indexes['alambda']];
	$number['qstart'] = floor(($chosen[$indexes['lss']] > 0 ? 
				 $chosen[$indexes['rlim']] : $chosen[$indexes['alim']]) / 2);

	if($number['alim'] > $number['qstart'])
		$number['alim'] = $number['qstart'] - 1;
	

	echo "The best Q Starting value is " . $number['qstart'] . "\n";
}

function getMinRels($number, $chosen)
{
	global $paramaters;	global $indexes;
  
	$estimatedMinRelations = 0;
	if ($chosen[$indexes['lpbr']] == 25)
		$estimatedMinRelations = 38000.0 * ($number['digits'] - 47);
	else if ($chosen[$indexes['lpbr']] == 26)
		$estimatedMinRelations = 91000.0 * ($number['digits'] - 55);
	else if ($chosen[$indexes['lpbr']] == 27)
		$estimatedMinRelations = 150000.0 * ($number['digits'] - 61);
	else if ($chosen[$indexes['lpbr']] == 28)
		$estimatedMinRelations = 440000.0 * ($number['digits'] - 89);
	else
		$estimatedMinRelations = pow(10.0,  ($number['digits'] / 41.0 + 4.0));

	$estimatedMinRelations /= 1000000;
	$estimatedMinRelations = round($estimatedMinRelations, 1);
  
	echo "Estimated Relations Needed: ".$estimatedMinRelations."M \n";
	return $estimatedMinRelations;
}

function printSiever($number)
{
	  $i = 0;
	  if	 ($number['digits'] < 95) $i = 1;
	  else if($number['digits'] < 110) $i = 2;
	  else if($number['digits'] < 140) $i = 3;
	  else if($number['digits'] < 158) $i = 4;
	  else if($number['digits'] < 185) $i = 5;
	  else if($number['digits'] < 999) $i = 6;
	  else $i = "???";
	  
	  echo "You should use the gnfs-lasieve4I1" . $i . "e siever.  (That's " . $i . ")\n";
}

function writeJobFile($polyfile, $filename)
{
	$f=fopen($filename,"wb");
	fwrite($f, "n: " . $polyfile['n'] . "\n");
	if($polyfile['c5'])
		fwrite($f, "c5: " . $polyfile['c5'] . "\n");
	fwrite($f, "c4: " . $polyfile['c4'] . "\n");
	fwrite($f, "c3: " . $polyfile['c3'] . "\n");
	fwrite($f, "c2: " . $polyfile['c2'] . "\n");
	fwrite($f, "c1: " . $polyfile['c1'] . "\n");
	fwrite($f, "c0: " . $polyfile['c0'] . "\n");
	fwrite($f, "Y1: " . $polyfile['y1'] . "\n");
	fwrite($f, "Y0: " . $polyfile['y0'] . "\n");
	fwrite($f, "skew: " . $polyfile['skew'] . "\n");
	fwrite($f, "rlim: " . $polyfile['rlim'] . "\n");
	fwrite($f, "alim: " . $polyfile['alim'] . "\n");
	fwrite($f, "lpbr: " . $polyfile['lpbr'] . "\n");
	fwrite($f, "lpba: " . $polyfile['lpba'] . "\n");
	fwrite($f, "mfbr: " . $polyfile['mfbr'] . "\n");
	fwrite($f, "mfba: " . $polyfile['mfba'] . "\n");
	fwrite($f, "rlambda: " . $polyfile['rlambda'] . "\n");
	fwrite($f, "alambda: " . $polyfile['alambda'] . "\n");
	fwrite($f, "q0: __START__"  . "\n");
	fwrite($f, "qintsize: __COUNT__" . "\n");
	fclose($f);
}



$indexes['type'] = 0;
$indexes['digits'] = 1;
$indexes['deg'] = 2;
$indexes['maxs1'] = 3;
$indexes['maxskew'] = 4;
$indexes['goodScore'] = 5;
$indexes['efrac'] = 6;
$indexes['j0'] = 7;
$indexes['j1'] = 8;
$indexes['eStepSize'] = 9;
$indexes['maxTime'] = 10;
$indexes['rlim'] = 11;
$indexes['alim'] = 12;
$indexes['lpbr'] = 13;
$indexes['lpba'] = 14;
$indexes['mfbr'] = 15;
$indexes['mfba'] = 16;
$indexes['rlambda'] = 17;
$indexes['alambda'] = 18;
$indexes['qintsize'] = 19;
$indexes['A'] = 20;
$indexes['B'] = 21;

//From def-par.txt
$parameters[] = explode(",", "gnfs,70,4,51,1500,4.0e-2,0.30,200,12,10000,200,300000,350000,24,24,34,34,1.7,1.7,8000,2000000,200");
$parameters[] = explode(",", "gnfs,75,4,52,1500,1.2e-2,0.30,200,12,10000,300,350000,400000,24,24,34,34,1.7,1.7,10000,2000000,200");
$parameters[] = explode(",", "gnfs,80,4,52,1500,5.0e-3,0.30,220,15,10000,400,350000,500000,24,24,37,37,1.7,1.7,10000,2000000,200");
$parameters[] = explode(",", "gnfs,85,4,56,1500,1.0e-3,0.30,200,15,10000,500,550000,550000,24,24,40,40,1.9,1.9,10000,2000000,200");
$parameters[] = explode(",", "gnfs,88,4,56,1500,6.0e-4,0.30,200,15,10000,500,600000,600000,25,25,43,43,2.2,2.2,10000,2000000,200");
$parameters[] = explode(",", "gnfs,90,4,58,2000,2.5e-4,0.30,220,15,10000,600,700000,700000,25,25,44,44,2.4,2.4,40000,2000000,200");
$parameters[] = explode(",", "gnfs,95,4,60,2000,1.0e-4,0.30,220,15,10000,600,1200000,1200000,25,25,45,45,2.4,2.4,60000,2000000,200");
$parameters[] = explode(",", "gnfs,100,5,58,1500,3.0e-3,0.4,220,15,10000,2000,1800000,1800000,26,26,48,48,2.5,2.5,100000,4000000,300");
$parameters[] = explode(",", "gnfs,103,5,59,2000,9.0e-4,0.35,200,15,15000,2000,2300000,2300000,26,26,49,49,2.6,2.6,100000,4000000,300");
$parameters[] = explode(",", "gnfs,106,5,59,2000,6.0e-4,0.25,200,15,15000,2000,2500000,2500000,26,26,49,49,2.6,2.6,150000,4000000,300");
$parameters[] = explode(",", "gnfs,110,5,61,2000,1.5e-4,0.3,250,15,50000,2400,3200000,3200000,27,27,50,50,2.6,2.6,100000,4000000,300");
$parameters[] = explode(",", "gnfs,112,5,61,2000,1.6e-4,0.25,250,15,50000,2800,3500000,3500000,27,27,50,50,2.6,2.6,100000,4000000,300");
$parameters[] = explode(",", "gnfs,118,5,63,2000,2.6e-5,0.28,250,20,50000,3600,4500000,4500000,27,27,50,50,2.4,2.4,60000,4000000,300");
$parameters[] = explode(",", "gnfs,122,5,65,2000,1.0e-5,0.28,250,20,50000,3600,5000000,5000000,27,27,50,50,2.4,2.4,60000,4000000,300");
$parameters[] = explode(",", "gnfs,126,5,67,2000,5.0e-6,0.28,250,20,50000,3600,5400000,5400000,27,27,51,51,2.5,2.5,60000,4000000,300");

if(count($argv) != 2 && count($argv) != 3) {
	echo "Usage: " . $argv[0] . " [-i] number.fb\n";
	exit(1);
}

if(count($argv) == 3)
	define('INFO_MODE', 1);
else
	define('INFO_MODE', 0);

$number = parseFile($argv[1 + INFO_MODE]);
$chosen = getChosenParameters($number);
populateObject($number, $chosen);
$estimatedMinRelations = getMinRels($number, $chosen);
printSiever($number);
if(!INFO_MODE)
{
	writeJobFile($number, str_replace(".fb", ".job", $argv[1 + INFO_MODE]));
	echo "Wrote job file: " . str_replace(".fb", ".job", $argv[1 + INFO_MODE]) . "\n";
}

function valueofline($lookfor, $contents)
{
	$ret = -256;
	for($i = 0; $i < count($contents); $i++)
	if(stripos(strtolower(trim($contents[$i])), strtolower($lookfor)) === 0)
	{
		$contents[$i] = str_replace("  ", " ", $contents[$i]);
		$ret = explode(" ", trim($contents[$i]));
		$ret = trim($ret[1]);
	}

	if($ret == -256)
	{
		echo "Error: Could not find value of '" . $lookfor . "' in supplied fb file.\n";
		exit(1);
	}

	return $ret;
}
