<?php
// File: UpdateProfile.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Handle preflight request
    exit(0);
}

require_once 'db.php'; // Make sure this file does pg_connect() to your PostgreSQL DB

$response = ["success" => false, "message" => ""];

try {
    // 1) Get form data (multipart/form-data)
    $userId    = isset($_POST['userId']) ? intval($_POST['userId']) : 0;
    $firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : "";
    $lastName  = isset($_POST['lastName']) ? trim($_POST['lastName']) : "";

    if ($userId <= 0) {
        throw new Exception("Invalid user ID.");
    }

    // 2) Handle file upload (profilePhoto) if provided
    $uploadedFileContent = null; // We'll store the raw binary file data in memory

    if (isset($_FILES['profilePhoto']) && $_FILES['profilePhoto']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['profilePhoto']['tmp_name'];

        // Read the file contents
        $uploadedFileContent = file_get_contents($tmpName);

        // If you want to store in the DB as bytea, escape it:
        $uploadedFileContent = pg_escape_bytea($conn, $uploadedFileContent);
    }

    // 3) Build the SQL with pg_query_params
    // If a new photo is uploaded, update `profile_picture` too
    if ($uploadedFileContent !== null) {
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
            $uploadedFileContent  // <-- escaped bytea data
        ];
    } else {
        // No new photo: don't update `profile_picture`
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

    // 4) Execute query
    $result = pg_query_params($conn, $query, $params);

    if (!$result) {
        throw new Exception("Failed to update profile: " . pg_last_error($conn));
    }

    // If the query succeeded
    $response["success"] = true;
    $response["message"] = "Profile updated successfully!";
} catch (Exception $e) {
    // If anything goes wrong, catch the exception and set an error message
    $response["success"] = false;
    $response["message"] = $e->getMessage();
}

// 5) Return JSON
echo json_encode($response);
pg_close($conn);
exit;
