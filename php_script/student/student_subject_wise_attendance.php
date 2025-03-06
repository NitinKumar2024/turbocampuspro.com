<?php
include('../config.php');
header('Content-Type: application/json');

$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['Reg']) && isset($_POST['college_code']) && isset($_POST['subject_code'])) {
        $reg = $_POST['Reg'];
        $college_code = $_POST['college_code'];
        $subject_code = $_POST['subject_code'];
        
        // Use prepared statement to prevent SQL injection
        $attendance_query = "SELECT Date, user_status FROM `$college_code` WHERE Roll = ? AND subject_code = ?";
        $stmt = $conn->prepare($attendance_query);
        $stmt->bind_param("ss", $reg, $subject_code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $response['success'] = true;
            $response['data'] = [];
            while ($row = $result->fetch_assoc()) {
                $response['data'][] = $row;
            }
        } else {
            $response['message'] = "No attendance records found.";
        }
        $stmt->close();
    } else {
        $response['error'] = "Missing required parameters.";
    }
} else {
    $response['error'] = "Invalid request method.";
}

echo json_encode($response);
?>