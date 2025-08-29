<?php
// student_scan_qr.php - Student's QR Scanning Interface
require('header.php');
require('conn.php');

// Ensure student is logged in
if (!isset($_SESSION['usertype']) || $_SESSION['usertype'] != 'STUDENT') {
    $_SESSION['msg'] = 'Please log in as a student to access this page.';
    header("location: student_login.php");
    exit();
}

$student_enrollment_no = $_SESSION['student_enrollment_no'];
$scan_message = '';

// Process Scanned QR Code Data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['scanned_qr_data'])) {
    $scanned_qr_content = mysqli_real_escape_string($conn, $_POST['scanned_qr_data']);

    $parts = explode('|', $scanned_qr_content);

    if (count($parts) === 3) {
        $session_id_from_qr = $parts[0];
        $timestamp_from_qr = $parts[1];
        $hash_token_from_qr = $parts[2];
        $secret_qr_key = 'Your_Super_Secret_QR_Key_12345'; // Ensure this matches the admin side

        // 1. Verify the hash to ensure the QR code is authentic
        $expected_hash = hash('sha256', $session_id_from_qr . '|' . $timestamp_from_qr . '|' . $secret_qr_key);
        if ($hash_token_from_qr !== $expected_hash) {
            $scan_message = '<div class="alert alert-danger">Invalid QR code. The code is not authentic.</div>';
        } else {
            // 2. Check if the QR code is expired (e.g., older than 30 seconds)
            $qr_code_age_seconds = time() - $timestamp_from_qr;
            if ($qr_code_age_seconds > 30) {
                $scan_message = '<div class="alert alert-warning">This QR code has expired. Please scan the current one.</div>';
            } else {
                // 3. Check if the session is still active
                $check_session_sql = "SELECT `semester`, `batch` FROM `daily_attendance_sessions` WHERE `session_id` = '$session_id_from_qr' AND `status` = 'active'";
                $check_session_result = mysqli_query($conn, $check_session_sql);

                if (mysqli_num_rows($check_session_result) > 0) {
                    $session_details = mysqli_fetch_assoc($check_session_result);

                    // 4. Check if the student belongs to the correct class for this session
                    $check_student_class_sql = "SELECT `enrollment_no` FROM `students` WHERE `enrollment_no` = '$student_enrollment_no' AND `semester` = '{$session_details['semester']}' AND `batch` = '{$session_details['batch']}'";
                    $check_student_class_result = mysqli_query($conn, $check_student_class_sql);

                    if (mysqli_num_rows($check_student_class_result) > 0) {
                        // 5. Check if attendance has already been marked
                        $check_attendance_sql = "SELECT `id` FROM `attendance` WHERE `session_id` = '$session_id_from_qr' AND `enrollment_no` = '$student_enrollment_no'";
                        $check_attendance_result = mysqli_query($conn, $check_attendance_sql);

                        if (mysqli_num_rows($check_attendance_result) == 0) {
                            // Mark attendance
                            $insert_sql = "INSERT INTO `attendance` (`session_id`, `enrollment_no`, `attendance_time`) VALUES ('$session_id_from_qr', '$student_enrollment_no', NOW())";
                            if (mysqli_query($conn, $insert_sql)) {
                                $scan_message = '<div class="alert alert-success">Attendance marked successfully!</div>';
                            } else {
                                $scan_message = '<div class="alert alert-danger">Error marking attendance: ' . mysqli_error($conn) . '</div>';
                            }
                        } else {
                            $scan_message = '<div class="alert alert-warning">You have already marked your attendance for this session.</div>';
                        }
                    } else {
                        $scan_message = '<div class="alert alert-danger">You do not belong to the class for this attendance session.</div>';
                    }
                } else {
                    $scan_message = '<div class="alert alert-warning">The attendance session is no longer active.</div>';
                }
            }
        }
    } else {
        $scan_message = '<div class="alert alert-danger">Invalid QR code format. Please scan the official attendance QR code.</div>';
    }
}
?>

<div class="container-fluid pt-4 px-4">
    <div class="row bg-light rounded mx-0">
        <div class="col-12">
            <div class="bg-light rounded h-100 p-4 text-center">
                <h4 class="mb-4">Scan Attendance QR Code</h4>
                <div class="alert alert-info">Please scan the QR code displayed by your teacher.</div>
                
                <?php if (!empty($scan_message)) : ?>
                    <div class="mt-4">
                        <?php echo $scan_message; ?>
                    </div>
                <?php endif; ?>
                
                <div class="text-center mt-3">
                    <video id="preview" class="w-100 border rounded" style="max-width: 400px; max-height: 400px;"></video>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://rawgit.com/schmich/instascan-js/master/instascan.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let scanner = new Instascan.Scanner({ video: document.getElementById('preview'), mirror: false });
        let qrScanForm = document.createElement('form');
        qrScanForm.method = 'POST';
        qrScanForm.action = 'student_scan_qr.php';
        document.body.appendChild(qrScanForm);
        
        let scannedQrDataInput = document.createElement('input');
        scannedQrDataInput.type = 'hidden';
        scannedQrDataInput.name = 'scanned_qr_data';
        qrScanForm.appendChild(scannedQrDataInput);

        scanner.addListener('scan', function(content) {
            scannedQrDataInput.value = content;
            qrScanForm.submit();
            scanner.stop(); 
        });

        Instascan.Camera.getCameras().then(function(cameras) {
            if (cameras.length > 0) {
                let selectedCamera = cameras[0];
                for (let i = 0; i < cameras.length; i++) {
                    if (cameras[i].name.toLowerCase().includes('back')) {
                        selectedCamera = cameras[i];
                        break;
                    }
                }
                scanner.start(selectedCamera);
            } else {
                console.error('No cameras found.');
                // Fallback to error message
            }
        }).catch(function(e) {
            console.error(e);
            // Fallback to error message
        });
    });
</script>

<?php require('footer.php'); ?>