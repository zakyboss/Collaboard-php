<?php
// File: Back-end/GetTasks.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db.php'; // Must define $conn as a pg_connect resource

$proj_id = isset($_GET['proj_id']) ? intval($_GET['proj_id']) : 0;
$response = ["tasks" => []];

if ($proj_id > 0) {
    // Make sure we also select 'is_done' if you want to track done/undone tasks
    $sql = "
        SELECT
            task_id,
            proj_id,
            task_name,
            task_description,
            status,
            priority,
            due_date,
            created_at,
            is_done
        FROM collaboardtable_tasks
        WHERE proj_id = $1
        ORDER BY task_id ASC
    ";
    $result = pg_query_params($conn, $sql, [$proj_id]);

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $response["tasks"][] = $row;
        }
    }
}

echo json_encode($response);
pg_close($conn);
exit();
