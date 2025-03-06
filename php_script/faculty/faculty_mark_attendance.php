<?php
// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Database connection details
    include '../config.php';

    // Get student details JSON string from the POST parameters
    $studentsJson = $_POST['students'];

    // Decode JSON string to an array of student objects
    $students = json_decode($studentsJson, true);

    // Initialize response array
    $response = [];

    // Check if decoding was successful
    if ($students !== null && isset($students['students']) && is_array($students['students'])) {
        // Process each student
        $date = date('Y-m-d'); // Get the current date
        foreach ($students['students'] as $student) {
            // Get student details
            $subject = $student['subject']; // Subject name sent with each student
            $college_code = $student['college_code'];
            $roll = $student['roll_no'];
            $status = $student['status'];

            // Check if the table exists, if not, create it
            $createTableQuery = "CREATE TABLE IF NOT EXISTS `$college_code` (
                Date DATE NOT NULL,
                Roll VARCHAR(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
                subject_code VARCHAR(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
                user_status VARCHAR(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
                PRIMARY KEY(Date, Roll, subject_code)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";

            // Execute the CREATE TABLE query
            if ($conn->query($createTableQuery) === TRUE) {
                // Prepare a statement for data insertion
                $stmt = $conn->prepare("INSERT INTO `$college_code` (Roll, user_status, Date, subject_code) VALUES (?, ?, ?, ?)");
                if ($stmt) {
                    // Bind parameters
                    $stmt->bind_param("ssss", $roll, $status, $date, $subject);

                    // Execute the statement
                    if ($stmt->execute()) {
                        $response[] = array("message" => "Student details inserted successfully for subject: $subject");
                    } else {
                        $response[] = array("message" => "Failed to insert student details for subject: $subject - " . $stmt->error);
                    }

                    // Close the statement
                    $stmt->close();
                } else {
                    $response[] = array("error" => "Failed to prepare statement for subject: $subject - " . $conn->error);
                }
            } else {
                $response[] = array("error" => "Error creating table for subject: $subject - " . $conn->error);
            }
        }
    } else {
        $response[] = array("error" => "Invalid student data format");
    }

    // Close the database connection after processing all students
    $conn->close();

    // Output the response as JSON
    echo json_encode($response);
} else {
    // Handle invalid request method
    echo json_encode(array("error" => "Invalid request method"));
}
?>
