<?php
// File: Collaboard-php/Login.php

// Disable error display for clean JSON responses
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);
ini_set('html_errors', 0);

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Include database connection
require_once __DIR__ . '/db.php';

// Decode JSON input
$content = file_get_contents("php://input");
$data = json_decode($content, true);

if (!is_array($data)) {
    $data = [];
}

$response = ["success" => false, "message" => ""];

// Extract and validate inputs (identifier can be email or username)
$identifier = trim($data["identifier"] ?? "");
$password   = trim($data["password"] ?? "");

if (empty($identifier) || empty($password)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Please provide username/email and password."]);
    exit;
}

try {
    // Prepare SQL to match either email OR username
    // IMPORTANT: Changed to profile_picture (not profile_photo)
    $query = "
        SELECT user_id, username, first_name, last_name, email, profile_picture, years_of_experience, password
        FROM collaboardtable_users
        WHERE email = $1 OR username = $1
    ";
    
    $result = pg_query_params($conn, $query, [$identifier]);
    
    if ($result === false) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Database query failed."]);
        exit;
    }
    
    // Check if user was found
    if ($row = pg_fetch_assoc($result)) {
        // Verify password
        if (password_verify($password, $row["password"])) {
            $response = [
                "success" => true,
                "message" => "Login successful!",
                "userData" => [
                    "id"                => (int) $row["user_id"],
                    "username"          => htmlspecialchars($row["username"]),
                    "firstName"         => htmlspecialchars($row["first_name"]),
                    "lastName"          => htmlspecialchars($row["last_name"]),
                    "email"             => htmlspecialchars($row["email"]),
                    "profilePhoto"      => isset($row["profile_picture"]) ? $row["profile_picture"] : null,
                    "yearsOfExperience" => isset($row["years_of_experience"]) ? (int)$row["years_of_experience"] : null
                ]
            ];
        } else {
            $response["message"] = "Incorrect password.";
        }
    } else {
        $response["message"] = "User not found.";
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "An error occurred during login."]);
    exit;
}

// Return the response
http_response_code(200);
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>