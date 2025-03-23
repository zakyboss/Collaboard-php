<?php
// File: UpdateProfile.php

// Enable detailed error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Initialize response
$response = ["success" => false, "message" => ""];

// Log incoming request for debugging
error_log("UpdateProfile.php called with POST data: " . print_r($_POST, true));
if (isset($_FILES['profilePhoto'])) {
    error_log("File upload info: " . print_r($_FILES['profilePhoto'], true));
}

try {
    // Make sure db.php exists and connects properly
    if (!file_exists('db.php')) {
        throw new Exception("Database connection file not found");
    }
    require_once 'db.php';
    
    // Check if database connection is working
    if (!isset($conn) || !$conn) {
        throw new Exception("Database connection failed");
    }
    
    // Get and validate form data
    $userId = isset($_POST['userId']) ? (int)$_POST['userId'] : 0;
    $firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : "";
    $lastName = isset($_POST['lastName']) ? trim($_POST['lastName']) : "";
    
    if ($userId <= 0) {
        throw new Exception("Invalid user ID: " . $userId);
    }
    
    // Check if user exists first
    $checkQuery = "SELECT user_id FROM collaboardtable_users WHERE user_id = $1";
    $checkResult = pg_query_params($conn, $checkQuery, [$userId]);
    
    if (!$checkResult || pg_num_rows($checkResult) === 0) {
        throw new Exception("User with ID $userId not found");
    }
    
    // Create base query without profile photo
    $query = "UPDATE collaboardtable_users SET first_name = $1, last_name = $2";
    $params = [$firstName, $lastName];
    $paramIndex = 3; // Next parameter index
    
    // Check if profile photo was uploaded
    if (isset($_FILES['profilePhoto']) && $_FILES['profilePhoto']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['profilePhoto']['tmp_name'];
        
        // Use PostgreSQL's built-in function for bytea data
        $query .= ", profile_picture = $" . $paramIndex;
        $params[] = file_get_contents($tmpName);
    }
    
    // Finish query
    $query .= " WHERE user_id = $" . $paramIndex;
    $params[] = $userId;
    
    // Execute update query
    $result = pg_query_params($conn, $query, $params);
    
    if (!$result) {
        throw new Exception("Database error: " . pg_last_error($conn));
    }
    
    // Success!
    $response["success"] = true;
    $response["message"] = "Profile updated successfully";
    
} catch (Exception $e) {
    // Log the error and return it
    $error = $e->getMessage();
    error_log("Profile update error: " . $error);
    
    if (isset($conn)) {
        error_log("Database error: " . pg_last_error($conn));
    }
    
    $response["success"] = false;
    $response["message"] = "Error updating profile: " . $error;
}

// Return response
echo json_encode($response);

// Close connection if it exists
if (isset($conn)) {
    pg_close($conn);
}
exit;