<?php
// Database connection details
include '../config.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $password = $_POST['password'];
    $token = $_POST['token'];
    $role = $_POST['role'];

    // Retrieve email associated with the token
    $sql = "SELECT email FROM reset_tokens WHERE token = '$token'";
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $email = $row['email'];

        // Update the password for the associated email
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql_update_password = "UPDATE `$role` SET password = '$hashed_password' WHERE email = '$email'";
        if (mysqli_query($conn, $sql_update_password)) {
            // Password updated successfully
            echo "<div style='
    background-color: #4CAF50; 
    color: white; 
    text-align: center; 
    padding: 20px; 
    margin: 20px; 
    font-size: 24px; 
    border-radius: 10px; 
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
'>
    Password updated successfully.
</div>";
        } else {
            echo "Error updating password: " . mysqli_error($conn);
        }

        // Delete the reset token from the database
        $sql_delete_token = "DELETE FROM reset_tokens WHERE token = '$token'";
        mysqli_query($conn, $sql_delete_token);
    } else {
        echo "Invalid reset token.";
    }

    // Close database connection
    mysqli_close($conn);
}
?>
