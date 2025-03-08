<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "postgres.railway.internal";
$port = "5432";
$dbname = "railway";
$user = "postgres";
$password = "AKiPkfkcWRKrZzAdbyfJPDFEnOXbuqnG"; // Replace with actual password

$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if (!$conn) {
    die("❌ Database connection failed: " . pg_last_error());
} else {
    echo "✅ Database connected successfully!";
}
?>
