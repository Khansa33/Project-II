<?php
// download_qr.php - Server-side script to force the download of a QR code image using a local library
session_start();
require('conn.php');

// Include the Composer autoloader to load the QR code library
require(__DIR__ . '/vendor/autoload.php');

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;

// Ensure only admins can access this page
if (!isset($_SESSION['usertype']) || $_SESSION['usertype'] != 'ADMIN') {
    header("location: login.php");
    exit();
}

$enrollment_no = isset($_GET['enrollment_no']) ? mysqli_real_escape_string($conn, $_GET['enrollment_no']) : '';

if (!$enrollment_no) {
    die("Invalid request. Enrollment number not specified.");
}

// Generate the QR code using the local PHP library
$writer = new PngWriter();
$qrCode = QrCode::create($enrollment_no)
    ->setSize(300)
    ->setMargin(10)
    ->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh());

$result = $writer->write($qrCode);

// Get the image data
$image_data = $result->getString();

// Set the HTTP headers for a file download
header('Content-Type: image/png');
header('Content-Disposition: attachment; filename="QR_' . $enrollment_no . '.png"');
header('Content-Length: ' . strlen($image_data));

// Output the image data to the browser
echo $image_data;
exit;
?>