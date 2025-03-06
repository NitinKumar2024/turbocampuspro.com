<?php
// api_handler.php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers to handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// API base URL
define('API_BASE_URL', 'https://smarteduai.turbocampuspro.com');

function handleExtractSyllabus() {
    $curl = curl_init();

    // Prepare the file upload
    $file = $_FILES['syllabus_file'];
    if (!$file) {
        http_response_code(400);
        echo json_encode(['error' => 'No file uploaded']);
        return;
    }

    // Create CURLFile object
    $cfile = new CURLFile(
        $file['tmp_name'],
        $file['type'],
        $file['name']
    );

    // Prepare form data
    $postData = [
        'file' => $cfile,
        'branch_code' => $_POST['branch_code'],
        'semester' => $_POST['semester'],
        'college_code' => $_POST['college_code'],
        'known_subjects' => $_POST['known_subjects']
    ];

    curl_setopt_array($curl, [
        CURLOPT_URL => API_BASE_URL . '/api/extract-syllabus',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_SSL_VERIFYPEER => false, // Only for development
        CURLOPT_SSL_VERIFYHOST => false  // Only for development
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if (curl_errno($curl)) {
        http_response_code(500);
        echo json_encode(['error' => 'Curl error: ' . curl_error($curl)]);
        return;
    }

    curl_close($curl);
    
    // Forward the API response
    http_response_code($httpCode);
    echo $response;
}

function handleSaveSyllabus() {
    $curl = curl_init();

    // Get POST data
    $postData = json_decode(file_get_contents('php://input'), true);

    if (!$postData) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data']);
        return;
    }

    curl_setopt_array($curl, [
        CURLOPT_URL => API_BASE_URL . '/api/save-syllabus',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($postData),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => false, // Only for development
        CURLOPT_SSL_VERIFYHOST => false  // Only for development
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if (curl_errno($curl)) {
        http_response_code(500);
        echo json_encode(['error' => 'Curl error: ' . curl_error($curl)]);
        return;
    }

    curl_close($curl);
    
    // Forward the API response
    http_response_code($httpCode);
    echo $response;
}

// Route requests
$route = $_SERVER['REQUEST_URI'];
if (strpos($route, '/api/extract-syllabus') !== false) {
    handleExtractSyllabus();
} elseif (strpos($route, '/api/save-syllabus') !== false) {
    handleSaveSyllabus();
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Route not found']);
}
?>