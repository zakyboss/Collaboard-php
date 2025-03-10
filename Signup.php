<?php
// File: Collaboard-php/Signup.php

// Enable error reporting for debugging (remove or disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db.php'; // Ensure this file uses pg_connect() to set $conn

// Determine the content type to handle file uploads vs. JSON
$contentType = $_SERVER["CONTENT_TYPE"] ?? '';

if (strpos($contentType, 'multipart/form-data') !== false) {
    // When files are uploaded, data comes in via $_POST and $_FILES
    $data = $_POST;
    $profilePhotoFile = $_FILES['profilePhoto'] ?? null;
} else {
    // Otherwise, read JSON input
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $profilePhotoFile = null;
}

// Extract and sanitize input fields
$username   = htmlspecialchars(trim($data["username"] ?? ""));
$first_name = htmlspecialchars(trim($data["firstName"] ?? ""));
$last_name  = htmlspecialchars(trim($data["lastName"] ?? ""));
$email      = filter_var($data["email"] ?? "", FILTER_SANITIZE_EMAIL);
$password   = trim($data["password"] ?? "");
$years_of_experience = trim($data["yearsOfExperience"] ?? "");

// Validate required fields
if (
    empty($username) || 
    empty($first_name) || 
    empty($last_name) ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    empty($password)
) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid input data."]);
    exit();
}

// Convert years_of_experience to an integer or set to null
if ($years_of_experience === "") {
    $years_of_experience = null;
} else {
    if (!is_numeric($years_of_experience)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Invalid years of experience."]);
        exit();
    }
    $years_of_experience = (int)$years_of_experience;
}

// Handle file upload for profile photo if provided
$profile_picture = null;
if ($profilePhotoFile && $profilePhotoFile['error'] === UPLOAD_ERR_OK) {
    // Create the uploads directory if it doesn't exist
    $uploadDir = __DIR__ . '/ProfileUploads';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Generate a unique filename and remove spaces (replace with underscores)
    $originalName = basename($profilePhotoFile['name']);
    $uniqueName = uniqid() . '_' . str_replace(' ', '_', $originalName);
    $targetPath = $uploadDir . '/' . $uniqueName;
    
    // Move the file from the temporary location to the uploads folder
    if (move_uploaded_file($profilePhotoFile['tmp_name'], $targetPath)) {
        $profile_picture = $uniqueName;
    }
}

// Hash the password using bcrypt
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// Check if the email already exists
$checkQuery = "SELECT user_id FROM collaboardtable_users WHERE email = $1";
$checkResult = pg_query_params($conn, $checkQuery, [$email]);
if (pg_num_rows($checkResult) > 0) {
    http_response_code(409);
    echo json_encode(["success" => false, "message" => "Email already exists!"]);
    exit();
}

// Insert the new user into collaboardtable_users
$query = "INSERT INTO collaboardtable_users 
          (username, first_name, last_name, email, password, years_of_experience, profile_picture)
          VALUES ($1, $2, $3, $4, $5, $6, $7)
          RETURNING user_id";
$result = pg_query_params($conn, $query, [
    $username,
    $first_name,
    $last_name,
    $email,
    $hashedPassword,
    $years_of_experience,
    $profile_picture
]);

if ($result) {
    $row = pg_fetch_assoc($result);
    echo json_encode([
        "success" => true,
        "message" => "User registered successfully!",
        "user_id" => $row["user_id"]
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Signup failed! Error: " . pg_last_error($conn)
    ]);
}

pg_close($conn);
exit();
