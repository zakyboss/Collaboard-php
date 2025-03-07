<?php
// File: Back-end/Login.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db.php';

$data = json_decode(file_get_contents("php://input"), true);
$response = ["success" => false, "message" => ""];

// Grab data from JSON
$email = isset($data["email"]) ? $data["email"] : "";
$password = isset($data["password"]) ? $data["password"] : "";

// Basic query
$query = "SELECT id, first_name, last_name, email, password FROM users WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Verify hashed password
    if (password_verify($password, $row["password"])) {
        $response["success"] = true;
        $response["message"] = "Login successful!";
        // Return any user data you want to store in Zustand or Redux
        $response["userData"] = [
            "id"        => $row["id"],
            "firstName" => $row["first_name"],
            "lastName"  => $row["last_name"],
            "email"     => $row["email"]
        ];
    } else {
        $response["message"] = "Incorrect password.";
    }
} else {
    $response["message"] = "User not found.";
}

echo json_encode($response);
$conn->close();
exit();
?>
