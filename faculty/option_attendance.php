<?php
include('header.php');

// Redirect to login if not logged in
if (!isset($_SESSION['username'])) {
    header('Location: ../');
    exit();
}

// Include database connection file
include('../config.php');

// Fetch user data from session
$username = $_SESSION['username'];
$email = $_SESSION['email'];
$number = $_SESSION['number'];
$college_code = $_SESSION['college_code'];
$branch = $_GET['branch_code'];
$semester = $_GET['semester'];
$subject = $_GET['subject'];
$subject_code = $_GET['subject_code'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard</title>
    <style>
     
        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 70vh;
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            width: 350px;
            text-align: center;
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-10px);
        }
        .card h2 {
            margin-bottom: 20px;
            font-size: 1.8em;
            color: #007BFF;
        }
        .card button {
            background-color: #007BFF;
            color: white;
            border: none;
            padding: 12px 25px;
            margin: 15px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s, box-shadow 0.3s;
        }
        .card button:hover {
            background-color: #0056b3;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }
        form {
            display: inline;
        }
    </style>
</head>
<body>
   
    <div class="container">
        <div class="card">
            <h2>Options</h2>
            <form action="make_attendance.php" method="GET">
                <input type="hidden" name="branch_code" value="<?php echo urlencode($branch); ?>">
                <input type="hidden" name="semester" value="<?php echo urlencode($semester); ?>">
                <input type="hidden" name="subject" value="<?php echo htmlspecialchars($subject); ?>">
                <input type="hidden" name="subject_code" value="<?php echo urlencode($subject_code); ?>">
                <button type="submit">Make Attendance</button>
            </form>
            <form action="export_attendance.php" method="GET">
                <input type="hidden" name="branch_code" value="<?php echo urlencode($branch); ?>">
                <input type="hidden" name="semester" value="<?php echo urlencode($semester); ?>">
                <input type="hidden" name="subject" value="<?php echo urlencode($subject); ?>">
                <input type="hidden" name="subject_code" value="<?php echo urlencode($subject_code); ?>">
                <button type="submit">Export Attendance</button>
            </form>
        </div>
    </div>
</body>
</html>
