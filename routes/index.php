<?php 
require_once (__DIR__ . "/routes.fnk.inc.php");

$r = q("SELECT * FROM %s%snotifications ", ROUTES_SQL_DB, ROUTES_SQL_PREFIX);
foreach ($r as $not) {
	print_r($not);
}
?>