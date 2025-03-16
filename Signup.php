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

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db.php'; // $conn = pg_connect(...)

// Determine content type (multipart/form-data or JSON)
$contentType = $_SERVER["CONTENT_TYPE"] ?? '';
if (strpos($contentType, 'multipart/form-data') !== false) {
    // Data comes from $_POST + $_FILES
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

// Convert years_of_experience
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

// Read raw image data if provided
$profile_picture_data = null;
if ($profilePhotoFile && $profilePhotoFile['error'] === UPLOAD_ERR_OK) {
    // Get file contents
    $fileData = file_get_contents($profilePhotoFile['tmp_name']);
    // Escape for PostgreSQL
    $escapedData = pg_escape_bytea($conn, $fileData);
    $profile_picture_data = $escapedData;
}

// Hash the password
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// Check if email already exists
$checkQuery = "SELECT user_id FROM collaboardtable_users WHERE email = $1";
$checkResult = pg_query_params($conn, $checkQuery, [$email]);
if (pg_num_rows($checkResult) > 0) {
    http_response_code(409);
    echo json_encode(["success" => false, "message" => "Email already exists!"]);
    exit();
}

// Insert new user, storing image in bytea column
$query = "
    INSERT INTO collaboardtable_users 
    (username, first_name, last_name, email, password, years_of_experience, profile_picture)
    VALUES ($1, $2, $3, $4, $5, $6, decode($7, 'escape'))
    RETURNING user_id
";
$params = [
    $username,
    $first_name,
    $last_name,
    $email,
    $hashedPassword,
    $years_of_experience,
    $profile_picture_data // can be null if no file
];
$result = pg_query_params($conn, $query, $params);

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
