<?php
// File: Back-end/CheckVolunteerStatus.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db.php';

$response = ["isApprovedVolunteer" => false, "message" => ""];

try {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data) {
        throw new Exception("No data received.");
    }

    $proj_id = isset($data["proj_id"]) ? intval($data["proj_id"]) : 0;
    $user_id = isset($data["user_id"]) ? intval($data["user_id"]) : 0;

    if ($proj_id <= 0 || $user_id <= 0) {
        throw new Exception("Invalid data.");
    }

    $stmt = $conn->prepare("
        SELECT volunteer_id 
        FROM collaboardtable_volunteers
        WHERE proj_id = ? AND user_id = ? AND is_approved = 1
        LIMIT 1
    ");
    $stmt->bind_param("ii", $proj_id, $user_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $response["isApprovedVolunteer"] = true;
    }
    $stmt->close();

    $response["message"] = "Check complete.";
} catch (Exception $e) {
    $response["message"] = $e->getMessage();
}

echo json_encode($response);
$conn->close();
exit();
