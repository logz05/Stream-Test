<?php

ini_set("display_errors", 1);
error_reporting(E_ALL);

require_once $_SERVER['DOCUMENT_ROOT'] . '/src/FacebookStream.php';

// Create and update Facebook stream
$fbStream = new FacebookStream(1, "102182536573392", "d8d83bd59512000bc26dfc888e6e006b");
$fbStream->update();