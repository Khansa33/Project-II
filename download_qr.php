<?php
// download_qr.php - Server-side script to force the download of a QR code image using a public API
session_start();
require('conn.php');

// Ensure only admins can access this page
if (!isset($_SESSION['usertype']) || $_SESSION['usertype'] != 'ADMIN') {
    header("location: login.php");
    exit();
}

$enrollment_no = isset($_GET['enrollment_no']) ? mysqli_real_escape_string($conn, $_GET['enrollment_no']) : '';

if (!$enrollment_no) {
    die("Invalid request. Enrollment number not specified.");
}

// Use a different, reliable public QR code generation service
$qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($enrollment_no);

// Fetch the image data from the URL
$image_data = file_get_contents($qr_code_url);

// Set the HTTP headers for a file download
header('Content-Type: image/png');
header('Content-Disposition: attachment; filename="QR_' . $enrollment_no . '.png"');
header('Content-Length: ' . strlen($image_data));

// Output the image data to the browser
echo $image_data;
exit;
?>