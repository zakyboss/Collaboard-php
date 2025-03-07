<?php
// File: Collaboard-php/db.php

error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");

// Use Railway PostgreSQL connection URL
$databaseUrl = getenv("DATABASE_URL") ?: "postgresql://postgres:AKiPkfkcWRKrZzAdbyfJPDFEnOXbuqnG@yamabiko.proxy.rlwy.net:54022/railway";

// Parse connection URL
$db = parse_url($databaseUrl);

if (!$db) {
    http_response_code(500);
    die(json_encode(["success" => false, "message" => "Invalid database URL!"]));
}

$host = $db["host"];
$port = $db["port"];
$user = $db["user"];
$pass = $db["pass"];
$dbname = ltrim($db["path"], "/");

// Connect to PostgreSQL
$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$pass");

if (!$conn) {
    http_response_code(500);
    die(json_encode(["success" => false, "message" => "Database connection failed: " . pg_last_error()]));
}

// Success Response (Optional)
echo json_encode(["success" => true, "message" => "Database connected successfully!"]);
?>
