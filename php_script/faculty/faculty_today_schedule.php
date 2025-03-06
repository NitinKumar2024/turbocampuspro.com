<?php
// Include database connection file
include('../config.php');

header('Content-Type: application/json');

$response = array('success' => false);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['email']) && isset($_POST['college_code'])) {
        $email = $_POST['email'];
        $college_code = $_POST['college_code'];

        // Prepare the SQL statement
        $sql = " SELECT 
                    F.username, 
                    F.email,
                    S.Subject_Code,
                    S.Subject, 
                    S.Branch, 
                    S.Branch_Code,
                    S.semester, 
                    FWS.subject_code, 
                    FWS.branch_code 
                FROM 
                    FacultyWithSubject FWS
                JOIN 
                    Faculty F ON FWS.email = F.email
                JOIN 
                    AllSubject S ON FWS.subject_code = S.Subject_Code 
                              AND FWS.branch_code = S.Branch_Code 
                              AND FWS.college_code = S.college_code
                WHERE 
                    F.college_code = ? 
                    AND FWS.email = ?";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ss", $college_code, $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $response['success'] = true;
                $response['data'] = array();

                while ($user = $result->fetch_assoc()) {
                    $response['data'][] = array(
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'subject_code' => $user['Subject_Code'],
                        'subject' => $user['Subject'],
                        'branch' => $user['Branch'],
                        'branch_code' => $user['Branch_Code'],
                        'semester' => $user['semester']
                    );
                }
            } else {
                $response['error'] = "No user found with this email.";
            }
            $stmt->close();
        } else {
            $response['error'] = "Error preparing statement: " . $conn->error;
        }
    } else {
        $response['error'] = "Email or college code not set.";
    }
} else {
    $response['error'] = "Invalid request method.";
}

echo json_encode($response);
?>
