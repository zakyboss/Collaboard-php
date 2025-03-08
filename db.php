<?php
// File: Back-end/db.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "sql206.infinityfree.com";
$username = "if0_38433489";
$password = "2weZpLIPN2O8";
$database = "if0_38433489_collaboard";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Database connection failed!"]));
}
$conn->set_charset("utf8");
