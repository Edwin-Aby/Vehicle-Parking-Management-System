<?php
// Database Configuration
$host = "localhost";       // Server host (XAMPP default)
$username = "root";        // MySQL username (XAMPP default)
$password = "";            // MySQL password (empty for XAMPP)
$database = "parking_management"; // Database name

// Create connection using MySQLi
$conn = new mysqli($host, $username, $password, $database);

// Check if the connection was successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
