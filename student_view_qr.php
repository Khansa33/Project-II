<?php
// student_view_qr.php - Displays the student's permanent QR code
require('header.php');
require('conn.php');

// Ensure student is logged in
if (!isset($_SESSION['usertype']) || $_SESSION['usertype'] != 'STUDENT') {
    $_SESSION['msg'] = 'Please log in as a student to access this page.';
    header("location: student_login.php");
    exit();
}

$enrollment_no = $_SESSION['student_enrollment_no'];
$student_name = $_SESSION['student_name'];

// Using the reliable QR code API
$qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($enrollment_no);
?>

<div class="container-fluid pt-4 px-4">
    <div class="row bg-light rounded mx-0">
        <div class="col-12">
            <div class="bg-light rounded h-100 p-4 text-center">
                <h4 class="mb-4">Your QR Code</h4>
                <p class="text-muted">
                    This QR code is unique to your enrollment number.
                </p>
                <div class="p-3 border rounded d-inline-block">
                    <img src="<?php echo htmlspecialchars($qr_code_url); ?>" alt="Student QR Code" class="img-fluid" style="max-width: 300px;" />
                </div>
                <div class="mt-4">
                    <p class="text-muted">You can use this QR code for any attendance session.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require('footer.php'); ?>