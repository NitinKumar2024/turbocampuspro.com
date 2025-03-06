<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if a file was uploaded
    if (isset($_FILES['pdfFile'])) {
        $file = $_FILES['pdfFile'];

        // Validate file type
        $allowedTypes = ['application/pdf'];
        $fileType = mime_content_type($file['tmp_name']);

        if (!in_array($fileType, $allowedTypes)) {
            echo json_encode(['error' => 'Invalid file type']);
            exit;
        }

        // Validate file extension
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (strtolower($fileExtension) !== 'pdf') {
            echo json_encode(['error' => 'Invalid file extension']);
            exit;
        }

        // Save the uploaded file temporarily
        $uploadDir = 'uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $filePath = $uploadDir . basename($file['name']);
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            echo json_encode(['error' => 'File upload failed']);
            exit;
        }

        // Step 1: Extract syllabus using the given API
        $extractApiUrl = 'https://smarteduai.turbocampuspro.com/api/extract-syllabus';
        $extractResponse = callApi($extractApiUrl, ['syllabus' => new CURLFile($filePath)]);

        if (isset($extractResponse['error'])) {
            echo json_encode(['error' => 'Error during syllabus extraction: ' . $extractResponse['error']]);
            exit;
        }

        // Step 2: Save the extracted syllabus using the second API
        $saveApiUrl = 'https://smarteduai.turbocampuspro.com/api/save-syllabus';
        $saveResponse = callApi($saveApiUrl, $extractResponse);

        if (isset($saveResponse['error'])) {
            echo json_encode(['error' => 'Error during syllabus saving: ' . $saveResponse['error']]);
            exit;
        }

        // Success response
        echo json_encode(['success' => true, 'message' => 'Syllabus uploaded and processed successfully!', 'data' => $saveResponse]);
    } else {
        echo json_encode(['error' => 'No file uploaded']);
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}

/**
 * Helper function to call APIs
 *
 * @param string $url
 * @param array $postData
 * @return array
 */
function callApi($url, $postData) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: multipart/form-data',
        'Accept: application/json'
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['error' => $error];
    }

    return json_decode($response, true);
}
?>
