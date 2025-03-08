<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the DATABASE_URL from Railway environment variables
$database_url = getenv("DATABASE_URL") ?: "postgresql://postgres:AKiPkfkcWRKrZzAdbyfJPDFEnOXbuqnG@your_actual_PGHOST:5432/railway";

// Parse the DATABASE_URL
$parsed_url = parse_url($database_url);
$host = $parsed_url["host"];
$port = $parsed_url["port"];
$user = $parsed_url["user"];
$password = $parsed_url["pass"];
$dbname = ltrim($parsed_url["path"], "/");

// Connect to PostgreSQL
$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if (!$conn) {
    die(json_encode(["success" => false, "message" => "Database connection failed: " . pg_last_error()]));
} else {
    echo json_encode(["success" => true, "message" => "Connected to PostgreSQL successfully!"]);
}
