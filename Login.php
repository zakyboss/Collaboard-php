<?php
// File: Login.php

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);
ini_set('html_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once __DIR__ . '/db.php';

$content = file_get_contents("php://input");
$data = json_decode($content, true);
if (!is_array($data)) {
    $data = [];
}

$response = ["success" => false, "message" => ""];

$identifier = trim($data["identifier"] ?? "");
$password   = trim($data["password"] ?? "");

if (empty($identifier) || empty($password)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Please provide username/email and password."]);
    exit;
}

try {
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
    
    if ($row = pg_fetch_assoc($result)) {
        if (password_verify($password, $row["password"])) {
            // Convert bytea to base64 data URI
            $profilePhoto = null;
            if (!empty($row["profile_picture"])) {
                $binaryData = pg_unescape_bytea($row["profile_picture"]);
                $base64     = base64_encode($binaryData);
                $profilePhoto = "data:image/png;base64," . $base64;
            }

            $response = [
                "success" => true,
                "message" => "Login successful!",
                "userData" => [
                    "id"                => (int) $row["user_id"],
                    "username"          => htmlspecialchars($row["username"]),
                    "firstName"         => htmlspecialchars($row["first_name"]),
                    "lastName"          => htmlspecialchars($row["last_name"]),
                    "email"             => htmlspecialchars($row["email"]),
                    "profilePhoto"      => $profilePhoto,
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

http_response_code(200);
echo json_encode($response);
pg_close($conn);
exit;
?>
