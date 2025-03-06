<?php
session_start();
include('header.php');
require_once '../config.php';

// Check if user is logged in and has necessary permissions
if (!isset($_SESSION['college_code'])) {
    header('Location: login.php');
    exit();
}

$college_code = $_SESSION['college_code'];

// Function to handle database errors
function handleDatabaseError($conn, $context = '') {
    $_SESSION['error'] = "Database error $context: " . $conn->error;
    error_log("Database error $context: " . $conn->error);
}

try {
    // Fetch all faculty with prepared statement
    $facultyQuery = "SELECT faculty_id, username, email 
                    FROM faculty 
                    WHERE college_code = ?";
    $facultyStmt = $conn->prepare($facultyQuery);
    $facultyStmt->bind_param('s', $college_code);
    $facultyStmt->execute();
    $facultyResult = $facultyStmt->get_result();

    // Fetch all subjects with branch information using JOIN
    $subjectQuery = "SELECT s.subject_id, s.Subject, s.Subject_Code, 
                            s.Branch_Code, s.semester, b.branch_name
                     FROM allsubject s
                     LEFT JOIN branch b ON s.Branch_Code = b.branch_code
                     WHERE s.college_code = ?
                     ORDER BY s.semester, s.Subject";
    $subjectStmt = $conn->prepare($subjectQuery);
    $subjectStmt->bind_param('s', $college_code);
    $subjectStmt->execute();
    $subjectResult = $subjectStmt->get_result();

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['assign_subject'])) {
            // Validate input
            $faculty_id = filter_input(INPUT_POST, 'faculty_id', FILTER_SANITIZE_STRING);
            $subject_id = filter_input(INPUT_POST, 'subject_id', FILTER_SANITIZE_STRING);
            
            // Check if assignment already exists
            $checkQuery = "SELECT assignment_id FROM faculty_subject_assignments 
                          WHERE faculty_id = ? AND subject_id = ?";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->bind_param('ss', $faculty_id, $subject_id);
            $checkStmt->execute();
            $existingAssignment = $checkStmt->get_result()->fetch_assoc();

            if ($existingAssignment) {
                $_SESSION['error'] = "This subject is already assigned to the selected faculty.";
            } else {
                // Insert new assignment
                $insertQuery = "INSERT INTO faculty_subject_assignments 
                              (faculty_id, subject_id, assignment_date) 
                              VALUES (?, ?, NOW())";
                $insertStmt = $conn->prepare($insertQuery);
                $insertStmt->bind_param('ss', $faculty_id, $subject_id);
                
                if ($insertStmt->execute()) {
                    $_SESSION['success'] = "Subject assigned successfully!";
                } else {
                    handleDatabaseError($conn, 'while assigning subject');
                }
                $insertStmt->close();
            }
            $checkStmt->close();
        } elseif (isset($_POST['remove_assignment'])) {
            $assignment_id = filter_input(INPUT_POST, 'assignment_id', FILTER_SANITIZE_NUMBER_INT);
            $deleteQuery = "DELETE FROM faculty_subject_assignments WHERE assignment_id = ?";
            $deleteStmt = $conn->prepare($deleteQuery);
            $deleteStmt->bind_param('i', $assignment_id);
            
            if ($deleteStmt->execute()) {
                $_SESSION['success'] = "Assignment removed successfully!";
            } else {
                handleDatabaseError($conn, 'while removing assignment');
            }
            $deleteStmt->close();
        }
    }

    // Fetch existing assignments with detailed information
    $assignedQuery = "SELECT f.username, f.email, s.Subject, s.Subject_Code,
                             b.branch_name, s.semester, fsa.assignment_id,
                             fsa.assignment_date
                      FROM faculty_subject_assignments fsa
                      JOIN faculty f ON fsa.faculty_id = f.faculty_id
                      JOIN allsubject s ON fsa.subject_id = s.subject_id
                      LEFT JOIN branch b ON s.Branch_Code = b.branch_code
                      WHERE f.college_code = ?
                      ORDER BY f.username, s.semester";
    $assignedStmt = $conn->prepare($assignedQuery);
    $assignedStmt->bind_param('s', $college_code);
    $assignedStmt->execute();
    $assignedResult = $assignedStmt->get_result();

} catch (Exception $e) {
    error_log("Error in faculty subject management: " . $e->getMessage());
    $_SESSION['error'] = "An unexpected error occurred. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Subject Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #f5f6fa;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --text-color: #2c3e50;
            --border-color: #dcdde1;
        }

        body {
            background-color: var(--secondary-color);
            color: var(--text-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: transform 0.2s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background: var(--primary-color);
            color: white;
            padding: 1.5rem;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .card-body {
            padding: 1.5rem;
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid var(--success-color);
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid var(--danger-color);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-color);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--border-color);
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #357abd;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }

        .table-container {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .table th {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem;
            text-align: left;
        }

        .table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .table tr:hover {
            background-color: #f8f9fa;
        }

        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .badge-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-card i {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .stat-card h3 {
            font-size: 1.5rem;
            margin: 0.5rem 0;
        }

        .stat-card p {
            color: #666;
            margin: 0;
        }

        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .table-container {
                margin: 0 -1rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php 
                    echo htmlspecialchars($_SESSION['success']); 
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php 
                    echo htmlspecialchars($_SESSION['error']); 
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="stats-container">
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <h3><?php echo $facultyResult->num_rows; ?></h3>
                <p>Total Faculty</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-book"></i>
                <h3><?php echo $subjectResult->num_rows; ?></h3>
                <p>Total Subjects</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-tasks"></i>
                <h3><?php echo $assignedResult->num_rows; ?></h3>
                <p>Active Assignments</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-plus-circle"></i> Assign Subject to Faculty
            </div>
            <div class="card-body">
                <form method="POST" id="assignForm">
                    <div class="form-group">
                        <label class="form-label" for="faculty_id">Select Faculty:</label>
                        <select name="faculty_id" id="faculty_id" class="form-control" required>
                            <option value="">--Select Faculty--</option>
                            <?php while ($faculty = $facultyResult->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($faculty['faculty_id']); ?>">
                                    <?php echo htmlspecialchars($faculty['username'] . ' (' . $faculty['email'] . ')'); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="subject_id">Select Subject:</label>
                        <select name="subject_id" id="subject_id" class="form-control" required>
                            <option value="">--Select Subject--</option>
                            <?php while ($subject = $subjectResult->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($subject['subject_id']); ?>">
                                    <?php echo htmlspecialchars($subject['Subject'] . ' - ' . 
                                                              $subject['branch_name'] . ' - Semester ' . 
                                                              $subject['semester']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <button type="submit" name="assign_subject" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Assign Subject
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-list"></i> Current Subject Assignments
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Faculty Name</th>
                                <th>Email</th>
                                <th>Subject</th>
                                <th>Branch</th>
                                <th>Semester</th>
                                <th>Assigned Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($assignment = $assignedResult->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <i class="fas fa-user-tie text-primary"></i>
                                            <?php echo htmlspecialchars($assignment['username']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <i class="fas fa-envelope text-secondary"></i>
                                            <?php echo htmlspecialchars($assignment['email']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="badge badge-primary">
                                            <?php echo htmlspecialchars($assignment['Subject'] . 
                                                                      ' (' . $assignment['Subject_Code'] . ')'); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <i class="fas fa-code-branch"></i>
                                            <?php echo htmlspecialchars($assignment['branch_name']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="badge badge-primary">
                                            Semester <?php echo htmlspecialchars($assignment['semester']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <i class="fas fa-calendar-alt text-info"></i>
                                            <?php echo date('M d, Y', strtotime($assignment['assignment_date'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="assignment_id" 
                                                   value="<?php echo htmlspecialchars($assignment['assignment_id']); ?>">
                                            <button type="submit" name="remove_assignment" 
                                                    class="btn btn-danger btn-sm" 
                                                    onclick="return confirm('Are you sure you want to remove this assignment?');">
                                                <i class="fas fa-trash-alt"></i> Remove
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Form validation and enhanced UI interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Form validation
            const assignForm = document.getElementById('assignForm');
            if (assignForm) {
                assignForm.onsubmit = function(e) {
                    const faculty = document.getElementById('faculty_id').value;
                    const subject = document.getElementById('subject_id').value;
                    
                    if (!faculty || !subject) {
                        e.preventDefault();
                        alert('Please select both faculty and subject');
                        return false;
                    }
                    return true;
                };
            }

            // Add animation to alerts
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }, 3000);
            });

            // Enhanced select boxes
            const selects = document.querySelectorAll('select');
            selects.forEach(select => {
                select.addEventListener('change', function() {
                    if (this.value) {
                        this.style.borderColor = 'var(--primary-color)';
                    } else {
                        this.style.borderColor = 'var(--border-color)';
                    }
                });
            });
        });
    </script>
</body>
</html>