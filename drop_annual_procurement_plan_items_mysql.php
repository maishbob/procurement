<?php
$mysqli = new mysqli('localhost', 'root', 'danzo2000', 'procurement');
if ($mysqli->connect_errno) {
    die('Connect Error: ' . $mysqli->connect_error);
}
$mysqli->query('SET FOREIGN_KEY_CHECKS=0;');
$mysqli->query('DROP TABLE IF EXISTS annual_procurement_plan_items;');
$mysqli->query('SET FOREIGN_KEY_CHECKS=1;');
$mysqli->close();
echo "annual_procurement_plan_items table dropped (if it existed).\n";
