<?php
include('header.php');

// Redirect to login if not logged in
if (!isset($_SESSION['username'])) {
    header('Location: ../');
    exit();
}

// Include database connection file
include('../config.php');

// Fetch user data from session
$username = $_SESSION['username'];
$email = $_SESSION['email'];
$number = $_SESSION['number'];
$college_code = $_SESSION['college_code'];
$branch = $_GET['branch_code'];
$semester = $_GET['semester'];

// Initialize an array to hold the subjects
$subjects = [];

// SQL query to retrieve subjects based on branch, semester, and email
$sql = "
    SELECT S.Subject, S.Branch, S.semester, S.Subject_Code
    FROM FacultyWithSubject FWS
    JOIN Faculty F ON FWS.email = F.email
    JOIN AllSubject S ON FWS.subject_code = S.Subject_Code AND FWS.branch_code = S.Branch_Code AND FWS.college_code = S.college_code
    WHERE F.college_code = '$college_code' AND FWS.branch_code = '$branch' 
    AND FWS.semester = '$semester' 
    AND FWS.email = '$email'; "; // Adjust the college code as needed
    
// Execute the query
$result = $conn->query($sql);
    
// Fetch rows one by one
while ($row = $result->fetch_assoc()) {
    // Append each subject to the array
    $subjects[] = $row;
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Subjects</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
        }
        .subject {
            width: calc(100% - 12px);
            margin: 6px;
            background-color: #FFC107;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
        }
        .subject a {
            text-decoration: none;
            color: #9C27B0;
        }
        .subject h2 {
            font-size: 18px;
            color: inherit;
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div id="subjectsContainer">
        <?php foreach ($subjects as $subject): ?>
            <div class="subject">
                <a href="option_attendance.php?branch_code=<?php echo urlencode($branch); ?>&semester=<?php echo urlencode($semester); ?>&subject=<?php echo urlencode($subject['Subject']); ?>&subject_code=<?php echo urlencode($subject['Subject_Code']); ?>">
                    <h2><?php echo htmlspecialchars($subject['Subject']); ?></h2>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
