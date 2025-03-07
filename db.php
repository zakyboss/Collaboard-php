<?php
// File: Back-end/db.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Your PostgreSQL URL from Railway
$databaseUrl = "postgresql://postgres:AKiPkfkcWRKrZzAdbyfJPDFEnOXbuqnG@postgres.railway.internal:5432/railway";

// Parse the URL
$db = parse_url($databaseUrl);
$host = $db["host"];
$port = $db["port"];
$user = $db["user"];
$pass = $db["pass"];
$dbname = ltrim($db["path"], "/");

// Connect to PostgreSQL
$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$pass");

if (!$conn) {
    die(json_encode(["success" => false, "message" => "Database connection failed!"]));
} else {
    echo json_encode(["success" => true, "message" => "Database connected!"]);
}
?>
