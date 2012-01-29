<?php

require_once("Compose/pake_helpers.php");
require_once("Compose/default_pakefile.php");

pake_properties("Compose/pake_properties.ini");

pake_desc("Main deploy task");
pake_task("deploy", "default_deploy");
function run_deploy($obj, $args)
{
	pake_echo_action("DEPLOYED", "** Application has finished deploying **");
}
