<?php
 $role = $_GET['role'];
// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Database connection details
    require_once '../config.php';
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Get student details JSON string from the POST parameters
    $email = $_POST['email'];
    $role = $_GET['role'];
    
    // SQL query to select password for the provided email
    $sql = "SELECT password FROM `$role` WHERE email='$email'";
    
    // Execute the query
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Fetch the password from the result
        $row = $result->fetch_assoc();
        // $password = $row["password"];
        
            // Generate a unique token for each email
        $token = bin2hex(random_bytes(32));

        // Calculate expiration date (10 minutes from now)
        $expire_date = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        // Check if there is already a reset token for the user's email
        $sql_check_existing = "SELECT * FROM reset_tokens WHERE email = '$email'";
        $result_existing = $conn->query($sql_check_existing);
        
        if ($result_existing->num_rows > 0) {
            // If a token exists, update the existing record
            $sql = "UPDATE reset_tokens SET token = '$token', expire_date = '$expire_date' WHERE email = '$email'";
        } else {
            // If no token exists, insert a new record
            $sql = "INSERT INTO reset_tokens (email, token, expire_date) VALUES ('$email', '$token', '$expire_date')";
        }
        // Execute the query
        if ($conn->query($sql) === TRUE) {
          
          $resetLink = "https://turbocampuspro.com/auth/reset_password.php?token=$token&email=$email&role=$role";
          
          
          // Data to be sent in the POST request
          $data = [
              'to' => $email,
              'resetLink' => $resetLink
          ];

          // URL of the server-side script
          $url = 'https://viddoer.com/Inside%20Mark/diploma/email.php'; // Replace with your server URL

          // Initialize cURL session
          $ch = curl_init($url);

          // Set cURL options for POST request
          curl_setopt($ch, CURLOPT_POST, 1);
          curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

          // Execute cURL session
          $response = curl_exec($ch);

          // Check for errors
          if ($response === false) {
              echo 'Error: ' . curl_error($ch);
          } else {
              $error =  $response;
          }

          // Close cURL session
          curl_close($ch);

            
            
        }
    } else {
        $error = "No records found for email: $email";
    }


  
    
    // Close the database connection after processing all students
    $conn->close();
}
 
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            background: linear-gradient(45deg, #24DDF4, #388E3C);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #fff;
        }
        .forgot-password-container {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            width: 350px;
            text-align: center;
        }
        .forgot-password-container h1 {
            margin-bottom: 30px;
            font-size: 2.5em;
        }
        .input-field {
            width: calc(100% - 40px);
            padding: 12px;
            margin: 10px 0;
            border: none;
            border-radius: 30px;
            background: rgba(255, 255, 255, 0.8);
            font-size: 1em;
            color: #333;
        }
        .input-field:focus {
            outline: none;
            box-shadow: 0 0 5px rgba(50, 115, 220, 0.5);
        }
        .submit-button {
            width: 100%;
            padding: 12px;
            margin: 20px 0;
            border: none;
            border-radius: 30px;
            background: #388E3C;
            color: white;
            font-size: 1.2em;
            cursor: pointer;
            transition: background 0.3s;
        }
        .submit-button:hover {
            background: #2e7031;
        }
        .back-to-login {
            display: block;
            margin-top: 20px;
            color: #fff;
            text-decoration: none;
            font-size: 0.9em;
        }
        .back-to-login:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="forgot-password-container">
        <h1>Forgot Password</h1>
        <p>Please enter your email address to reset your password.</p>
        <form action="" method="post">
            <input type="email" class="input-field" name="email" placeholder="Email" required>
            <button type="submit" class="submit-button">Submit</button>
        </form>
        <?php if(isset($error)) { echo "<p style='color:red;'>$error</p>"; } ?>
         <a href="<?php echo 'login.php?role=' . $role; ?>" class="back-to-login">Back to Login</a>
    </div>
</body>
</html>
