#!/usr/bin/php
<?php

function coalesce() 
{
  $args = func_get_args();
  foreach ($args as $arg) 
    if (!empty($arg)) 
      return $arg;
  return NULL;
}

define(ROOT, '/home/path/to/wordlist/dir/');
$filelinecache = array();
function linesInFile($file)
{
  global $filelinecache;
  if(isset($filelinecache[$file])) return $filelinecache[$file];
  $lines = 0;
  if ($fh = @fopen($file, 'r')) 
    while (!feof($fh)) 
      if (fgets($fh)) 
	$lines++;
  $filelinecache[$file] = $lines;
  return $lines;
}
function getCryptsec($file)
{
  $s = @exec("grep Processed \"$file\" 2>/dev/null");
  $a = split("=", $s);
  return str_replace("k/s", "", $a[1]) * 1000;
}
function getTimeRun($file)
{
  $start = str_replace("Started:", "", exec("grep Started \"$file\" 2>/dev/null"));
  $stop = str_replace("Stopped:", "", exec("grep Stopped \"$file\" 2>/dev/null"));
  $start = strtotime($start);
  $stop = strtotime($stop);
  return $stop - $start;
}

$getfiles = "
SELECT 
	r.id,
	r.potfile,
	r.logfile,
	IF(r.wordlist REGEXP '[[:alnum:]]+_[[:alnum:][:punct:]]+\.?[[:alnum:]]*_[[:digit:]]+_[[:digit:]]+_[[:digit:]]+_wl', 1, 0) as splitwordlist,
	case
		when r.bruteforce then 'bruteforce'
		when r.wordlist REGEXP '[[:alnum:]]+_[[:alnum:][:punct:]]+\.?[[:alnum:]]*_[[:digit:]]+_[[:digit:]]+_[[:digit:]]+_wl' then
				SUBSTRING(REPLACE(SUBSTRING_INDEX(r.wordlist, '_', 2), SUBSTRING_INDEX(r.wordlist, '_', 1), ''), 2)
		else
				SUBSTRING(REPLACE(SUBSTRING_INDEX(r.wordlist, '_', 2), SUBSTRING_INDEX(r.wordlist, '_', 1), ''), 2)
		end as wordlist,
	case
		when r.wordlist REGEXP '[[:alnum:]]+_[[:alnum:][:punct:]]+\.?[[:alnum:]]*_[[:digit:]]+_[[:digit:]]+_[[:digit:]]+_wl' then
			SUBSTRING(REPLACE(SUBSTRING_INDEX(r.wordlist, '_', 4), SUBSTRING_INDEX(r.wordlist, '_', 3), ''), 2)			
		else
				-1
		end as linesinfile,
	r.wordlist as originalwordlist
FROM (

SELECT
r.id,
r.name as potfile,
IF(INSTR(w.xml_doc, '--incremental') > 0 OR INSTR(w.xml_doc, '--bf-cs-buf') > 0, 1, 0) as bruteforce,
CONCAT(TRIM(TRAILING '0' FROM r.name), '1') as logfile,
CAST(substring(w.xml_doc,
     instr(w.xml_doc, '<open_name>passwordlist</open_name>') + 92,
     instr(w.xml_doc, '<open_name>wordlist</open_name>') - instr(w.xml_doc, '<open_name>passwordlist</open_name>') - 98 - 11
     ) AS CHAR(100) CHARACTER SET utf8) as wordlist
FROM `result` r
INNER JOIN workunit w
  on w.id = r.workunitid
INNER JOIN app_version av
  on av.id = r.app_version_id
INNER JOIN app a
  on a.id = av.appid
WHERE a.id IN (31)
) r

";
/*
and r.server_state = 5
and outcome = 1
and client_state = 5
and validate_state = 1";
*/

$conn = mysql_connect("localhost", "USERNAME", "PASSWORD");
mysql_select_db("DATABASE");
$result = mysql_query($getfiles, $conn);

$i = 0;
while($row = mysql_fetch_array($result))
    $results[$i++] = $row;

echo "Got ".count($results)." results to go through...\n";
for($i=0; $i < count($results); $i++)
  {
    if($i % 10 == 0) echo".";
    $results[$i]['linesinfile'] = 
      coalesce(
	       linesInFile(ROOT . '../wordlists/categorized/special/' . $results[$i]['wordlist']),
	       linesInFile(ROOT . '../wordlists/categorized/foreign/' . $results[$i]['wordlist']),
	       linesInFile(ROOT . '../wordlists/categorized/uppercase/' . $results[$i]['wordlist']),
	       linesInFile(ROOT . '../wordlists/categorized/english/' . $results[$i]['wordlist']),
               linesInFile(ROOT . 'wordlists/extralarge/' . $results[$i]['wordlist']),
               linesInFile(ROOT . 'markovlists/lvl123/' . $results[$i]['wordlist']),
	       0);
    $results[$i]['cracked'] = linesInFile(ROOT . 'sample_results/' . $results[$i]['potfile']);
    $results[$i]['cracks/sec'] = getCryptsec(ROOT . 'sample_results/' . $results[$i]['logfile']);
    $results[$i]['time'] = getTimeRun(ROOT . 'sample_results/' . $results[$i]['logfile']);
  }
echo "\n";
$sql = "INSERT INTO result_cudaparsed ";
for($i=0; $i < count($results); $i++)
  {
    $sql .= ($i != 0) ? " UNION ALL\nSELECT " : "\nSELECT ";
    $sql .= $results[$i]['id'] . " as id, ";
    $sql .= $results[$i]['linesinfile'] . " as linesinfile, ";
    $sql .= $results[$i]['cracked'] . " as cracked, ";
    $sql .= $results[$i]['cracks/sec'] . " as cryptsec, ";
    $sql .= $results[$i]['time'] . " as total_sec, '";
    $sql .= $results[$i]['wordlist'] . "' as wordlist ";
  }
echo $sql . "\n\n";
$result = mysql_query("DROP TABLE IF EXISTS result_cudaparsed", $conn) or die( mysql_error($conn)."\n");
$result = mysql_query("CREATE TABLE result_cudaparsed (id INT, linesinfile INT, cracked INT, cryptsec INT, total_sec INT, wordlist varchar(400))", $conn) or die( mysql_error($conn)."\n");
$result = mysql_query($sql, $conn) or die( mysql_error($conn)."\n");