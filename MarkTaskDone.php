<?php
// File: Back-end/MarkTaskDone.php

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

    $stmt = $conn->prepare("
        UPDATE collaboardtable_tasks
        SET is_done = ?
        WHERE task_id = ?
    ");
    $stmt->bind_param("ii", $is_done, $task_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to update task status.");
    }

    $response["success"] = true;
    $response["message"] = "Task status updated!";
} catch (Exception $e) {
    $response["message"] = $e->getMessage();
}

echo json_encode($response);
$conn->close();
exit();
