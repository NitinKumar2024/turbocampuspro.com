<?php
// Start session
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['username'])) {
    header('Location: ../');
    exit();
}

// Include database connection file
include('../config.php');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fetch user data from session
$username = $_SESSION['username'];
$email = $_SESSION['email'];
$number = $_SESSION['number'];
$college_code = $_SESSION['college_code'];
$branch = $_GET['branch_code'];
$semester = $_GET['semester'];
$subject_code = $_GET['subject_code'];

// Initialize variables
$attendance_data = [];
$total_students = 0;
$total_present = 0;
$total_absent = 0;

// Fetch attendance data from the database

if (isset($_POST['filter'])) {
    // Sanitize input data
    $start_date = $conn->real_escape_string($_POST['start_date']);
    $end_date = $conn->real_escape_string($_POST['end_date']);
    $college_code = $conn->real_escape_string($college_code);
    $branch = $conn->real_escape_string($branch);
    $semester = $conn->real_escape_string($semester);
    $subject_code = $conn->real_escape_string($subject_code);

    // Prepare the SQL query
    $sql = "SELECT A.Date, A.Roll, S.username, A.user_status 
            FROM `$college_code` A
            JOIN students S ON A.Roll = S.Reg 
            WHERE S.college_code = ? AND S.branch = ? AND S.semester = ? AND A.subject_code = ?
            AND A.Date BETWEEN ? AND ? 
            ORDER BY A.Date, A.Roll";

    // Prepare the statement
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // Bind parameters
        $stmt->bind_param("ssssss", $college_code, $branch, $semester, $subject_code, $start_date, $end_date);

        // Execute the statement
        $stmt->execute();

        // Get the result
        $result = $stmt->get_result();

        // Fetch data
        while ($row = $result->fetch_assoc()) {
            $attendance_data[] = $row;
            $total_students++;
            if ($row['user_status'] == 'Present') {
                $total_present++;
            } else {
                $total_absent++;
            }
        }

        // Free the result set
        $result->free();

        // Close the statement
        $stmt->close();
    } else {
        $response[] = array("error" => "Failed to prepare statement - " . $conn->error);
    }
}

// Optionally, handle or display $response, $attendance_data, $total_students, $total_present, $total_absent as needed

// For debugging purposes, you can print the error message (if any)
if (!empty($response)) {
    foreach ($response as $res) {
        echo $res['error'];
    }
}




// Function to export data to CSV
function exportCSV($conn, $college_code, $branch, $semester, $subject_code, $start_date, $end_date) {
    // File name
    $filename = "attendance_data_{$branch}_{$semester}_{$subject_code}.csv";

    // Open file in write mode
    $file = fopen('php://output', 'w');

    // Fetch data from the database
    $sql = "SELECT A.Date, A.Roll, S.username, A.user_status 
            FROM `$college_code` A
            JOIN students S ON A.Roll = S.Reg 
            WHERE S.college_code = '$college_code' AND S.branch = '$branch' AND S.semester = '$semester' AND A.subject_code = '$subject_code'
            AND A.Date BETWEEN '$start_date' AND '$end_date' 
            ORDER BY A.Date, A.Roll";
    $result = $conn->query($sql);

    // Set headers for CSV file
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // Column headers
    $fields = array('Date', 'Roll', 'Username', 'Status');
    fputcsv($file, $fields);

    // Output each row of the data
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            fputcsv($file, $row);
        }
    }

    // Close file
    fclose($file);
    exit();
}

// Function to export data to PDF
function exportPDF($conn, $college_code, $branch, $semester, $subject_code, $start_date, $end_date) {
    require('fpdf/fpdf.php');

    // Fetch data from the database
    $sql = "SELECT A.Date, A.Roll, S.username, A.user_status 
            FROM `$college_code` A
            JOIN students S ON A.Roll = S.Reg 
            WHERE S.college_code = '$college_code' AND S.branch = '$branch' AND S.semester = '$semester' AND A.subject_code = '$subject_code'
            AND A.Date BETWEEN '$start_date' AND '$end_date' 
            ORDER BY A.Date, A.Roll";
    $result = $conn->query($sql);

    // Create PDF
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 14);

    // Header
    // Add company logo
    $pdf->Image('../principal/img/logo.png', 10, 6, 30); // Adjust the path and size as needed

    // Faculty name
    $faculty_name = $_SESSION['username']; // Assuming faculty name is stored in the session
    $pdf->Cell(0, 10, 'Attendance Report', 0, 1, 'C');
    $pdf->Ln(5);

    // Faculty name
    $pdf->SetFont('Arial', 'I', 12);
    $pdf->Cell(0, 10, "Faculty: $faculty_name", 0, 1, 'C');
    $pdf->Ln(5);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(0, 0, 0); // Black color

    // Column headers with background color
    $pdf->SetFillColor(200, 220, 255); // Light blue color
    $pdf->Cell(40, 10, 'Date', 1, 0, 'C', true);
    $pdf->Cell(40, 10, 'Roll', 1, 0, 'C', true);
    $pdf->Cell(60, 10, 'Username', 1, 0, 'C', true);
    $pdf->Cell(40, 10, 'Status', 1, 1, 'C', true);

    // Output each row of the data
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(0, 0, 0); // Black color
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $pdf->Cell(40, 10, $row['Date'], 1);
            $pdf->Cell(40, 10, $row['Roll'], 1);
            $pdf->Cell(60, 10, $row['username'], 1);
            $pdf->Cell(40, 10, $row['user_status'], 1);
            $pdf->Ln();
        }
    } else {
        $pdf->Cell(0, 10, 'No records found for the selected date range.', 1, 1, 'C');
    }

    // Footer
    $pdf->SetY(-15);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 10, 'Page ' . $pdf->PageNo(), 0, 0, 'C');

    // Output PDF
    $pdf->Output('D', "attendance_data_{$branch}_{$semester}_{$subject_code}.pdf");
    exit();
}


// Handle export form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export'])) {
    $export_type = $_POST['export_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    if ($export_type == 'csv') {
        exportCSV($conn, $college_code, $branch, $semester, $subject_code, $start_date, $end_date);
    } elseif ($export_type == 'pdf') {
        exportPDF($conn, $college_code, $branch, $semester, $subject_code, $start_date, $end_date);
    }
}
?>

<?php include('header.php'); ?>
<main>
    <section class="export-section">
        <h2>Export Attendance Data</h2>
        <form action="" method="POST">
            <div class="form-group">
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" required>
            </div>
            <div class="form-group">
                <label for="end_date">End Date:</label>
                <input type="date" id="end_date" name="end_date" required>
            </div>
            
            <div class="form-group">
                <button type="submit" name="filter" class="btn btn-primary">Filter Data</button>
            </div>
        </form>
        <form action="" method="POST">
                <input type="hidden" name="start_date" value="<?php echo $start_date; ?>">
                <input type="hidden" name="end_date" value="<?php echo $end_date; ?>">
                <input type="hidden" name="export_type" value="csv">
                <button type="submit" name="export" class="btn btn-secondary">Download CSV</button>
            </form>
            <form action="" method="POST">
                <input type="hidden" name="start_date" value="<?php echo $start_date; ?>">
                <input type="hidden" name="end_date" value="<?php echo $end_date; ?>">
                <input type="hidden" name="export_type" value="pdf">
                <button type="submit" name="export" class="btn btn-secondary">Download PDF</button>
            </form>

        <?php if (!empty($attendance_data)) { ?>
            <div class="summary">
                <h3>Attendance Summary</h3>
                <p>Total Students: <?php echo $total_students; ?></p>
                <p>Present: <?php echo $total_present; ?></p>
                <p>Absent: <?php echo $total_absent; ?></p>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Roll</th>
                        <th>Username</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendance_data as $data) { ?>
                        <tr>
                            <td><?php echo $data['Date']; ?></td>
                            <td><?php echo $data['Roll']; ?></td>
                            <td><?php echo $data['username']; ?></td>
                            <td><?php echo $data['user_status']; ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            
        <?php } ?>
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
    /* Styling for download buttons */
.btn {
    display: block;
    width: 100%;
    padding: 10px;
    font-size: 1em;
    color: white;
    text-align: center;
    text-decoration: none;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    margin-top: 10px;
}

.btn-secondary {
    background-color: #6c757d;
}

.btn-secondary:hover {
    background-color: #5a6268;
}


    main {
        padding: 40px 20px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .export-section {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .export-section h2 {
        font-size: 2em;
        margin-bottom: 20px;
        text-align: center;
        color: #0044cc;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        font-weight: bold;
        margin-bottom: 5px;
    }

    .form-group input, .form-group select {
        width: 100%;
        padding: 10px;
        font-size: 1em;
        border: 1px solid #ccc;
        border-radius: 5px;
    }

    .form-group .btn {
        display: block;
        width: 100%;
        padding: 10px;
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

    .summary {
        margin-top: 20px;
        background: #e9ecef;
        padding: 20px;
        border-radius: 8px;
    }

    table {
        width: 100%;
        margin-top: 20px;
        border-collapse: collapse;
    }

    table, th, td {
        border: 1px solid #ccc;
    }

    th, td {
        padding: 10px;
        text-align: left;
    }

    th {
        background: #f4f4f4;
    }

    .btn-secondary {
        margin-top: 10px;
        background-color: #6c757d;
        border: none;
    }

    .btn-secondary:hover {
        background-color: #5a6268;
    }
</style>
