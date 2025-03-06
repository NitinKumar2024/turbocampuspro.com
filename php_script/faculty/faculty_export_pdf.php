<?php
require('fpdf/fpdf.php');
require('../config.php');

if (!class_exists('FPDF')) {
    die("FPDF class not found. Please check your installation.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete'])) {

class PDF extends FPDF
{
    function Header()
    {
        // Logo
        $this->Image('https://www.gpbarh.in/wp-content/uploads/2023/12/WhatsApp_Image_2023-12-23_at_12.45.01_AM-removebg-preview.png', 10, 6, 30);
        // Arial bold 15
        $this->SetFont('Arial', 'B', 15);
        // Move to the right
        $this->Cell(80);
        // Title
        $this->SetTextColor(0, 102, 204);
        $this->Cell(30, 10, 'Attendance Report', 0, 0, 'C');
        // Line break
        $this->Ln(20);
    }

    function Footer()
    {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    function ChapterTitle($label)
    {
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor(200, 220, 255);
        $this->Cell(0, 6, $label, 0, 1, 'L', true);
        $this->Ln(4);
    }

    function TableHeader()
    {
        $this->SetFillColor(0, 102, 204);
        $this->SetTextColor(255);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(40, 10, 'Date', 1, 0, 'C', true);
        $this->Cell(60, 10, 'Name', 1, 0, 'C', true);
        $this->Cell(30, 10, 'Roll No', 1, 0, 'C', true);
        $this->Cell(60, 10, 'Status', 1, 1, 'C', true);
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete'])) {
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $subject_code = $_POST['subject_code'] ?? null;
    $college_code = $_POST['college_code'] ?? null;
    $branch = $_POST['branch_code'] ?? null;
    $semester = $_POST['semester'] ?? null;

    if (!$start_date || !$end_date || !$subject_code || !$college_code || !$branch || !$semester) {
        die('Error: Missing required parameters.');
    }

    error_log("Query parameters: start_date=$start_date, end_date=$end_date, subject_code=$subject_code, college_code=$college_code, branch=$branch, semester=$semester");

    $sql = "SELECT A.Date, A.Roll, S.username, A.user_status 
            FROM `" . mysqli_real_escape_string($conn, $college_code) . "` A
            JOIN students S ON A.Roll = S.Reg 
            WHERE S.college_code = ? AND S.branch = ? AND S.semester = ? AND A.subject_code = ?
            AND A.Date BETWEEN ? AND ? 
            ORDER BY A.Date, A.Roll";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die('Prepare failed: ' . htmlspecialchars($conn->error));
    }

    $stmt->bind_param("ssssss", $college_code, $branch, $semester, $subject_code, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
        die('Execute failed: ' . htmlspecialchars($stmt->error));
    }

    $row_count = $result->num_rows;
    error_log("Number of rows retrieved: " . $row_count);

    if ($row_count == 0) {
        die('No data found for the given parameters.');
    }

    error_log("Starting PDF generation");

    ob_start();

    $pdf = new PDF();
    $pdf->AliasNbPages();
    $pdf->AddPage();

    // Report details
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(0);
    $pdf->Cell(0, 10, "Subject Code: $subject_code", 0, 1);
    $pdf->Cell(0, 10, "Branch: $branch", 0, 1);
    $pdf->Cell(0, 10, "Semester: $semester", 0, 1);
    $pdf->Cell(0, 10, "Period: $start_date to $end_date", 0, 1);
    $pdf->Ln(10);

    // Summary Section
    $pdf->ChapterTitle("Attendance Summary");
    $total_students = $row_count;
    $present_count = 0;
    $absent_count = 0;

    while ($row = $result->fetch_assoc()) {
        if (strtolower($row['user_status']) == 'present') {
            $present_count++;
        } else {
            $absent_count++;
        }
    }

    $attendance_rate = ($present_count / $total_students) * 100;

    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, "Total Students: $total_students", 0, 1);
    $pdf->Cell(0, 10, "Present: $present_count", 0, 1);
    $pdf->Cell(0, 10, "Absent: $absent_count", 0, 1);
    $pdf->Cell(0, 10, sprintf("Attendance Rate: %.2f%%", $attendance_rate), 0, 1);
    $pdf->Ln(10);

    // Reset result pointer
    $result->data_seek(0);

    // Detailed Attendance
    $pdf->ChapterTitle("Detailed Attendance");
    $pdf->TableHeader();

    $pdf->SetFont('Arial', '', 12);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetTextColor(0);

    $fill = false;
    while ($row = $result->fetch_assoc()) {
        $pdf->Cell(40, 10, $row['Date'], 1, 0, 'L', $fill);
        $pdf->Cell(60, 10, $row['username'], 1, 0, 'L', $fill);
        $pdf->Cell(30, 10, $row['Roll'], 1, 0, 'L', $fill);
        $pdf->Cell(60, 10, $row['user_status'], 1, 1, 'L', $fill);
        $fill = !$fill;
    }

    $stmt->close();
    $conn->close();

    $pdfContent = $pdf->Output('S');
    $pdfSize = strlen($pdfContent);
    error_log("PDF size: $pdfSize bytes");

    if ($pdfSize == 0) {
        error_log("Generated PDF is empty");
        die("Error: Generated PDF is empty");
    }

    ob_end_clean();

    header('Content-Type: application/pdf');
    header('Content-Length: ' . $pdfSize);
    header('Content-Disposition: attachment; filename="attendance_report.pdf"');
    echo $pdfContent;

    exit;
}
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['summary'])) {
class PDF extends FPDF
{
        function Header()
    {
        // Logo
        $this->Image('https://www.gpbarh.in/wp-content/uploads/2023/12/WhatsApp_Image_2023-12-23_at_12.45.01_AM-removebg-preview.png', 10, 6, 30);
        // Arial bold 15
        $this->SetFont('Arial', 'B', 15);
        // Move to the right
        $this->Cell(80);
        // Title
        $this->SetTextColor(0, 102, 204);
        $this->Cell(30, 10, 'Attendance Report', 0, 0, 'C');
        // Line break
        $this->Ln(20);
    }

    function Footer()
    {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    function ChapterTitle($label)
    {
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor(200, 220, 255);
        $this->Cell(0, 6, $label, 0, 1, 'L', true);
        $this->Ln(4);
    }


    function TableHeader()
    {
        $this->SetFillColor(200, 220, 255);
        $this->SetTextColor(0);
        $this->SetDrawColor(128, 0, 0);
        $this->SetLineWidth(.3);
        $this->SetFont('', 'B');
        
        $this->Cell(25, 7, 'Roll No', 1, 0, 'C', true);
        $this->Cell(40, 7, 'Name', 1, 0, 'C', true);
        $this->Cell(25, 7, 'Total Classes', 1, 0, 'C', true);
        $this->Cell(20, 7, 'Present', 1, 0, 'C', true);
        $this->Cell(20, 7, 'Absent', 1, 0, 'C', true);
        $this->Cell(25, 7, 'Working Days', 1, 0, 'C', true);
        $this->Cell(25, 7, 'Attendance %', 1, 0, 'C', true);
        $this->Ln();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subject_code = $_POST['subject_code'] ?? null;
    $college_code = $_POST['college_code'] ?? null;
    $branch = $_POST['branch_code'] ?? null;
    $semester = $_POST['semester'] ?? null;
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;

    if (!$subject_code || !$college_code || !$branch || !$semester || !$start_date || !$end_date) {
        die('Error: Missing required parameters.');
    }

    $sql = "SELECT S.Reg as roll_no, S.username as name,
                   COUNT(DISTINCT A.Date) as working_days,
                   SUM(CASE WHEN A.user_status = 'present' THEN 1 ELSE 0 END) as present,
                   SUM(CASE WHEN A.user_status = 'absent' THEN 1 ELSE 0 END) as absent
            FROM students S
            LEFT JOIN `" . mysqli_real_escape_string($conn, $college_code) . "` A 
                ON S.Reg = A.Roll AND A.subject_code = ? AND A.Date BETWEEN ? AND ?
            WHERE S.college_code = ? AND S.branch = ? AND S.semester = ?
            GROUP BY S.Reg, S.username
            ORDER BY S.Reg";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die('Prepare failed: ' . htmlspecialchars($conn->error));
    }

    $stmt->bind_param("ssssss", $subject_code, $start_date, $end_date, $college_code, $branch, $semester);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
        die('Execute failed: ' . htmlspecialchars($stmt->error));
    }

    $pdf = new PDF();
    $pdf->AliasNbPages();
    $pdf->AddPage('L'); // Landscape orientation
    $pdf->SetFont('Arial', '', 10);
  
   // Report details
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(0);
    $pdf->Cell(0, 10, "Subject Code: $subject_code", 0, 1);
    $pdf->Cell(0, 10, "Branch: $branch", 0, 1);
    $pdf->Cell(0, 10, "Semester: $semester", 0, 1);
    $pdf->Cell(0, 10, "Period: $start_date to $end_date", 0, 1);
    $pdf->Ln(10);

    $pdf->TableHeader();

    while ($row = $result->fetch_assoc()) {
        $total_classes = $row['working_days'];
        $present = $row['present'];
        $absent = $row['absent'];
        $attendance_percentage = ($total_classes > 0) ? ($present / $total_classes) * 100 : 0;

        $pdf->Cell(25, 6, $row['roll_no'], 1);
        $pdf->Cell(40, 6, $row['name'], 1);
        $pdf->Cell(25, 6, $total_classes, 1, 0, 'C');
        $pdf->Cell(20, 6, $present, 1, 0, 'C');
        $pdf->Cell(20, 6, $absent, 1, 0, 'C');
        $pdf->Cell(25, 6, $row['working_days'], 1, 0, 'C');
        $pdf->Cell(25, 6, number_format($attendance_percentage, 2) . '%', 1, 0, 'C');
        $pdf->Ln();
    }

    $stmt->close();
    $conn->close();

    $pdfContent = $pdf->Output('S');
    $pdfSize = strlen($pdfContent);

  
    ob_clean();
    header('Content-Type: application/pdf');
    header('Content-Length: ' . $pdfSize);
    header('Content-Disposition: attachment; filename="student_attendance_summary.pdf"');
    echo $pdfContent;

    exit;
}
}
?>