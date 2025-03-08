<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "postgres.railway.internal"; // Use this when running inside Railway
$port = "5432";
$dbname = "railway";
$user = "postgres";
$password = "AKiPkfkcWRKrZzAdbyfJPDFEnOXbuqnG";

$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if (!$conn) {
    die(json_encode(["success" => false, "message" => "Database connection failed: " . pg_last_error()]));
} else {
    echo json_encode(["success" => true, "message" => "Connected to PostgreSQL successfully!"]);
}
