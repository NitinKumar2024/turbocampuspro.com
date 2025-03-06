<?php
$server_servername = "localhost";
$server_username = "root";
$server_password = "";
$server_database = "smart-edu-ai-flask";


// Create connection
$conn = new mysqli($server_servername, $server_username, $server_password, $server_database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>
