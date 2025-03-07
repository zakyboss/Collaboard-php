<?php
// File: Collaboard-php/GetProjects.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/db.php';

$response = ["projects" => []];

$sql = "SELECT proj_id, user_id, proj_name, description, thumbnail, dev_needed,
               days_to_complete, pdf_file, created
        FROM collaboardtable_projects
        ORDER BY proj_id DESC";

$result = pg_query($conn, $sql);

if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $response["projects"][] = $row;
    }
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Failed to fetch projects."]);
    exit();
}

echo json_encode($response);
pg_close($conn);
exit();
?>
