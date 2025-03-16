<?php
// File: Collaboard-php/Signup.php

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

require_once 'db.php'; // pg_connect()

// 1) Detect if multipart/form-data or JSON
$contentType = $_SERVER["CONTENT_TYPE"] ?? '';
if (strpos($contentType, 'multipart/form-data') !== false) {
    $data = $_POST;
    $profilePhotoFile = $_FILES['profilePhoto'] ?? null;
} else {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $profilePhotoFile = null;
}

// 2) Extract & validate fields
$username   = htmlspecialchars(trim($data["username"] ?? ""));
$first_name = htmlspecialchars(trim($data["firstName"] ?? ""));
$last_name  = htmlspecialchars(trim($data["lastName"] ?? ""));
$email      = filter_var($data["email"] ?? "", FILTER_SANITIZE_EMAIL);
$password   = trim($data["password"] ?? "");
$years_of_experience = trim($data["yearsOfExperience"] ?? "");

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

// 3) Read raw image data (if a file is provided)
$profile_picture_data = null;
if ($profilePhotoFile && $profilePhotoFile['error'] === UPLOAD_ERR_OK) {
    // Read the file contents into a variable
    $fileData = file_get_contents($profilePhotoFile['tmp_name']);
    // Escape for PostgreSQL
    $escapedData = pg_escape_bytea($fileData);
    $profile_picture_data = $escapedData; 
}

// 4) Hash the password
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// 5) Check if the email already exists
$checkQuery = "SELECT user_id FROM collaboardtable_users WHERE email = $1";
$checkResult = pg_query_params($conn, $checkQuery, [$email]);
if (pg_num_rows($checkResult) > 0) {
    http_response_code(409);
    echo json_encode(["success" => false, "message" => "Email already exists!"]);
    exit();
}

// 6) Insert new user, storing image as bytea with decode(..., 'escape')
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
