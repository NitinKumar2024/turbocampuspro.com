<?php
session_start();
// Check if the user is logged in and is a principal
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'principal') {
    header('Location: ../');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Principal Dashboard</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
        }

        /* Header styles */
        header {
            background: linear-gradient(135deg, #6e8efb, #a777e3);
            padding: 1rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
        }

        .logo img {
            height: 50px;
            margin-right: 1rem;
        }

        .logo h1 {
            color: #fff;
            font-size: 1.5rem;
            font-weight: 700;
        }

        /* Navigation styles */
        nav {
            display: flex;
            align-items: center;
        }

        #nav-links {
            display: flex;
            list-style-type: none;
        }

        #nav-links li {
            margin-left: 1.5rem;
        }

        #nav-links a {
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        #nav-links a:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        #nav-links a i {
            margin-right: 0.5rem;
        }

        #hamburger-menu {
            display: none;
            color: #fff;
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* Responsive styles */
        @media screen and (max-width: 768px) {
            .header-container {
                flex-direction: column;
                align-items: flex-start;
            }

            nav {
                width: 100%;
                margin-top: 1rem;
            }

            #nav-links {
                display: none;
                flex-direction: column;
                width: 100%;
                background-color: #fff;
                border-radius: 5px;
                overflow: hidden;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }

            #nav-links.show {
                display: flex;
            }

            #nav-links li {
                margin: 0;
                width: 100%;
            }

            #nav-links a {
                color: #333;
                padding: 1rem;
                display: block;
                border-bottom: 1px solid #eee;
                transition: background-color 0.3s ease;
            }

            #nav-links a:hover {
                background-color: #f4f4f4;
            }

            #hamburger-menu {
                display: block;
                position: absolute;
                top: 1rem;
                right: 1rem;
            }
        }

        /* Your existing main content styles */
        main {
            padding: 40px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        /* ... (rest of your existing styles) ... */
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <img src="../principal/img/logo.png" alt="Logo">
                <h1>Principal Dashboard</h1>
            </div>
            <nav>
                <ul id="nav-links">
                    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="manage-faculty.php"><i class="fas fa-chalkboard-teacher"></i> Manage Faculty</a></li>
                    <li><a href="display_branch.php"><i class="fas fa-user-graduate"></i> Manage Students</a></li>
                    <li><a href="upload_subject.php"><i class="fas fa-upload"></i> Upload Subject</a></li>
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
        });
    </script>
</body>
</html>

