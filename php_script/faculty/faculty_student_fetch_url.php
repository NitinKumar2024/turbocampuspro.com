<?php
// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Database connection details
    include '../config.php';
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Get student details JSON string from the POST parameters
    $studentsJson = $_POST['students'];

    // Decode JSON string to an array of student objects
    $students = json_decode($studentsJson, true);
    
    // Check if decoding was successful

    // Initialize an array to hold the student data
    $allStudents = array();

    // Prepare the SQL statement with placeholders
    $sql = "SELECT * FROM students WHERE branch = ? AND semester = ? AND college_code = ?";
    $stmt = $conn->prepare($sql);

    // Bind parameters
    $stmt->bind_param("sss", $subject, $semester, $college_code);

    // Process each student
    foreach ($students['students'] as $student) {
        // Get student details
        $subject = $student['branch'];
        $semester = $student['semester'];
        $college_code = $student['college_code'];
      
        // Execute the prepared statement
        $stmt->execute();

        // Get result set
        $result = $stmt->get_result();
        
        // Fetch rows one by one
        while ($row = $result->fetch_assoc()) {
            // Append each student to the array
            $allStudents[] = $row;
        }
    }

    // Return JSON response with all students' data
    header('Content-Type: application/json');
    echo json_encode($allStudents);

    // Close the prepared statement and database connection after processing all students
    $stmt->close();
    $conn->close();
   
} else {
    // Handle invalid request method
    echo json_encode(array("error" => "Invalid request method"));
}
?>
