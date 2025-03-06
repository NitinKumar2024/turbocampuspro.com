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

// Fetch user data from session
$reg = $_SESSION['Reg'];
$college_code = $_SESSION['college_code'];
$branch = $_SESSION['branch'];
$semester = $_SESSION['semester']; // Semester is fixed from the session data

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

                $attendance_data[] = [
                    'subject' => $subject['Subject'],
                    'subject_code' => $subject_code,
                    'total_classes' => $subject_total_classes,
                    'present_count' => $subject_present_count,
                    'absent_count' => $subject_absent_count,
                    'attendance_percentage' => $subject_attendance_percentage,
                    'attendance' => $attendance
                ];
            }
        }
    }
}

// Calculate overall attendance percentage
$overall_attendance_percentage = $total_classes > 0 ? round(($total_present / $total_classes) * 100, 2) : 0;

// Handle PDF export
if (isset($_POST['export_pdf'])) {
    require('../faculty/fpdf/fpdf.php');

    class PDF extends FPDF {
        function Header() {
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(0, 10, 'Attendance Report', 0, 1, 'C');
            $this->Ln(10);
        }

        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
        }
    }

    $pdf = new PDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 12);

    // Overall summary
    $pdf->Cell(0, 10, 'Overall Summary', 0, 1);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'Total Classes: ' . $total_classes, 0, 1);
    $pdf->Cell(0, 10, 'Present: ' . $total_present, 0, 1);
    $pdf->Cell(0, 10, 'Absent: ' . $total_absent, 0, 1);
    $pdf->Cell(0, 10, 'Attendance Percentage: ' . $overall_attendance_percentage . '%', 0, 1);
    $pdf->Ln(10);

    // Subject-wise attendance
    foreach ($attendance_data as $data) {
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, $data['subject'], 0, 1);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 10, 'Total Classes: ' . $data['total_classes'], 0, 1);
        $pdf->Cell(0, 10, 'Present: ' . $data['present_count'], 0, 1);
        $pdf->Cell(0, 10, 'Absent: ' . $data['absent_count'], 0, 1);
        $pdf->Cell(0, 10, 'Attendance Percentage: ' . $data['attendance_percentage'] . '%', 0, 1);
        $pdf->Ln(10);

        // Add chart image
        $chart_file = 'chart_' . $data['subject_code'] . '.png';
        file_put_contents($chart_file, file_get_contents('data:image/png;base64,' . $_POST['chart_' . $data['subject_code']]));
        $pdf->Image($chart_file, 10, $pdf->GetY(), 180, 60);
        $pdf->Ln(70);

        // Remove chart image file
        unlink($chart_file);
    }

    $pdf->Output('D', "attendance_report_$reg.pdf");
    exit();
}
?>

<?php include('header.php'); ?>
<main>
    <section class="attendance-section">
        <h2>Overall Attendance Report</h2>

        <div class="overall-summary">
            <h3>Overall Summary</h3>
            <p>Total Classes: <?php echo $total_classes; ?></p>
            <p>Present: <?php echo $total_present; ?></p>
            <p>Absent: <?php echo $total_absent; ?></p>
            <p>Attendance Percentage: <?php echo $overall_attendance_percentage; ?>%</p>
        </div>

        <?php foreach ($attendance_data as $data) { ?>
            <div class="subject-attendance">
                <h3><?php echo htmlspecialchars($data['subject']); ?></h3>
                <p>Total Classes: <?php echo $data['total_classes']; ?></p>
                <p>Present: <?php echo $data['present_count']; ?></p>
                <p>Absent: <?php echo $data['absent_count']; ?></p>
                <p>Attendance Percentage: <?php echo $data['attendance_percentage']; ?>%</p>

                <canvas id="chart_<?php echo htmlspecialchars($data['subject_code']); ?>" width="400" height="200"></canvas>
            </div>
        <?php } ?>

        <form action="" method="POST">
            <?php foreach ($attendance_data as $data) { ?>
                <input type="hidden" name="chart_<?php echo htmlspecialchars($data['subject_code']); ?>" id="chart_data_<?php echo htmlspecialchars($data['subject_code']); ?>">
            <?php } ?>
            <button type="submit" name="export_pdf" class="btn btn-secondary">Download PDF</button>
        </form>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    <?php foreach ($attendance_data as $data) { ?>
        const ctx_<?php echo $data['subject_code']; ?> = document.getElementById('chart_<?php echo $data['subject_code']; ?>').getContext('2d');
        const chart_<?php echo $data['subject_code']; ?> = new Chart(ctx_<?php echo $data['subject_code']; ?>, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($data['attendance'], 'Date')); ?>,
                datasets: [{
                    label: 'Attendance Status',
                    data: <?php echo json_encode(array_map(function($entry) {
                        return $entry['user_status'] === 'Present' ? 1 : 0;
                    }, $data['attendance'])); ?>,
                    backgroundColor: <?php echo json_encode(array_map(function($entry) {
                        return $entry['user_status'] === 'Present' ? 'rgba(0, 128, 0, 0.6)' : 'rgba(255, 0, 0, 0.6)';
                    }, $data['attendance'])); ?>,
                    borderColor: <?php echo json_encode(array_map(function($entry) {
                        return $entry['user_status'] === 'Present' ? 'rgba(0, 128, 0, 1)' : 'rgba(255, 0, 0, 1)';
                    }, $data['attendance'])); ?>,
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            callback: function(value) { if (value % 1 === 0) { return value === 1 ? 'Present' : 'Absent'; } }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.raw === 1 ? 'Present' : 'Absent';
                            }
                        }
                    }
                }
            }
        });

        document.getElementById('chart_data_<?php echo $data['subject_code']; ?>').value = chart_<?php echo $data['subject_code']; ?>.toBase64Image().split(',')[1];
    <?php } ?>
</script>

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

    .overall-summary {
        margin-bottom: 40px;
        text-align: center;
    }

    .overall-summary h3 {
        font-size: 1.5em;
        margin-bottom: 10px;
    }

    .subject-attendance {
        margin-bottom: 40px;
    }

    .subject-attendance h3 {
        font-size: 1.5em;
        margin-bottom: 10px;
    }

    .subject-attendance p {
        margin: 5px 0;
    }

    .subject-attendance canvas {
        margin-top: 20px;
    }

    .btn {
        padding: 10px 20px;
        font-size: 1em;
        color: white;
        background-color: #0044cc;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .btn-secondary {
        background-color: #6c757d;
    }

    .btn:hover {
        background-color: #003399;
    }
</style>
