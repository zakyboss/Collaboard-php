<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the DATABASE_URL from Railway environment variables
$database_url = getenv("DATABASE_URL");

if (!$database_url) {
    die(json_encode(["success" => false, "message" => "DATABASE_URL not found in environment variables."]));
}

// Convert DATABASE_URL to correct format for pg_connect
$database_url = str_replace("postgresql://", "postgres://", $database_url); // Fix URL format if needed
$conn = pg_connect($database_url);

if (!$conn) {
    die(json_encode(["success" => false, "message" => "Database connection failed: " . pg_last_error()]));
} else {
    echo json_encode(["success" => true, "message" => "Database connection successful"]);
}
?>
