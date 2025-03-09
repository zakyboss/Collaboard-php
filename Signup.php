<?php
// File: Collaboard-php/Signup.php

require_once 'db.php';

// Enable error reporting (development only)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json"); // Ensure JSON response

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Determine the content type of the request
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

// Read the input data based on the Content-Type header
if (strpos($contentType, 'application/json') !== false) {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
} else if (strpos($contentType, 'multipart/form-data') !== false) {
    $data = $_POST;
} else {
    $data = $_POST;
}

// For debugging: log the input data (check your PHP error log)
error_log("Signup.php received data: " . print_r($data, true));

// Read and sanitize input data
$username   = htmlspecialchars(trim($data["username"] ?? ""));
$first_name = htmlspecialchars(trim($data["firstName"] ?? ""));
$last_name  = htmlspecialchars(trim($data["lastName"] ?? ""));
$email      = filter_var($data["email"] ?? "", FILTER_SANITIZE_EMAIL);
$password   = trim($data["password"] ?? "");
$years_of_experience = trim($data["yearsOfExperience"] ?? "");

// Validate required inputs
if (empty($username) || empty($first_name) || empty($last_name) || 
    !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid input data."]);
    exit();
}

// Convert years_of_experience to an integer if provided; otherwise set to NULL
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

// Hash the password
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// Check if the email already exists
$checkQuery = "SELECT user_id FROM collaboardtable_users WHERE email = $1";
$checkResult = pg_query_params($conn, $checkQuery, [$email]);

if (pg_num_rows($checkResult) > 0) {
    http_response_code(409);
    echo json_encode(["success" => false, "message" => "Email already exists!"]);
    exit();
}

// Insert user into the database, including username
$query = "INSERT INTO collaboardtable_users (username, first_name, last_name, email, password, years_of_experience)
          VALUES ($1, $2, $3, $4, $5, $6) RETURNING user_id";
$result = pg_query_params($conn, $query, [
    $username,
    $first_name,
    $last_name,
    $email,
    $hashedPassword,
    $years_of_experience
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
