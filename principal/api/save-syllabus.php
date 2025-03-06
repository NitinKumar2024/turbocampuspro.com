<?php
// api/save-syllabus.php
header('Content-Type: application/json');
require_once '../config.php';

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get and decode JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            throw new Exception('Invalid JSON data');
        }

        // Validate required fields
        $required_fields = ['syllabus_data', 'branch_code', 'semester', 'college_code'];
        foreach ($required_fields as $field) {
            if (!isset($input[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        // Prepare the cURL request
        $curl = curl_init('https://smarteduai.turbocampuspro.com/api/save-syllabus');
        
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($input),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => false, // Only for development
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($response === false) {
            throw new Exception('cURL Error: ' . curl_error($curl));
        }

        curl_close($curl);

        if ($http_code !== 200) {
            throw new Exception('API Error: ' . $response);
        }

        // Decode and validate the response
        $result = json_decode($response, true);
        if (!$result || !isset($result['status'])) {
            throw new Exception('Invalid API response');
        }

        // Return the API response
        echo $response;

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'error' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'error' => 'Method not allowed'
    ]);
}
?>