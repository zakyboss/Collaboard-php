<?php 
// File: Collaboard-php/CreateProject.php 
 
// Enable error reporting (remove in production) 
ini_set('display_errors', 1); 
ini_set('display_startup_errors', 1); 
error_reporting(E_ALL); 
 
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: POST, OPTIONS"); 
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With"); 
header("Content-Type: application/json; charset=UTF-8"); 
 
// Handle preflight OPTIONS 
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    exit(0); 
} 
 
require_once 'db.php'; // $conn = pg_connect(...) 

// Log incoming data for debugging
$log_file = fopen("project_debug.log", "a");
fwrite($log_file, "==== " . date('Y-m-d H:i:s') . " ====\n");
fwrite($log_file, "CONTENT TYPE: " . ($_SERVER["CONTENT_TYPE"] ?? 'none') . "\n");

// 1) Detect multipart/form-data vs. JSON 
$contentType = $_SERVER["CONTENT_TYPE"] ?? ''; 
if (strpos($contentType, 'multipart/form-data') !== false) { 
    $data = $_POST; 
    $thumbnailFile = $_FILES['thumbnail'] ?? null;
    $pdfFile = $_FILES['pdf_file'] ?? null;
    
    // Log form data
    fwrite($log_file, "POST DATA: " . print_r($_POST, true) . "\n");
    fwrite($log_file, "FILES: " . print_r($_FILES, true) . "\n");
} else { 
    $rawData = file_get_contents('php://input');
    $data = json_decode($rawData, true) ?: []; 
    $thumbnailFile = null;
    $pdfFile = null;
    
    // Log JSON data
    fwrite($log_file, "RAW INPUT: " . $rawData . "\n");
    fwrite($log_file, "PARSED DATA: " . print_r($data, true) . "\n");
} 
 
// 2) Extract fields
// For user_id, check both 'user_id' and 'userId' as frontend may use either
$user_id = trim($data["user_id"] ?? $data["userId"] ?? ""); 
$proj_name = htmlspecialchars(trim($data["proj_name"] ?? $data["projName"] ?? "")); 
$description = htmlspecialchars(trim($data["description"] ?? "")); 
$dev_needed = trim($data["dev_needed"] ?? $data["devNeeded"] ?? ""); 
$days_to_complete = trim($data["days_to_complete"] ?? $data["daysToComplete"] ?? ""); 
$whatsapp_link = htmlspecialchars(trim($data["whatsapp_link"] ?? $data["whatsappLink"] ?? ""));

// Convert empty strings to valid values
if ($dev_needed === "") {
    $dev_needed = 0;
}
if ($days_to_complete === "") {
    $days_to_complete = 0;
}

// Log extracted fields
fwrite($log_file, "EXTRACTED FIELDS:\n");
fwrite($log_file, "user_id: $user_id\n");
fwrite($log_file, "proj_name: $proj_name\n");
fwrite($log_file, "description: $description\n");
fwrite($log_file, "dev_needed: $dev_needed\n");
fwrite($log_file, "days_to_complete: $days_to_complete\n");
fwrite($log_file, "whatsapp_link: $whatsapp_link\n");
 
// Validate 
if (empty($user_id) || empty($proj_name)) { 
    fwrite($log_file, "VALIDATION FAILED: Required fields missing\n");
    fclose($log_file);
    
    http_response_code(400); 
    echo json_encode([
        "success" => false, 
        "message" => "Required fields missing.",
        "missing" => [
            "user_id" => empty($user_id),
            "proj_name" => empty($proj_name)
        ]
    ]); 
    exit(); 
} 
 
// 3) Process thumbnail if provided
$thumbnail_data = null; 
if ($thumbnailFile && $thumbnailFile['error'] === UPLOAD_ERR_OK) { 
    $fileData = file_get_contents($thumbnailFile['tmp_name']); 
    $thumbnail_data = pg_escape_bytea($conn, $fileData); 
} 

// Process PDF file if provided
$pdf_file_data = null;
if ($pdfFile && $pdfFile['error'] === UPLOAD_ERR_OK) {
    $pdfData = file_get_contents($pdfFile['tmp_name']);
    $pdf_file_name = htmlspecialchars($pdfFile['name']);
    $pdf_file_data = $pdf_file_name;
}
 
// 4) Insert into collaboardtable_projects
$query = " 
    INSERT INTO collaboardtable_projects  
    (user_id, proj_name, description, dev_needed, days_to_complete, thumbnail, pdf_file, whatsapp_link) 
    VALUES ($1, $2, $3, $4, $5, $6, $7, $8) 
    RETURNING proj_id 
"; 
 
$params = [ 
    $user_id, 
    $proj_name, 
    $description, 
    $dev_needed, 
    $days_to_complete, 
    $thumbnail_data,
    $pdf_file_data,
    $whatsapp_link
]; 

fwrite($log_file, "EXECUTING QUERY: $query\n");
fwrite($log_file, "PARAMS: " . print_r($params, true) . "\n");
 
$result = pg_query_params($conn, $query, $params); 
 
if ($result) { 
    $row = pg_fetch_assoc($result); 
    fwrite($log_file, "SUCCESS: Project created with ID " . $row["proj_id"] . "\n");
    fclose($log_file);
    
    echo json_encode([ 
        "success" => true, 
        "message" => "Project created successfully!", 
        "proj_id" => $row["proj_id"] 
    ]); 
} else { 
    $error = pg_last_error($conn);
    fwrite($log_file, "ERROR: $error\n");
    fclose($log_file);
    
    http_response_code(500); 
    echo json_encode([ 
        "success" => false, 
        "message" => "Error: " . $error 
    ]); 
} 
 
pg_close($conn); 
exit();