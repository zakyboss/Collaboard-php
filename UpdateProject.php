<?php
// File: Back-end/UpdateProject.php

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

    $proj_id     = isset($data["proj_id"]) ? intval($data["proj_id"]) : 0;
    $projectName = isset($data["projectName"]) ? trim($data["projectName"]) : "";
    $description = isset($data["description"]) ? trim($data["description"]) : "";
    $tasks       = isset($data["tasks"]) ? $data["tasks"] : [];

    if ($proj_id <= 0) {
        throw new Exception("Invalid project ID.");
    }

    // Update project name and description
    $stmt = $conn->prepare("UPDATE collaboardtable_projects SET proj_name = ?, description = ? WHERE proj_id = ?");
    $stmt->bind_param("ssi", $projectName, $description, $proj_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to update project.");
    }
    $stmt->close();

    // Remove old tasks
    $deleteStmt = $conn->prepare("DELETE FROM collaboardtable_tasks WHERE proj_id = ?");
    $deleteStmt->bind_param("i", $proj_id);
    $deleteStmt->execute();
    $deleteStmt->close();

    // Insert updated tasks
    foreach ($tasks as $task) {
        $taskName = isset($task['task_name']) ? trim($task['task_name']) : "";
        $duration = isset($task['duration']) ? trim($task['duration']) : "";
        if (!empty($taskName)) {
            $insertStmt = $conn->prepare("INSERT INTO collaboardtable_tasks (proj_id, task_name, duration) VALUES (?, ?, ?)");
            $insertStmt->bind_param("iss", $proj_id, $taskName, $duration);
            $insertStmt->execute();
            $insertStmt->close();
        }
    }

    $response["success"] = true;
    $response["message"] = "Project updated successfully!";
} catch (Exception $e) {
    $response["message"] = $e->getMessage();
}

echo json_encode($response);
$conn->close();
exit();
