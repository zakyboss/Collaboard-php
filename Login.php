<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once __DIR__ . '/db.php';

$data = json_decode(file_get_contents("php://input"), true);
$response = ["success" => false, "message" => ""];

// Validate email and password input
$email = filter_var($data["email"] ?? "", FILTER_SANITIZE_EMAIL);
$password = $data["password"] ?? "";

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid email or password."]);
    exit;
}

// Secure query execution
$query = "SELECT id, first_name, last_name, email, password FROM users WHERE email = $1::text";
$result = pg_query_params($conn, $query, [$email]);

if ($result === false) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database query failed."]);
    exit;
}

if ($row = pg_fetch_assoc($result)) {
    if (password_verify($password, $row["password"])) {
        $response = [
            "success" => true,
            "message" => "Login successful!",
            "userData" => [
                "id" => (int) $row["id"],
                "firstName" => htmlspecialchars($row["first_name"]),
                "lastName" => htmlspecialchars($row["last_name"]),
                "email" => htmlspecialchars($row["email"])
            ]
        ];
    } else {
        $response["message"] = "Incorrect password.";
    }
} else {
    $response["message"] = "User not found.";
}

http_response_code(200);
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
