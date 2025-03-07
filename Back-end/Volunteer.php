<?php
// File: Back-end/Volunteer.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db.php';

$response = ["success" => false, "message" => ""];

try {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data) {
        throw new Exception("No data received.");
    }

    $proj_id         = isset($data["proj_id"]) ? intval($data["proj_id"]) : 0;
    $user_id         = isset($data["user_id"]) ? intval($data["user_id"]) : 0;
    $github_username = isset($data["github_username"]) ? trim($data["github_username"]) : "";
    $task_id         = isset($data["task_id"]) ? intval($data["task_id"]) : null;

    if ($proj_id <= 0 || $user_id <= 0 || empty($github_username)) {
        throw new Exception("Invalid volunteer data.");
    }

    // Check if user already volunteered for this project
    $stmtCheck = $conn->prepare("
        SELECT volunteer_id
        FROM collaboardtable_volunteers
        WHERE proj_id = ? AND user_id = ?
        LIMIT 1
    ");
    $stmtCheck->bind_param("ii", $proj_id, $user_id);
    $stmtCheck->execute();
    $stmtCheck->store_result();
    if ($stmtCheck->num_rows > 0) {
        throw new Exception("You have already volunteered for this project.");
    }
    $stmtCheck->close();

    // Insert new volunteer row
    $stmt = $conn->prepare("
        INSERT INTO collaboardtable_volunteers (proj_id, user_id, github_username, task_id)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("iisi", $proj_id, $user_id, $github_username, $task_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to submit volunteer request.");
    }

    $response["success"] = true;
    $response["message"] = "Volunteer request submitted!";
} catch (Exception $e) {
    $response["message"] = $e->getMessage();
}

echo json_encode($response);
$conn->close();
exit();
