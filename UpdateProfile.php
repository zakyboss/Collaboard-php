<?php
// File: UpdateProfile.php

// Enable detailed error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set CORS and content type headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle CORS preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Initialize response array
$response = ["success" => false, "message" => ""];

// Debugging: Log incoming request data
error_log("UpdateProfile.php - POST data received: " . print_r($_POST, true));
if (isset($_FILES['profilePhoto'])) {
    error_log("UpdateProfile.php - File upload info: " . print_r($_FILES['profilePhoto'], true));
}

try {
    // Include database connection
    require_once 'db.php';
    
    // Validate database connection
    if (!isset($conn) || !$conn) {
        throw new Exception("Database connection failed");
    }
    
    // Get form data with validation
    $userId = isset($_POST['userId']) ? intval($_POST['userId']) : 0;
    $firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : "";
    $lastName = isset($_POST['lastName']) ? trim($_POST['lastName']) : "";
    
    // Debug
    error_log("Parsed User ID: $userId");
    
    // Validate user ID
    if ($userId <= 0) {
        throw new Exception("Invalid user ID: $userId");
    }
    
    // Validate first and last name
    if (empty($firstName) || strlen($firstName) > 255) {
        throw new Exception("First name is required and must be less than 255 characters");
    }
    
    if (empty($lastName) || strlen($lastName) > 255) {
        throw new Exception("Last name is required and must be less than 255 characters");
    }
    
    // First check if user exists
    $checkQuery = "SELECT user_id FROM collaboardtable_users WHERE user_id = $1";
    $checkResult = pg_query_params($conn, $checkQuery, [$userId]);
    
    if (!$checkResult) {
        throw new Exception("Database error checking user: " . pg_last_error($conn));
    }
    
    if (pg_num_rows($checkResult) === 0) {
        throw new Exception("User with ID $userId was not found");
    }
    
    // Determine if we're updating with or without a photo
    $hasPhotoUpload = isset($_FILES['profilePhoto']) && 
                      $_FILES['profilePhoto']['error'] === UPLOAD_ERR_OK && 
                      !empty($_FILES['profilePhoto']['tmp_name']);
    
    if ($hasPhotoUpload) {
        // Profile photo was uploaded
        $tmpName = $_FILES['profilePhoto']['tmp_name'];
        $fileData = file_get_contents($tmpName);
        
        if ($fileData === false) {
            throw new Exception("Failed to read uploaded file");
        }
        
        // PostgreSQL binary data handling
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
            $fileData, // PostgreSQL can handle binary data directly
            $userId
        ]);
    } else {
        // No profile photo update, just update text fields
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
    
    // Check query result
    if (!$result) {
        throw new Exception("Database error updating profile: " . pg_last_error($conn));
    }
    
    // Check if any rows were affected
    $rowsAffected = pg_affected_rows($result);
    
    if ($rowsAffected === 0) {
        throw new Exception("No changes were made to the profile");
    }
    
    // Success!
    $response["success"] = true;
    $response["message"] = "Profile updated successfully!";
    $response["userId"] = $userId;
    
} catch (Exception $e) {
    // Log the error
    error_log("UpdateProfile.php - Error: " . $e->getMessage());
    
    // Get database error if available
    if (isset($conn)) {
        error_log("UpdateProfile.php - DB Error: " . pg_last_error($conn));
    }
    
    // Set response
    $response["success"] = false;
    $response["message"] = "Error updating profile: " . $e->getMessage();
}

// Send response
echo json_encode($response);

// Close connection if it exists
if (isset($conn)) {
    pg_close($conn);
}
exit;