<?php
session_start();

// Enable error reporting for debugging purposes
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Including the configuration file
require_once '../config.php';

$role = isset($_GET['role']) ? $_GET['role'] : '';
$error = '';

// Check if the user is already logged in
if (isset($_SESSION['role'])) {
    // Redirect the user to their respective dashboard based on their role
    switch ($_SESSION['role']) {
        case 'students':
            header("Location: ../student/");
            exit();
        case 'faculty':
            
            header("Location: ../faculty/");
            exit();
        case 'principal':
            header("Location: ../principal/");
            exit();
        default:
            // Redirect to a default page if the role is not recognized
            header("Location: index.php");
            exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validate role to prevent SQL injection
    $valid_roles = ['students', 'faculty', 'principal'];
    if (!in_array($role, $valid_roles)) {
        $error = "Invalid role.";
    } else {
        // Prepare the SQL statement
        $stmt = $conn->prepare("SELECT * FROM `$role` WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $role;
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['number'] = $user['number'];
                    $_SESSION['college_code'] = $user['college_code'];

                    switch ($role) {
                        case 'students':
                            $_SESSION['semester'] = $user['semester'];
                            $_SESSION['Reg'] = $user['Reg'];
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['number'] = $user['number'];
                            $_SESSION['branch'] = $user['branch'];
                            header("Location: ../student/");
                            exit();
                        case 'faculty':
                            $_SESSION['faculty_id'] = $user['faculty_id'];
                            header("Location: ../faculty/");
                            exit();
                        case 'principal':
                            header("Location: ../principal/");
                            $_SESSION['college_name'] = $user['college_name'];
                      	    $_SESSION['principal_id'] = $user['principal_id'];
                            exit();
                        default:
                            $error = "Invalid role.";
                            break;
                    }
                } else {
                    $error = "Invalid password.";
                }
            } else {
                $error = "No user found with this email.";
            }
            $stmt->close();
        } else {
            $error = "Error preparing statement: " . $conn->error;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inside Mark: Login Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(45deg, #24DDF4, #388E3C);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #fff;
            overflow: hidden;
        }

        .background-animation {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        .bubble {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 8s infinite ease-in-out;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .login-container {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            width: 380px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .login-container:hover {
            transform: translateY(-5px);
        }

        .login-container h1 {
            margin-bottom: 30px;
            font-size: 2.5em;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }

        .input-group {
            position: relative;
            margin-bottom: 20px;
        }

        .input-group input {
            width: 100%;
            padding: 15px 20px;
            border: none;
            border-radius: 30px;
            background: rgba(255, 255, 255, 0.8);
            font-size: 1em;
            color: #333;
            transition: all 0.3s ease;
        }

        .input-group input:focus {
            outline: none;
            box-shadow: 0 0 15px rgba(50, 115, 220, 0.7);
        }

        .input-group label {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            pointer-events: none;
            transition: all 0.3s ease;
        }

        .input-group input:focus + label,
        .input-group input:not(:placeholder-shown) + label {
            top: 0;
            left: 15px;
            font-size: 0.8em;
            padding: 0 5px;
            background: #fff;
            border-radius: 10px;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #333;
            transition: all 0.3s ease;
        }

        .toggle-password:hover {
            color: #24DDF4;
        }

        .login-button {
            width: 100%;
            padding: 15px;
            margin: 20px 0;
            border: none;
            border-radius: 30px;
            background: #388E3C;
            color: white;
            font-size: 1.2em;
            cursor: pointer;
            transition: all 0.3s ease;
            overflow: hidden;
            position: relative;
        }

        .login-button::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(-50%, -50%) scale(0);
            transition: transform 0.5s ease;
        }

        .login-button:hover::after {
            transform: translate(-50%, -50%) scale(1);
        }

        .login-button:hover {
            background: #2e7031;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .forgot-password {
            display: inline-block;
            margin-top: 20px;
            color: #fff;
            text-decoration: none;
            font-size: 0.9em;
            transition: all 0.3s ease;
            position: relative;
        }

        .forgot-password::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 2px;
            bottom: -2px;
            left: 0;
            background-color: #fff;
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .forgot-password:hover::after {
            transform: scaleX(1);
        }

        .error-message {
            color: #ff6b6b;
            margin-top: 10px;
            font-size: 0.9em;
            animation: shake 0.82s cubic-bezier(.36,.07,.19,.97) both;
        }

        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }
    </style>
</head>
<body>
    <div class="background-animation"></div>
    <div class="login-container">
        <h1>Welcome</h1>
        <form action="" method="post">
            <div class="input-group">
                <input type="email" id="email" name="email" required placeholder=" ">
                <label for="email">Email</label>
            </div>
            <div class="input-group">
                <input type="password" id="password" name="password" required placeholder=" ">
                <label for="password">Password</label>
                <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
            </div>
            <button type="submit" class="login-button">Log In</button>
        </form>
        <?php if (!empty($error)) { echo "<p class='error-message'>$error</p>"; } ?>
        <a href="../auth/forgot_password.php?role=<?php echo htmlspecialchars($role); ?>" class="forgot-password">Forgot your password?</a>
    </div>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.querySelector('.toggle-password');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Create animated background bubbles
        const backgroundAnimation = document.querySelector('.background-animation');
        for (let i = 0; i < 20; i++) {
            const bubble = document.createElement('div');
            bubble.classList.add('bubble');
            bubble.style.left = `${Math.random() * 100}%`;
            bubble.style.top = `${Math.random() * 100}%`;
            bubble.style.width = `${Math.random() * 100 + 50}px`;
            bubble.style.height = bubble.style.width;
            bubble.style.animationDuration = `${Math.random() * 4 + 4}s`;
            bubble.style.animationDelay = `${Math.random() * 2}s`;
            backgroundAnimation.appendChild(bubble);
        }
    </script>
</body>
</html>
