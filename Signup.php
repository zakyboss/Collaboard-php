<?php
// File: Collaboard-php/Signup.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json"); // Add this header to ensure JSON response

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/db.php';

// Read inputs from request body for JSON requests
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

if (strpos($contentType, 'application/json') !== false) {
    // Handle JSON input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
} else if (strpos($contentType, 'multipart/form-data') !== false) {
    // Handle form data (your current approach)
    $data = $_POST;
} else {
    // Fallback
    $data = $_POST;
}

// Read and sanitize input data
$first_name = htmlspecialchars(trim($data["firstName"] ?? ""));
$last_name = htmlspecialchars(trim($data["lastName"] ?? ""));
$email = filter_var($data["email"] ?? "", FILTER_SANITIZE_EMAIL);
$password = trim($data["password"] ?? "");

// Validate inputs
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password) || empty($first_name) || empty($last_name)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid input data."]);
    exit();
}

// Hash the password
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// Check if the email already exists using the correct table name
$checkQuery = "SELECT id FROM collaboardtable_users WHERE email = $1";
$checkResult = pg_query_params($conn, $checkQuery, [$email]);

if (pg_num_rows($checkResult) > 0) {
    http_response_code(409);
    echo json_encode(["success" => false, "message" => "Email already exists!"]);
    exit();
}

// Insert user into the database with the correct table name
$query = "INSERT INTO collaboardtable_users (first_name, last_name, email, password) VALUES ($1, $2, $3, $4) RETURNING id";
$result = pg_query_params($conn, $query, [$first_name, $last_name, $email, $hashedPassword]);

if ($result) {
    $row = pg_fetch_assoc($result);
    echo json_encode(["success" => true, "message" => "User registered successfully!", "user_id" => $row["id"]]);
} else {
    http_response_code(500);
    // Optionally include the error message for debugging purposes (remove in production)
    echo json_encode(["success" => false, "message" => "Signup failed! Error: " . pg_last_error($conn)]);
}

// Close database connection
pg_close($conn);
exit();
?>