<?php
// File: Back-end/Volunteer.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db.php'; // Must define $conn as pg_connect(...)

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

    // 1) Check if user already volunteered
    $checkQuery  = "
        SELECT volunteer_id
        FROM collaboardtable_volunteers
        WHERE proj_id = $1 AND user_id = $2
        LIMIT 1
    ";
    $checkResult = pg_query_params($conn, $checkQuery, [$proj_id, $user_id]);
    if (!$checkResult) {
        throw new Exception("Error checking existing volunteer: " . pg_last_error($conn));
    }
    if (pg_num_rows($checkResult) > 0) {
        throw new Exception("You have already volunteered for this project.");
    }

    // 2) Insert new volunteer row
    $insertQuery = "
        INSERT INTO collaboardtable_volunteers (proj_id, user_id, github_username, task_id)
        VALUES ($1, $2, $3, $4)
    ";
    $insertResult = pg_query_params($conn, $insertQuery, [$proj_id, $user_id, $github_username, $task_id]);
    if (!$insertResult) {
        throw new Exception("Failed to submit volunteer request: " . pg_last_error($conn));
    }

    $response["success"] = true;
    $response["message"] = "Volunteer request submitted!";
} catch (Exception $e) {
    $response["message"] = $e->getMessage();
}

echo json_encode($response);
pg_close($conn);
exit();
