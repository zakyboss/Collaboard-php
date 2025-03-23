<?php
// File: UpdateProfile.php

// For development, show errors; in production, set these to 0
ini_set('display_errors', 0); // Changed to 0 for production
ini_set('display_startup_errors', 0); // Changed to 0 for production
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
    // Log incoming request data for debugging (remove in production)
    $requestLog = [
        'POST' => $_POST,
        'FILES' => isset($_FILES) ? array_keys($_FILES) : []
    ];
    file_put_contents('update_profile_log.txt', date('Y-m-d H:i:s') . ': ' . json_encode($requestLog) . PHP_EOL, FILE_APPEND);

    // 1) Include database connection
    require_once 'db.php';
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // 2) Grab form data (multipart/form-data)
    $userId = isset($_POST['userId']) ? $_POST['userId'] : null;
    
    // Ensure userId is a valid integer
    if (!is_numeric($userId)) {
        throw new Exception("User ID must be a number, received: " . gettype($userId));
    }
    
    $userId = intval($userId);
    $firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : "";
    $lastName = isset($_POST['lastName']) ? trim($_POST['lastName']) : "";

    // Validate user ID
    if ($userId <= 0) {
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
        $tmpName = $_FILES['profilePhoto']['tmp_name'];
        $fileData = file_get_contents($tmpName);
        if ($fileData === false) {
            throw new Exception("Failed to read uploaded file");
        }

        // Build query (storing binary directly in the bytea column)
        $query = "
            UPDATE collaboardtable_users
            SET first_name = $1,
                last_name = $2,
                profile_picture = $3
            WHERE user_id = $4
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
                last_name = $2
            WHERE user_id = $3
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
        // Not necessarily an error if nothing changed
        $response["success"] = true;
        $response["message"] = "No changes were made to the profile";
    } else {
        // 6) Success
        $response["success"] = true;
        $response["message"] = "Profile updated successfully!";
    }
    
    $response["userId"] = $userId;

} catch (Exception $e) {
    // Catch & handle any exceptions
    http_response_code(400); // Set appropriate HTTP status code
    $response["success"] = false;
    $response["message"] = $e->getMessage();
    
    // Log errors for debugging (remove in production)
    file_put_contents('update_profile_errors.txt', date('Y-m-d H:i:s') . ': ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
}

// Return JSON
echo json_encode($response);

// Close connection
if (isset($conn)) {
    pg_close($conn);
}
exit;