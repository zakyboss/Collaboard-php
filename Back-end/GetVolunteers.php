<?php
// File: Back-end/GetVolunteers.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db.php';

$proj_id = isset($_GET['proj_id']) ? intval($_GET['proj_id']) : 0;
$response = ["volunteers" => []];

if ($proj_id > 0) {
    $sql = "
        SELECT v.volunteer_id, v.user_id, v.github_username, v.task_id, v.is_approved, v.created,
               u.first_name, u.last_name, u.profile_photo
        FROM collaboardtable_volunteers v
        JOIN users u ON v.user_id = u.id
        WHERE v.proj_id = ?
        ORDER BY v.created DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $proj_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $response["volunteers"][] = $row;
    }
    $stmt->close();
}

echo json_encode($response);
$conn->close();
exit();
