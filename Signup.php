<?php
// File: Collaboard-php/Signup.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/db.php';

// Read the incoming JSON data
$data = json_decode(file_get_contents("php://input"), true);
$response = ["success" => false, "message" => ""];

// Validate and sanitize inputs
$first_name = htmlspecialchars(trim($data["first_name"] ?? ""));
$last_name = htmlspecialchars(trim($data["last_name"] ?? ""));
$email = filter_var($data["email"] ?? "", FILTER_SANITIZE_EMAIL);
$password = trim($data["password"] ?? "");

// Check if input fields are valid
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password) || empty($first_name) || empty($last_name)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid input data."]);
    exit();
}

// Hash the password
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// Check if the email already exists
$checkQuery = "SELECT id FROM users WHERE email = $1";
$checkResult = pg_query_params($conn, $checkQuery, [$email]);

if (pg_num_rows($checkResult) > 0) {
    http_response_code(409);
    echo json_encode(["success" => false, "message" => "Email already exists!"]);
    exit();
}

// Insert user into the database
$query = "INSERT INTO users (first_name, last_name, email, password) VALUES ($1, $2, $3, $4) RETURNING id";
$result = pg_query_params($conn, $query, [$first_name, $last_name, $email, $hashedPassword]);

if ($result) {
    $row = pg_fetch_assoc($result);
    echo json_encode(["success" => true, "message" => "User registered successfully!", "user_id" => $row["id"]]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Signup failed!"]);
}

// Close database connection
pg_close($conn);
exit();
?>
