<?php
session_start();
// Check if the user is logged in and is a principal
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'faculty') {
    header('Location: ../');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #eef2f3;
            color: #333;
        }
        header {
            background-color: #0044cc;
            color: white;
            padding: 15px 0;
            position: relative;
        }
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .logo {
            display: flex;
            align-items: center;
        }
        .logo img {
            height: 50px;
            margin-right: 10px;
        }
        .logo h1 {
            font-size: 1.5em;
            margin: 0;
        }
        nav ul {
            list-style-type: none;
            margin: 0;
            padding: 0;
            display: flex;
        }
        nav ul li {
            margin: 0 10px;
        }
        nav ul li a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            display: flex;
            align-items: center;
        }
        nav ul li a:hover {
            background-color: #003399;
        }
        nav ul li a i {
            margin-right: 8px;
        }
        #hamburger-menu {
            display: none;
            cursor: pointer;
            font-size: 1.5em;
        }
        @media (max-width: 768px) {
            nav ul {
                display: none;
                flex-direction: column;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background-color: #0044cc;
                padding: 20px;
            }
            nav ul.show {
                display: flex;
            }
            nav ul li {
                margin: 10px 0;
            }
            #hamburger-menu {
                display: block;
            }
        }
      footer {
    background-color: #0044cc;
    color: white;
    text-align: center;
    padding: 15px 0;
    position: fixed;
    bottom: 0;
    width: 100%;
    box-shadow: 0 -4px 6px rgba(0, 0, 0, 0.1);
}
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <img src="../principal/img/logo.png" alt="Logo">
                <h1>Faculty Dashboard</h1>
            </div>
            <nav>
                <ul id="nav-links">
                    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="manage-attendance.php"><i class="fas fa-user-check"></i> Manage Attendance</a></li>
                    <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                </ul>
                <div id="hamburger-menu">
                    <i class="fas fa-bars"></i>
                </div>
            </nav>
        </div>
    </header>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hamburgerMenu = document.getElementById('hamburger-menu');
            const navLinks = document.getElementById('nav-links');
            hamburgerMenu.addEventListener('click', function() {
                navLinks.classList.toggle('show');
            });

            // Close menu when clicking outside
            document.addEventListener('click', function(event) {
                const isClickInside = navLinks.contains(event.target) || hamburgerMenu.contains(event.target);
                if (!isClickInside && navLinks.classList.contains('show')) {
                    navLinks.classList.remove('show');
                }
            });

            // Adjust menu visibility on window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    navLinks.classList.remove('show');
                }
            });
        });
    </script>
</body>
</html>

