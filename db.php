<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");

// Get database credentials from environment variables
$host = getenv("PGHOST") ?: "yamabiko.proxy.rlwy.net";
$port = getenv("PGPORT") ?: "54022";
$dbname = getenv("PGDATABASE") ?: "railway";
$user = getenv("PGUSER") ?: "postgres";
$password = getenv("PGPASSWORD") ?: "your_actual_password_here";  // Replace with Railway ENV variable

// Establish PostgreSQL connection
$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if (!$conn) {
    http_response_code(500);
    die(json_encode(["success" => false, "message" => "Database connection failed: " . pg_last_error()]));
}

// Success message (for debugging, remove in production)
echo json_encode(["success" => true, "message" => "Database connected successfully!"]);
?>
