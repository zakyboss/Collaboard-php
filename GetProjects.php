<?php
// File: Collaboard-php/GetProjects.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db.php'; // $conn = pg_connect(...)

$response = ["projects" => []];

$sql = "
    SELECT proj_id, user_id, proj_name, description, dev_needed, days_to_complete, thumbnail, pdf_file, whatsapp_link
    FROM collaboardtable_projects
    ORDER BY proj_id DESC
";

$result = pg_query($conn, $sql);
if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        // Convert thumbnail bytea to base64
        $thumbnailBase64 = null;
        if (!empty($row["thumbnail"])) {
            $binaryData = pg_unescape_bytea($row["thumbnail"]);
            $base64     = base64_encode($binaryData);
            $thumbnailBase64 = "data:image/png;base64," . $base64;
        }

        $response["projects"][] = [
            "proj_id"         => $row["proj_id"],
            "user_id"         => $row["user_id"],
            "proj_name"       => $row["proj_name"],
            "description"     => $row["description"],
            "dev_needed"      => $row["dev_needed"],
            "days_to_complete"=> $row["days_to_complete"],
            "thumbnail"       => $thumbnailBase64,
            "pdf_file"        => $row["pdf_file"],
            "whatsapp_link"   => $row["whatsapp_link"]
        ];
    }
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Failed to fetch projects."]);
    exit();
}

echo json_encode($response);
pg_close($conn);
exit();
