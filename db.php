<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");

// Database connection details
$host = "yamabiko.proxy.rlwy.net";
$port = "54022";
$dbname = "railway";
$user = "postgres";
$password = "AKiPkfkcWRKrZzAdbyfJPDFEnOXbuqnG";  // Your actual PostgreSQL password

// Connect to PostgreSQL
$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if (!$conn) {
    http_response_code(500);
    die(json_encode(["success" => false, "message" => "Database connection failed: " . pg_last_error()]));
}

// Success message
echo json_encode(["success" => true, "message" => "Database connected successfully!"]);
?>
