<?php
session_start();
include('header.php');
require_once '../config.php';

// Check if principal is logged in
if (!isset($_SESSION['college_code'])) {
    header('Location: login.php');
    exit();
}

$college_code = $_SESSION['college_code'];

try {
    // Get total faculty count
    $facultyQuery = "SELECT COUNT(*) as faculty_count FROM faculty WHERE college_code = ?";
    $stmt = $conn->prepare($facultyQuery);
    $stmt->bind_param('s', $college_code);
    $stmt->execute();
    $facultyCount = $stmt->get_result()->fetch_assoc()['faculty_count'];
  
  	    // Get total student count
    $studentQuery = "SELECT COUNT(*) as student_count FROM students WHERE college_code = ?";
    $stmt = $conn->prepare($studentQuery);
    $stmt->bind_param('s', $college_code);
    $stmt->execute();
    $studentCount = $stmt->get_result()->fetch_assoc()['student_count'];

    // Get total subjects count
    $subjectQuery = "SELECT COUNT(*) as subject_count FROM allsubject WHERE college_code = ?";
    $stmt = $conn->prepare($subjectQuery);
    $stmt->bind_param('s', $college_code);
    $stmt->execute();
    $subjectCount = $stmt->get_result()->fetch_assoc()['subject_count'];

    // Get total branches
    $branchQuery = "SELECT COUNT(*) as branch_count FROM branch WHERE college_code = ?";
    $stmt = $conn->prepare($branchQuery);
    $stmt->bind_param('s', $college_code);
    $stmt->execute();
    $branchCount = $stmt->get_result()->fetch_assoc()['branch_count'];

    // Get recent subject assignments
    $assignmentQuery = "SELECT f.username, s.Subject, b.branch_name, fsa.assignment_date
                       FROM faculty_subject_assignments fsa
                       JOIN faculty f ON fsa.faculty_id = f.faculty_id
                       JOIN allsubject s ON fsa.subject_id = s.subject_id
                       LEFT JOIN branch b ON s.Branch_Code = b.branch_code
                       WHERE f.college_code = ?
                       ORDER BY fsa.assignment_date DESC
                       LIMIT 5";
    $stmt = $conn->prepare($assignmentQuery);
    $stmt->bind_param('s', $college_code);
    $stmt->execute();
    $recentAssignments = $stmt->get_result();

} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Principal Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #f5f6fa;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --danger-color: #e74c3c;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f4f6f9;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .dashboard-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .dashboard-header h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
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
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        .stat-card h3 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }

        .stat-card p {
            color: #7f8c8d;
            font-size: 1.1rem;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .dashboard-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .dashboard-card h2 {
            color: #2c3e50;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--secondary-color);
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 1rem 2rem;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: #357abd;
            transform: translateY(-2px);
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background: #27ae60;
            transform: translateY(-2px);
        }

        .recent-activity {
            margin-top: 1rem;
        }

        .activity-item {
            padding: 1rem;
            border-bottom: 1px solid var(--secondary-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                text-align: center;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <h1>Principal Dashboard</h1>
            <p>Welcome back! Here's your college overview.</p>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <i class="fas fa-chalkboard-teacher"></i>
                <h3><?php echo $facultyCount; ?></h3>
                <p>Total Faculty Members</p>
            </div>
           <div class="stat-card">
                <i class="fas fa-chalkboard-teacher"></i>
                <h3><?php echo $studentCount; ?></h3>
                <p>Total Students</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-book"></i>
                <h3><?php echo $subjectCount; ?></h3>
                <p>Total Subjects</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-code-branch"></i>
                <h3><?php echo $branchCount; ?></h3>
                <p>Total Branches</p>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h2><i class="fas fa-history"></i> Recent Subject Assignments</h2>
                <div class="recent-activity">
                    <?php while ($assignment = $recentAssignments->fetch_assoc()): ?>
                        <div class="activity-item">
                            <div>
                                <strong><?php echo htmlspecialchars($assignment['username']); ?></strong>
                                <p><?php echo htmlspecialchars($assignment['Subject']); ?> - 
                                   <?php echo htmlspecialchars($assignment['branch_name']); ?></p>
                            </div>
                            <div>
                                <?php echo date('M d, Y', strtotime($assignment['assignment_date'])); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <div class="action-buttons">
            <a href="assign_faculty.php" class="btn btn-primary">
                <i class="fas fa-book-reader"></i> Assign Subjects to Faculty
            </a>
            <a href="faculty_task_assign.php" class="btn btn-success">
                <i class="fas fa-tasks"></i> Assign Tasks to Faculty
            </a>
            
        </div>
    </div>

    <script>
        // Add any interactive features or real-time updates here
        document.addEventListener('DOMContentLoaded', function() {
            // Animation for stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-10px)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>