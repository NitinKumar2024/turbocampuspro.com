<?php
// Include database connection file
include('../config.php');

header('Content-Type: application/json');
$attendance_data = array('success' => false);


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['Reg']) && isset($_POST['college_code'])) {
        // Fetch user data from session
        $reg = $_POST['Reg'];
        $college_code = $_POST['college_code'];
        $branch = $_POST['branch'];
        $semester = $_POST['semester']; // Semester is fixed from the session data

      // Fetch subjects for the student
        $subjects = [];
        $sql = "SELECT Subject, Subject_Code FROM AllSubject WHERE Branch_Code = '$branch' AND semester = '$semester' AND college_code = '$college_code'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $subjects[] = $row;
            }
        }

        // Fetch attendance data for all subjects
        $attendance_data = [];
        $total_present = 0;
        $total_absent = 0;
        $total_classes = 0;
        $attendance_data['success'] = true;
        

        foreach ($subjects as $subject) {
            $subject_code = $subject['Subject_Code'];
        
            // Check if the subject's attendance table exists and has entries for the student
            $table_exists_query = "SHOW TABLES LIKE '$college_code'";
            $table_exists_result = $conn->query($table_exists_query);
        
            if ($table_exists_result->num_rows > 0) {
                $attendance_query = "SELECT Date, user_status FROM `$college_code` WHERE Roll = '$reg' AND subject_code = '$subject_code'";
                $attendance_result = $conn->query($attendance_query);
        
                if ($attendance_result->num_rows > 0) {
                    $attendance = [];
                    while ($row = $attendance_result->fetch_assoc()) {
                        $attendance[] = $row;
                    }
        
                    if (count($attendance) > 0) {
                        $subject_total_classes = count($attendance);
                        $subject_present_count = count(array_filter($attendance, function ($entry) {
                            return $entry['user_status'] === 'Present';
                        }));
                        $subject_absent_count = $subject_total_classes - $subject_present_count;
                        $subject_attendance_percentage = $subject_total_classes > 0 ? round(($subject_present_count / $subject_total_classes) * 100, 2) : 0;
        
                        $total_present += $subject_present_count;
                        $total_absent += $subject_absent_count;
                        $total_classes += $subject_total_classes;
                      
        
                        $attendance_data['data'][] = array(
                            'subject' => $subject['Subject'],
                            'subject_code' => $subject_code,
                            'total_classes' => $subject_total_classes,
                            'present_count' => $subject_present_count,
                            'absent_count' => $subject_absent_count,
                            'attendance_percentage' => $subject_attendance_percentage,
                            'attendance' => $attendance
                        );
                    }
                }
            }
        }

        // Calculate overall attendance percentage
        $overall_attendance_percentage = $total_classes > 0 ? round(($total_present / $total_classes) * 100, 2) : 0;
    }
} else {
    $attendance_data['error'] = "Invalid request method.";
}

echo json_encode($attendance_data);
?>
