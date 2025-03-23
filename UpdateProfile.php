<?php
// File: UpdateProfile.php

// In production, you might want to set these to 0 to avoid HTML error output:
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db.php'; // Must contain pg_connect() to your PostgreSQL

$response = ["success" => false, "message" => ""];

try {
    // 1) Retrieve form data (multipart/form-data)
    $userId    = isset($_POST['userId']) ? intval($_POST['userId']) : 0;
    $firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : "";
    $lastName  = isset($_POST['lastName']) ? trim($_POST['lastName']) : "";

    if ($userId <= 0) {
        http_response_code(400);
        throw new Exception("Invalid user ID.");
    }

    // 2) Handle new profile photo if provided
    $uploadedFileContent = null;
    if (isset($_FILES['profilePhoto']) && $_FILES['profilePhoto']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['profilePhoto']['tmp_name'];
        $fileData = file_get_contents($tmpName);

        // Escape for PostgreSQL
        $uploadedFileContent = pg_escape_bytea($conn, $fileData);
    }

    // 3) Build the SQL with pg_query_params
    if ($uploadedFileContent !== null) {
        // If a new photo is uploaded, update profile_picture
        $query = "
            UPDATE collaboardtable_users
            SET first_name = $2,
                last_name  = $3,
                profile_picture = decode($4, 'escape')
            WHERE user_id = $1
        ";
        $params = [
            $userId,
            $firstName,
            $lastName,
            $uploadedFileContent // escaped bytea data
        ];
    } else {
        // No new photo: don't touch profile_picture
        $query = "
            UPDATE collaboardtable_users
            SET first_name = $2,
                last_name  = $3
            WHERE user_id = $1
        ";
        $params = [
            $userId,
            $firstName,
            $lastName
        ];
    }

    // 4) Execute the query
    $result = pg_query_params($conn, $query, $params);
    if (!$result) {
        http_response_code(500);
        throw new Exception("Failed to update profile: " . pg_last_error($conn));
    }

    // If the query succeeded
    $response["success"] = true;
    $response["message"] = "Profile updated successfully!";
} catch (Exception $e) {
    $response["success"] = false;
    $response["message"] = $e->getMessage();
}

// 5) Return JSON
echo json_encode($response);
pg_close($conn);
exit;
