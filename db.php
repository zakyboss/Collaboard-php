<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");

// Get database credentials from Railway environment variables
$host = getenv("DB_HOST") ?: "yamabiko.proxy.rlwy.net";
$port = getenv("DB_PORT") ?: "54022";
$dbname = getenv("DB_NAME") ?: "railway";
$user = getenv("DB_USER") ?: "postgres";
$password = getenv("DB_PASSWORD") ?: "Zakyboss";  // Use Railway ENV variable

// Establish PostgreSQL connection
$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if (!$conn) {
    http_response_code(500);
    die(json_encode(["success" => false, "message" => "Database connection failed: " . pg_last_error()]));
}

// Success message
echo json_encode(["success" => true, "message" => "Database connected successfully!"]);
?>