<?php

$query = mysql_query("SELECT * FROM `applications` WHERE `device_id` = '".$device['device_id']."'");

print_optionbar_start();

unset($sep);

while ($app = mysql_fetch_assoc($query))
{
  echo($sep);

  if (!$_GET['opta']) { $_GET['opta'] = $app['app_type']; }

  if ($_GET['opta'] == $app['app_type'])
  {
    echo("<span class='pagemenu-selected'>");
    #echo('<img src="images/icons/'.$app['app_type'].'.png" class="optionicon" />');
  } else {
    #echo('<img src="images/icons/greyscale/'.$app['app_type'].'.png" class="optionicon" />');
  }
  echo("<a href='device/".$device['device_id']."/apps/" . $app['app_type'] . ($_GET['optb'] ? "/" . $_GET['optb'] : ''). "/'> " . $app['app_type'] ."</a>");
  if ($_GET['opta'] == $app['app_type']) { echo("</span>"); }
  $sep = " | ";
}

print_optionbar_end();

$app = mysql_fetch_assoc(mysql_query("SELECT * FROM `applications` WHERE `device_id` = '".$device['device_id']."' AND `app_type` = '".$_GET['opta']."'"));

if (is_file("pages/device/apps/".mres($_GET['opta']).".inc.php"))
{
   include("pages/device/apps/".mres($_GET['opta']).".inc.php");
}

?>
