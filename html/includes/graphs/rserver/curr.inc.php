<?php

##$ds_in = "RserverCurrentConns";
##$ds_out = "RserverTotalConns";

$scale_min = 0;

include("includes/graphs/common.inc.php");

$graph_max = 1;

$ds = "RserverCurrentConns";

$colour_area = "B0C4DE";
$colour_line = "191970";

$colour_area_max = "FFEE99";

$nototal   = 1;
$unit_text = "Conns";

include("includes/graphs/generic_simplex.inc.php");

?>
