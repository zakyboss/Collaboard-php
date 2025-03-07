<?php
// File: Collaboard-php/Volunteer.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/db.php';

$response = ["success" => false, "message" => ""];

try {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data) {
        throw new Exception("No data received.");
    }

    $proj_id = intval($data["proj_id"] ?? 0);
    $user_id = intval($data["user_id"] ?? 0);
    $github_username = htmlspecialchars(trim($data["github_username"] ?? ""));
    $task_id = isset($data["task_id"]) ? intval($data["task_id"]) : null;

    if ($proj_id <= 0 || $user_id <= 0 || empty($github_username)) {
        throw new Exception("Invalid volunteer data.");
    }

    $queryCheck = "SELECT volunteer_id FROM collaboardtable_volunteers WHERE proj_id = $1 AND user_id = $2 LIMIT 1";
    $resultCheck = pg_query_params($conn, $queryCheck, [$proj_id, $user_id]);
    
    if (pg_num_rows($resultCheck) > 0) {
        throw new Exception("You have already volunteered for this project.");
    }

    $queryInsert = "INSERT INTO collaboardtable_volunteers (proj_id, user_id, github_username, task_id) VALUES ($1, $2, $3, $4)";
    $resultInsert = pg_query_params($conn, $queryInsert, [$proj_id, $user_id, $github_username, $task_id]);

    if (!$resultInsert) {
        throw new Exception("Failed to submit volunteer request.");
    }

    $response = ["success" => true, "message" => "Volunteer request submitted!"];
} catch (Exception $e) {
    http_response_code(400);
    $response["message"] = $e->getMessage();
}

echo json_encode($response);
pg_close($conn);
exit();
?>