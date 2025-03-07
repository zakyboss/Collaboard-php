<?php
// File: Back-end/CreateProject.php

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
    $projectName     = isset($_POST['projectName']) ? trim($_POST['projectName']) : "";
    $description     = isset($_POST['description']) ? trim($_POST['description']) : "";
    $tasksJSON       = isset($_POST['tasks']) ? $_POST['tasks'] : "[]";
    $tasks           = json_decode($tasksJSON, true);
    $devNeeded       = isset($_POST['devNeeded']) ? (int)$_POST['devNeeded'] : 1;
    $daysToComplete  = isset($_POST['daysToComplete']) ? (int)$_POST['daysToComplete'] : 30;
    $userId          = isset($_POST['userId']) ? (int)$_POST['userId'] : 0;

    if ($userId <= 0) {
        throw new Exception("You must be logged in to create a project.");
    }

    if (empty($projectName) || empty($description)) {
        throw new Exception("Project name and description are required.");
    }

    // Thumbnail upload
    $thumbnailName = null;
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $tmpName      = $_FILES['thumbnail']['tmp_name'];
        $originalName = $_FILES['thumbnail']['name'];
        $targetDir    = __DIR__ . "/uploads/";

        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $uniqueName  = time() . "_" . preg_replace("/[^a-zA-Z0-9_\.-]/", "_", $originalName);
        $targetFile  = $targetDir . $uniqueName;

        if (move_uploaded_file($tmpName, $targetFile)) {
            $thumbnailName = $uniqueName;
        }
    }

    // PDF upload
    $pdfFileName = null;
    if (isset($_FILES['pdfFile']) && $_FILES['pdfFile']['error'] === UPLOAD_ERR_OK) {
        $tmpName      = $_FILES['pdfFile']['tmp_name'];
        $originalName = $_FILES['pdfFile']['name'];
        $targetDir    = __DIR__ . "/uploads/";

        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $uniqueName  = time() . "_" . preg_replace("/[^a-zA-Z0-9_\.-]/", "_", $originalName);
        $targetFile  = $targetDir . $uniqueName;

        if (move_uploaded_file($tmpName, $targetFile)) {
            $pdfFileName = $uniqueName;
        }
    }

    // Insert into collaboardtable_projects
    $query = "
        INSERT INTO collaboardtable_projects 
        (user_id, proj_name, description, thumbnail, dev_needed, days_to_complete, pdf_file)
        VALUES ($1, $2, $3, $4, $5, $6, $7) RETURNING proj_id
    ";
    $params = [$userId, $projectName, $description, $thumbnailName, $devNeeded, $daysToComplete, $pdfFileName];
    $result = pg_query_params($conn, $query, $params);

    if (!$result) {
        throw new Exception("Failed to create project. Please try again.");
    }

    $row = pg_fetch_assoc($result);
    $projectId = $row['proj_id'];

    // Insert tasks
    if (is_array($tasks)) {
        foreach ($tasks as $task) {
            $taskName = isset($task['task_name']) ? trim($task['task_name']) : "";
            $duration = isset($task['duration']) ? trim($task['duration']) : "";
            if (!empty($taskName)) {
                $taskQuery = "
                    INSERT INTO collaboardtable_tasks (proj_id, task_name, duration)
                    VALUES ($1, $2, $3)
                ";
                pg_query_params($conn, $taskQuery, [$projectId, $taskName, $duration]);
            }
        }
    }

    $response["success"] = true;
    $response["message"] = "Project created successfully!";
} catch (Exception $e) {
    $response["message"] = $e->getMessage();
}

echo json_encode($response);
pg_close($conn);
exit();
