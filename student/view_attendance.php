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

// Get the selected subject from GET parameters
$selected_subject = isset($_GET['subject_code']) ? $_GET['subject_code'] : '';

// Fetch subjects for the dropdown
$subjects = [];
$sql = "SELECT Subject, Subject_Code FROM AllSubject WHERE Branch_Code = '$branch' AND semester = '$semester' AND college_code = '$college_code'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
}

// Fetch attendance data for the selected subject
$attendance = [];
if ($selected_subject) {
    $sql = "SELECT Date, user_status FROM `$college_code` WHERE Roll = '$reg' AND subject_code = '$selected_subject'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $attendance[] = $row;
        }
    }
}

// Calculate attendance summary
$total_classes = count($attendance);
$present_count = count(array_filter($attendance, function ($entry) {
    return $entry['user_status'] === 'Present';
}));
$absent_count = $total_classes - $present_count;
$attendance_percentage = $total_classes > 0 ? round(($present_count / $total_classes) * 100, 2) : 0;

// Prepare data for the chart
$dates = array_column($attendance, 'Date');
$statuses = array_column($attendance, 'user_status');
?>

<?php include('header.php'); ?>
<main>
    <section class="attendance-section">
        <h2>Attendance</h2>

        <form action="" method="GET">
            <label for="subject_code">Select Subject:</label>
            <select name="subject_code" id="subject_code">
                <option value="">-- Select Subject --</option>
                <?php foreach ($subjects as $subject) { ?>
                    <option value="<?php echo htmlspecialchars($subject['Subject_Code']); ?>" <?php if ($selected_subject == $subject['Subject_Code']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($subject['Subject']); ?>
                    </option>
                <?php } ?>
            </select>

            <button type="submit" class="btn btn-primary">View Attendance</button>
        </form>

        <?php if ($selected_subject) { ?>
            <div class="attendance-summary">
                <h3>Attendance Summary for <?php echo htmlspecialchars($selected_subject); ?></h3>
                <p>Total Classes: <?php echo $total_classes; ?></p>
                <p>Present: <?php echo $present_count; ?></p>
                <p>Absent: <?php echo $absent_count; ?></p>
                <p>Attendance Percentage: <?php echo $attendance_percentage; ?>%</p>
            </div>

            <canvas id="attendanceChart" width="400" height="200"></canvas>

            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendance as $entry) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($entry['Date']); ?></td>
                            <td><?php echo htmlspecialchars($entry['user_status']); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } ?>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    const attendanceChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [{
                label: 'Attendance Status',
                data: <?php echo json_encode(array_map(function($status) {
                    return $status === 'Present' ? 1 : 0;
                }, $statuses)); ?>,
                backgroundColor: <?php echo json_encode(array_map(function($status) {
                    return $status === 'Present' ? 'rgba(0, 128, 0, 0.6)' : 'rgba(255, 0, 0, 0.6)';
                }, $statuses)); ?>,
                borderColor: <?php echo json_encode(array_map(function($status) {
                    return $status === 'Present' ? 'rgba(0, 128, 0, 1)' : 'rgba(255, 0, 0, 1)';
                }, $statuses)); ?>,
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

    form {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
    }

    label {
        font-weight: bold;
        margin-right: 10px;
    }

    select {
        padding: 5px;
        border: 1px solid #ddd;
        border-radius: 4px;
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

    .btn:hover {
        background-color: #003399;
    }

    .attendance-summary {
        margin-bottom: 20px;
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
</style>
