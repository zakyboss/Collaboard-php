<?php
// File: Back-end/ApproveVolunteer.php

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

    $volunteer_id = isset($data["volunteer_id"]) ? intval($data["volunteer_id"]) : 0;
    $approve      = isset($data["approve"]) ? intval($data["approve"]) : 0;

    if ($volunteer_id <= 0) {
        throw new Exception("Invalid volunteer ID.");
    }

    // Update is_approved in collaboardtable_volunteers
    $stmt = $conn->prepare("
        UPDATE collaboardtable_volunteers
        SET is_approved = ?
        WHERE volunteer_id = ?
    ");
    $stmt->bind_param("ii", $approve, $volunteer_id);

    if (!$stmt->execute()) {
        throw new Exception("Failed to update volunteer approval status.");
    }

    $response["success"] = true;
    $response["message"] = "Volunteer status updated!";
} catch (Exception $e) {
    $response["message"] = $e->getMessage();
}

echo json_encode($response);
$conn->close();
exit();
