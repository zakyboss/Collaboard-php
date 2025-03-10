<?php
// File: Back-end/GetVolunteers.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db.php'; // must define $conn via pg_connect()

$proj_id = isset($_GET['proj_id']) ? intval($_GET['proj_id']) : 0;
$response = ["volunteers" => []];

if ($proj_id > 0) {
    $sql = "
        SELECT v.volunteer_id,
               v.user_id,
               v.github_username,
               v.task_id,
               v.is_approved,
               v.created,
               u.first_name,
               u.last_name,
               u.profile_photo
        FROM collaboardtable_volunteers v
        JOIN collaboardtable_users u ON v.user_id = u.id
        WHERE v.proj_id = $1
        ORDER BY v.created DESC
    ";

    $result = pg_query_params($conn, $sql, [$proj_id]);

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $response["volunteers"][] = $row;
        }
    }
}

echo json_encode($response);
pg_close($conn);
exit();
