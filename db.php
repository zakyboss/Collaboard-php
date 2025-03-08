<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection details
$database_url = getenv("DATABASE_URL") ?: "postgresql://postgres:AKiPkfkcWRKrZzAdbyfJPDFEnOXbuqnG@yamabiko.proxy.rlwy.net:54022/railway";

// Parse the DATABASE_URL
$parsed_url = parse_url($database_url);

$host = $parsed_url["host"] ?? "yamabiko.proxy.rlwy.net";
$port = $parsed_url["port"] ?? "54022";
$user = $parsed_url["user"] ?? "postgres";
$password = $parsed_url["pass"] ?? "AKiPkfkcWRKrZzAdbyfJPDFEnOXbuqnG";
$dbname = isset($parsed_url["path"]) ? ltrim($parsed_url["path"], "/") : "railway";

// Connect to PostgreSQL
$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

//
