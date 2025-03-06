<?php
 
$server_servername = "localhost";
$server_username = "insidemark";
$server_password = "Nitin_Kumar@123";
$server_database = "insidemark";

// Create connection
$conn = new mysqli($server_servername, $server_username, $server_password, $server_database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
