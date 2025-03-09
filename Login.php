<?php
// File: Collaboard-php/Login.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once __DIR__ . '/db.php';

// Decode JSON input
$data = json_decode(file_get_contents("php://input"), true);
$response = ["success" => false, "message" => ""];

// Extract and validate inputs (identifier can be email or username)
$identifier = trim($data["identifier"] ?? "");
$password   = trim($data["password"] ?? "");

if (empty($identifier) || empty($password)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Please provide username/email and password."]);
    exit;
}

// Prepare SQL to match either email OR username; include additional fields as needed
$query = "
    SELECT user_id, username, first_name, last_name, email, profile_photo, years_of_experience, password
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
        // Build the full user data object with every detail fetched from the database.
        $response = [
            "success" => true,
            "message" => "Login successful!",
            "userData" => [
                "id"                => (int) $row["user_id"],
                "username"          => htmlspecialchars($row["username"]),
                "firstName"         => htmlspecialchars($row["first_name"]),
                "lastName"          => htmlspecialchars($row["last_name"]),
                "email"             => htmlspecialchars($row["email"]),
                "profilePhoto"      => isset($row["profile_photo"]) ? $row["profile_photo"] : null,
                "yearsOfExperience" => isset($row["years_of_experience"]) ? $row["years_of_experience"] : null
                // Add any additional fields as needed
            ]
        ];
    } else {
        $response["message"] = "Incorrect password.";
    }
} else {
    $response["message"] = "User not found.";
}

// Return JSON response
http_response_code(200);
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>
