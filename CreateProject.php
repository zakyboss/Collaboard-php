<?php
// File: Collaboard-php/CreateProject.php

// Enable error reporting (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db.php'; // $conn = pg_connect(...)

// 1) Detect multipart/form-data vs. JSON
$contentType = $_SERVER["CONTENT_TYPE"] ?? '';
if (strpos($contentType, 'multipart/form-data') !== false) {
    $data = $_POST;
    $thumbnailFile = $_FILES['thumbnail'] ?? null;
} else {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $thumbnailFile = null;
}

// 2) Extract fields
$user_id          = trim($data["user_id"] ?? "");
$proj_name        = htmlspecialchars(trim($data["proj_name"] ?? ""));
$description      = htmlspecialchars(trim($data["description"] ?? ""));
$dev_needed       = trim($data["dev_needed"] ?? "");
$days_to_complete = trim($data["days_to_complete"] ?? "");

// Validate
if (empty($user_id) || empty($proj_name) || empty($description)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Required fields missing."]);
    exit();
}

// 3) Read raw image data if a file is provided
$thumbnail_data = null;
if ($thumbnailFile && $thumbnailFile['error'] === UPLOAD_ERR_OK) {
    $fileData     = file_get_contents($thumbnailFile['tmp_name']);
    // Escape for PostgreSQL
    $escapedData  = pg_escape_bytea($conn, $fileData);
    $thumbnail_data = $escapedData;
}

// 4) Insert into collaboardtable_projects, storing the binary in `thumbnail`
$query = "
    INSERT INTO collaboardtable_projects 
    (user_id, proj_name, description, dev_needed, days_to_complete, thumbnail)
    VALUES (
      $1,
      $2,
      $3,
      $4,
      $5,
      CASE WHEN $6 IS NOT NULL THEN decode($6, 'escape') ELSE NULL END
    )
    RETURNING proj_id
";

$params = [
    $user_id,
    $proj_name,
    $description,
    $dev_needed,
    $days_to_complete,
    $thumbnail_data
];

$result = pg_query_params($conn, $query, $params);

if ($result) {
    $row = pg_fetch_assoc($result);
    echo json_encode([
        "success" => true,
        "message" => "Project created successfully!",
        "proj_id" => $row["proj_id"]
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error: " . pg_last_error($conn)
    ]);
}

pg_close($conn);
exit();
