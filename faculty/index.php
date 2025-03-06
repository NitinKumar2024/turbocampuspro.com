<?php
session_start();

// Security check
if (!isset($_SESSION['username']) || !isset($_SESSION['email'])) {
    header('Location: ../index.php');
    exit();
}

require_once('../config.php');

// Initialize variables from session
$username = $_SESSION['username'];
$college_code = $_SESSION['college_code'];
$email = $_SESSION['email'];
$faculty_id = $_SESSION['faculty_id'] ?? null;

// Function to get quick stats
function getFacultyStats($conn, $faculty_id, $email) {
    $stats = [
        'total_subjects' => 0,
        'total_branches' => 0,
        'total_students' => 0,
        'upcoming_classes' => 0
    ];
    
    // Get total subjects
    $query = "SELECT COUNT(DISTINCT fsa.subject_id) as total_subjects,
                     COUNT(DISTINCT s.Branch_Code) as total_branches
              FROM faculty_subject_assignments fsa
              JOIN allsubject s ON fsa.subject_id = s.subject_id
              WHERE fsa.faculty_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $faculty_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    $stats['total_subjects'] = $result['total_subjects'] ?? 0;
    $stats['total_branches'] = $result['total_branches'] ?? 0;
    
    return $stats;
}

// Get faculty stats
$faculty_stats = getFacultyStats($conn, $faculty_id, $email);

// Fetch assigned subjects with detailed information
$query = "SELECT DISTINCT
            s.Subject,
            s.Subject_Code,
            s.semester,
            b.branch_name,
            fsa.assignment_date
          FROM faculty_subject_assignments fsa
          JOIN allsubject s ON fsa.subject_id = s.subject_id
          JOIN branch b ON s.Branch_Code = b.branch_code
          WHERE fsa.faculty_id = ?
          ORDER BY s.semester, s.Subject";

$stmt = $conn->prepare($query);
$stmt->bind_param('s', $faculty_id);
$stmt->execute();
$assigned_subjects = $stmt->get_result();

include('header.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #f5f6fa;
            --text-color: #2c3e50;
            --border-color: #e1e8ed;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --danger-color: #e74c3c;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--secondary-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .welcome-banner {
            background: linear-gradient(135deg, var(--primary-color), #2980b9);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .welcome-banner h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 500;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card i {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        .stat-card h3 {
            margin: 0;
            font-size: 2rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .stat-card p {
            margin: 0.5rem 0 0;
            color: #666;
            font-size: 0.9rem;
        }

        .subjects-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .subjects-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .subjects-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 1rem;
        }

        .subjects-table th,
        .subjects-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .subjects-table th {
            background-color: var(--secondary-color);
            font-weight: 600;
            color: var(--text-color);
        }

        .subjects-table tr:hover {
            background-color: #f8f9fa;
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .badge-primary {
            background-color: #e3f2fd;
            color: var(--primary-color);
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #357abd;
        }

        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }

            .subjects-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="welcome-banner">
            <h1>Welcome, <?php echo htmlspecialchars($username); ?></h1>
            <p>Manage your subjects and track your teaching activities</p>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <i class="fas fa-book"></i>
                <h3><?php echo $faculty_stats['total_subjects']; ?></h3>
                <p>Total Subjects</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-code-branch"></i>
                <h3><?php echo $faculty_stats['total_branches']; ?></h3>
                <p>Branches</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-calendar-alt"></i>
                <h3><?php echo date('M Y'); ?></h3>
                <p>Current Term</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-clock"></i>
                <h3>Active</h3>
                <p>Status</p>
            </div>
        </div>

        <div class="subjects-section">
            <div class="subjects-header">
                <h2>Assigned Subjects</h2>
                <button class="action-btn btn-primary">
                    <i class="fas fa-download"></i> Export Schedule
                </button>
            </div>

            <?php if ($assigned_subjects->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="subjects-table">
                    <thead>
                        <tr>
                            <th>Subject Name</th>
                            <th>Code</th>
                            <th>Branch</th>
                            <th>Semester</th>
                            <th>Assigned Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($subject = $assigned_subjects->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($subject['Subject']); ?></td>
                            <td>
                                <span class="badge badge-primary">
                                    <?php echo htmlspecialchars($subject['Subject_Code']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($subject['branch_name']); ?></td>
                            <td><?php echo htmlspecialchars($subject['semester']); ?></td>
                            <td><?php echo date('d M Y', strtotime($subject['assignment_date'])); ?></td>
                            <td>
                                <button class="action-btn btn-primary" onclick="viewDetails('<?php echo htmlspecialchars($subject['Subject_Code']); ?>')">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="no-data">
                <p>No subjects have been assigned yet.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function viewDetails(subjectCode) {
            // Implement subject details view functionality
            alert('Viewing details for subject: ' + subjectCode);
        }

        // Add smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>