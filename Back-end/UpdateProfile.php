<?php
// File: Back-end/UpdateProfile.php

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
    // We'll assume we're receiving formData (multipart/form-data) for the photo
    $userId    = isset($_POST['userId']) ? intval($_POST['userId']) : 0;
    $firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : "";
    $lastName  = isset($_POST['lastName']) ? trim($_POST['lastName']) : "";

    if ($userId <= 0) {
        throw new Exception("Invalid user ID.");
    }

    // Handle new profile photo if present
    $profilePhotoName = null;
    if (isset($_FILES['profilePhoto']) && $_FILES['profilePhoto']['error'] === UPLOAD_ERR_OK) {
        $tmpName      = $_FILES['profilePhoto']['tmp_name'];
        $originalName = $_FILES['profilePhoto']['name'];
        $targetDir    = __DIR__ . "/uploads/";

        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $uniqueName  = time() . "_" . preg_replace("/[^a-zA-Z0-9_\.-]/", "_", $originalName);
        $targetFile  = $targetDir . $uniqueName;

        if (move_uploaded_file($tmpName, $targetFile)) {
            $profilePhotoName = $uniqueName;
        }
    }

    // If no photo was uploaded, we won't change the existing photo
    if ($profilePhotoName) {
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, profile_photo = ? WHERE id = ?");
        $stmt->bind_param("sssi", $firstName, $lastName, $profilePhotoName, $userId);
    } else {
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ? WHERE id = ?");
        $stmt->bind_param("ssi", $firstName, $lastName, $userId);
    }

    if (!$stmt->execute()) {
        throw new Exception("Failed to update profile.");
    }

    $response["success"] = true;
    $response["message"] = "Profile updated successfully!";
} catch (Exception $e) {
    $response["message"] = $e->getMessage();
}

echo json_encode($response);
$conn->close();
exit();
