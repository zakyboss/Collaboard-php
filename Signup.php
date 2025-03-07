

<?php
// File: Collaboard-php/Signup.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/db.php';

$data = json_decode(file_get_contents("php://input"), true);
$response = ["success" => false, "message" => ""];

$first_name = htmlspecialchars(trim($data["first_name"] ?? ""));
$last_name = htmlspecialchars(trim($data["last_name"] ?? ""));
$email = filter_var($data["email"] ?? "", FILTER_SANITIZE_EMAIL);
$password = $data["password"] ?? "";

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password) || empty($first_name) || empty($last_name)) {
    http_response_code(400);
    die(json_encode(["success" => false, "message" => "Invalid input data."]));
}

$hashedPassword = password_hash($password, PASSWORD_BCRYPT);
$query = "INSERT INTO users (first_name, last_name, email, password) VALUES ($1, $2, $3, $4) RETURNING id";
$result = pg_query_params($conn, $query, [$first_name, $last_name, $email, $hashedPassword]);

if ($result) {
    $row = pg_fetch_assoc($result);
    $response = ["success" => true, "message" => "User registered successfully!", "user_id" => $row["id"]];
} else {
    http_response_code(500);
    $response["message"] = "Signup failed! Email might already exist.";
}

echo json_encode($response);
pg_close($conn);
exit();
?>
