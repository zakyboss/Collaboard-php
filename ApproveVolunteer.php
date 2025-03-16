<?php
// File: ApproveVolunteer.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Preflight for CORS
    http_response_code(200);
    exit(0);
}

require_once 'db.php'; // must define $conn via pg_connect()

$response = ["success" => false, "message" => ""];

try {
    // Read JSON input
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data) {
        throw new Exception("No input data.");
    }

    $volunteer_id = isset($data["volunteer_id"]) ? intval($data["volunteer_id"]) : 0;
    $approve      = isset($data["approve"]) ? intval($data["approve"]) : 0;

    if ($volunteer_id <= 0) {
        throw new Exception("Invalid volunteer_id.");
    }

    // Update collaboardtable_volunteers with the new approval status
    $updateSql = "
        UPDATE collaboardtable_volunteers
        SET is_approved = $1
        WHERE volunteer_id = $2
    ";
    $result = pg_query_params($conn, $updateSql, [$approve, $volunteer_id]);
    if (!$result) {
        throw new Exception("Failed to update volunteer: " . pg_last_error($conn));
    }

    $response["success"] = true;
    $response["message"] = "Volunteer updated successfully!";
} catch (Exception $e) {
    $response["message"] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
pg_close($conn);
exit();
