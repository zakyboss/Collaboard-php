<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Retrieve the internal DATABASE_URL from environment variables
$database_url = getenv("DATABASE_URL");
if (!$database_url) {
    die("❌ DATABASE_URL is not set!");
}

// Parse the DATABASE_URL
$parsed_url = parse_url($database_url);

$host = $parsed_url["host"] ?? "localhost";
$port = $parsed_url["port"] ?? "5432";
$user = $parsed_url["user"] ?? "postgres";
$password = $parsed_url["pass"] ?? "";
$dbname = isset($parsed_url["path"]) ? ltrim($parsed_url["path"], "/") : "railway";

// Connect to PostgreSQL
$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if (!$conn) {
    die("❌ Database connection failed: " . pg_last_error());
}
