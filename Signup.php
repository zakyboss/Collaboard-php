<?php
// File: Back-end/Signup.php

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Include database connection
require_once 'db.php';

// Prepare a JSON response
$response = ["success" => false, "message" => ""];

// Because we sent multipart/form-data, we get data from $_POST and file from $_FILES
try {
    $firstName         = isset($_POST['firstName']) ? trim($_POST['firstName']) : "";
    $lastName          = isset($_POST['lastName']) ? trim($_POST['lastName']) : "";
    $email             = isset($_POST['email']) ? trim($_POST['email']) : "";
    $password          = isset($_POST['password']) ? $_POST['password'] : "";
    $yearsOfExperience = isset($_POST['yearsOfExperience']) ? (int)$_POST['yearsOfExperience'] : 0;

    // Basic validation
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
        throw new Exception("All required fields must be filled!");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format!");
    }

    // Check if the email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        throw new Exception("Email already registered!");
    }
    $stmt->close();

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // OPTIONAL: Handle file upload if profilePhoto is present
    // Example: storing the file in a "uploads" folder
    $profilePhotoName = null;
    if (isset($_FILES['profilePhoto']) && $_FILES['profilePhoto']['error'] === UPLOAD_ERR_OK) {
        $tmpName      = $_FILES['profilePhoto']['tmp_name'];
        $originalName = $_FILES['profilePhoto']['name'];
        $targetDir    = __DIR__ . "/uploads/";

        // Create the folder if it doesn't exist
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // Construct a unique filename
        $uniqueName  = time() . "_" . preg_replace("/[^a-zA-Z0-9_\.-]/", "_", $originalName);
        $targetFile  = $targetDir . $uniqueName;

        if (move_uploaded_file($tmpName, $targetFile)) {
            $profilePhotoName = $uniqueName;
        }
    }

    // Insert into the database
    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, years_experience, profile_photo) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "ssssis",
        $firstName,
        $lastName,
        $email,
        $hashedPassword,
        $yearsOfExperience,
        $profilePhotoName
    );

    if ($stmt->execute()) {
        $response["success"] = true;
        $response["message"] = "Registration successful!";
    } else {
        throw new Exception("Registration failed. Please try again.");
    }

    $stmt->close();
} catch (Exception $e) {
    $response["message"] = $e->getMessage();
}

// Close DB connection
$conn->close();

// Return JSON
echo json_encode($response);
exit();
?>
