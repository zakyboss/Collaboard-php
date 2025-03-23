<?php
// File: UpdateProfile.php

// For development, show errors; in production, set these to 0
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set CORS & JSON headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$response = ["success" => false, "message" => ""];

try {
    // 1) Include database connection
    require_once 'db.php';
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // 2) Grab form data (multipart/form-data)
    $userId    = isset($_POST['userId']) ? intval($_POST['userId']) : 0;
    $firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : "";
    $lastName  = isset($_POST['lastName']) ? trim($_POST['lastName']) : "";

    // Validate user ID
    if ($userId <= 0) {
        http_response_code(400);
        throw new Exception("Invalid user ID: $userId");
    }

    // Optional: Validate name lengths
    if (empty($firstName) || strlen($firstName) > 255) {
        throw new Exception("First name is required and must be <= 255 chars");
    }
    if (empty($lastName) || strlen($lastName) > 255) {
        throw new Exception("Last name is required and must be <= 255 chars");
    }

    // 3) Check if user exists
    $checkQuery = "SELECT user_id FROM collaboardtable_users WHERE user_id = $1";
    $checkResult = pg_query_params($conn, $checkQuery, [$userId]);
    if (!$checkResult) {
        throw new Exception("Database error checking user: " . pg_last_error($conn));
    }
    if (pg_num_rows($checkResult) === 0) {
        throw new Exception("User with ID $userId was not found");
    }

    // 4) Handle new profile photo if provided
    $hasPhotoUpload = (
        isset($_FILES['profilePhoto']) &&
        $_FILES['profilePhoto']['error'] === UPLOAD_ERR_OK &&
        !empty($_FILES['profilePhoto']['tmp_name'])
    );

    if ($hasPhotoUpload) {
        // Read file data
        $tmpName  = $_FILES['profilePhoto']['tmp_name'];
        $fileData = file_get_contents($tmpName);
        if ($fileData === false) {
            throw new Exception("Failed to read uploaded file");
        }

        // Build query (storing binary directly in the bytea column)
        $query = "
            UPDATE collaboardtable_users
            SET first_name = $1,
                last_name  = $2,
                profile_picture = $3
            WHERE user_id  = $4
        ";
        $result = pg_query_params($conn, $query, [
            $firstName,
            $lastName,
            $fileData,   // Store raw binary data directly
            $userId
        ]);
    } else {
        // No photo upload, just update name fields
        $query = "
            UPDATE collaboardtable_users
            SET first_name = $1,
                last_name  = $2
            WHERE user_id  = $3
        ";
        $result = pg_query_params($conn, $query, [
            $firstName,
            $lastName,
            $userId
        ]);
    }

    // 5) Check query result
    if (!$result) {
        throw new Exception("Database error updating profile: " . pg_last_error($conn));
    }

    $rowsAffected = pg_affected_rows($result);
    if ($rowsAffected === 0) {
        // Not necessarily an error if nothing changed, but let's throw an exception
        throw new Exception("No changes were made to the profile");
    }

    // 6) Success
    $response["success"] = true;
    $response["message"] = "Profile updated successfully!";
    $response["userId"]  = $userId;

} catch (Exception $e) {
    // Catch & handle any exceptions
    $response["success"] = false;
    $response["message"] = $e->getMessage();
}

// Return JSON
echo json_encode($response);

// Close connection
if (isset($conn)) {
    pg_close($conn);
}
exit;
