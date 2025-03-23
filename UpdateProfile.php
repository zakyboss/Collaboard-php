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
    
    // Basic validation
    if ($userId <= 0) {
        http_response_code(400);
        throw new Exception("Invalid user ID.");
    }
    
    // 2) Handle new profile photo if provided
    $hasProfilePhoto = false;
    $fileData = null;
    
    if (isset($_FILES['profilePhoto']) && $_FILES['profilePhoto']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['profilePhoto']['tmp_name'];
        $fileData = file_get_contents($tmpName);
        $hasProfilePhoto = true;
    }
    
    // 3) Build and execute the SQL query
    if ($hasProfilePhoto) {
        // With profile photo update
        $query = "
            UPDATE collaboardtable_users
            SET first_name = $1,
                last_name = $2,
                profile_picture = $3
            WHERE user_id = $4
            RETURNING user_id
        ";
        
        $result = pg_query_params(
            $conn,
            $query,
            [$firstName, $lastName, $fileData, $userId]
        );
    } else {
        // Without profile photo update
        $query = "
            UPDATE collaboardtable_users
            SET first_name = $1,
                last_name = $2
            WHERE user_id = $3
            RETURNING user_id
        ";
        
        $result = pg_query_params(
            $conn,
            $query,
            [$firstName, $lastName, $userId]
        );
    }
    
    if (!$result) {
        http_response_code(500);
        throw new Exception("Failed to update profile: " . pg_last_error($conn));
    }
    
    // Check if any rows were affected
    $rowsAffected = pg_affected_rows($result);
    if ($rowsAffected === 0) {
        throw new Exception("No user found with ID: $userId");
    }
    
    // If the query succeeded
    $response["success"] = true;
    $response["message"] = "Profile updated successfully!";
    
} catch (Exception $e) {
    // If anything goes wrong, catch the exception and set an error message
    $response["success"] = false;
    $response["message"] = $e->getMessage();
    $response["details"] = pg_last_error($conn);
    
    // Log the error for server-side debugging
    error_log("Profile update error: " . $e->getMessage() . " - " . pg_last_error($conn));
}

// 5) Return JSON
echo json_encode($response);
pg_close($conn);
exit;