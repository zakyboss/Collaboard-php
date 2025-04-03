<?php

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

    $task_id = isset($data["task_id"]) ? intval($data["task_id"]) : 0;
    $is_done = isset($data["is_done"]) ? intval($data["is_done"]) : 0;

    if ($task_id <= 0) {
        throw new Exception("Invalid task ID.");
    }

    // Use pg_query_params for PostgreSQL
    $updateQuery = "
        UPDATE collaboardtable_tasks
        SET is_done = $1
        WHERE task_id = $2
    ";
    $result = pg_query_params($conn, $updateQuery, [$is_done, $task_id]);
    if (!$result) {
        throw new Exception('Failed to update task status: ' . pg_last_error($conn));
    }

    $response["success"] = true;
    $response["message"] = "Task status updated!";
} catch (Exception $e) {
    $response["message"] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
pg_close($conn);
exit();
