<?php
// Include database connection file

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
  include('../config.php');

  header('Content-Type: application/json');

  $response = array('success' => false);

  if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
      $email = $_POST['email'];
      $password = $_POST['password'];
      $role = $_POST['role'];

      // Validate role to prevent SQL injection
      $valid_roles = ['students', 'Faculty', 'principal'];
      if (!in_array($role, $valid_roles)) {

          $response['error'] = "Invalid role.";
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
                      $response['success'] = true;
                      $response['role'] = $role;

                      // Session data converted to response data
                      $response['username'] = $user['username'];
                      $response['email'] = $user['email'];
                      $response['number'] = $user['number'];
                      $response['college_code'] = $user['college_code'];
                      $response['unique_token'] = $user['unique_token'];
                      $response['faculty_branch_fetch_url'] = "https://turbocampuspro.com/php_script/faculty/faculty_branch_fetch_url.php";
                      $response['faculty_subject_fetch_url'] = "https://turbocampuspro.com/php_script/faculty/faculty_subject_fetch_url.php";
                      $response['faculty_student_fetch_url'] = "https://turbocampuspro.com/php_script/faculty/faculty_student_fetch_url.php";
                      $response['faculty_mark_attendance'] = "https://turbocampuspro.com/php_script/faculty/faculty_mark_attendance.php";
                      $response['faculty_today_schedule'] = "https://turbocampuspro.com/php_script/faculty/faculty_today_schedule.php";
                      $response['faculty_export_pdf'] = "https://turbocampuspro.com/php_script/faculty/faculty_export_pdf.php";

                      // For Student

                      $response['student_overall_attendance'] = "https://turbocampuspro.com/php_script/student/student_overall_attendance.php";
                      $response['student_subject_wise_attendance'] = "https://turbocampuspro.com/php_script/student/student_subject_wise_attendance.php";


                      switch ($role) {
                          case 'students':
                              $response['semester'] = $user['semester'];
                              $response['Reg'] = $user['Reg'];
                              $response['branch'] = $user['branch'];
                              break;
                          case 'principal':
                              $response['college_name'] = $user['college_name'];

                              break;
                      }
                  } else {
                      $response['error'] = "Invalid password.";
                  }
              } else {
                  $response['error'] = "No user found with this email.";
              }
              $stmt->close();
          } else {
              $response['error'] = "Error preparing statement: " . $conn->error;
          }
      }
  } else {
      $response['error'] = "Invalid request method.";
  }

  echo json_encode($response);
}

// Forgot Password Implementations

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot_password'])) {
    // Database connection details
    require_once '../config.php';
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Get student details JSON string from the POST parameters
    $email = $_POST['email'];
    $role = $_POST['role'];
    
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
              $response =  $response;
          }

          // Close cURL session
          curl_close($ch);

            
            
        }
    } else {
        $response = "No records found for email: $email";
    }


  
    
    // Close the database connection after processing all students
    $conn->close();
}
 echo $response;
?>
