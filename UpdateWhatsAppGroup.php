<?php
// File: UpdateWhatsAppGroup.php

// 1) CORS HEADERS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// 2) Handle preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 3) Connect to DB
require_once 'db.php'; // must define $conn = pg_connect(...)

// 4) Parse JSON body
$rawData = file_get_contents("php://input");
$data    = json_decode($rawData, true);

$proj_id       = isset($data["proj_id"]) ? intval($data["proj_id"]) : 0;
$whatsapp_link = trim($data["whatsapp_link"] ?? "");

// 5) Validate
if ($proj_id <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid project ID."]);
    exit();
}

// 6) Update the record
$query  = "UPDATE collaboardtable_projects SET whatsapp_link = $1 WHERE proj_id = $2";
$result = pg_query_params($conn, $query, [$whatsapp_link, $proj_id]);

if (!$result) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => pg_last_error($conn)]);
    exit();
}

// 7) Success
echo json_encode(["success" => true, "message" => "WhatsApp link updated!"]);
pg_close($conn);
exit();
