<?php
// conn.php - Database Connection
date_default_timezone_set('Asia/Kathmandu'); // Set your timezone
$servername = "localhost"; // Or your database host
$username = "root";        // Your database username
$password = "";            // Your database password
$dbname = "attendance_system"; // The database name

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
// Optionally set character set for consistent data handling
mysqli_set_charset($conn, "utf8mb4");
?>