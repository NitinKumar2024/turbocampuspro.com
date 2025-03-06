<?php
session_start();
require_once '../config.php';
include('header.php');
 

// Check if user is logged in and is a principal
if (!isset($_SESSION['principal_id']) || $_SESSION['role'] !== 'principal') {
    header('Location: login.php');
    exit();
}

$college_code = $_SESSION['college_code'];
$principal_id = $_SESSION['principal_id'];

// Function to handle file upload
function handleFileUpload() {
    if (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $target_dir = "../uploads/tasks/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_extension = strtolower(pathinfo($_FILES["attachment"]["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;

    // Validate file type
    $allowed_types = ['pdf', 'doc', 'docx', 'txt'];
    if (!in_array($file_extension, $allowed_types)) {
        throw new Exception("Only PDF, DOC, DOCX, and TXT files are allowed.");
    }

    if (move_uploaded_file($_FILES["attachment"]["tmp_name"], $target_file)) {
        return $target_file;
    } else {
        throw new Exception("Failed to upload file.");
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->begin_transaction();

        if (isset($_POST['create_task'])) {
            // Validate input
            $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
            $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
            $due_date = filter_input(INPUT_POST, 'due_date', FILTER_SANITIZE_STRING);
            $priority = filter_input(INPUT_POST, 'priority', FILTER_SANITIZE_STRING);
            $faculty_ids = $_POST['faculty_ids'] ?? [];

            if (empty($title) || empty($due_date) || empty($priority) || empty($faculty_ids)) {
                throw new Exception("Please fill all required fields.");
            }

            // Handle file upload
            $attachment_path = handleFileUpload();

            // Insert task
            $task_query = "INSERT INTO tasks (title, description, due_date, priority, created_by, attachment_path) 
                          VALUES (?, ?, ?, ?, ?, ?)";
            $task_stmt = $conn->prepare($task_query);
            $task_stmt->bind_param('ssssss', $title, $description, $due_date, $priority, $principal_id, $attachment_path);
            $task_stmt->execute();
            $task_id = $conn->insert_id;

            // Assign task to selected faculty members
            $assign_query = "INSERT INTO task_assignments (task_id, faculty_id) VALUES (?, ?)";
            $assign_stmt = $conn->prepare($assign_query);
            foreach ($faculty_ids as $faculty_id) {
                $assign_stmt->bind_param('ii', $task_id, $faculty_id);
                $assign_stmt->execute();
            }

            $conn->commit();
            $_SESSION['success'] = "Task created and assigned successfully!";
        }

        // Handle task deletion
        if (isset($_POST['delete_task'])) {
            $task_id = filter_input(INPUT_POST, 'task_id', FILTER_SANITIZE_NUMBER_INT);
            
            // Delete task assignments first
            $delete_assignments = "DELETE FROM task_assignments WHERE task_id = ?";
            $stmt = $conn->prepare($delete_assignments);
            $stmt->bind_param('i', $task_id);
            $stmt->execute();

            // Then delete the task
            $delete_task = "DELETE FROM tasks WHERE task_id = ? AND created_by = ?";
            $stmt = $conn->prepare($delete_task);
            $stmt->bind_param('ii', $task_id, $principal_id);
            $stmt->execute();

            $conn->commit();
            $_SESSION['success'] = "Task deleted successfully!";
        }

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
    }
   
}

// Fetch faculty list
$faculty_query = "SELECT faculty_id, username, email FROM faculty WHERE college_code = ?";
$faculty_stmt = $conn->prepare($faculty_query);
$faculty_stmt->bind_param('s', $college_code);
$faculty_stmt->execute();
$faculty_result = $faculty_stmt->get_result();

// Fetch existing tasks with assignment details
$tasks_query = "
    SELECT t.*, 
           GROUP_CONCAT(f.username) as assigned_faculty,
           GROUP_CONCAT(f.faculty_id) as faculty_ids,
           COUNT(DISTINCT ta.faculty_id) as assigned_count,
           COUNT(DISTINCT CASE WHEN ta.completed_at IS NOT NULL THEN ta.faculty_id END) as completed_count
    FROM tasks t
    LEFT JOIN task_assignments ta ON t.task_id = ta.task_id
    LEFT JOIN faculty f ON ta.faculty_id = f.faculty_id
    WHERE t.created_by = ?
    GROUP BY t.task_id
    ORDER BY t.due_date ASC";
$tasks_stmt = $conn->prepare($tasks_query);
$tasks_stmt->bind_param('i', $principal_id);
$tasks_stmt->execute();
$tasks_result = $tasks_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Management - Principal Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4a90e2;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --light-gray: #f8f9fa;
            --dark-gray: #343a40;
        }

        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color), #2c5282);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .dashboard-header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 600;
        }

        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .stat-card i {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        .task-form {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .task-form h2 {
            color: var(--dark-gray);
            margin-bottom: 1.5rem;
            font-weight: 600;
            border-bottom: 2px solid var(--light-gray);
            padding-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark-gray);
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #357abd;
            transform: translateY(-1px);
        }

        .task-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px 0;
        }

        .task-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .task-card:hover {
            transform: translateY(-5px);
        }

        .task-card h3 {
            color: var(--dark-gray);
            margin-bottom: 1rem;
            font-size: 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .priority-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .priority-high {
            background-color: #ffd7d7;
            color: var(--danger-color);
        }

        .priority-medium {
            background-color: #fff3cd;
            color: #856404;
        }

        .priority-low {
            background-color: #d4edda;
            color: var(--success-color);
        }

        .task-meta {
            display: flex;
            align-items: center;
            color: #666;
            font-size: 0.875rem;
            margin: 0.5rem 0;
        }

        .task-meta i {
            margin-right: 0.5rem;
        }

        .progress-bar {
            height: 8px;
            background: var(--light-gray);
            border-radius: 4px;
            overflow: hidden;
            margin: 1rem 0;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), #357abd);
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        .task-actions {
            display: flex;
            gap: 10px;
            margin-top: 1rem;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .btn-info {
            background-color: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background-color: #138496;
        }

        .select2-container--default .select2-selection--multiple {
            border: 1px solid #ddd;
            border-radius: 8px;
            min-height: 45px;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }

        .alert i {
            margin-right: 0.5rem;
            font-size: 1.25rem;
        }

        .alert-success {
            background-color: #d4edda;
            color: var(--success-color);
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: var(--danger-color);
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .dashboard-header {
                padding: 1.5rem;
            }
            
            .task-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <h1><i class="fas fa-tasks"></i> Task Management Dashboard</h1>
        </div>

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

        <div class="dashboard-stats">
            <div class="stat-card">
                <i class="fas fa-clipboard-list"></i>
                <h3>Total Tasks</h3>
                <p><?php echo $tasks_result->num_rows; ?></p>
            </div>
            <!-- Add more stat cards as needed -->
        </div>

        <div class="task-form">
            <h2><i class="fas fa-plus-circle"></i> Create New Task</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title"><i class="fas fa-heading"></i> Task Title *</label>
                    <input type="text" name="title" id="title" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="description"><i class="fas fa-align-left"></i> Description</label>
                    <textarea name="description" id="description" class="form-control" rows="4"></textarea>
                </div>

                <div class="form-group">
                    <label for="due_date"><i class="fas fa-calendar"></i> Due Date *</label>
                    <input type="date" name="due_date" id="due_date" class="form-control" 
                           min="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label for="priority"><i class="fas fa-flag"></i> Priority *</label>
                    <select name="priority" id="priority" class="form-control" required>
                        <option value="Low">Low</option>
                        <option value="Medium">Medium</option>
                        <option value="High">High</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="faculty_ids"><i class="fas fa-users"></i> Assign to Faculty *</label>
                    <select name="faculty_ids[]" id="faculty_ids" class="form-control" multiple required>
                        <?php while ($faculty = $faculty_result->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($faculty['faculty_id']); ?>">
                                <?php echo htmlspecialchars($faculty['username'] . ' (' . $faculty['email'] . ')'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="attachment"><i class="fas fa-paperclip"></i> Attachment</label>
                    <input type="file" name="attachment" id="attachment" class="form-control">
                    <small class="text-muted">Allowed files: PDF, DOC, DOCX, TXT</small>
                </div>

                <button type="submit" name="create_task" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Task
                </button>
            </form>
        </div>

        <h2><i class="fas fa-list"></i> Active Tasks</h2>
        <div class="task-list">
            <?php while ($task = $tasks_result->fetch_assoc()): ?>
                <div class="task-card">
                    <h3>
                        <?php echo htmlspecialchars($task['title']); ?>
                        <span class="priority-badge priority-<?php echo strtolower($task['priority']); ?>">
                            <?php echo htmlspecialchars($task['priority']); ?>
                        </span>
                    </h3>
                    
                    <?php if ($task['description']): ?>
                        <p><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
                    <?php endif; ?>

                    <div class="task-meta">
                        <i class="fas fa-calendar-alt"></i>
                        Due: <?php echo date('F j, Y', strtotime($task['due_date'])); ?>
                    </div>
                    
                    <div class="task-meta">
                        <i class="fas fa-users"></i>
                        Assigned to: <?php echo htmlspecialchars($task['assigned_faculty']); ?>
                    </div>
                    
                    <div class="progress-bar">
                        <div class="progress-bar-fill" style="width: <?php 
                            echo $task['assigned_count'] > 0 
                                ? ($task['completed_count'] / $task['assigned_count'] * 100) 
                                : 0; 
                        ?>%"></div>
                    </div>
                    <div class="task-meta">
                        <i class="fas fa-chart-pie"></i>
                        Progress: <?php echo $task['completed_count']; ?>/<?php echo $task['assigned_count']; ?> completed
                    </div>

                    <div class="task-actions">
                        <?php if ($task['attachment_path']): ?>
                            <a href="<?php echo htmlspecialchars($task['attachment_path']); ?>" 
                               target="_blank" class="btn btn-sm btn-info">
                                <i class="fas fa-paperclip"></i> View Attachment
                            </a>
                        <?php endif; ?>

                        <form method="POST" style="display: inline;" 
                              onsubmit="return confirm('Are you sure you want to delete this task?');">
                            <input type="hidden" name="task_id" value="<?php echo $task['task_id']; ?>">
                            <button type="submit" name="delete_task" class="btn btn-sm btn-danger">
                                <i class="fas fa-trash-alt"></i> Delete
                            </button>
                        </form>
                    </div>
          </div>
         <?php endwhile; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2 for faculty selection
            $('#faculty_ids').select2({
                placeholder: 'Select faculty members',
                width: '100%',
                theme: 'classic'
            });

            // File input change handler
            $('#attachment').on('change', function() {
                const file = this.files[0];
                if (file) {
                    const fileSize = file.size / 1024 / 1024; // Convert to MB
                    const fileType = file.name.split('.').pop().toLowerCase();
                    const allowedTypes = ['pdf', 'doc', 'docx', 'txt'];

                    if (fileSize > 10) {
                        alert('File size should not exceed 10MB');
                        this.value = '';
                        return;
                    }

                    if (!allowedTypes.includes(fileType)) {
                        alert('Only PDF, DOC, DOCX, and TXT files are allowed');
                        this.value = '';
                        return;
                    }
                }
            });

            // Form validation for task creation only
            $('form:has([name="create_task"])').on('submit', function(e) {
                const title = $('#title').val().trim();
                const dueDate = $('#due_date').val();
                const priority = $('#priority').val();
                const facultyIds = $('#faculty_ids').val();

                if (!title || !dueDate || !priority || !facultyIds.length) {
                    e.preventDefault();
                    alert('Please fill all required fields');
                    return false;
                }

                return confirm('Are you sure you want to create this task?');
            });

            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);

            // Initialize tooltips if you're using Bootstrap
            if (typeof $().tooltip === 'function') {
                $('[data-toggle="tooltip"]').tooltip();
            }

            // Responsive handling for task cards
            function adjustTaskCards() {
                const windowWidth = $(window).width();
                const taskCards = $('.task-card');
                
                if (windowWidth < 768) {
                    taskCards.css('width', '100%');
                } else {
                    taskCards.css('width', '');
                }
            }

            // Call on load and window resize
            adjustTaskCards();
            $(window).on('resize', adjustTaskCards);

            // Add dynamic due date validation
            const dueDateInput = $('#due_date');
            const today = new Date().toISOString().split('T')[0];
            dueDateInput.attr('min', today);

            // Progress bar animation
            $('.progress-bar-fill').each(function() {
                const width = $(this).css('width');
                $(this).css('width', '0').animate({
                    width: width
                }, 1000);
            });
        });
    </script>
</body>
</html>