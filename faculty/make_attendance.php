<?php
// Start session
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['username'])) {
    header('Location: ../');
    exit();
}

// Fetch user data from session
$username = $_SESSION['username'];
$email = $_SESSION['email'];
$number = $_SESSION['number'];
$college_code = $_SESSION['college_code'];
$branch = $_GET['branch_code'];
$semester = $_GET['semester'];
$subject = $_GET['subject'];
$subject_code = $_GET['subject_code'];

// Include database connection file
include('../config.php');

// Fetch student list from the database
$students = [];
$sql = "SELECT Reg, username FROM students WHERE college_code = '$college_code' AND branch = '$branch' AND semester = '$semester'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_attendance'])) {
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
      $date = date('Y-m-d'); // Get the current date
        foreach ($students as $student) {
            $attendance = isset($_POST['attendance'][$student['Reg']]) ? $_POST['attendance'][$student['Reg']] : 'Absent';
            $student_id = $student['Reg'];

            // Prepare a statement for data insertion
            $stmt = $conn->prepare("INSERT INTO `$college_code` (Roll, user_status, Date, subject_code) VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE user_status=?");

            if ($stmt) {
                // Bind parameters
                 $stmt->bind_param("sssss", $student_id, $attendance, $date, $subject_code, $attendance);
                
                // Execute the statement
                if ($stmt->execute()) {
                    $response[] = array("message" => "Student details inserted successfully for subject: $subject");
                } else {
                    $response[] = array("error" => "Failed to insert student details for subject: $subject - " . $stmt->error);
                }
                
                // Close the statement
                $stmt->close();
            } else {
                $response[] = array("error" => "Failed to prepare statement for subject: $subject - " . $conn->error);
            }
        }

        $success_message = "Attendance submitted successfully.";
    } else {
        $response[] = array("error" => "Error creating table for subject: $subject - " . $conn->error);
    }
}

?>

<?php include('header.php'); ?>
<main>
    <section class="attendance-section">
        <h2><?php echo htmlspecialchars($subject); ?></h2>
        <?php if (isset($success_message)) {
            echo "<p class='success-message'>{$success_message}</p>";
        } ?>
        <form action="" method="POST">
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['username']), " (", substr($student['Reg'], -3), ")"; ?></td>
                            <td>
                                <label>
                                    <input type="radio" name="attendance[<?php echo $student['Reg']; ?>]" value="Present" class="present-checkbox">
                                    Present
                                </label>
                                <label>
                                    <input type="radio" name="attendance[<?php echo $student['Reg']; ?>]" value="Absent" class="absent-checkbox" checked>
                                    Absent
                                </label>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <div class="attendance-summary">
                <p>Total Students: <span id="total-students"><?php echo count($students); ?></span></p>
                <p>Present: <span id="present-count">0</span></p>
                <p>Absent: <span id="absent-count"><?php echo count($students); ?></span></p>
            </div>
            <div class="form-group">
                <button type="submit" name="submit_attendance" class="btn btn-primary">Submit Attendance</button>
            </div>
        </form>
    </section>
</main>

<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f0f2f5;
        color: #333;
    }

    main {
        padding: 40px 20px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .attendance-section {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .attendance-section h2 {
        font-size: 2em;
        margin-bottom: 20px;
        text-align: center;
        color: #0044cc;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    table th, table td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: left;
    }

    table th {
        background-color: #f4f4f4;
        font-weight: bold;
    }

    .attendance-summary {
        text-align: center;
        margin-bottom: 20px;
        font-size: 1.2em;
        font-weight: bold;
    }

    .form-group {
        text-align: center;
    }

    .form-group .btn {
        display: inline-block;
        padding: 10px 20px;
        font-size: 1em;
        color: white;
        background-color: #0044cc;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .form-group .btn:hover {
        background-color: #003399;
    }

    .success-message {
        color: green;
        font-weight: bold;
        text-align: center;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const presentCheckboxes = document.querySelectorAll('.present-checkbox');
        const absentCheckboxes = document.querySelectorAll('.absent-checkbox');
        const totalStudents = document.getElementById('total-students');
        const presentCount = document.getElementById('present-count');
        const absentCount = document.getElementById('absent-count');

        function updateCounts() {
            const totalPresent = document.querySelectorAll('.present-checkbox:checked').length;
            const totalAbsent = parseInt(totalStudents.textContent) - totalPresent;
            presentCount.textContent = totalPresent;
            absentCount.textContent = totalAbsent;
        }

        presentCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateCounts);
        });

        absentCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateCounts);
        });
    });
</script>
