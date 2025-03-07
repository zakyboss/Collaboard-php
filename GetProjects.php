<?php
// File: Back-end/GetProjects.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db.php';

$response = ["projects" => []];

$sql = "SELECT proj_id, user_id, proj_name, description, thumbnail, dev_needed,
               days_to_complete, pdf_file, created
        FROM collaboardtable_projects
        ORDER BY proj_id DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $response["projects"][] = $row;
    }
}

echo json_encode($response);
$conn->close();
exit();
