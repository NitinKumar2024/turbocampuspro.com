<?php
// extract_syllabus.php
error_reporting(0); // Disable error reporting
header('Content-Type: application/json'); // Set JSON header

// API endpoint URL
$url = 'https://smarteduai.turbocampuspro.com/api/extract-syllabus';

// Prepare the file
$file = curl_file_create(
    $_FILES['file']['tmp_name'],
    $_FILES['file']['type'],
    $_FILES['file']['name']
);

// Prepare the form data
$data = array(
    'file' => $file,
    'branch_code' => $_POST['branch_code'],
    'semester' => $_POST['semester'],
    'college_code' => $_POST['college_code'],
    'known_subjects' => $_POST['known_subjects']
);

// Initialize cURL
$ch = curl_init();

// Set cURL options
curl_setopt_array($ch, array(
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $data,
    CURLOPT_SSL_VERIFYPEER => false, // Only for testing, remove in production
));

// Execute the request
$response = curl_exec($ch);

// Check for errors
if (curl_errno($ch)) {
    echo json_encode(array('error' => curl_error($ch)));
} else {
    echo $response;
}

// Close cURL
curl_close($ch);
?>