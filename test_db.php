<?php
header("Content-Type: application/json; charset=UTF-8");

// Retrieve DATABASE_URL and connect to DB as before...
$database_url = getenv("DATABASE_URL") ?: "postgresql://postgres:AKiPkfkcWRKrZzAdbyfJPDFEnOXbuqnG@yamabiko.proxy.rlwy.net:54022/railway";
$parsed_url = parse_url($database_url);
$host = $parsed_url["host"] ?? "yamabiko.proxy.rlwy.net";
$port = $parsed_url["port"] ?? "54022";
$user = $parsed_url["user"] ?? "postgres";
$password = $parsed_url["pass"] ?? "AKiPkfkcWRKrZzAdbyfJPDFEnOXbuqnG";
$dbname = isset($parsed_url["path"]) ? ltrim($parsed_url["path"], "/") : "railway";

$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if (!$conn) {
    echo json_encode([
      "status" => "error",
      "message" => "Database connection failed: " . pg_last_error()
    ]);
    exit();
}

// For test purposes, list tables
$response = ["status" => "success", "tables" => []];
$result = pg_query($conn, "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");

if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $response["tables"][] = $row['table_name'];
    }
} else {
    $response = [
      "status" => "error",
      "message" => "Query to fetch tables failed: " . pg_last_error($conn)
    ];
}
pg_close($conn);
echo json_encode($response);
exit();
?>
