<?php
// File: Back-end/db.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "postgres.railway.internal";  // Railway PostgreSQL Host
$port = "5432";                     // Railway PostgreSQL Port
$dbname = "railway";                 // Railway Database Name
$user = "postgres";                   // Railway PostgreSQL Username
$password = "AKiPkfkcWRKrZzAdbyfJPDFEnOXbuqnG";                // Railway PostgreSQL Password (Check Railway Variables)

// Connect to PostgreSQL
$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if (!$conn) {
    die(json_encode(["success" => false, "message" => "Database connection failed: " . pg_last_error()]));
}
