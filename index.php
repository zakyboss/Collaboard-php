<?php
header("Content-Type: application/json");

$response = [
    "message" => "Welcome to the Collaboard PHP Backend API!",
    "endpoints" => [
        "Signup" => "/Signup.php",
        "Login" => "/Login.php",
        "Get Projects" => "/GetProjects.php",
        "Get Tasks" => "/GetTasks.php",
        "Get Volunteers" => "/GetVolunteers.php",
        "Create Project" => "/CreateProject.php",
        "Update Profile" => "/UpdateProfile.php",
        "Approve Volunteer" => "/ApproveVolunteer.php"
    ],
    "status" => "Running"
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>
