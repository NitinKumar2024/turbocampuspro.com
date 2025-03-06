<?php
// Start session
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['username'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Include database connection file
include('../config.php');

// Fetch user data from session
$username = $_SESSION['username'];
$email = $_SESSION['email'];
$number = $_SESSION['number'];
$college_code = $_SESSION['college_code'];

// Handle password change form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];

    // Validate passwords
    if ($new_password !== $confirm_new_password) {
        $error_message = "New passwords do not match.";
    } else {
        // Verify the current password
        $sql = "SELECT password FROM students WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->bind_result($hashed_password);
        $stmt->fetch();

        if (password_verify($current_password, $hashed_password)) {
            // Update the password
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt->close();

            $sql = "UPDATE students SET password = ? WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ss', $new_hashed_password, $email);

            if ($stmt->execute()) {
                $success_message = "Password changed successfully.";
            } else {
                $error_message = "Error changing password.";
            }
        } else {
            $error_message = "Current password is incorrect.";
        }

        $stmt->close();
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    session_unset();
    header('Location: ../');
    exit();
}

?>

<?php include('header.php'); ?>
<main>
    <section class="profile-section">
        <h2>Profile</h2>
        <div class="profile-info">
            <p><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
            <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($number); ?></p>
            <p><strong>College Code:</strong> <?php echo htmlspecialchars($college_code); ?></p>
        </div>
        
        <form action="profile.php" method="POST" class="form-card">
            <h3>Change Password</h3>
            <?php
            if (isset($success_message)) {
                echo "<p class='success-message'>{$success_message}</p>";
            }
            if (isset($error_message)) {
                echo "<p class='error-message'>{$error_message}</p>";
            }
            ?>
            <div class="form-group">
                <label for="current_password">Current Password:</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            <div class="form-group">
                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>
            <div class="form-group">
                <label for="confirm_new_password">Confirm New Password:</label>
                <input type="password" id="confirm_new_password" name="confirm_new_password" required>
            </div>
            <div class="form-group">
                <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
            </div>
        </form>
        
        <form action="" method="GET" id="logoutForm">
            <div class="form-group">
                <button type="submit" name="logout" class="btn btn-danger">Logout</button>
            </div>
        </form>

<script>
    document.getElementById('logoutForm').addEventListener('submit', function(event) {
        var isConfirmed = confirm('Are you sure you want to logout?');
        if (!isConfirmed) {
            event.preventDefault(); // Prevent form submission if user cancels
        }
    });
</script>
    </section>
</main>
<footer>
    <p>&copy; 2024 Student Dashboard. All rights reserved.</p>
</footer>

<style>


    main {
        padding: 40px 20px;
        max-width: 800px;
        margin: 0 auto;
    }

    .profile-section {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .profile-section h2 {
        font-size: 2em;
        margin-bottom: 20px;
        text-align: center;
        color: #0044cc;
    }

    .profile-info {
        margin-bottom: 40px;
        border-bottom: 1px solid #ddd;
        padding-bottom: 20px;
    }

    .profile-info p {
        font-size: 1.1em;
        margin: 10px 0;
    }

    .form-card {
        background: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        margin-bottom: 20px;
    }

    .form-card h3 {
        font-size: 1.5em;
        margin-bottom: 20px;
        color: #333;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        font-weight: bold;
        margin-bottom: 5px;
    }

    .form-group input {
        width: 100%;
        padding: 10px;
        font-size: 1em;
        border: 1px solid #ccc;
        border-radius: 5px;
    }

    .form-group .btn {
        display: inline-block;
        width: 100%;
        padding: 10px;
        font-size: 1em;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .form-group .btn-primary {
        background-color: #0044cc;
    }

    .form-group .btn-primary:hover {
        background-color: #003399;
    }

    .form-group .btn-danger {
        background-color: #e74c3c;
    }

    .form-group .btn-danger:hover {
        background-color: #c0392b;
    }

    .success-message {
        color: green;
        font-weight: bold;
    }

    .error-message {
        color: red;
        font-weight: bold;
    }

    footer {
        background-color: #0044cc;
        color: white;
        text-align: center;
        padding: 15px 0;
        margin-top: 20px;
        position: fixed;
        bottom: 0;
        width: 100%;
        box-shadow: 0 -4px 6px rgba(0, 0, 0, 0.1);
    }
</style>


