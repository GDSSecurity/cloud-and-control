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

define(ROOT, '/home/path/to/wordlist/dir');
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

$getfiles = "
SELECT
        r.id,
        IF(r.wordlist REGEXP '[[:alnum:]]+_[[:alnum:][:punct:]]+\.?[[:alnum:]]*_[[:digit:]]+_[[:digit:]]+_[[:digit:]]+_wl', 1, 0) as splitwordlist,
        case
                when r.bruteforce then 'bruteforce'
                when r.wordlist REGEXP '[[:alnum:]]+_[[:alnum:][:punct:]]+\.?[[:alnum:]]*_[[:digit:]]+_[[:digit:]]+_[[:digit:]]+_wl' then
                        SUBSTRING_INDEX(
                left(REPLACE(r.wordlist, SUBSTRING_INDEX(r.wordlist, '_', -3), ''),
                        LENGTH(REPLACE(r.wordlist, SUBSTRING_INDEX(r.wordlist, '_', -4), '')) - 1),
                '_', -1)
                else
                        SUBSTRING(REPLACE(REPLACE(r.wordlist, '_wl', ''), SUBSTRING_INDEX(r.wordlist, '_', 1), ''), 2)
                end as wordlist,
        case
                when r.wordlist REGEXP '[[:alnum:]]+_[[:alnum:][:punct:]]+\.?[[:alnum:]]*_[[:digit:]]+_[[:digit:]]+_[[:digit:]]+_wl' then
                        SUBSTRING_INDEX(
                left(REPLACE(r.wordlist, SUBSTRING_INDEX(r.wordlist, '_', -1), ''),
                        LENGTH(REPLACE(r.wordlist, SUBSTRING_INDEX(r.wordlist, '_', -2), '')) - 1),
                '_', -1)
                else
                        -1
                end as linesinfile,
        r.wordlist as originalwordlist

FROM (

SELECT
	r.id,
	IF(INSTR(w.xml_doc, '--incremental') > 0 OR INSTR(w.xml_doc, '--bf-cs-buf') > 0, 1, 0) as bruteforce,
	CAST(substring(w.xml_doc,
		instr(w.xml_doc, '<open_name>john.conf</open_name>') + 71,
		instr(w.xml_doc, '<open_name>wordlist</open_name>') - instr(w.xml_doc, '<open_name>john.conf</open_name>') - 88 - 11
		) AS CHAR(100) CHARACTER SET utf8) as wordlist
FROM `result` r
INNER JOIN workunit w
  on w.id = r.workunitid
INNER JOIN app_version av
  on av.id = r.app_version_id
INNER JOIN app a
  on a.id = av.appid
WHERE a.id IN (18)
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
    if($results[$i]['linesinfile'] < 0 && $results[$i]['wordlist'] != "bruteforce")
      $results[$i]['linesinfile'] = 
	coalesce(
		 linesInFile(ROOT . 'wordlists/categorized/special/' . $results[$i]['wordlist']),
		 linesInFile(ROOT . 'wordlists/categorized/foreign/' . $results[$i]['wordlist']),
		 linesInFile(ROOT . 'wordlists/categorized/uppercase/' . $results[$i]['wordlist']),
		 linesInFile(ROOT . 'wordlists/categorized/english/' . $results[$i]['wordlist']),
		 linesInFile(ROOT . 'wordlists/extralarge/' . $results[$i]['wordlist']),
		 linesInFile(ROOT . 'markovlists/lvl123/' . $results[$i]['wordlist'])
		 );
    if($results[$i]['linesinfile'] < 0 || empty($results[$i]['linesinfile'])) 
      $results[$i]['linesinfile'] = 0;
    //$results[$i]['cracked'] = linesInFile(ROOT . 'sample_results/' . $results[$i]['potfile']);
    //$results[$i]['cracks/sec'] = getCryptsec(ROOT . 'sample_results/' . $results[$i]['logfile']);
    //$results[$i]['time'] = getTimeRun(ROOT . 'sample_results/' . $results[$i]['logfile']);
  }
echo "\n";
$sql = "INSERT INTO result_johnparsed ";
for($i=0; $i < count($results); $i++)
  {
    $sql .= ($i != 0) ? " UNION ALL\nSELECT " : "\nSELECT ";
    $sql .= $results[$i]['id'] . " as id, ";
    $sql .= $results[$i]['linesinfile'] . " as linesinfile, '";
    $sql .= $results[$i]['wordlist'] . "' as wordlist ";
    //$sql .= $results[$i]['cracked'] . " as cracked, ";
    //$sql .= $results[$i]['cracks/sec'] . " as cryptsec, ";
    //$sql .= $results[$i]['time'] . " as total_sec ";
  }
//echo $sql . "\n\n";
$result = mysql_query("DROP TABLE IF EXISTS result_johnparsed", $conn) or die( mysql_error($conn)."\n");
$result = mysql_query("CREATE TABLE result_johnparsed (id INT, linesinfile INT, wordlist varchar(400))", $conn) or die( mysql_error($conn)."\n");
$result = mysql_query($sql, $conn) or die( mysql_error($conn)."\n");