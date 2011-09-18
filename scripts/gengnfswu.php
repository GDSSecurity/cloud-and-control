#!/usr/bin/php
<?php

$stamp=strftime("%b%d_%H%M",time());
$dir="tmp_hostname";
if(!is_dir($dir)) die("$dir/ must exist");

function generate($input,$name,$first,$cnt,$dest) 
{
	global $stamp;

	$patterns[0]	 = '/__START__/';
	$replacements[0] = $first;
	$patterns[1]	 = '/__COUNT__/';
	$replacements[1] = $cnt;
	ksort($patterns);
	ksort($replacements);

	$template		= file_get_contents($input);
	$data			= preg_replace($patterns,$replacements,$template);
	$wuname		  = sprintf("%s_%09d_%d_%s",$name,$first,$cnt,$stamp);
	file_put_contents("{$dest}/{$wuname}",$data);
	return $wuname;
}

if(count($argv) < 7 || count($argv) > 9) 
{
	echo "need 6 or 7 args:\n";
	echo " - input job file\n";
	echo " - base name\n";
	echo " - gnfs version (single digit)\n";
	echo " - first search value\n";
	echo " - last search value\n";
	echo " - units per WU\n";
	echo " - fpops/q (defaults to 500M)\n";
	echo " - every nth (defaults to every)\n";
	echo "example: gengnfswu.php key_ti89.job key_ti89 10000000 12000000 1000\n";
	echo "Output goes to $dir/ \n";
	exit(1);
}

$input = $argv[1];
$name  = $argv[2];
$ver  = $argv[3];
$start = $argv[4];
$end   = $argv[5];
$count = $argv[6];

$fpopsperq = 500000000;
if(count($argv) == 8) $fpopsperq = $argv[7];

$every = 1;
if(count($argv) == 9) $every = $argv[8];


echo "generating work for $name from $start to $end, count = $count\n";

@unlink("createWorkScript");
$f=fopen("createWorkScript","wb");

$wus = 0;
for($i = $start, $loop = 0; $i < $end; $i += $count, $loop++) 
{
	if($loop % $every != 0) continue;

	$wun=generate($input, $name, $i, $count, $dir);
	$cw="";
	
	$cw.="mv $dir/$wun `bin/dir_hier_path $wun`\n";
	
	$cw.="bin/create_work ";
	$cw.=" --appname gnfslasieve4I1".$ver."e ";
	$cw.=" --wu_name $wun ";
	$cw.=" --wu_template templates/gnfslasieve4I1Xe_wu.xml ";
	$cw.=" --result_template templates/gnfslasieve4I1Xe_result.xml ";
	$cw.=" --rsc_fpops_est " . ( $fpopsperq * $count );
	$cw.=" --rsc_fpops_bound " . ( $fpopsperq * $count * 10);

	$cw.=" $wun\n";
	
	fwrite($f,$cw);
	$wus++;
}

fwrite($f,"rm createWorkScript\n");
fclose($f);

echo "done, created $wus work units\n";

?>
