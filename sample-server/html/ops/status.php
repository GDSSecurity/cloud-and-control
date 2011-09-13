<?php
// This file is part of BOINC.
// http://boinc.berkeley.edu
// Copyright (C) 2008 University of California
//
// BOINC is free software; you can redistribute it and/or modify it
// under the terms of the GNU Lesser General Public License
// as published by the Free Software Foundation,
// either version 3 of the License, or (at your option) any later version.
//
// BOINC is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with BOINC.  If not, see <http://www.gnu.org/licenses/>.

require_once("../inc/util_ops.inc");
require_once("../inc/db_ops.inc");

db_init();

admin_page_head("Workunit Status");

?>

<style type="text/css">
body { zoom: .65; }
.error {
  font-weight: normal !important;
}
.box {
width:33px;
height:30px;
position:relative;
line-height:30px;
  float:left;
  text-align:center;
}
.box>a {
  z-index: 99;
  position: inherit;
}
.box .wu{
font-size:9px;
width:32px;
position:absolute;
top:-10px;
text-align:right;
}
.box .x{
font-size:9px;
position:absolute;
right:2px;
top:9px;
}
.demo {
padding:5px;
 }
.success {
background: #0C0;
border: 5px solid #0C0;
}
.working {
background: yellow;
border: 5px solid yellow;
}
.unsent {
background: #C0C;
border:5px solid #C0C;
}
.canceled {
background: #0C0;
border:5px solid #669900;
}
.abortedbyproject {
background: #0C0;
border:5px solid pink;
}
.error {
background: #F00;
border:5px solid #666;
}
.resourcelimit {
background: #F00;
border:5px dashed #900;
}
.noreply {
background: #F00;
border:5px ridge #600;
}
.clientdetached {
background: #F00;
border:5px groove #600;
}
.download {
background: #F00;
border: 5px dotted #C00;
}
.resourcelimit, .resourcelimit a, .error, .error a, .download, .download a {
color: white;
}
.unknown {
background: orange;
border: 5px solid orange;
}
</style>

<div style="text-align:center">
  <span class="demo success">Success</span>
  <span class="demo working">Working</span>
  <span class="demo unsent">Unsent</span>
  <span class="demo canceled">Canceled</span>
  <span class="demo abortedbyproject">Admin-Aborted</span>
  <span class="demo error">Error</span>
  <span class="demo resourcelimit">Resource Limit</span>
  <span class="demo download">Download Error</span>
  <span class="demo clientdetached">Detached</span>
  <span class="demo noreply">No Reply</span>
  <span class="demo unknown">Unknown</span>
</div>
<br style="clear:both;" />
<hr />

<?php

define('SERVERSTATE_INACTIVE', 1);
define('SERVERSTATE_UNSENT', 2);
define('SERVERSTATE_INPROGRESS', 4);
define('SERVERSTATE_OVER', 5);

define('OUTCOME_INIT', 0);
define('OUTCOME_SUCCESS', 1);
define('OUTCOME_CLIENTERROR', 3);
define('OUTCOME_NOREPLY', 4);
define('OUTCOME_DIDNTNEED', 5);
define('OUTCOME_CLIENTDETACHED', 7);

define('CLIENTSTATE_NEW', 0);
define('CLIENTSTATE_DOWNLOADING', 1);
define('CLIENTSTATE_COMPUTEERROR', 3);
define('CLIENTSTATE_UPLOADED', 5);
define('CLIENTSTATE_ABORTED', 6);

define('VALIDATESTATE_INITIAL', 0);
define('VALIDATESTATE_VALID', 1);
define('VALIDATESTATE_INVALID', 2);

$q = "SELECT id, workunitid, server_state, outcome, client_state, validate_state, exit_status FROM result";
$result = mysql_query($q) or die("MySQL Error: " . mysql_error());
while ($res = mysql_fetch_array($result)) 
  {
    $serverstate = $res['server_state'];
    $outcome = $res['outcome'];
    $clientstate = $res['client_state'];
    $validatestate = $res['validate_state'];
    $exitstatus = $res['exit_status'];
    
    echo "<div class=\"box ";
    
    if($serverstate == SERVERSTATE_UNSENT && $outcome == OUTCOME_INIT && $clientstate == CLIENTSTATE_NEW && $validatestate == VALIDATESTATE_INITIAL)
      echo "unsent";
    else if($serverstate == SERVERSTATE_INPROGRESS && $outcome == OUTCOME_INIT && $clientstate == CLIENTSTATE_NEW && $validatestate == VALIDATESTATE_INITIAL)
      echo "working";
    else if($serverstate == SERVERSTATE_OVER && $outcome == OUTCOME_SUCCESS && $clientstate == CLIENTSTATE_UPLOADED && $validatestate == VALIDATESTATE_VALID)
      echo "success";
    else if($serverstate == SERVERSTATE_OVER && $outcome == OUTCOME_DIDNTNEED && $clientstate == CLIENTSTATE_NEW && $validatestate == VALIDATESTATE_INITIAL)
      echo "canceled";
    else if($serverstate == SERVERSTATE_OVER && $outcome == OUTCOME_CLIENTERROR && $clientstate == CLIENTSTATE_ABORTED && $validatestate == VALIDATESTATE_INVALID && $exitstatus == -221)
      echo "abortedbyproject";
    else if($serverstate == SERVERSTATE_OVER && $outcome == OUTCOME_CLIENTDETACHED)
      echo "clientdetached";
    else if($serverstate == SERVERSTATE_OVER && $outcome == OUTCOME_CLIENTERROR && ($clientstate == CLIENTSTATE_COMPUTEERROR || $clientstate == CLIENTSTATE_ABORTED) && $validatestate == VALIDATESTATE_INVALID && $exitstatus == -177)
      echo "resourcelimit";
    else if($serverstate == SERVERSTATE_OVER && $outcome == OUTCOME_CLIENTERROR && $clientstate == CLIENTSTATE_DOWNLOADING && $validatestate == VALIDATESTATE_INVALID && $exitstatus == -186)
      echo "download";
    else if($serverstate == SERVERSTATE_OVER && $outcome == OUTCOME_NOREPLY && $clientstate == CLIENTSTATE_NEW && $validatestate == VALIDATESTATE_INITIAL)
      echo "noreply";
    else if($serverstate == SERVERSTATE_OVER && $outcome == OUTCOME_CLIENTERROR && $validatestate == VALIDATESTATE_INVALID)
      echo "error";
    else
      echo "unknown";

    echo "\"><a href=\"db_action.php?table=result&id=" . $res['id']  . "\">" . $res['id']  . "</a>";
    echo "<div class=\"wu\"><a href=\"db_action.php?table=workunit&id=".$res['workunitid']."\">".$res['workunitid']."</a></div>";
    echo "<span class=\"x\"><a href=\"cancel_wu_action.php?wuid1=".$res['workunitid']."&wuid2=".$res['workunitid']."\">x</a></span>";
    echo "<!-- SS:$serverstate OC:$outcome CS:$clientstate VS:$validatestate ES:$exitstatus --></div>\n";
  }

echo '<br style="clear:both;" />' . "\n";    
echo '<script type="text/javascript">window.setTimeout("location.reload();", 10000);</script>' . "\n";

admin_page_tail();
$cvs_version_tracker[]="\$Id: db_action.php 15975 2008-09-07 07:40:56Z davea $";  //Generated automatically - do not edit
?>
