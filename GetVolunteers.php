<?php
// File: Back-end/GetVolunteers.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db.php'; // Ensure this file uses pg_connect to set $conn

$proj_id = isset($_GET['proj_id']) ? intval($_GET['proj_id']) : 0;
$response = ["volunteers" => []];

if ($proj_id > 0) {
    // Use the correct column name from your users table:
    // If it's "profile_picture", alias it as profile_photo for the frontend
    $sql = "
        SELECT v.volunteer_id,
               v.user_id,
               v.github_username,
               v.task_id,
               v.is_approved,
               v.created,
               u.first_name,
               u.last_name,
               u.profile_picture AS profile_photo
        FROM collaboardtable_volunteers v
        JOIN collaboardtable_users u ON v.user_id = u.user_id
        WHERE v.proj_id = $1
        ORDER BY v.created DESC
    ";

    $result = pg_query_params($conn, $sql, [$proj_id]);
    if (!$result) {
        // Capture and output the PostgreSQL error for debugging
        $error = pg_last_error($conn);
        echo json_encode(["success" => false, "error" => $error]);
        pg_close($conn);
        exit();
    }

    while ($row = pg_fetch_assoc($result)) {
        $response["volunteers"][] = $row;
    }
}

echo json_encode($response);
pg_close($conn);
exit();
