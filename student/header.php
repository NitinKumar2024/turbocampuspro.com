<?php
session_start();
// Check if the user is logged in and is a principal
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'students') {
    header('Location: ../');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="../faculty/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <header>
        <div class="logo">
            <img src="../principal/img/logo.png" alt="Logo">
            <h1>Student Dashboard</h1>
        </div>
        <nav>
            <ul id="nav-links">
                <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="view_attendance.php"><i class="fas fa-user-check"></i> View Attendance</a></li>
               
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                
            </ul>
            <div id="hamburger-menu">
                <i class="fas fa-bars"></i>
            </div>
        </nav>
    </header>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
    const hamburgerMenu = document.getElementById('hamburger-menu');
    const navLinks = document.getElementById('nav-links');

    hamburgerMenu.addEventListener('click', function() {
        navLinks.classList.toggle('show');
    });
});

    </script>
</body>
</html>


