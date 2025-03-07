<?php
header("Content-Type: application/json");

$response = [
    "message" => "Welcome to the Collaboard PHP Backend API!",
    "endpoints" => [
        "Signup" => "/Back-end/Signup.php",
        "Login" => "/Back-end/Login.php",
        "Get Projects" => "/Back-end/GetProjects.php",
        "Get Tasks" => "/Back-end/GetTasks.php",
        "Get Volunteers" => "/Back-end/GetVolunteers.php",
        "Create Project" => "/Back-end/CreateProject.php",
        "Update Profile" => "/Back-end/UpdateProfile.php",
        "Approve Volunteer" => "/Back-end/ApproveVolunteer.php"
    ],
    "status" => "Running"
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>
