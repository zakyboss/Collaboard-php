<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🚀 PHP PostgreSQL Connection Test</h2>";

// Get DATABASE_URL from environment or use fallback credentials
$database_url = getenv("DATABASE_URL") ?: "postgresql://postgres:AKiPkfkcWRKrZzAdbyfJPDFEnOXbuqnG@yamabiko.proxy.rlwy.net:54022/railway";
$parsed_url = parse_url($database_url);

// Extract database connection details
$host = $parsed_url["host"] ?? "yamabiko.proxy.rlwy.net";
$port = $parsed_url["port"] ?? "54022";
$user = $parsed_url["user"] ?? "postgres";
$password = $parsed_url["pass"] ?? "AKiPkfkcWRKrZzAdbyfJPDFEnOXbuqnG";
$dbname = isset($parsed_url["path"]) ? ltrim($parsed_url["path"], "/") : "railway";

// Attempt PostgreSQL connection
$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if ($conn) {
    echo "<p style='color: green;'>✅ Database connected successfully!</p>";

    // Fetch tables as a simple query test
    $result = pg_query($conn, "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
    
    if ($result) {
        echo "<h3>📌 Tables in database:</h3><ul>";
        while ($row = pg_fetch_assoc($result)) {
            echo "<li>" . $row['table_name'] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠️ Query to fetch tables failed: " . pg_last_error($conn) . "</p>";
    }
    
    pg_close($conn); // Close connection after checking tables
} else {
    echo "<p style='color: red;'>❌ Database connection failed: " . pg_last_error() . "</p>";
}
?>
