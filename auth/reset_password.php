<?php
// Database connection details
include '../config.php';

// Check if token is provided and valid
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $email = $_GET['email'];
    $role = $_GET['role'];
 

    // Retrieve token and expiration date from the database
    $sql = "SELECT * FROM reset_tokens WHERE token = '$token' AND email = '$email'";
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $expire_date = strtotime($row['expire_date']);
        if ($expire_date > time()) {
            // Token is valid, allow user to reset password
            // Reset password form...
            ?>
        <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            margin: 0 auto;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }

        form {
            margin-top: 20px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 10px;
        }

        input[type="password"], input[type="text"] {
            width: calc(100% - 22px);
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        input[type="checkbox"] {
            margin-left: 2px;
        }

        .show-password {
            margin-bottom: 10px;
        }

        input[type="submit"] {
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: 100%;
        }

        input[type="submit"]:hover {
            background-color: #0056b3;
        }

        .error-message {
            color: red;
            font-size: 14px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Reset Password</h1>
        <form action="update_password.php" method="POST" onsubmit="return validateForm()">
            <label for="password">New Password:</label><br>
            <input type="password" id="password" name="password" required><br>
            
            <label for="confirm_password">Confirm Password:</label><br>
            <input type="password" id="confirm_password" name="confirm_password" required><br>

            <div class="show-password">
                <input type="checkbox" onclick="togglePassword()"> Show Password
            </div>
            
            <input type="hidden" name="token" value="<?php echo $token; ?>">
            <input type="hidden" name="email" value="<?php echo $email; ?>">
            <input type="hidden" name="role" value="<?php echo $role; ?>">

            <input type="submit" value="Reset Password">
            <div id="error-msg" class="error-message"></div>
        </form>
    </div>

    <script>
        function togglePassword() {
            var x = document.getElementById("password");
            var y = document.getElementById("confirm_password");
            if (x.type === "password") {
                x.type = "text";
                y.type = "text";
            } else {
                x.type = "password";
                y.type = "password";
            }
        }

        function validateForm() {
            var password = document.getElementById("password").value;
            var confirm_password = document.getElementById("confirm_password").value;
            var error_msg = document.getElementById("error-msg");

            if (password !== confirm_password) {
                error_msg.innerHTML = "Passwords do not match.";
                return false;
            } else {
                error_msg.innerHTML = "";
                return true;
            }
        }
    </script>
</body>
</html>


            <?php
        } else {
            echo "Reset link has expired.";
        }
    } else {
        echo "Invalid reset token.";
    }

    mysqli_close($conn);
} else {
    echo "Token not provided.";
}
?>
